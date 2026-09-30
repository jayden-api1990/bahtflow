<?php

namespace App\Admin\Controllers;

use App\Admin\Traits\ScopesByMerchant;
use App\Models\Merchant;
use App\Models\MerchantCreditLog;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Http\Controllers\AdminController;
use Dcat\Admin\Show;
use Illuminate\Support\Carbon;

class MerchantCreditLogController extends AdminController
{
    use ScopesByMerchant;

    protected function grid(): Grid
    {
        $isAdministratorUser = $this->isAdministratorUser();

        return Grid::make(new MerchantCreditLog(), function (Grid $grid) use ($isAdministratorUser): void {
            $this->applyMerchantScope($grid->model());
            $grid->model()->with(['merchant', 'adminUser'])->orderByDesc('id');

            $grid->column('id')->sortable();
            $grid->column('merchant.name', admin_trans_field('merchant_id'));
            $grid->column('type')->using(__('merchant-credit-log.options.type'))->label([
                'increase' => 'success',
                'decrease' => 'danger',
            ]);
            $grid->column('amount');
            $grid->column('balance_before');
            $grid->column('balance_after');
            $grid->column('adminUser.name', admin_trans_field('admin_user_id'));
            $grid->column('note')->limit(30);
            $grid->column('created_at')->display(fn ($value) => self::formatDateTime($value))->sortable();

            $grid->disableCreateButton();
            $grid->disableActions();

            $grid->filter(function (Grid\Filter $filter) use ($isAdministratorUser): void {
                if ($isAdministratorUser) {
                    $filter->equal('merchant_id')->select(
                        Merchant::query()->orderBy('name')->pluck('name', 'id')
                    );
                }

                $filter->equal('type')->select(__('merchant-credit-log.options.type'));
            });

            $this->restrictGridToViewOnly($grid);
        });
    }

    protected function detail($id): Show
    {
        $log = MerchantCreditLog::query()->findOrFail($id);
        $this->ensureMerchantAccess($log->merchant_id);

        return Show::make($id, new MerchantCreditLog(), function (Show $show): void {
            $show->field('id');
            $show->field('merchant.name', admin_trans_field('merchant_id'));
            $show->field('merchant_credit_id');
            $show->field('type')->using(__('merchant-credit-log.options.type'));
            $show->field('amount');
            $show->field('balance_before');
            $show->field('balance_after');
            $show->field('adminUser.name', admin_trans_field('admin_user_id'));
            $show->field('note');
            $show->field('created_at')->as(fn ($value) => self::formatDateTime($value));
            $show->field('updated_at')->as(fn ($value) => self::formatDateTime($value));
        });
    }

    protected function form(): Form
    {
        $this->authorizeAdministratorOnly();

        return Form::make(new MerchantCreditLog(), function (Form $form): void {
            $form->display('id');
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
