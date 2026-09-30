<?php

namespace App\Models;

use Dcat\Admin\Traits\HasDateTimeFormatter;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Merchant extends Model
{
    use HasDateTimeFormatter;
    use SoftDeletes;

    protected $table = 'merchant';

    protected $guarded = [];

    public function adminUsers(): HasMany
    {
        return $this->hasMany(AdminUser::class, 'merchant_id');
    }

    public function credit(): HasOne
    {
        return $this->hasOne(MerchantCredit::class, 'merchant_id');
    }

    public function apiIps(): HasMany
    {
        return $this->hasMany(MerchantApiIp::class, 'merchant_id');
    }
}
