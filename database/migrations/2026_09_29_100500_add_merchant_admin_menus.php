<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        $parentId = DB::table('admin_menu')->insertGetId([
            'parent_id' => 0,
            'order' => 8,
            'title' => 'Merchant',
            'icon' => 'fa-shopping-bag',
            'uri' => null,
            'extension' => '',
            'show' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $menus = [
            ['title' => 'merchant', 'icon' => 'fa-align-justify', 'uri' => 'merchant', 'order' => 1],
            ['title' => 'merchant-credit', 'icon' => 'fa-money', 'uri' => 'merchant-credit', 'order' => 2],
            ['title' => 'merchant-credit-log', 'icon' => 'fa-list', 'uri' => 'merchant-credit-log', 'order' => 3],
            ['title' => 'merchant-api-ip', 'icon' => 'fa-shield', 'uri' => 'merchant-api-ip', 'order' => 4],
        ];

        foreach ($menus as $menu) {
            DB::table('admin_menu')->insert([
                'parent_id' => $parentId,
                'order' => $menu['order'],
                'title' => $menu['title'],
                'icon' => $menu['icon'],
                'uri' => $menu['uri'],
                'extension' => '',
                'show' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $permissionId = DB::table('admin_permissions')->insertGetId([
            'name' => 'Merchant manage',
            'slug' => 'merchant.manage',
            'http_method' => '',
            'http_path' => "/merchant*\r\n/merchant-credit*\r\n/merchant-credit-log*\r\n/merchant-api-ip*",
            'order' => 20,
            'parent_id' => 0,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $adminRoleId = (int) (DB::table('admin_roles')->where('slug', 'administrator')->value('id') ?? 1);

        if ($adminRoleId > 0 && $permissionId > 0) {
            DB::table('admin_role_permissions')->insertOrIgnore([
                'role_id' => $adminRoleId,
                'permission_id' => $permissionId,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $menuIds = DB::table('admin_menu')
            ->where('parent_id', $parentId)
            ->orWhere('id', $parentId)
            ->pluck('id');

        foreach ($menuIds as $menuId) {
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
        $parentId = DB::table('admin_menu')->where('title', 'Merchant')->whereNull('uri')->value('id');

        if ($parentId) {
            $menuIds = DB::table('admin_menu')
                ->where('parent_id', $parentId)
                ->orWhere('id', $parentId)
                ->pluck('id');

            DB::table('admin_role_menu')->whereIn('menu_id', $menuIds)->delete();
            DB::table('admin_menu')->whereIn('id', $menuIds)->delete();
        }

        $permissionId = DB::table('admin_permissions')->where('slug', 'merchant.manage')->value('id');

        if ($permissionId) {
            DB::table('admin_role_permissions')->where('permission_id', $permissionId)->delete();
            DB::table('admin_permissions')->where('id', $permissionId)->delete();
        }
    }
};
