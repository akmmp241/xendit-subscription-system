<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plan_cycles', function (Blueprint $table) {
            $table->id();
            $table->string('cycle_id');
            $table->foreignId('plan_id')->constrained('xendit_plans')->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained('xendit_customers', 'customer_id');
            $table->string('type', 50);
            $table->string('status', 50);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plan_cycles');
    }
};
