<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class ForgetPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return ! Auth::check();
    }

    public function rules(): array
    {
        return [
            "email" => ["required", "email"]
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new ValidationException($validator);
    }
}
