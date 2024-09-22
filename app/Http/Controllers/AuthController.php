<?php

namespace App\Http\Controllers;

use App\Http\Requests\ForgetPasswordRequest;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RefreshTokenRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\ResetPasswordRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Requests\VerifyEmailRequest;
use App\Http\Resources\UserResource;
use App\Mail\ForgetPasswordMail;
use App\Mail\UserEmailVerification;
use App\Models\User;
use App\Models\XenditCustomer;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Str;
use Illuminate\Validation\UnauthorizedException;
use Symfony\Component\CssSelector\Exception\InternalErrorException;
use Symfony\Component\HttpFoundation\Response as ResponseCode;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Xendit\Customer\CustomerApi;
use Xendit\XenditSdkException;

class AuthController extends Controller
{
    public function __construct(public CustomerApi $customerApi)
    {
        $this->customerApi = new CustomerApi();
    }

    /**
     * @throws InternalErrorException
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $requests = $request->validated();

        try {
            DB::beginTransaction();

            $user = User::query()->create($requests);

            $customer = $this->customerApi->createCustomer(customer_request: [
                "reference_id" => uniqid(base64_encode($user->id) . '-'),
                "type" => "INDIVIDUAL",
                "individual_detail" => [
                    "given_names" => $user->name,
                ],
                "mobile_number" => $user->mobile_number,
                "email" => $user->email,
            ]);

            XenditCustomer::query()->create([
                "customer_id" => $customer->getId(),
                "user_id" => $user->id,
            ]);

            $token = Auth::login($user);
            $refreshToken = $this->createSession();

            DB::commit();
        } catch (XenditSdkException $e) {
            DB::rollBack();
            Log::error($e->getErrorMessage());
            throw new InternalErrorException("Something wrong with xendit provider");
        } catch (Exception $e) {
            DB::rollBack();
            Log::error($e->getMessage());
            throw new InternalErrorException("Failed To Register User");
        }

        return Response::json([
            "status" => "CREATED",
            "message" => "User Created Successfully",
            "data" => [
                ...$this->respondWithToken($token),
                'refresh_token' => $refreshToken,
            ],
            "errors" => null
        ])->setStatusCode(ResponseCode::HTTP_CREATED);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $requests = $request->validated();

        $credentials = $request->only('email', 'password');

        if (! $token = auth()->attempt($credentials)) {
            throw new UnauthorizedException("Invalid Credentials");
        }

        $refreshToken = $this->createSession();
        $user = User::query()->where('email', $requests["email"])->first();

        return Response::json([
            "status" => "OK",
            "message" => "User Login Successfully",
            "data" => [
                ...$this->respondWithToken($token),
                'refresh_token' => $refreshToken,
                "user" => new UserResource($user),
            ],
            "errors" => null
        ]);
    }

    public function logout(): JsonResponse
    {
        $this->destroySession();
        Auth::logout();

        return Response::json([
            "status" => "OK",
            "message" => "User Logout Successfully",
            "data" => true,
            "errors" => null
        ]);
    }

    /**
     * @throws InternalErrorException
     */
    public function refresh(RefreshTokenRequest $request): JsonResponse
    {
        $requests = $request->validated();

        try {
            if (Auth::check()) Auth::invalidate();
            $session = $this->checkRefreshToken($requests["refresh_token"]);
            $token = Auth::login($session->user);
        } catch (UnauthorizedException $e) {
            throw $e;
        } catch (Exception $e) {
            Log::error($e->getMessage());
            throw new InternalErrorException("Failed to process refresh token");
        }

        return Response::json([
            "status" => "OK",
            "message" => "Success Refresh Token",
            "data" => [
                ...$this->respondWithToken($token),
            ],
            "errors" => null
        ]);
    }

    public function me(): JsonResponse
    {
        return Response::json([
            "status" => "OK",
            "message" => "Get User Successfully",
            "data" => new UserResource(Auth::user()),
        ]);
    }

    public function update(UpdateUserRequest $request): JsonResponse
    {
        $requests = $request->validated();

        Auth::user()->update($requests);

        return Response::json([
            "status" => "OK",
            "message" => "Update User Successfully",
            "data" => new UserResource(Auth::user()),
            "errors" => null
        ]);
    }

    public function verification(Request $request): JsonResponse
    {
        $user = Auth::user();

        if (RateLimiter::tooManyAttempts('send-email-verification:' . $user->id . ':' . $request->ip(), $perMinute = 1)) {
            $seconds = RateLimiter::availableIn('send-email-verification:' . $user->id . ':' . $request->ip());
            throw new TooManyRequestsHttpException($seconds, 'You can only sent once in a minute. Try Again in ' . $seconds . ' seconds.');
        }

        $verificationToken = Str::uuid() . ':' . Str::random(10) . ':' . base64_encode($user->id);
        $encodedToken = base64_encode($verificationToken);

        Redis::hset('send-email-verification:' . $user->id, 'token', $verificationToken);
        // set expire for 5 minutes
        Redis::expire('send-email-verification:' . $user->id, 300);

        $url = env('APP_URL') . '/verify?token=' . $encodedToken;

        Mail::to($user)->queue(new UserEmailVerification($url, $encodedToken));

        RateLimiter::increment('send-email-verification:' . $user->id . ':' . $request->ip());

        return Response::json([
            "status" => "OK",
            "message" => "Email Verification Successfully Sent",
            "data" => true,
            "errors" => null
        ]);
    }

    public function verify(VerifyEmailRequest $request): JsonResponse
    {
        $requests = $request->validated();

        if (Auth::user()->email_verified_at) throw new BadRequestHttpException('Email has been already verified.');

        $token = Redis::hget('send-email-verification:' . Auth::id(), 'token');
        if (!$token) throw new UnauthorizedException('Token already expire');
        if ($token !== base64_decode($requests["token"])) throw new UnauthorizedException("Invalid Token");
        Redis::del('send-email-verification:' . Auth::id());

        Auth::user()->update([
            "email_verified_at" => Date::now()
        ]);

        return Response::json([
            "status" => "OK",
            "message" => "Verified User Email Successfully",
            "data" => true,
            "errors" => null
        ]);
    }

    public function forget(ForgetPasswordRequest $request): JsonResponse
    {
        $requests = $request->validated();

        $forgetPasswordToken = Str::random(8) . ':' . base64_encode($requests['email']);
        $encodedToken = base64_encode($forgetPasswordToken);

        if (RateLimiter::tooManyAttempts('send-email-forget-password:' . $requests["email"] . ':' . $request->ip(), $perMinute = 1)) {
            $seconds = RateLimiter::availableIn('send-email-forget-password:' . $requests["email"] . ':' . $request->ip());
            throw new TooManyRequestsHttpException($seconds, 'You can only sent once in a minute. Try Again in ' . $seconds . ' seconds.');
        }

        Redis::hset('send-email-forget-password:' . $forgetPasswordToken, 'token', $forgetPasswordToken);
        Redis::hset('send-email-forget-password:' . $forgetPasswordToken, 'email', $requests['email']);
        // set expire for 5 minutes
        Redis::expire('send-email-forget-password:' . $forgetPasswordToken, 300);

        $url = env('APP_URL') . '/password/reset?token=' . $encodedToken;

        Mail::to($requests['email'])->queue(new ForgetPasswordMail($url, $encodedToken));

        RateLimiter::increment('send-email-forget-password:' . $requests['email'] . ':' . $request->ip());

        return Response::json([
            "status" => "OK",
            "message" => "Forget Password Mail Successfully Sent",
            "data" => true,
            "errors" => null
        ]);
    }

    public function reset(ResetPasswordRequest $request): JsonResponse
    {
        $requests = $request->validated();

        $response = Redis::hgetall('send-email-forget-password:' . base64_decode($requests["reset_password_token"]));
        if (!$response) throw new UnauthorizedException('Token already expire');
        if ($response["token"] !== base64_decode($requests["reset_password_token"])) throw new UnauthorizedException("Invalid Token");

        Redis::del('send-email-forget-password:' . base64_decode($requests["reset_password_token"]));

        $user = User::query()->where('email', $response["email"])->first();
        if (! $user) throw new NotFoundHttpException("User Not Found");

        $user->update($requests);

        return Response::json([
            "status" => "OK",
            "message" => "Updated User Password Successfully",
            "data" => true,
            "errors" => null
        ]);
    }
}
