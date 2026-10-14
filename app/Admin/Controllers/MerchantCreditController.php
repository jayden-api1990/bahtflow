<?php

namespace App\Admin\Controllers;

use App\Admin\Traits\ScopesByMerchant;
use App\Models\Merchant;
use App\Models\MerchantCredit;
use App\Models\MerchantCreditLog;
use App\Services\MerchantCreditAdjustmentService;
use Dcat\Admin\Admin;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Http\Controllers\AdminController;
use Dcat\Admin\Layout\Content;
use Dcat\Admin\Show;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use RuntimeException;

class MerchantCreditController extends AdminController
{
    use ScopesByMerchant;

    public function adjust($merchant_credit, Content $content)
    {
        $this->authorizeAdministratorOnly();

        $credit = MerchantCredit::query()->with('merchant')->findOrFail($merchant_credit);
        $this->ensureMerchantAccess($credit->merchant_id);

        return $content
            ->header(admin_trans_label('MerchantCredit'))
            ->description(__('merchant-credit.labels.adjust'))
            ->breadcrumb(
                ['text' => admin_trans_label('MerchantCredit'), 'url' => admin_url('merchant-credit')],
                ['text' => __('merchant-credit.labels.adjust'), 'url' => ''],
            )
            ->body($this->adjustForm($credit));
    }

    public function saveAdjust($merchant_credit, Request $request, MerchantCreditAdjustmentService $service)
    {
        $this->authorizeAdministratorOnly();

        $credit = MerchantCredit::query()->findOrFail($merchant_credit);
        $this->ensureMerchantAccess($credit->merchant_id);
        $form = $this->adjustForm($credit);

        $validated = $request->validate([
            'type' => ['required', 'in:increase,decrease'],
            'amount' => ['required', 'numeric', 'min:0.0001'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $service->adjust(
                (int) $merchant_credit,
                $validated['type'],
                (float) $validated['amount'],
                $validated['note'] ?? null,
                Admin::user()?->getKey(),
            );
        } catch (RuntimeException $exception) {
            $message = match ($exception->getMessage()) {
                'INSUFFICIENT_BALANCE' => __('merchant-credit.messages.insufficient_balance'),
                default => __('merchant-credit.messages.adjust_failed'),
            };

            return $form->response()->error($message);
        }

        return $form->response()
            ->success(__('merchant-credit.messages.adjust_success'))
            ->redirect(admin_url('merchant-credit'));
    }

    protected function grid(): Grid
    {
        $isMerchantUser = $this->isMerchantUser();
        $isAdministratorUser = $this->isAdministratorUser();

        return Grid::make(new MerchantCredit(), function (Grid $grid) use ($isMerchantUser, $isAdministratorUser): void {
            $this->applyMerchantScope($grid->model());
            $grid->model()->with('merchant')->orderByDesc('id');

            //$grid->column('id')->sortable();
            $grid->column('merchant.name', admin_trans_field('merchant_id'));
            $grid->column('credit_balance')->sortable();
            $grid->column('created_at')->display(fn ($value) => self::formatDateTime($value))->sortable();
            $grid->column('updated_at')->display(fn ($value) => self::formatDateTime($value))->sortable();

            $grid->disableCreateButton();
            $grid->disableEditButton();
            $grid->disableDeleteButton();

            $grid->actions(function (Grid\Displayers\Actions $actions) use ($isMerchantUser): void {
                $actions->disableEdit();
                $actions->disableDelete();

                if ($isMerchantUser) {
                    return;
                }

                $actions->append(
                    '<a href="'.admin_url('merchant-credit/'.$actions->getKey().'/adjust').'" class="grid-row-action">
                        <i class="feather icon-edit-1"></i> '.__('merchant-credit.labels.adjust').'
                    </a>'
                );
            });

            $grid->filter(function (Grid\Filter $filter) use ($isAdministratorUser): void {
                //$filter->equal('id');

                if ($isAdministratorUser) {
                    $filter->equal('merchant_id')->select(
                        Merchant::query()->orderBy('name')->pluck('name', 'id')
                    );
                }
            });

            $this->restrictGridToViewOnly($grid);
        });
    }

    protected function detail($id): Show
    {
        $credit = MerchantCredit::query()->findOrFail($id);
        $this->ensureMerchantAccess($credit->merchant_id);

        return Show::make($id, new MerchantCredit(), function (Show $show): void {
            $show->field('id');
            $show->field('merchant.name', admin_trans_field('merchant_id'));
            $show->field('credit_balance');
            $show->field('created_at')->as(fn ($value) => self::formatDateTime($value));
            $show->field('updated_at')->as(fn ($value) => self::formatDateTime($value));

            $show->relation('logs', __('merchant-credit.labels.logs'), function ($model) {
                return Grid::make(
                    MerchantCreditLog::query()
                        ->with('adminUser')
                        ->where('merchant_credit_id', $model->getKey())
                        ->orderByDesc('id'),
                    function (Grid $grid): void {
                        $grid->column('id');
                        $grid->column('type')->using(__('merchant-credit.options.type'));
                        $grid->column('amount');
                        $grid->column('balance_before');
                        $grid->column('balance_after');
                        $grid->column('adminUser.name', admin_trans_field('admin_user_id'));
                        $grid->column('note');
                        $grid->column('created_at')->display(fn ($value) => self::formatDateTime($value));
                        $grid->disableActions();
                        $grid->disableCreateButton();
                        $grid->disablePagination();
                    }
                );
            });
        });
    }

    protected function form(): Form
    {
        $this->authorizeAdministratorOnly();

        return Form::make(new MerchantCredit(), function (Form $form): void {
            $form->display('id');
            $form->display('merchant_id');
            $form->display('credit_balance');
            $form->display('created_at');
            $form->display('updated_at');

            $form->disableCreatingCheck();
            $form->disableEditingCheck();
            $form->disableViewCheck();
        });
    }

    protected function adjustForm(MerchantCredit $credit): Form
    {
        return Form::make(new MerchantCreditLog(), function (Form $form) use ($credit): void {
            $form->action('merchant-credit/'.$credit->id.'/adjust');

            $form->display('merchant_name', admin_trans_field('merchant_id'))
                ->value($credit->merchant?->name ?? $credit->merchant_id);
            $form->display('current_balance', __('merchant-credit.fields.current_balance'))
                ->value($credit->credit_balance);

            $form->radio('type', admin_trans_field('type'))
                ->options(__('merchant-credit.options.type'))
                ->required()
                ->default('increase');

            $form->decimal('amount', admin_trans_field('amount'))
                ->required()
                ->rules('numeric|min:0.0001');

            $form->textarea('note', admin_trans_field('note'));

            $form->disableCreatingCheck();
            $form->disableEditingCheck();
            $form->disableViewCheck();
            $form->disableListButton(false);
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
