<?php

namespace App\Models;

use Dcat\Admin\Traits\HasDateTimeFormatter;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MerchantCredit extends Model
{
    use HasDateTimeFormatter;

    protected $table = 'merchant_credit';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'credit_balance' => 'decimal:4',
        ];
    }

    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class, 'merchant_id');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(MerchantCreditLog::class, 'merchant_credit_id');
    }
}
