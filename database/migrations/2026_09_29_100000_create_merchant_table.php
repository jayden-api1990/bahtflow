<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('merchant', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name')->unique()->default('');
            $table->string('merchant_serial')->unique()->default('');
            $table->string('md5key')->unique()->default('');
            $table->tinyInteger('status')->default(1);
            $table->string('callback_url')->default('');
            $table->string('currency')->default('');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('merchant');
    }
};
