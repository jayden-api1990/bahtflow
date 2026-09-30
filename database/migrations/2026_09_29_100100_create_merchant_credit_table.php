<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('merchant_credit', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('merchant_id');
            $table->decimal('credit_balance', 18, 4)->default(0);
            $table->timestamps();

            $table->foreign('merchant_id')
                ->references('id')
                ->on('merchant')
                ->cascadeOnDelete();

            $table->unique('merchant_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('merchant_credit');
    }
};
