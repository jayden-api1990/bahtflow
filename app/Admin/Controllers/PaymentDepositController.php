<?php

namespace App\Admin\Controllers;

use App\Models\PaymentOrder;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;

class PaymentDepositController extends PaymentOrderController
{
    protected string $orderType = PaymentOrder::TYPE_DEPOSIT;

    protected function title(): string
    {
        return __('menu.titles.payment-deposit');
    }

    protected function addPartyColumns(Grid $grid): void
    {
        $grid->column('payer', admin_trans_field('payer'))->display(function () {
            return self::formatParty($this->request_payload, ['fromBankCode', 'fromAccount', 'fullName']);
        });
        $grid->column('matched_withdraw', admin_trans_field('matched_withdraw'))->display(function () {
            return self::formatMatchedTargets($this->upstream_response);
        });
    }

    protected function addPartyFields(Show $show): void
    {
        $formatParty = fn (mixed $source, array $keys): string => self::formatParty($source, $keys);
        $formatTargets = fn (mixed $upstream): string => self::formatMatchedTargets($upstream);

        $show->field('payer', admin_trans_field('payer'))->unescape()->as(function () use ($formatParty) {
            return $formatParty($this->request_payload, ['fromBankCode', 'fromAccount', 'fullName']);
        });
        $show->field('matched_withdraw', admin_trans_field('matched_withdraw'))->unescape()->as(function () use ($formatTargets) {
            return $formatTargets($this->upstream_response);
        });
    }
}
