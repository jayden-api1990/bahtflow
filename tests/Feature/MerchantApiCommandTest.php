<?php

namespace Tests\Feature;

use App\Models\Merchant;
use App\Models\MerchantCredit;
use App\Services\Merchant\MerchantSignService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MerchantApiCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_print_outputs_signed_body_without_calling_the_api(): void
    {
        $merchant = $this->merchant();

        $this->artisan('merchant:api', [
            'endpoint' => 'balance',
            '--merchant' => $merchant->merchant_serial,
            '--print' => true,
        ])->assertSuccessful()
            ->expectsOutputToContain('"merchant_serial": "'.$merchant->merchant_serial.'"')
            ->expectsOutputToContain('"sign":');
    }

    public function test_balance_is_signed_and_returned(): void
    {
        $merchant = $this->merchant(500);

        $this->artisan('merchant:api', [
            'endpoint' => 'balance',
            '--merchant' => $merchant->merchant_serial,
        ])->assertSuccessful()
            ->expectsOutputToContain('HTTP 200')
            ->expectsOutputToContain('"creditBalance": "500"');
    }

    public function test_deposit_request_is_signed_and_sent(): void
    {
        $merchant = $this->merchant();
        Http::fake([
            'https://jlapi-v1-stg.loooopool.io/v1/p2p/deposit' => Http::response([
                'code' => '0000',
                'message' => 'Success',
                'data' => [
                    'ref' => 'JINLP-D-CMD',
                    'uploadUrl' => 'https://slip.example/upload',
                    'uploadToken' => 'token',
                    'expiredDate' => '2026-04-02T12:16:00.000Z',
                    'matchedWithdraws' => [],
                ],
            ], 201),
        ]);

        $this->artisan('merchant:api', [
            'endpoint' => 'deposit',
            '--merchant' => $merchant->merchant_serial,
            '--data' => json_encode([
                'merchantOrderNo' => 'D-CMD',
                'amount' => 100,
                'fromAccount' => '0987654321',
                'fromBankCode' => '004',
                'fullName' => 'Tester',
            ], JSON_THROW_ON_ERROR),
        ])->assertSuccessful()
            ->expectsOutputToContain('HTTP 200')
            ->expectsOutputToContain('https://slip.example/upload');
    }

    public function test_unknown_endpoint_and_invalid_json_fail(): void
    {
        $merchant = $this->merchant();

        $this->artisan('merchant:api', [
            'endpoint' => 'refund',
            '--merchant' => $merchant->merchant_serial,
        ])->assertFailed();

        $this->artisan('merchant:api', [
            'endpoint' => 'balance',
            '--merchant' => $merchant->merchant_serial,
            '--data' => '[1,2]',
        ])->assertFailed();
    }

    public function test_printed_sign_matches_merchant_key(): void
    {
        $merchant = $this->merchant();
        $sign = app(MerchantSignService::class)->sign([
            'merchant_serial' => $merchant->merchant_serial,
        ], (string) $merchant->md5key);

        $this->artisan('merchant:api', [
            'endpoint' => 'balance',
            '--merchant' => $merchant->merchant_serial,
            '--print' => true,
        ])->assertSuccessful()
            ->expectsOutputToContain($sign);
    }

    private function merchant(float $balance = 0): Merchant
    {
        $merchant = Merchant::query()->create([
            'name' => 'Merchant '.uniqid(),
            'merchant_serial' => (string) str()->uuid(),
            'md5key' => 'test-md5-key-'.uniqid(),
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
}
