<?php

namespace App\Rules;

use App\Models\User;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Auth;

class DuplicateMobileNumber implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (Auth::user()->mobile_number === $value) return;

        if (User::query()->where('mobile_number', $value)->exists()) $fail('Email already exists.');
    }
}
