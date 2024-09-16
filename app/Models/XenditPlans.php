<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class XenditPlans extends Model
{
    use HasFactory;

    protected $fillable = [
        'plan_id',
        'user_id',
        'customer_id',
        'recurring_action',
        'recurring_cycle_count',
        'amount',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function user(): HasOne
    {
        return $this->hasOne(User::class, 'id', 'user_id');
    }

    public function customer(): HasOne
    {
        return $this->hasOne(XenditCustomer::class, 'customer_id', 'customer_id');
    }

    public function cycle(): BelongsTo
    {
        return $this->belongsTo(PlanCycles::class, 'plan_id', 'id');
    }
}
