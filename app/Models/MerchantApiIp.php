<?php

namespace App\Models;

use Dcat\Admin\Traits\HasDateTimeFormatter;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MerchantApiIp extends Model
{
    use HasDateTimeFormatter;

    protected $table = 'merchant_api_ip';

    protected $guarded = [];

    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class, 'merchant_id');
    }

    public static function isAllowed(int $merchantId, string $ip): bool
    {
        $whitelist = static::query()
            ->where('merchant_id', $merchantId)
            ->where('status', 1)
            ->pluck('ip_address');

        if ($whitelist->isEmpty()) {
            return true;
        }

        return $whitelist->contains($ip);
    }
}
