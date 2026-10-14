<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_api_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_order_id')->nullable()->constrained('payment_orders')->nullOnDelete();
            $table->foreignId('merchant_id')->nullable()->constrained('merchant')->nullOnDelete();
            $table->string('direction', 32);
            $table->string('url', 1000)->default('');
            $table->json('request_body')->nullable();
            $table->unsignedSmallInteger('response_status')->nullable();
            $table->json('response_body')->nullable();
            $table->string('error', 500)->nullable();
            $table->timestamps();

            $table->index(['payment_order_id', 'id']);
            $table->index(['merchant_id', 'id']);
            $table->index('direction');
        });

        if (Schema::hasColumn('payment_orders', 'request_payload')) {
            DB::table('payment_orders')->orderBy('id')->chunkById(100, function ($orders): void {
                $rows = [];
                foreach ($orders as $order) {
                    $rows = array_merge($rows, $this->legacyRows($order));
                }
                if ($rows !== []) {
                    DB::table('payment_api_logs')->insert($rows);
                }
            });

            Schema::table('payment_orders', function (Blueprint $table) {
                $table->dropColumn(['request_payload', 'upstream_response', 'callback_payload']);
            });
        }

        $this->addAdminMenu();
    }

    public function down(): void
    {
        if (Schema::hasTable('admin_menu')) {
            $menuId = DB::table('admin_menu')->where('uri', 'payment-api-log')->value('id');
            if ($menuId) {
                if (Schema::hasTable('admin_role_menu')) {
                    DB::table('admin_role_menu')->where('menu_id', $menuId)->delete();
                }
                DB::table('admin_menu')->where('id', $menuId)->delete();
            }
        }

        Schema::dropIfExists('payment_api_logs');

        if (! Schema::hasColumn('payment_orders', 'request_payload')) {
            Schema::table('payment_orders', function (Blueprint $table) {
                $table->json('request_payload')->nullable();
                $table->json('upstream_response')->nullable();
                $table->json('callback_payload')->nullable();
            });
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function legacyRows(object $order): array
    {
        $rows = [];
        $request = $this->jsonOrNull($order->request_payload ?? null);
        $upstream = $this->jsonOrNull($order->upstream_response ?? null);
        $callback = $this->jsonOrNull($order->callback_payload ?? null);
        $createdAt = $order->created_at ?? now();
        $updatedAt = $order->updated_at ?? $createdAt;
        $merchantPath = ($order->type ?? '') === 'withdraw' ? '/api/v1/withdraw' : '/api/v1/deposit';
        $upstreamPath = ($order->type ?? '') === 'withdraw' ? '/v1/p2p/withdraw' : '/v1/p2p/deposit';
        $callbackPath = ($order->type ?? '') === 'withdraw'
            ? '/api/jinlipay/withdraw-callback'
            : '/api/jinlipay/deposit-callback';

        if ($request !== null) {
            $rows[] = $this->row($order, 'merchant_request', $merchantPath, $request, null, $createdAt);
        }
        if ($upstream !== null) {
            $rows[] = $this->row($order, 'upstream', $upstreamPath, null, $upstream, $updatedAt);
        }
        if ($callback !== null) {
            $rows[] = $this->row($order, 'upstream_callback', $callbackPath, $callback, null, $updatedAt);
        }

        return $rows;
    }

    /**
     * @return array<string, mixed>
     */
    private function row(object $order, string $direction, string $url, ?string $requestBody, ?string $responseBody, mixed $at): array
    {
        return [
            'payment_order_id' => $order->id,
            'merchant_id' => $order->merchant_id,
            'direction' => $direction,
            'url' => $url,
            'request_body' => $requestBody,
            'response_status' => null,
            'response_body' => $responseBody,
            'error' => null,
            'created_at' => $at,
            'updated_at' => $at,
        ];
    }

    private function jsonOrNull(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (! is_string($value)) {
            $encoded = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

            return $encoded === false || $encoded === '[]' || $encoded === '{}' || $encoded === 'null' ? null : $encoded;
        }

        $trim = trim($value);
        if ($trim === '' || $trim === 'null' || $trim === '[]' || $trim === '{}') {
            return null;
        }

        json_decode($trim);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return json_encode(['raw' => $value], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: null;
        }

        return $trim;
    }

    private function addAdminMenu(): void
    {
        if (! Schema::hasTable('admin_menu')) {
            return;
        }

        $now = now();
        $parentId = DB::table('admin_menu')->where('title', 'Merchant')->whereNull('uri')->value('id');
        if ($parentId && ! DB::table('admin_menu')->where('uri', 'payment-api-log')->exists()) {
            $order = (int) DB::table('admin_menu')->where('parent_id', $parentId)->max('order') + 1;
            $menuId = DB::table('admin_menu')->insertGetId([
                'parent_id' => $parentId,
                'order' => $order,
                'title' => 'payment-api-log',
                'icon' => 'fa-file-text-o',
                'uri' => 'payment-api-log',
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

        if (! Schema::hasTable('admin_permissions')) {
            return;
        }

        $permission = DB::table('admin_permissions')->where('slug', 'merchant.manage')->first();
        if (! $permission || str_contains((string) $permission->http_path, '/payment-api-log')) {
            return;
        }

        $path = trim((string) $permission->http_path);
        $separator = str_contains($path, "\n") ? "\r\n" : ',';
        DB::table('admin_permissions')->where('id', $permission->id)->update([
            'http_path' => $path.$separator.'/payment-api-log*',
            'updated_at' => $now,
        ]);
    }
};
