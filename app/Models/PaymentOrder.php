<?php

namespace App\Models;

use Dcat\Admin\Traits\HasDateTimeFormatter;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PaymentOrder extends Model
{
    use HasDateTimeFormatter;

    public const TYPE_DEPOSIT = 'deposit';

    public const TYPE_WITHDRAW = 'withdraw';

    public const STATUS_PROCESSING = 'processing';

    public const STATUS_PENDING = 'pending';

    public const STATUS_FAILED = 'failed';

    public const STATUS_UPDATE = 'update';

    public const STATUS_COMPLETE = 'complete';

    public const STATUS_COMPLETE_BELOW_MIN = 'complete_below_min';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_CANCELLED_BY_ADMIN = 'cancelled_by_admin';

    public const STATUS_TIMEOUT = 'timeout';

    public const DEPOSIT_FINAL = [
        self::STATUS_COMPLETE,
        self::STATUS_CANCELLED,
        self::STATUS_CANCELLED_BY_ADMIN,
        self::STATUS_TIMEOUT,
        self::STATUS_FAILED,
    ];

    public const WITHDRAW_FINAL = [
        self::STATUS_COMPLETE,
        self::STATUS_COMPLETE_BELOW_MIN,
        self::STATUS_CANCELLED,
        self::STATUS_CANCELLED_BY_ADMIN,
        self::STATUS_TIMEOUT,
        self::STATUS_FAILED,
    ];

    protected $table = 'payment_orders';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:4',
            'amount_fee' => 'decimal:4',
            'credit_locked' => 'decimal:4',
            'credit_released' => 'decimal:4',
            'credit_applied' => 'decimal:4',
            'completed_amount' => 'decimal:4',
            'remaining_amount' => 'decimal:4',
            'request_payload' => 'array',
            'upstream_response' => 'array',
            'merchant_notified_at' => 'datetime',
        ];
    }

    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class, 'merchant_id');
    }

    public function apiLogs(): HasMany
    {
        return $this->hasMany(PaymentApiLog::class, 'payment_order_id');
    }

    public function isFinal(): bool
    {
        $finals = $this->type === self::TYPE_WITHDRAW ? self::WITHDRAW_FINAL : self::DEPOSIT_FINAL;

        return in_array($this->status, $finals, true);
    }
}
