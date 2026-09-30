<?php

namespace App\Admin\Controllers;

use App\Models\Merchant;
use App\Models\PaymentOrder;
use Dcat\Admin\Grid;
use Dcat\Admin\Http\Controllers\AdminController;
use Dcat\Admin\Show;
use Illuminate\Support\Carbon;

class PaymentOrderController extends AdminController
{
    protected function grid(): Grid
    {
        return Grid::make(new PaymentOrder(), function (Grid $grid): void {
            $grid->model()->with('merchant')->orderByDesc('id');
            $grid->column('id')->sortable();
            $grid->column('merchant.name', admin_trans_field('merchant_id'));
            $grid->column('type')->using(__('payment-order.options.type'));
            $grid->column('merchant_order_no');
            $grid->column('upstream_ref');
            $grid->column('amount');
            $grid->column('status');
            $grid->column('credit_locked');
            $grid->column('credit_released');
            $grid->column('credit_applied');
            $grid->column('created_at')->display(fn ($value) => self::formatDateTime($value))->sortable();

            $grid->disableCreateButton();
            $grid->disableEditButton();
            $grid->disableDeleteButton();
            $grid->actions(function (Grid\Displayers\Actions $actions): void {
                $actions->disableEdit();
                $actions->disableDelete();
            });

            $grid->filter(function (Grid\Filter $filter): void {
                $filter->equal('merchant_id')->select(Merchant::query()->orderBy('name')->pluck('name', 'id'));
                $filter->equal('type')->select(__('payment-order.options.type'));
                $filter->equal('status');
                $filter->like('merchant_order_no');
                $filter->like('upstream_ref');
            });
        });
    }

    protected function detail($id): Show
    {
        return Show::make($id, new PaymentOrder(), function (Show $show): void {
            $show->field('id');
            $show->field('merchant.name', admin_trans_field('merchant_id'));
            $show->field('type');
            $show->field('merchant_order_no');
            $show->field('upstream_ref');
            $show->field('amount');
            $show->field('status');
            $show->field('callback_url');
            $show->field('upload_url');
            $show->field('credit_locked');
            $show->field('credit_released');
            $show->field('credit_applied');
            $show->field('completed_amount');
            $show->field('remaining_amount');
            $show->field('request_payload')->json();
            $show->field('upstream_response')->json();
            $show->field('callback_payload')->json();
            $show->field('created_at')->as(fn ($value) => self::formatDateTime($value));
            $show->field('updated_at')->as(fn ($value) => self::formatDateTime($value));
        });
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
