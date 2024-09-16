<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_payment_methods', function (Blueprint $table) {
            $table->id();
            $table->string('payment_method_id')->unique();
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('customer_id')->constrained('xendit_customers', 'customer_id');
            $table->string("status", 50);
            $table->string("type", 50);
            $table->string("failure_code", 50)->nullable();
            $table->string("channel_type", 50);
            $table->string("channel_code", 50);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_payment_methods');
    }
};
