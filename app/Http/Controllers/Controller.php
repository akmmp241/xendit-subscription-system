<?php

namespace App\Http\Controllers;

use App\SessionHandler;

abstract class Controller
{
    use SessionHandler;
    protected function respondWithToken($token): array
    {
        return [
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth()->factory()->getTTL()
        ];
    }
}
