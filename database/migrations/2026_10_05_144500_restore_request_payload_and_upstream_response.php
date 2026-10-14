<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('payment_orders', 'input')) {
            Schema::table('payment_orders', function (Blueprint $table) {
                $table->renameColumn('input', 'request_payload');
            });
        } elseif (! Schema::hasColumn('payment_orders', 'request_payload')) {
            Schema::table('payment_orders', function (Blueprint $table) {
                $table->json('request_payload')->nullable()->after('return_url');
            });
        }

        if (! Schema::hasColumn('payment_orders', 'upstream_response')) {
            Schema::table('payment_orders', function (Blueprint $table) {
                $table->json('upstream_response')->nullable()->after('request_payload');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('payment_orders', 'upstream_response')) {
            Schema::table('payment_orders', function (Blueprint $table) {
                $table->dropColumn('upstream_response');
            });
        }

        if (Schema::hasColumn('payment_orders', 'request_payload') && ! Schema::hasColumn('payment_orders', 'input')) {
            Schema::table('payment_orders', function (Blueprint $table) {
                $table->renameColumn('request_payload', 'input');
            });
        }
    }
};
