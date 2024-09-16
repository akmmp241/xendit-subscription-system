<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreatePaymentMethodRequest;
use App\Models\UserPaymentMethods;
use Exception;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;
use Symfony\Component\CssSelector\Exception\InternalErrorException;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpFoundation\Response as ResponseCode;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Xendit\PaymentMethod\PaymentMethodApi;
use Xendit\PaymentMethod\PaymentMethodReusability;
use Xendit\PaymentMethod\PaymentMethodType;
use Xendit\XenditSdkException;

class XenditPaymentMethodsController extends Controller
{
    public function __construct(public PaymentMethodApi $paymentMethod)
    {
        $this->paymentMethod = new PaymentMethodApi();
    }

    /**
     * @throws InternalErrorException
     */
    public function create(CreatePaymentMethodRequest $request): JsonResponse
    {
        $requests = $request->validated();

        // pm stand for payment method
        $pm = UserPaymentMethods::query()->where('channel_code', $requests['channel_code'])->first();

        if ($pm) throw new HttpResponseException(Response::json([
            "status" => "DUPLICATE_PAYMENT_METHOD",
            "message" => "Payment method with channel code {$requests["channel_code"]} already attach in user with status {$pm->status}",
            "data" => null,
            "errors" => null
        ])->setStatusCode(ResponseCode::HTTP_BAD_REQUEST));

        try {
            DB::beginTransaction();

            $paymentMethod = $this->paymentMethod->createPaymentMethod(payment_method_parameters: [
                "type" => PaymentMethodType::EWALLET,
                "country" => "ID",
                "reusability" => PaymentMethodReusability::MULTIPLE_USE,
                "ewallet" => [
                    "channel_code" => $requests['channel_code'],
                    "channel_properties" => [
                        "success_return_url" => "https://www.youtube.com",
                        "failure_return_url" => "https://www.youtube.com",
                    ]
                ],
                "customer_id" => Auth::user()->xenditCustomer->customer_id,
            ]);

            UserPaymentMethods::query()->create([
                "payment_method_id" => $paymentMethod->getId(),
                "user_id" => Auth::id(),
                "customer_id" => $paymentMethod->getCustomerId(),
                "status" => $paymentMethod->getStatus(),
                "type" => $paymentMethod->getType(),
                "failure_code" => $paymentMethod->getFailureCode(),
                "channel_type" => PaymentMethodType::EWALLET,
                "channel_code" => $requests['channel_code'],
            ]);

            DB::commit();
        } catch (XenditSdkException $e) {
            DB::rollBack();
            Log::error($e->getErrorMessage());
            throw new InternalErrorException("Something wrong with xendit provider");
        } catch (Exception $e) {
            DB::rollBack();
            Log::error($e->getMessage());
            throw new InternalErrorException("Failed To Create Payment Method");
        }

        return Response::json([
            "status" => "SUCCESSFULLY_CREATE_PAYMENT_METHODS",
            "message" => "Payment Method Successfully Created",
            "data" => [
                "payment_method_id" => $paymentMethod->getId(),
                "actions" => [
                    ...$paymentMethod->getActions()
                ],
            ],
            "errors" => null
        ])->setStatusCode(ResponseCode::HTTP_CREATED);
    }

    public function activated(Request $request): JsonResponse
    {
        $data = $request->get('data');


        if ($request->get('event') === "payment_method.activated") {
            $paymentMethod = UserPaymentMethods::query()->where('payment_method_id', $data["id"])->first();
            $paymentMethod->status = $data["status"];
            $paymentMethod->save();

            return Response::json([
                "status" => "PAYMENT_METHOD_ACTIVATED",
                "message" => "Payment Method Successfully Activated",
                "data" => [
                    "payment_method_id" => $data["id"],
                ],
                "errors" => null
            ])->setStatusCode(ResponseCode::HTTP_ACCEPTED);
        }

        throw new BadRequestException("Unhandled Event Type");
    }

    public function all(): JsonResponse
    {
        $paymentMethods = UserPaymentMethods::query()->where('user_id', Auth::id())->get();

        if ($paymentMethods->isEmpty()) throw new NotFoundHttpException("User not yet have payment method");

        return Response::json([
            "status" => "OK",
            "message" => "Successfully Get Payment Methods",
            "data" => $paymentMethods,
            "errors" => null
        ]);
    }

    public function get(string $id): JsonResponse
    {
        $paymentMethod = UserPaymentMethods::query()
            ->where('user_id', Auth::id())
            ->where('id', $id)->first();

        if (! $paymentMethod) throw new NotFoundHttpException("Payment Method not found");

        return Response::json([
            "status" => "OK",
            "message" => "Successfully Get Payment Methods",
            "data" => $paymentMethod,
            "errors" => null
        ]);
    }

    /**
     * @throws InternalErrorException
     */
    public function delete(string $id): JsonResponse
    {
        $paymentMethod = UserPaymentMethods::query()
            ->where('user_id', Auth::id())
            ->where('id', $id)->first();

        if (! $paymentMethod) throw new NotFoundHttpException("Payment Method not found");

        try {
            DB::beginTransaction();

            $res = $this->paymentMethod->expirePaymentMethod($paymentMethod->payment_method_id);

            $paymentMethod->delete();

            DB::commit();
        } catch (XenditSdkException $e) {
            DB::rollBack();
            Log::error($e->getErrorMessage());
            throw new InternalErrorException("Something wrong with xendit provider");
        }

        return Response::json([
            "status" => "OK",
            "message" => "Successfully Expire Payment Method",
            "data" => [
                "payment_method_id" => $res->getId(),
                "status" => $res->getStatus(),
            ],
            "errors" => null
        ]);
    }
}
