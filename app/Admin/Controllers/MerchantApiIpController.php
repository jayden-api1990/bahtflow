<?php

namespace App\Admin\Controllers;

use App\Models\Merchant;
use App\Models\MerchantApiIp;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Http\Controllers\AdminController;
use Dcat\Admin\Show;
use Illuminate\Support\Carbon;

class MerchantApiIpController extends AdminController
{
    protected function grid(): Grid
    {
        return Grid::make(new MerchantApiIp(), function (Grid $grid): void {
            $grid->model()->with('merchant')->orderByDesc('id');

            $grid->column('id')->sortable();
            $grid->column('merchant.name', admin_trans_field('merchant_id'));
            $grid->column('ip_address');
            $grid->column('status')->using(__('merchant-api-ip.options.status'))->label([
                0 => 'danger',
                1 => 'success',
            ]);
            $grid->column('remark')->limit(30);
            $grid->column('created_at')->display(fn ($value) => self::formatDateTime($value))->sortable();
            $grid->column('updated_at')->display(fn ($value) => self::formatDateTime($value))->sortable();

            $grid->filter(function (Grid\Filter $filter): void {
                $filter->equal('merchant_id')->select(
                    Merchant::query()->orderBy('name')->pluck('name', 'id')
                );
                $filter->equal('status')->select(__('merchant-api-ip.options.status'));
                $filter->like('ip_address');
            });
        });
    }

    protected function detail($id): Show
    {
        return Show::make($id, new MerchantApiIp(), function (Show $show): void {
            $show->field('id');
            $show->field('merchant.name', admin_trans_field('merchant_id'));
            $show->field('ip_address');
            $show->field('status')->using(__('merchant-api-ip.options.status'));
            $show->field('remark');
            $show->field('created_at')->as(fn ($value) => self::formatDateTime($value));
            $show->field('updated_at')->as(fn ($value) => self::formatDateTime($value));
        });
    }

    protected function form(): Form
    {
        return Form::make(new MerchantApiIp(), function (Form $form): void {
            $form->display('id');

            $form->select('merchant_id', admin_trans_field('merchant_id'))
                ->options(Merchant::query()->orderBy('name')->pluck('name', 'id'))
                ->required();

            $form->text('ip_address', admin_trans_field('ip_address'))
                ->required()
                ->rules('required|ip');

            $form->radio('status', admin_trans_field('status'))
                ->options(__('merchant-api-ip.options.status'))
                ->default(1)
                ->required();

            $form->text('remark', admin_trans_field('remark'));
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
