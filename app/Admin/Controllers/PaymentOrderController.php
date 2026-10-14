<?php

namespace App\Admin\Controllers;

use App\Admin\Traits\ScopesByMerchant;
use App\Models\Merchant;
use App\Models\PaymentOrder;
use Dcat\Admin\Grid;
use Dcat\Admin\Http\Controllers\AdminController;
use Dcat\Admin\Show;
use Illuminate\Support\Carbon;

class PaymentOrderController extends AdminController
{
    protected string $orderType;

    protected $translation = 'payment-order';

    use ScopesByMerchant;

    protected function grid(): Grid
    {
        $isAdministratorUser = $this->isAdministratorUser();
        
        return Grid::make(new PaymentOrder, function (Grid $grid) use ($isAdministratorUser): void {
            $this->applyMerchantScope($grid->model());
            $grid->model()->where('type', $this->orderType)->with('merchant')->orderByDesc('id');
            //$grid->column('id')->sortable();
            if($isAdministratorUser) 
                $grid->column('merchant.name', admin_trans_field('merchant_id'));

            $grid->column('merchant_order_no', admin_trans_field('order_no'))->display(function () {
                return self::formatOrderRefs($this->merchant_order_no, $this->upstream_ref);
            });
            $this->addPartyColumns($grid);
            $this->addAmountColumn($grid);
            $this->alignRight($grid->column('amount_fee'));
            $grid->column('status')->using(__('payment-order.options.status'))->label([
                PaymentOrder::STATUS_PROCESSING => 'info',
                PaymentOrder::STATUS_PENDING => 'primary',
                PaymentOrder::STATUS_UPDATE => 'info',
                PaymentOrder::STATUS_COMPLETE => 'success',
                PaymentOrder::STATUS_COMPLETE_BELOW_MIN => 'success',
                PaymentOrder::STATUS_FAILED => 'danger',
                PaymentOrder::STATUS_CANCELLED => 'warning',
                PaymentOrder::STATUS_CANCELLED_BY_ADMIN => 'warning',
                PaymentOrder::STATUS_TIMEOUT => 'danger',
            ]);
            $grid->column('created_at')->display(fn ($value) => self::formatDateTime($value))->sortable();
            $grid->column('updated_at')->display(fn ($value) => self::formatDateTime($value))->sortable();
            $grid->disableCreateButton();
            $grid->disableEditButton();
            $grid->disableDeleteButton();
            $grid->actions(function (Grid\Displayers\Actions $actions): void {
                $actions->disableEdit();
                $actions->disableDelete();
            });

            $grid->filter(function (Grid\Filter $filter) use ($isAdministratorUser): void {
                if($isAdministratorUser) 
                    $filter->equal('merchant_id')->select(Merchant::query()->orderBy('name')->pluck('name', 'id'));
                $filter->equal('status')->select(__('payment-order.options.status'));
                $filter->like('merchant_order_no');
                $filter->like('upstream_ref');
            });
        });
    }

    protected function detail($id): Show
    {
        $order = PaymentOrder::query()->findOrFail($id);
        if ($order->type !== $this->orderType) {
            abort(404);
        }

        $formatDateTime = fn (mixed $value): string => self::formatDateTime($value);
        $formatJson = fn (mixed $value): string => self::formatJson($value);

        return Show::make($id, new PaymentOrder, function (Show $show) use ($formatDateTime, $formatJson): void {
            $show->field('id');
            $show->field('merchant.name', admin_trans_field('merchant_id'));
            $show->field('type')->using(__('payment-order.options.type'));
            $show->field('merchant_order_no');
            $show->field('upstream_ref');
            $show->field('amount');
            $this->addPartyFields($show);
            $show->field('amount_fee');
            $show->field('status')->using(__('payment-order.options.status'));
            $show->field('callback_url');
            $show->field('request_payload')->unescape()->as(function ($value) use ($formatJson) {
                return $formatJson($value);
            });
            $show->field('upstream_response')->unescape()->as(function ($value) use ($formatJson) {
                return $formatJson($value);
            });
            $show->field('upload_url');
            $show->field('credit_locked');
            $show->field('credit_released');
            $show->field('credit_applied');
            $show->field('completed_amount');
            $show->field('remaining_amount');
            $show->field('created_at')->as(function ($value) use ($formatDateTime) {
                return $formatDateTime($value);
            });
            $show->field('updated_at')->as(function ($value) use ($formatDateTime) {
                return $formatDateTime($value);
            });
        });
    }

    protected function addPartyColumns(Grid $grid): void {}

    protected function addAmountColumn(Grid $grid): void
    {
        $this->alignRight($grid->column('amount'))->display(function () {
            return '<b>'.e($this->amount).'</b>';
        });
    }

    protected function alignRight(Grid\Column $column): Grid\Column
    {
        return $column->style('text-align:right')->setHeaderAttributes(['style' => 'text-align:right']);
    }

    protected function addPartyFields(Show $show): void {}

    protected static function formatOrderRefs(mixed $merchantOrderNo, mixed $upstreamRef): string
    {
        $lines = [];
        $merchantOrderNo = trim((string) $merchantOrderNo);
        $upstreamRef = trim((string) $upstreamRef);

        if ($merchantOrderNo !== '') {
            $lines[] = e(admin_trans_field('merchant_order_no')).'：'.e($merchantOrderNo);
        }
        if ($upstreamRef !== '') {
            $lines[] = e(admin_trans_field('upstream_ref')).'：'.e($upstreamRef);
        }

        return implode('<br>', $lines);
    }

    /**
     * @param  list<string>  $keys
     */
    protected static function formatParty(mixed $source, array $keys): string
    {
        if (! is_array($source)) {
            return '';
        }

        $lines = [];
        foreach ($keys as $key) {
            $value = trim((string) ($source[$key] ?? ''));
            if ($value !== '') {
                $lines[] = e($value);
            }
        }

        return implode('<br>', $lines);
    }

    protected static function formatMatchedTargets(mixed $upstream): string
    {
        if (! is_array($upstream)) {
            return '';
        }

        $matches = $upstream['data']['matchedWithdraws'] ?? $upstream['matchedWithdraws'] ?? [];
        if (! is_array($matches) || $matches === []) {
            return '';
        }

        if (isset($matches['toAccount']) || isset($matches['bankShortNameEN']) || isset($matches['toAccountName'])) {
            $matches = [$matches];
        }

        $blocks = [];
        foreach ($matches as $match) {
            $block = self::formatParty($match, ['bankShortNameEN', 'toAccount', 'toAccountName']);
            if ($block !== '') {
                $blocks[] = $block;
            }
        }

        return implode('<hr style="margin:6px 0;border:0;border-top:1px solid #eee">', $blocks);
    }

    private static function formatJson(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        $json = is_string($value)
            ? $value
            : json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return '<pre style="margin:0;max-width:640px;max-height:320px;overflow:auto;white-space:pre-wrap;">'.e((string) $json).'</pre>';
    }

    private static function formatDateTime(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        try {
            return Carbon::parse((string) $value)->format('Y-m-d H:i:s');
        } catch (\Throwable) {
            return (string) $value;
        }
    }
}
