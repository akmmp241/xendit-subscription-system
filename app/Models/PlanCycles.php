<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class PlanCycles extends Model
{
    use HasFactory;

    protected $fillable = [
        'cycle_id',
        'plan_id',
        'xendit_plan_id',
        'customer_id',
        'status',
        'type'
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(XenditCustomer::class, 'customer_id', 'customer_id');
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(XenditPlans::class, 'plan_id');
    }
}
