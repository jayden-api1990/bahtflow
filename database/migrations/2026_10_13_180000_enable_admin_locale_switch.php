<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('admin_settings')) {
            return;
        }

        $now = now();
        $exists = DB::table('admin_settings')
            ->where('group_name', 'layout_config')
            ->where('slug', 'show_locale_switch')
            ->exists();

        if ($exists) {
            DB::table('admin_settings')
                ->where('group_name', 'layout_config')
                ->where('slug', 'show_locale_switch')
                ->update([
                    'value' => '1',
                    'updated_at' => $now,
                ]);
        } else {
            DB::table('admin_settings')->insert([
                'group_name' => 'layout_config',
                'slug' => 'show_locale_switch',
                'value' => '1',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('admin_settings')) {
            return;
        }

        DB::table('admin_settings')
            ->where('group_name', 'layout_config')
            ->where('slug', 'show_locale_switch')
            ->update([
                'value' => '',
                'updated_at' => now(),
            ]);
    }
};
