<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('merchant_id');
            $table->string('type', 20);
            $table->string('merchant_order_no', 64);
            $table->string('upstream_ref', 64)->nullable();
            $table->decimal('amount', 18, 4);
            $table->string('status', 32);
            $table->string('callback_url', 500)->default('');
            $table->string('return_url', 500)->nullable();
            $table->decimal('credit_locked', 18, 4)->default(0);
            $table->decimal('credit_released', 18, 4)->default(0);
            $table->decimal('credit_applied', 18, 4)->default(0);
            $table->decimal('completed_amount', 18, 4)->default(0);
            $table->decimal('remaining_amount', 18, 4)->default(0);
            $table->unsignedBigInteger('callback_ts')->nullable();
            $table->string('upload_url', 1000)->nullable();
            $table->string('upload_token', 255)->nullable();
            $table->json('request_payload')->nullable();
            $table->json('upstream_response')->nullable();
            $table->json('callback_payload')->nullable();
            $table->timestamp('merchant_notified_at')->nullable();
            $table->string('merchant_notify_status', 32)->nullable();
            $table->timestamps();

            $table->foreign('merchant_id')->references('id')->on('merchant');
            $table->unique(['merchant_id', 'merchant_order_no']);
            $table->unique('upstream_ref');
            $table->index(['type', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_orders');
    }
};
