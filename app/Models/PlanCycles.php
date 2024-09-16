<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
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

    public function customer(): HasOne
    {
        return $this->hasOne(XenditCustomer::class, 'customer_id', 'customer_id');
    }

    public function plan(): HasOne
    {
        return $this->hasOne(XenditPlans::class, 'id', 'plan_id');
    }
}
