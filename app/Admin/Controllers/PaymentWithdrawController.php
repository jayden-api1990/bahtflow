<?php

namespace App\Admin\Controllers;

use App\Models\PaymentOrder;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;

class PaymentWithdrawController extends PaymentOrderController
{
    protected string $orderType = PaymentOrder::TYPE_WITHDRAW;

    protected function title(): string
    {
        return __('menu.titles.payment-withdraw');
    }

    protected function addAmountColumn(Grid $grid): void
    {
        $this->alignRight($grid->column('amount'))->display(function () {
            return self::formatAmountBreakdown($this->amount, $this->completed_amount, $this->remaining_amount);
        });
    }

    protected function addPartyColumns(Grid $grid): void
    {
        $grid->column('payee', admin_trans_field('payee'))->display(function () {
            return self::formatParty($this->request_payload, ['toBankCode', 'toAccount', 'toAccountName']);
        });
    }

    protected function addPartyFields(Show $show): void
    {
        $formatParty = fn (mixed $source, array $keys): string => self::formatParty($source, $keys);

        $show->field('payee', admin_trans_field('payee'))->unescape()->as(function () use ($formatParty) {
            return $formatParty($this->request_payload, ['toBankCode', 'toAccount', 'toAccountName']);
        });
    }

    private static function formatAmountBreakdown(mixed $amount, mixed $completed, mixed $remaining): string
    {
        $lines = [
            '<b>'.e(self::plainAmount($amount)).'</b>',
            '<span style="color:#3c9a62">已匹配 '.e(self::plainAmount($completed)).'</span>',
            '<span style="color:#e39b3a">↩退回 '.e(self::plainAmount($remaining)).'</span>',
        ];

        return implode('<br>', $lines);
    }

    private static function plainAmount(mixed $value): string
    {
        return number_format((float) $value, 2, '.', '');
    }
}
