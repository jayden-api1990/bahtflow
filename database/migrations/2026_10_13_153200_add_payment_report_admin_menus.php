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
        $parentId = DB::table('admin_menu')->where('title', 'Report')->whereNull('uri')->value('id');
        if (! $parentId) {
            $parentId = DB::table('admin_menu')->insertGetId([
                'parent_id' => 0,
                'order' => ((int) DB::table('admin_menu')->where('parent_id', 0)->max('order')) + 1,
                'title' => 'Report',
                'icon' => 'fa-bar-chart',
                'uri' => null,
                'extension' => '',
                'show' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $this->grantMenuToAdministrator((int) $parentId, $now);
        }

        $this->ensureChildMenu((int) $parentId, 'payment-deposit-report', 'fa-line-chart', 1, $now);
        $this->ensureChildMenu((int) $parentId, 'payment-withdraw-report', 'fa-area-chart', 2, $now);
        $this->ensureReportPermission($now);
    }

    public function down(): void
    {
        if (! Schema::hasTable('admin_menu')) {
            return;
        }

        $menuIds = DB::table('admin_menu')
            ->whereIn('uri', ['payment-deposit-report', 'payment-withdraw-report'])
            ->orWhere(function ($query): void {
                $query->where('title', 'Report')->whereNull('uri');
            })
            ->pluck('id');

        if ($menuIds->isNotEmpty() && Schema::hasTable('admin_role_menu')) {
            DB::table('admin_role_menu')->whereIn('menu_id', $menuIds)->delete();
        }
        if ($menuIds->isNotEmpty()) {
            DB::table('admin_menu')->whereIn('id', $menuIds)->delete();
        }

        if (Schema::hasTable('admin_permissions')) {
            $permissionId = DB::table('admin_permissions')->where('slug', 'report.view')->value('id');
            if ($permissionId && Schema::hasTable('admin_role_permissions')) {
                DB::table('admin_role_permissions')->where('permission_id', $permissionId)->delete();
            }
            if ($permissionId) {
                DB::table('admin_permissions')->where('id', $permissionId)->delete();
            }
        }
    }

    private function ensureChildMenu(int $parentId, string $uri, string $icon, int $order, mixed $now): void
    {
        $existing = DB::table('admin_menu')->where('uri', $uri)->first();
        if ($existing) {
            return;
        }

        $menuId = DB::table('admin_menu')->insertGetId([
            'parent_id' => $parentId,
            'order' => $order,
            'title' => $uri,
            'icon' => $icon,
            'uri' => $uri,
            'extension' => '',
            'show' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $this->grantMenuToAdministrator((int) $menuId, $now);
    }

    private function grantMenuToAdministrator(int $menuId, mixed $now): void
    {
        if (! Schema::hasTable('admin_role_menu')) {
            return;
        }

        $adminRoleId = (int) (DB::table('admin_roles')->where('slug', 'administrator')->value('id') ?? 0);
        if ($adminRoleId < 1) {
            return;
        }

        DB::table('admin_role_menu')->insertOrIgnore([
            'role_id' => $adminRoleId,
            'menu_id' => $menuId,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    private function ensureReportPermission(mixed $now): void
    {
        if (! Schema::hasTable('admin_permissions')) {
            return;
        }

        $permissionId = DB::table('admin_permissions')->where('slug', 'report.view')->value('id');
        if (! $permissionId) {
            $permissionId = DB::table('admin_permissions')->insertGetId([
                'name' => 'Report view',
                'slug' => 'report.view',
                'http_method' => '',
                'http_path' => "/payment-deposit-report*\r\n/payment-withdraw-report*",
                'order' => ((int) DB::table('admin_permissions')->max('order')) + 1,
                'parent_id' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        if (! Schema::hasTable('admin_role_permissions')) {
            return;
        }

        $adminRoleId = (int) (DB::table('admin_roles')->where('slug', 'administrator')->value('id') ?? 0);
        if ($adminRoleId < 1) {
            return;
        }

        DB::table('admin_role_permissions')->insertOrIgnore([
            'role_id' => $adminRoleId,
            'permission_id' => $permissionId,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }
};
