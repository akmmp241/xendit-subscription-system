<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use PHPOpenSourceSaver\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    use HasFactory;

    protected $fillable = [
        'name',
        'mobile_number',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function getJWTIdentifier(): mixed
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims(): array
    {
        return [];
    }

    public function xenditCustomer(): HasOne
    {
        return $this->hasOne(XenditCustomer::class, 'user_id', 'id');
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(Session::class, 'user_id');
    }

    public function userPaymentMethods(): BelongsTo
    {
        return $this->belongsTo(UserPaymentMethods::class, 'user_id');
    }

    public function xenditPlan(): BelongsTo
    {
        return $this->belongsTo(XenditPlans::class, 'user_id');
    }
}
