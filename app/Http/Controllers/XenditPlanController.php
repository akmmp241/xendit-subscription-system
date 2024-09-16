<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreatePlanRequest;
use App\Http\Requests\UpdatePlanRequest;
use App\Models\XenditPlans;
use App\PlanHandler;
use App\XenditRequestClient;
use Exception;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;
use Symfony\Component\CssSelector\Exception\InternalErrorException;
use Symfony\Component\HttpFoundation\Response as ResponseCode;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class XenditPlanController extends Controller
{
    use XenditRequestClient, PlanHandler;

    /**
     * @throws InternalErrorException
     */
    public function create(CreatePlanRequest $request): JsonResponse
    {
        $requests = $request->validated();
        $amount = (int)$requests['amount'];
        $pmId = $requests['payment_method_id'];

        // get active plan
        $xenditPlan = XenditPlans::query()
            ->where('status', 'ACTIVE')
            ->where('user_id', Auth::id())->first();

        if ($xenditPlan) throw new HttpResponseException(Response::json([
            "status" => "DUPLICATE_PLAN_REQUEST",
            "message" => "User already have {$xenditPlan->status} plan",
            "data" => null,
            "errors" => null,
        ])->setStatusCode(ResponseCode::HTTP_BAD_REQUEST));

        try {
            DB::beginTransaction();

            $plan = $this->createRecurringPlan($amount, $pmId)->json();

            $xenditPlan = XenditPlans::query()->create([
                "plan_id" => $plan["id"],
                "user_id" => Auth::id(),
                "customer_id" => $plan["customer_id"],
                "recurring_action" => $plan["recurring_action"],
                "recurring_cycle_count" => $plan["recurring_cycle_count"],
                "amount" => $amount,
                "status" => $plan["status"],
            ]);

            DB::commit();
        } catch (RequestException|ConnectionException $e) {
            Log::info($e->getMessage());
            DB::rollBack();
            throw new InternalErrorException("Something wrong with xendit provider");
        } catch (Exception $e) {
            Log::info($e->getMessage());
            DB::rollBack();
            throw new InternalErrorException("Failed to create plan");
        }

        return Response::json([
            "status" => "SUCCESS_CREATE_RECURRING_PLAN",
            "message" => "Subscription plan successfully created",
            "data" => $xenditPlan,
            "errors" => null
        ])->setStatusCode(ResponseCode::HTTP_CREATED);
    }

    /**
     * @throws InternalErrorException
     */
    public function update(UpdatePlanRequest $request, string $id): JsonResponse
    {
        $requests = $request->validated();

        $xenditPlan = XenditPlans::query()
            ->where('status', 'ACTIVE')
            ->where('user_id', Auth::id())
            ->where("id", $id)->first();

        if (!$xenditPlan) throw new NotFoundHttpException("Plan not found");

        try {
            DB::beginTransaction();

            $plan = $this->updatePlan($xenditPlan->plan_id, $requests);

            $xenditPlan->status = $plan["status"];
            $xenditPlan->save();

            DB::commit();
        } catch (RequestException|ConnectionException $e) {
            Log::info($e->getMessage());
            DB::rollBack();
            throw new InternalErrorException("Something wrong with xendit provider");
        } catch (Exception $e) {
            Log::info($e->getMessage());
            DB::rollBack();
            throw new InternalErrorException("Failed to update plan");
        }

        return Response::json([
            "status" => "SUCCESS_UPDATE_RECURRING_PLAN",
            "message" => "Subscription plan successfully updated",
            "data" => $xenditPlan,
            "errors" => null
        ]);
    }

    /**
     * @throws InternalErrorException
     */
    public function deactivate(string $id): JsonResponse
    {
        $xenditPlan = XenditPlans::query()
            ->where('status', 'ACTIVE')
            ->where('user_id', Auth::id())
            ->where("id", $id)->first();

        if (!$xenditPlan) throw new NotFoundHttpException("Plan not found");

        try {
            DB::beginTransaction();
            $plan = $this->deactivatePlan($xenditPlan->plan_id);

            $xenditPlan->status = $plan["status"];
            $xenditPlan->save();

            DB::commit();
        } catch (ConnectionException|RequestException $e) {
            DB::rollBack();
            Log::info($e->getMessage());
            throw new InternalErrorException("Something wrong with xendit provider");
        } catch (Exception $e) {
            DB::rollBack();
            Log::info($e->getMessage());
            throw new InternalErrorException("Failed to deactivate plan");
        }

        return Response::json([
            "status" => "SUCCESS_DEACTIVATE_RECURRING_PLAN",
            "message" => "Subscription plan successfully deactivated",
            "data" => $xenditPlan,
            "errors" => null
        ]);
    }

    public function webhook(Request $request): JsonResponse
    {
        $event = explode(".", $request->get('event'));
        $data = $request->get('data');

        if ($event[1] === "plan") return $this->handlePlan($data);
        if ($event[1] === "cycle") return $this->handleCycle($data);
        else throw new NotFoundHttpException("Unhandled event");
    }
}
