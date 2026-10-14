<?php

namespace App\Admin\Controllers;

use App\Admin\Traits\ScopesByMerchant;
use App\Models\Merchant;
use App\Models\PaymentApiLog;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Http\Controllers\AdminController;
use Dcat\Admin\Show;
use Illuminate\Support\Carbon;

class PaymentApiLogController extends AdminController
{
    use ScopesByMerchant;

    protected function grid(): Grid
    {
        $isAdministratorUser = $this->isAdministratorUser();

        return Grid::make(new PaymentApiLog, function (Grid $grid) use ($isAdministratorUser): void {
            $this->applyMerchantScope($grid->model());
            $grid->model()->with(['merchant', 'paymentOrder'])->orderByDesc('id');

            $grid->column('id')->sortable();
            $grid->column('merchant.name', admin_trans_field('merchant_id'));
            $grid->column('paymentOrder.merchant_order_no', admin_trans_field('payment_order_id'));
            $grid->column('direction')->using(__('payment-api-log.options.direction'));
            $grid->column('url')->limit(48);
            $grid->column('request_body')->display(fn ($value) => self::formatJson($value));
            $grid->column('response_status');
            $grid->column('response_body')->display(fn ($value) => self::formatJson($value));
            $grid->column('error')->limit(40);
            $grid->column('created_at')->display(fn ($value) => self::formatDateTime($value))->sortable();

            $grid->disableCreateButton();
            $grid->disableEditButton();
            $grid->disableDeleteButton();
            $grid->disableViewButton();
            $grid->disableActions();

            $grid->filter(function (Grid\Filter $filter) use ($isAdministratorUser): void {
                if ($isAdministratorUser) {
                    $filter->equal('merchant_id')->select(Merchant::query()->orderBy('name')->pluck('name', 'id'));
                }
                $filter->equal('direction')->select(__('payment-api-log.options.direction'));
                $filter->like('paymentOrder.merchant_order_no', admin_trans_field('payment_order_id'));
                $filter->equal('response_status');
            });

            $this->restrictGridToViewOnly($grid);
        });
    }

    protected function detail($id): Show
    {
        $log = PaymentApiLog::query()->findOrFail($id);
        $this->ensureMerchantAccess($log->merchant_id);
        $formatJson = fn (mixed $value): string => self::formatJson($value);
        $formatDateTime = fn (mixed $value): string => self::formatDateTime($value);

        return Show::make($id, new PaymentApiLog, function (Show $show) use ($formatJson, $formatDateTime): void {
            $show->field('id');
            $show->field('merchant.name', admin_trans_field('merchant_id'));
            $show->field('paymentOrder.merchant_order_no', admin_trans_field('payment_order_id'));
            $show->field('direction')->using(__('payment-api-log.options.direction'));
            $show->field('url');
            $show->field('request_body')->unescape()->as(function ($value) use ($formatJson) {
                return $formatJson($value);
            });
            $show->field('response_status');
            $show->field('response_body')->unescape()->as(function ($value) use ($formatJson) {
                return $formatJson($value);
            });
            $show->field('error');
            $show->field('created_at')->as(function ($value) use ($formatDateTime) {
                return $formatDateTime($value);
            });
        });
    }

    protected function form(): Form
    {
        $this->authorizeAdministratorOnly();

        return Form::make(new PaymentApiLog, function (Form $form): void {
            $form->display('id');
        });
    }

    private static function formatJson(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        $json = is_string($value)
            ? $value
            : json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return '<pre style="margin:0;max-width:420px;max-height:240px;overflow:auto;white-space:pre-wrap;">'.e((string) $json).'</pre>';
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
