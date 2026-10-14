<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('merchant', function (Blueprint $table) {
            $table->decimal('deposit_fee', 8, 4)->default(0)->after('currency');
            $table->decimal('withdraw_fee', 8, 4)->default(0)->after('deposit_fee');
        });
    }

    public function down(): void
    {
        Schema::table('merchant', function (Blueprint $table) {
            $table->dropColumn(['deposit_fee', 'withdraw_fee']);
        });
    }
};
