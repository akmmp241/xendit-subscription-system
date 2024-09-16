<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('xendit_plans', function (Blueprint $table) {
            $table->id();
            $table->string('plan_id');
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('customer_id')->constrained('xendit_customers', 'customer_id');
            $table->string('recurring_action', 50);
            $table->unsignedInteger('recurring_cycle_count');
            $table->unsignedInteger('amount');
            $table->string('type', 50);
            $table->string('status', 50);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('xendit_plans');
    }
};
