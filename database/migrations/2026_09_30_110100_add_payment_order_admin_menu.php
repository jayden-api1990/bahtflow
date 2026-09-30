<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('admin_menu')) {
            return;
        }

        $now = now();
        $parentId = DB::table('admin_menu')->where('title', 'Merchant')->whereNull('uri')->value('id');

        if (! $parentId || DB::table('admin_menu')->where('uri', 'payment-order')->exists()) {
            return;
        }

        $order = (int) DB::table('admin_menu')->where('parent_id', $parentId)->max('order') + 1;

        $menuId = DB::table('admin_menu')->insertGetId([
            'parent_id' => $parentId,
            'order' => $order,
            'title' => 'payment-order',
            'icon' => 'fa-list-alt',
            'uri' => 'payment-order',
            'extension' => '',
            'show' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $adminRoleId = (int) (DB::table('admin_roles')->where('slug', 'administrator')->value('id') ?? 0);
        if ($adminRoleId > 0 && Schema::hasTable('admin_role_menu')) {
            DB::table('admin_role_menu')->insertOrIgnore([
                'role_id' => $adminRoleId,
                'menu_id' => $menuId,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('admin_menu')) {
            return;
        }

        $menuId = DB::table('admin_menu')->where('uri', 'payment-order')->value('id');
        if (! $menuId) {
            return;
        }

        if (Schema::hasTable('admin_role_menu')) {
            DB::table('admin_role_menu')->where('menu_id', $menuId)->delete();
        }

        DB::table('admin_menu')->where('id', $menuId)->delete();
    }
};
