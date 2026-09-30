<?php

namespace Tests\Feature;

use App\Models\Merchant;
use App\Models\MerchantCredit;
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
        $this->assertSame(PaymentOrder::STATUS_TIMEOUT, PaymentOrder::query()->first()->status);
    }

    public function test_withdraw_refunds_when_upstream_rejects(): void
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
    }

    private function merchant(float $balance = 0): Merchant
    {
        $merchant = Merchant::query()->create([
            'name' => 'Merchant '.uniqid(),
            'merchant_serial' => (string) str()->uuid(),
            'md5key' => 'test-md5-key',
            'status' => 1,
            'callback_url' => 'https://merchant.example/callback',
            'currency' => 'THB',
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
