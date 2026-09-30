<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('merchant_api_ip', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('merchant_id');
            $table->string('ip_address', 45);
            $table->tinyInteger('status')->default(1);
            $table->string('remark')->default('');
            $table->timestamps();

            $table->foreign('merchant_id')
                ->references('id')
                ->on('merchant')
                ->cascadeOnDelete();

            $table->unique(['merchant_id', 'ip_address']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('merchant_api_ip');
    }
};
