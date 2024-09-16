<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Models\XenditCustomer;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;
use Illuminate\Validation\UnauthorizedException;
use Symfony\Component\CssSelector\Exception\InternalErrorException;
use Symfony\Component\HttpFoundation\Response as ResponseCode;
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
                "reference_id" => "$user->id",
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
            "data" => new UserResource($user),
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

        $user = User::query()->where('email', $requests["email"])->first();

        return Response::json([
            "status" => "OK",
            "message" => "User Login Successfully",
            "data" => [
                ...$this->respondWithToken($token),
                "user" => new UserResource($user),
            ],
            "errors" => null
        ]);
    }

    public function logout(): JsonResponse
    {
        Auth::logout();

        return Response::json([
            "status" => "OK",
            "message" => "User Logout Successfully",
            "data" => true,
            "errors" => null
        ]);
    }

    public function refresh(): JsonResponse
    {
        return Response::json([
            "status" => "OK",
            "message" => "Success Get Refresh Token",
            "data" => [
                ...$this->respondWithToken(Auth::refresh()),
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
}
