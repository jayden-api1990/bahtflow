<?php

namespace App\Models;

use Dcat\Admin\Traits\HasDateTimeFormatter;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentApiLog extends Model
{
    use HasDateTimeFormatter;

    public const DIRECTION_MERCHANT_REQUEST = 'merchant_request';

    public const DIRECTION_UPSTREAM = 'upstream';

    public const DIRECTION_UPSTREAM_CALLBACK = 'upstream_callback';

    public const DIRECTION_MERCHANT_CALLBACK = 'merchant_callback';

    protected $table = 'payment_api_logs';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'request_body' => 'array',
            'response_body' => 'array',
        ];
    }

    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class, 'merchant_id');
    }

    public function paymentOrder(): BelongsTo
    {
        return $this->belongsTo(PaymentOrder::class, 'payment_order_id');
    }
}
