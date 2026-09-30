<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('merchant_credit_log', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('merchant_id');
            $table->unsignedBigInteger('merchant_credit_id');
            $table->string('type', 20)->comment('increase|decrease');
            $table->decimal('amount', 18, 4);
            $table->decimal('balance_before', 18, 4);
            $table->decimal('balance_after', 18, 4);
            $table->string('note', 500)->nullable();
            $table->unsignedBigInteger('admin_user_id')->nullable();
            $table->timestamps();

            $table->foreign('merchant_id')
                ->references('id')
                ->on('merchant')
                ->cascadeOnDelete();

            $table->foreign('merchant_credit_id')
                ->references('id')
                ->on('merchant_credit')
                ->cascadeOnDelete();

            $table->foreign('admin_user_id')
                ->references('id')
                ->on('admin_users')
                ->nullOnDelete();

            $table->index(['merchant_id', 'type']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('merchant_credit_log');
    }
};
