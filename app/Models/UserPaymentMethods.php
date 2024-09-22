<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class UserPaymentMethods extends Model
{
    use HasFactory;

    protected $fillable = [
        'payment_method_id',
        'user_id',
        'customer_id',
        'status',
        'type',
        'failure_code',
        'channel_type',
        'channel_code'
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
        return $this->belongsTo(User::class, 'user_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(XenditCustomer::class, 'customer_id', 'customer_id');
    }
}
