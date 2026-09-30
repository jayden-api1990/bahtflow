<?php

namespace App\Admin\Controllers;

use App\Admin\Traits\ScopesByMerchant;
use App\Models\AdminUser;
use App\Models\Merchant;
use App\Models\MerchantCredit;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Http\Controllers\AdminController;
use Dcat\Admin\Layout\Content;
use Dcat\Admin\Show;
use Dcat\Admin\Support\Helper;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class MerchantController extends AdminController
{
    use ScopesByMerchant;

    public function index(Content $content)
    {
        return $content
            ->header(admin_trans_label())
            ->description('全部')
            ->breadcrumb(['text' => admin_trans_label().'列表', 'url' => ''])
            ->body($this->grid());
    }

    protected function grid()
    {
        return Grid::make(new Merchant(), function (Grid $grid) {
            $this->applyMerchantScopeById($grid->model());
            $grid->model()->withCount('adminUsers');
            $grid->column('id')->sortable();
            $grid->column('admin_users_count')->sortable();
            $grid->column('name');
            $grid->column('merchant_serial');
            $grid->column('md5key');
            $grid->column('status')->display(function ($v) {
                return admin_trans_option($v, 'status');
            })->label([
                0 => 'danger',
                1 => 'success',
            ]);
            $grid->column('callback_url')->hide();
            $grid->column('currency')->hide();
            $grid->column('created_at')->display(fn ($value) => self::formatDateTime($value));
            $grid->column('updated_at')->display(fn ($value) => self::formatDateTime($value))->sortable();
            $grid->setActionClass(Grid\Displayers\Actions::class);
            $grid->filter(function (Grid\Filter $filter) {
                $filter->equal('id');
            });

            $this->restrictGridToViewOnly($grid);
        });
    }

    protected function detail($id)
    {
        $this->ensureMerchantAccess((int) $id);

        return Show::make($id, new Merchant(), function (Show $show) {
            $show->field('id');
            $show->relation('adminUsers', '管理员', function ($model) {
                return Grid::make(AdminUser::query()->where('merchant_id', $model->getKey()), function (Grid $grid) {
                    $grid->column('id')->sortable();
                    $grid->column('username', '登录名');
                    $grid->column('name', '姓名');
                });
            });
            $show->field('name');
            $show->field('merchant_serial');
            $show->field('md5key');
            $show->field('status');
            $show->field('callback_url');
            $show->field('currency');
            $show->field('created_at')->as(fn ($value) => self::formatDateTime($value));
            $show->field('updated_at')->as(fn ($value) => self::formatDateTime($value));
        });
    }

    protected function form()
    {
        $this->authorizeAdministratorOnly();

        return Form::make(new Merchant(), function (Form $form) {
            $merchantUuid = Str::uuid();
            $merchantKey = md5($merchantUuid.Str::random());

            $pendingAdminUserIds = [];

            $defaultAdminIds = $form->isEditing()
                ? $form->model()->adminUsers()->pluck('id')->toArray()
                : [];

            $form->submitted(function () use (&$pendingAdminUserIds) {
                $pendingAdminUserIds = Helper::array(request()->input('admin_user_ids'));
            });

            $form->text('name');
            $form->text('merchant_serial')->default(Str::uuid())->readonly();
            $form->text('md5key')->default($merchantKey)->readonly();
            $form->switch('status')->default(1);
            $form->multipleSelect('admin_user_ids', '管理员')
                ->options(AdminUser::query()->orderBy('id')->pluck('name', 'id'))
                ->default($defaultAdminIds, true);
            $form->text('callback_url');
            $form->text('currency');

            $form->ignore('admin_user_ids');

            $form->saved(function (Form $form) use (&$pendingAdminUserIds) {
                $merchantId = (int) $form->getKey();
                if ($merchantId === 0) {
                    return;
                }

                MerchantCredit::query()->firstOrCreate(
                    ['merchant_id' => $merchantId],
                    ['credit_balance' => 0]
                );

                $ids = array_values(array_unique(array_filter(array_map(
                    static fn ($id) => (int) $id,
                    $pendingAdminUserIds
                ))));

                AdminUser::query()->where('merchant_id', $merchantId)->update(['merchant_id' => null]);
                if ($ids !== []) {
                    AdminUser::query()->whereIn('id', $ids)->update(['merchant_id' => $merchantId]);
                }
            });

            $form->display('created_at');
            $form->display('updated_at');
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
