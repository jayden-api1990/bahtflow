<?php

namespace Tests\Feature;

use App\Models\Merchant;
use App\Models\MerchantCredit;
use App\Models\PaymentApiLog;
use App\Models\PaymentOrder;
use App\Services\Merchant\MerchantSignService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MerchantPaymentTest extends TestCase
{
    use RefreshDatabase;

    public function test_deposit_returns_upload_url_and_complete_callback_credits_once(): void
    {
        $merchant = $this->merchant();
        Http::fake([
            'https://jlapi-v1-stg.loooopool.io/v1/p2p/deposit' => Http::response([
                'code' => '0000',
                'message' => 'Success',
                'data' => [
                    'ref' => 'JINLP-D-1',
                    'uploadUrl' => 'https://slip.example/upload',
                    'uploadToken' => 'token',
                    'expiredDate' => '2026-04-02T12:16:00.000Z',
                    'matchedWithdraws' => [],
                ],
            ], 201),
            'https://merchant.example/callback' => Http::response(['code' => '0000'], 200),
        ]);

        $response = $this->postJson('/api/v1/deposit', $this->signed($merchant, [
            'merchantOrderNo' => 'D001',
            'amount' => 100,
            'fromAccount' => '0987654321',
            'fromBankCode' => '004',
            'fullName' => 'Tester',
        ]));

        $response->assertOk()->assertJsonPath('data.uploadUrl', 'https://slip.example/upload');
        $this->assertSame('0.0000', (string) $merchant->credit->fresh()->credit_balance);

        $this->postJson('/api/jinlipay/deposit-callback', [
            'ref' => 'JINLP-D-1',
            'amount' => 100,
            'status' => 'complete',
        ])->assertOk();

        $this->postJson('/api/jinlipay/deposit-callback', [
            'ref' => 'JINLP-D-1',
            'amount' => 100,
            'status' => 'complete',
        ])->assertOk();

        $this->assertSame('100.0000', (string) $merchant->credit->fresh()->credit_balance);
        $this->assertSame(1, $merchant->credit->logs()->count());

        $order = PaymentOrder::query()->where('merchant_order_no', 'D001')->firstOrFail();
        $this->assertSame('D001', $order->request_payload['merchantOrderNo'] ?? null);
        $this->assertSame('0987654321', $order->request_payload['fromAccount'] ?? null);
        $this->assertSame('JINLP-D-1', $order->upstream_response['data']['ref'] ?? null);
        $this->assertSame(1, $order->apiLogs()->where('direction', PaymentApiLog::DIRECTION_MERCHANT_REQUEST)->count());
        $upstream = $order->apiLogs()->where('direction', PaymentApiLog::DIRECTION_UPSTREAM)->first();
        $this->assertNotNull($upstream);
        $this->assertArrayNotHasKey('key', $upstream->request_body ?? []);
        $this->assertSame('0987654321', $upstream->request_body['fromAccount'] ?? null);
        $this->assertSame(2, $order->apiLogs()->where('direction', PaymentApiLog::DIRECTION_UPSTREAM_CALLBACK)->count());
        $this->assertSame(1, $order->apiLogs()->where('direction', PaymentApiLog::DIRECTION_MERCHANT_CALLBACK)->count());
        $this->assertNull($order->apiLogs()->whereNotNull('request_body')->get()->first(fn ($log) => array_key_exists('md5key', $log->request_body ?? [])));
    }

    public function test_deposit_fee_is_deducted_from_credited_amount(): void
    {
        $merchant = $this->merchant(depositFee: 2.5);
        Http::fake([
            'https://jlapi-v1-stg.loooopool.io/v1/p2p/deposit' => Http::response([
                'code' => '0000',
                'message' => 'Success',
                'data' => [
                    'ref' => 'JINLP-D-FEE',
                    'uploadUrl' => 'https://slip.example/upload',
                    'uploadToken' => 'token',
                ],
            ], 201),
            'https://merchant.example/callback' => Http::response(['code' => '0000'], 200),
        ]);

        $this->postJson('/api/v1/deposit', $this->signed($merchant, [
            'merchantOrderNo' => 'D-FEE',
            'amount' => 200,
            'fromAccount' => '0987654321',
            'fromBankCode' => '004',
            'fullName' => 'Tester',
        ]))->assertOk();

        $this->assertSame('5.0000', (string) PaymentOrder::query()->first()->amount_fee);

        $this->postJson('/api/jinlipay/deposit-callback', [
            'ref' => 'JINLP-D-FEE',
            'amount' => 200,
            'status' => 'complete',
        ])->assertOk();

        $this->assertSame('195.0000', (string) $merchant->credit->fresh()->credit_balance);
        $order = PaymentOrder::query()->first();
        $this->assertSame('5.0000', (string) $order->amount_fee);
        $this->assertSame('195.0000', (string) $order->credit_applied);
        $this->assertSame('200.0000', (string) $order->completed_amount);
    }

    public function test_withdraw_fee_is_locked_and_settled_on_partial_completion(): void
    {
        $merchant = $this->merchant(500, withdrawFee: 1);
        Http::fake([
            'https://jlapi-v1-stg.loooopool.io/v1/p2p/withdraw' => Http::response([
                'code' => '0000',
                'message' => 'Success',
                'data' => [
                    'ref' => 'JINLP-W-FEE',
                    'amount' => 100,
                ],
            ], 201),
            'https://merchant.example/callback' => Http::response(['code' => '0000'], 200),
        ]);

        $this->postJson('/api/v1/withdraw', $this->signed($merchant, [
            'merchantOrderNo' => 'W-FEE',
            'amount' => 100,
            'toAccount' => '1234567890',
            'toBankCode' => '004',
            'toAccountName' => 'Tester',
        ]))->assertOk();

        $this->assertSame('399.0000', (string) $merchant->credit->fresh()->credit_balance);
        $this->assertSame('101.0000', (string) PaymentOrder::query()->first()->credit_locked);

        $this->postJson('/api/jinlipay/withdraw-callback', [
            'ref' => 'JINLP-W-FEE',
            'amount' => 100,
            'completedAmount' => 40,
            'remainingAmount' => 60,
            'status' => 'timeout',
            'ts' => 2000,
        ])->assertOk();

        $this->assertSame('459.6000', (string) $merchant->credit->fresh()->credit_balance);
        $this->assertSame('0.4000', (string) PaymentOrder::query()->first()->amount_fee);
    }

    public function test_withdraw_rejects_when_balance_cannot_cover_fee(): void
    {
        $merchant = $this->merchant(100, withdrawFee: 1);
        Http::fake();

        $this->postJson('/api/v1/withdraw', $this->signed($merchant, [
            'merchantOrderNo' => 'W-FEE-SHORT',
            'amount' => 100,
            'toAccount' => '1234567890',
            'toBankCode' => '004',
            'toAccountName' => 'Tester',
        ]))->assertStatus(400)->assertJsonPath('respMsg', 'Insufficient merchant credit.');

        $this->assertSame('100.0000', (string) $merchant->credit->fresh()->credit_balance);
        $this->assertSame(0, PaymentOrder::query()->count());
    }

    public function test_withdraw_locks_credit_and_refunds_remaining_on_final_callback(): void
    {
        $merchant = $this->merchant(500);
        Http::fake([
            'https://jlapi-v1-stg.loooopool.io/v1/p2p/withdraw' => Http::response([
                'code' => '0000',
                'message' => 'Success',
                'data' => [
                    'ref' => 'JINLP-W-1',
                    'amount' => 100,
                    'expiredDate' => '2026-04-02T12:30:00.000Z',
                ],
            ], 201),
            'https://merchant.example/callback' => Http::response(['code' => '0000'], 200),
        ]);

        $this->postJson('/api/v1/withdraw', $this->signed($merchant, [
            'merchantOrderNo' => 'W001',
            'amount' => 100,
            'toAccount' => '1234567890',
            'toBankCode' => '004',
            'toAccountName' => 'Tester',
        ]))->assertOk();

        $this->assertSame('400.0000', (string) $merchant->credit->fresh()->credit_balance);
        $withdraw = PaymentOrder::query()->where('merchant_order_no', 'W001')->firstOrFail();
        $this->assertSame('1234567890', $withdraw->request_payload['toAccount'] ?? null);
        $this->assertSame('JINLP-W-1', $withdraw->upstream_response['data']['ref'] ?? null);

        $this->postJson('/api/jinlipay/withdraw-callback', [
            'ref' => 'JINLP-W-1',
            'amount' => 100,
            'receivedAmount' => 40,
            'completedAmount' => 40,
            'remainingAmount' => 60,
            'status' => 'update',
            'partialFill' => true,
            'ts' => 1000,
        ])->assertOk();

        $this->assertSame('400.0000', (string) $merchant->credit->fresh()->credit_balance);
        $order = PaymentOrder::query()->where('merchant_order_no', 'W001')->firstOrFail();
        $this->assertSame(PaymentOrder::STATUS_UPDATE, $order->status);
        $this->assertSame(1, $order->apiLogs()->where('direction', PaymentApiLog::DIRECTION_MERCHANT_CALLBACK)->count());

        $this->postJson('/api/jinlipay/withdraw-callback', [
            'ref' => 'JINLP-W-1',
            'amount' => 100,
            'completedAmount' => 40,
            'remainingAmount' => 60,
            'status' => 'timeout',
            'partialFill' => true,
            'ts' => 2000,
        ])->assertOk();

        $this->assertSame('460.0000', (string) $merchant->credit->fresh()->credit_balance);
        $order = PaymentOrder::query()->where('merchant_order_no', 'W001')->firstOrFail();
        $this->assertSame(PaymentOrder::STATUS_TIMEOUT, $order->status);
        $this->assertSame(2, $order->apiLogs()->where('direction', PaymentApiLog::DIRECTION_MERCHANT_CALLBACK)->count());

        $this->postJson('/api/jinlipay/withdraw-callback', [
            'ref' => 'JINLP-W-1',
            'amount' => 100,
            'completedAmount' => 40,
            'remainingAmount' => 60,
            'status' => 'update',
            'ts' => 3000,
        ])->assertOk();

        $this->assertSame('460.0000', (string) $merchant->credit->fresh()->credit_balance);
        $this->assertSame(2, $order->apiLogs()->where('direction', PaymentApiLog::DIRECTION_MERCHANT_CALLBACK)->count());
    }

    public function test_withdraw_is_not_stored_when_upstream_rejects(): void
    {
        $merchant = $this->merchant(100);
        Http::fake([
            'https://jlapi-v1-stg.loooopool.io/v1/p2p/withdraw' => Http::response([
                'code' => 'P2P_022',
                'message' => 'Insufficient available balance',
                'messages' => ['en' => 'Insufficient available balance'],
                'data' => ['available' => 0],
            ], 400),
            'https://merchant.example/callback' => Http::response(['code' => '0000'], 200),
        ]);

        $this->postJson('/api/v1/withdraw', $this->signed($merchant, [
            'merchantOrderNo' => 'W002',
            'amount' => 80,
            'toAccount' => '1234567890',
            'toBankCode' => '004',
            'toAccountName' => 'Tester',
        ]))->assertStatus(400)->assertJsonPath('data.upstreamCode', 'P2P_022');

        $this->assertSame('100.0000', (string) $merchant->credit->fresh()->credit_balance);
        $this->assertSame(0, PaymentOrder::query()->count());
        $this->assertSame(0, $merchant->credit->logs()->count());
    }

    public function test_deposit_is_not_stored_when_upstream_rejects(): void
    {
        $merchant = $this->merchant();
        Http::fake([
            'https://jlapi-v1-stg.loooopool.io/v1/p2p/deposit' => Http::response([
                'code' => 'P2P_011',
                'message' => 'Amount must be greater than 0.',
            ], 400),
        ]);

        $this->postJson('/api/v1/deposit', $this->signed($merchant, [
            'merchantOrderNo' => 'D-FAIL',
            'amount' => 100,
            'fromAccount' => '0987654321',
            'fromBankCode' => '004',
            'fullName' => 'Tester',
        ]))->assertStatus(400)->assertJsonPath('data.upstreamCode', 'P2P_011');

        $this->assertSame(0, PaymentOrder::query()->count());
    }

    private function merchant(float $balance = 0, float $depositFee = 0, float $withdrawFee = 0): Merchant
    {
        $merchant = Merchant::query()->create([
            'name' => 'Merchant '.uniqid(),
            'merchant_serial' => (string) str()->uuid(),
            'md5key' => 'test-md5-key',
            'status' => 1,
            'callback_url' => 'https://merchant.example/callback',
            'currency' => 'THB',
            'deposit_fee' => $depositFee,
            'withdraw_fee' => $withdrawFee,
        ]);
        MerchantCredit::query()->create([
            'merchant_id' => $merchant->id,
            'credit_balance' => $balance,
        ]);

        return $merchant->fresh('credit');
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function signed(Merchant $merchant, array $payload): array
    {
        $payload['merchant_serial'] = $merchant->merchant_serial;
        $payload['sign'] = app(MerchantSignService::class)->sign($payload, (string) $merchant->md5key);

        return $payload;
    }
}
