<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Xendit\PaymentMethod\EWalletChannelCode;

class CreatePaymentMethodRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check();
    }

    public function rules(): array
    {
        return [
            "channel_code" => ["required", "string", Rule::in([
                EWalletChannelCode::OVO,
                EWalletChannelCode::SHOPEEPAY
            ])]
        ];
    }
}
