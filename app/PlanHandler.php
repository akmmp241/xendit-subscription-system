<?php

namespace App;

use App\Models\PlanCycles;
use App\Models\XenditPlans;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Response;

trait PlanHandler
{
    public function handlePlan(array $data): JsonResponse
    {
        XenditPlans::query()
            ->where('plan_id', $data['id'])->update([
                "status" => $data["status"],
            ]);

        return $this->success();
    }

    public function handleCycle(array $data): JsonResponse
    {
        $plan = XenditPlans::query()
            ->where('plan_id', $data['plan_id'])->first();

        $cycle = PlanCycles::query()->where('plan_id', $plan->id);

        if (! $cycle->exists()) {
            PlanCycles::query()->create([
                "cycle_id" => $data["id"],
                "plan_id" => $plan->id,
                "customer_id" => $data["customer_id"],
                "type" => $data["type"],
                "status" => $data["status"],
            ]);

            return $this->success();
        }

        $cycle->update([
            "status" => $data["status"],
            "type" => $data["type"],
        ]);

        return $this->success();
    }

    public function success(): JsonResponse
    {
        return Response::json([
            "message" => "success"
        ]);
    }
}
