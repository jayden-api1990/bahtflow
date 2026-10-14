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
        if (! $parentId) {
            return;
        }

        $deposit = DB::table('admin_menu')->where('uri', 'payment-deposit')->first();
        if (! $deposit) {
            $legacy = DB::table('admin_menu')->where('uri', 'payment-order')->first();
            if ($legacy) {
                DB::table('admin_menu')->where('id', $legacy->id)->update([
                    'title' => 'payment-deposit',
                    'icon' => 'fa-arrow-circle-down',
                    'uri' => 'payment-deposit',
                    'show' => 1,
                    'updated_at' => $now,
                ]);
                $depositId = (int) $legacy->id;
                $depositOrder = (int) $legacy->order;
            } else {
                $depositOrder = (int) DB::table('admin_menu')->where('parent_id', $parentId)->max('order') + 1;
                $depositId = DB::table('admin_menu')->insertGetId([
                    'parent_id' => $parentId,
                    'order' => $depositOrder,
                    'title' => 'payment-deposit',
                    'icon' => 'fa-arrow-circle-down',
                    'uri' => 'payment-deposit',
                    'extension' => '',
                    'show' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
                $this->grantMenuToAdministrator($depositId, $now);
            }
        } else {
            $depositId = (int) $deposit->id;
            $depositOrder = (int) $deposit->order;
        }

        if (! DB::table('admin_menu')->where('uri', 'payment-withdraw')->exists()) {
            $withdrawOrder = $depositOrder + 1;
            DB::table('admin_menu')
                ->where('parent_id', $parentId)
                ->where('order', '>=', $withdrawOrder)
                ->increment('order');

            $withdrawId = DB::table('admin_menu')->insertGetId([
                'parent_id' => $parentId,
                'order' => $withdrawOrder,
                'title' => 'payment-withdraw',
                'icon' => 'fa-arrow-circle-up',
                'uri' => 'payment-withdraw',
                'extension' => '',
                'show' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            if (Schema::hasTable('admin_role_menu')) {
                $roleIds = DB::table('admin_role_menu')->where('menu_id', $depositId)->pluck('role_id');
                foreach ($roleIds as $roleId) {
                    DB::table('admin_role_menu')->insertOrIgnore([
                        'role_id' => $roleId,
                        'menu_id' => $withdrawId,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
            }
        }

        $this->grantPaymentPaths($now);
    }

    public function down(): void
    {
        if (! Schema::hasTable('admin_menu')) {
            return;
        }

        $withdrawId = DB::table('admin_menu')->where('uri', 'payment-withdraw')->value('id');
        if ($withdrawId && Schema::hasTable('admin_role_menu')) {
            DB::table('admin_role_menu')->where('menu_id', $withdrawId)->delete();
        }
        if ($withdrawId) {
            DB::table('admin_menu')->where('id', $withdrawId)->delete();
        }

        DB::table('admin_menu')->where('uri', 'payment-deposit')->update([
            'title' => 'payment-order',
            'icon' => 'fa-list-alt',
            'uri' => 'payment-order',
            'updated_at' => now(),
        ]);
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

    private function grantPaymentPaths(mixed $now): void
    {
        if (! Schema::hasTable('admin_permissions')) {
            return;
        }

        $permission = DB::table('admin_permissions')->where('slug', 'merchant.manage')->first();
        if (! $permission) {
            return;
        }

        $path = (string) $permission->http_path;
        $separator = str_contains($path, "\n") ? "\r\n" : ',';
        $updated = str_replace('/payment-order*', '/payment-deposit*,/payment-withdraw*', $path);

        foreach (['/payment-deposit*', '/payment-withdraw*'] as $pattern) {
            if (str_contains($updated, $pattern)) {
                continue;
            }

            $updated = trim($updated);
            $updated = $updated === '' ? $pattern : $updated.$separator.$pattern;
        }

        if ($updated === $path) {
            return;
        }

        DB::table('admin_permissions')->where('id', $permission->id)->update([
            'http_path' => $updated,
            'updated_at' => $now,
        ]);
    }
};
