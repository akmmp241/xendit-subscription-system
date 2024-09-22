<?php

namespace App;

use App\Models\Session;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Str;
use Illuminate\Validation\UnauthorizedException;

trait SessionHandler
{
    public function createSession(): string
    {
        $refreshToken = Str::password();

        Session::query()->updateOrCreate([
            "user_agent" => request()->userAgent(),
        ], [
            'id' => base64_encode(uniqid(auth()->id() . '@')),
            'user_id' => auth()->id(),
            'ip_address' => request()->ip(),
            'refresh_token' => $refreshToken,
            'expires_at' => Date::now()->addDays(5),
        ]);

        return base64_encode($refreshToken);
    }

    public function checkRefreshToken(string $refreshToken): Session
    {
        $session = Session::query()->with('user')->where('refresh_token', base64_decode($refreshToken))->first();

        if (!$session) throw new UnauthorizedException('Invalid refresh token');

        if ($session->expires_at < Date::now()) throw new UnauthorizedException('Expired refresh token');

        return $session;
    }

    public function destroySession(): void
    {
        Session::query()->where('user_id', Auth::id())->delete();
    }
}
