<?php

namespace App\Console\Commands;

use App\Models\Merchant;
use App\Services\Merchant\MerchantSignService;
use Illuminate\Console\Command;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;
use JsonException;
use Symfony\Component\HttpFoundation\Response;

class MerchantApiCommand extends Command
{
    private const ENDPOINTS = [
        'balance',
        'deposit',
        'withdraw',
        'deposit/cancel',
    ];

    protected $signature = 'merchant:api
        {endpoint : balance、deposit、withdraw 或 deposit/cancel}
        {--merchant= : 商户序号 merchant_serial}
        {--data= : JSON 请求体，不需要带 merchant_serial 和 sign}
        {--print : 只输出签好名的 JSON，不发送请求}';

    protected $description = '用商户密钥签名并调用 /api/v1 接口';

    public function handle(MerchantSignService $signer): int
    {
        $endpoint = trim((string) $this->argument('endpoint'), '/');
        if (! in_array($endpoint, self::ENDPOINTS, true)) {
            $this->error('接口必须是 '.implode('、', self::ENDPOINTS).' 之一。');

            return self::FAILURE;
        }

        $serial = trim((string) $this->option('merchant'));
        if ($serial === '') {
            $this->error('请用 --merchant= 指定商户序号。');

            return self::FAILURE;
        }

        $merchant = Merchant::query()->where('merchant_serial', $serial)->first();
        if (! $merchant) {
            $this->error('找不到该商户序号。');

            return self::FAILURE;
        }

        try {
            $payload = $this->payload();
        } catch (JsonException) {
            $this->error('--data 必须是 JSON 对象。');

            return self::FAILURE;
        }

        $payload['merchant_serial'] = $merchant->merchant_serial;
        unset($payload['sign']);
        $payload['sign'] = $signer->sign($payload, (string) $merchant->md5key);

        $json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($json === false) {
            $this->error('请求体无法编码为 JSON。');

            return self::FAILURE;
        }

        if ($this->option('print')) {
            $this->line($json);

            return self::SUCCESS;
        }

        $response = $this->dispatch($endpoint, $json);
        $this->line('HTTP '.$response->getStatusCode());
        $this->line($this->prettyBody((string) $response->getContent()));

        $decoded = json_decode((string) $response->getContent(), true);

        return is_array($decoded) && ($decoded['respCode'] ?? null) === '0000'
            ? self::SUCCESS
            : self::FAILURE;
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(): array
    {
        $raw = $this->option('data');
        if ($raw === null || $raw === '') {
            return [];
        }

        $decoded = json_decode((string) $raw, true, 512, JSON_THROW_ON_ERROR);
        if (! is_array($decoded) || ($decoded !== [] && array_is_list($decoded))) {
            throw new JsonException('JSON payload must be an object.');
        }

        return $decoded;
    }

    private function dispatch(string $endpoint, string $json): Response
    {
        $server = [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
            'REMOTE_ADDR' => '127.0.0.1',
        ];

        $domain = config('app.api_domain');
        if (is_string($domain) && $domain !== '') {
            $server['HTTP_HOST'] = $domain;
            $server['SERVER_NAME'] = $domain;
        }

        $request = Request::create('/api/v1/'.$endpoint, 'POST', server: $server, content: $json);
        $kernel = app(Kernel::class);
        $response = $kernel->handle($request);
        $kernel->terminate($request, $response);

        return $response;
    }

    private function prettyBody(string $body): string
    {
        try {
            $decoded = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return $body;
        }

        return json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: $body;
    }
}
