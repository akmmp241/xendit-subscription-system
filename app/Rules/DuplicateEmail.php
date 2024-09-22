<?php

namespace App\Rules;

use App\Models\User;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Auth;

class DuplicateEmail implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (Auth::user()->email === $value) return;

        if (User::query()->where('email', $value)->exists()) $fail('Email already exists.');
    }
}
