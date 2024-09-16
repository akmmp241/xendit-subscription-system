<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class XenditCustomer extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'user_id',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function userPaymentMethods(): BelongsTo
    {
        return $this->belongsTo(UserPaymentMethods::class, 'customer_id', 'customer_id');
    }

    public function xenditPlan(): BelongsTo
    {
        return $this->belongsTo(XenditPlans::class, 'customer_id', 'customer_id');
    }

    public function planCycle(): BelongsTo
    {
        return $this->belongsTo(PlanCycles::class, 'customer_id', 'customer_id');
    }
}
