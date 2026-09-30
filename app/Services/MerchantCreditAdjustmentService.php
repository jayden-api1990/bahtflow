<?php

namespace App\Services;

use App\Models\MerchantCredit;
use App\Models\MerchantCreditLog;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class MerchantCreditAdjustmentService
{
    public function adjust(
        int $merchantCreditId,
        string $type,
        float $amount,
        ?string $note = null,
        ?int $adminUserId = null,
    ): MerchantCreditLog {
        if (! in_array($type, ['increase', 'decrease'], true)) {
            throw new RuntimeException('INVALID_ADJUSTMENT_TYPE');
        }

        if ($amount <= 0) {
            throw new RuntimeException('INVALID_AMOUNT');
        }

        return DB::transaction(function () use ($merchantCreditId, $type, $amount, $note, $adminUserId): MerchantCreditLog {
            $merchantCredit = MerchantCredit::query()
                ->whereKey($merchantCreditId)
                ->lockForUpdate()
                ->firstOrFail();

            $balanceBefore = (float) $merchantCredit->credit_balance;

            if ($type === 'decrease' && $balanceBefore < $amount) {
                throw new RuntimeException('INSUFFICIENT_BALANCE');
            }

            if ($type === 'increase') {
                $merchantCredit->increment('credit_balance', $amount);
            } else {
                $merchantCredit->decrement('credit_balance', $amount);
            }

            $merchantCredit->refresh();

            return MerchantCreditLog::query()->create([
                'merchant_id' => $merchantCredit->merchant_id,
                'merchant_credit_id' => $merchantCredit->id,
                'type' => $type,
                'amount' => $amount,
                'balance_before' => $balanceBefore,
                'balance_after' => (float) $merchantCredit->credit_balance,
                'note' => $note,
                'admin_user_id' => $adminUserId,
            ]);
        });
    }
}
