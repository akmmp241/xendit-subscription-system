<?php

namespace App;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Http;

trait XenditRequestClient
{
    public function client(): PendingRequest
    {
        return Http::withHeaders([
            "Authorization" => "Basic " . base64_encode(env('XENDIT_API_KEY') . ":"),
            "Content-Type" => "application/json",
            "Accept" => "application/json",
        ])->baseUrl(env('XENDIT_BASE_URL'));
    }

    /**
     * @throws ConnectionException
     * @throws RequestException
     */
    public function createRecurringPlan(int $amount, $pmId): Response
    {
        return $this->client()
            ->post('/recurring/plans', [
                "reference_id" => "plan-" . base64_encode(Auth::id()),
                "customer_id" => Auth::user()->xenditCustomer->customer_id,
                "recurring_action" => "PAYMENT",
                "currency" => "IDR",
                "amount" => $amount,
                "payment_methods" => [
                    [
                        // pmId stand for Payment Method ID
                        "payment_method_id" => $pmId,
                        "rank" => 1
                    ]
                ],
                "schedule" => [
                    "reference_id" => uniqid('plan@' . base64_encode(Auth::id())),
                    "interval" => "MONTH",
                    "interval_count" => 1,
                    "total_recurrence" => null,
                    "anchor_date" => Date::now()->addHour()->toIso8601String(),
                    "retry_interval" => "DAY",
                    "retry_interval_count" => 1,
                    "total_retry" => 2,
                    "failed_attempt_notifications" => [1, 2]
                ],
                "immediate_action_type" => "FULL_AMOUNT",
                "notification_config" => [
                    "recurring_created" => ["WHATSAPP", "EMAIL"],
                    "recurring_succeeded" => ["WHATSAPP", "EMAIL"],
                    "recurring_failed" => ["WHATSAPP", "EMAIL"],
                    "locale" => "id"
                ],
                "failed_cycle_action" => "STOP",
            ])->throw();
    }

    /**
     * @throws RequestException
     * @throws ConnectionException
     */
    public function updatePlan(string $planId, array $data): Response
    {
        return $this->client()
            ->patch('/recurring/plans/' . $planId, [
                "payment_methods" => [
                    [
                        "payment_method_id" => $data["payment_method_id"],
                        "rank" => 1
                    ]
                ]
            ])->throw();
    }

    /**
     * @throws RequestException
     * @throws ConnectionException
     */
    public function deactivatePlan(string $planId): Response
    {
        return $this->client()
            ->post('/recurring/plans/' . $planId . '/deactivate')->throw();
    }
}
