<?php

namespace App\Admin\Traits;

use App\Models\AdminUser;
use Dcat\Admin\Admin;
use Dcat\Admin\Grid;
use Dcat\Admin\Grid\Model as GridModel;

trait ScopesByMerchant
{
    protected function adminUser(): ?AdminUser
    {
        $user = Admin::user();

        if ($user instanceof AdminUser) {
            return $user;
        }

        // Session may still hold Dcat Administrator after switching users_model;
        // reload as AdminUser so role checks keep working.
        if ($user && method_exists($user, 'getKey') && $user->getKey()) {
            return AdminUser::query()->find($user->getKey());
        }

        return null;
    }

    protected function isAdministratorUser(): bool
    {
        return (bool) $this->adminUser()?->isAdministrator();
    }

    protected function isMerchantUser(): bool
    {
        return (bool) $this->adminUser()?->isMerchant();
    }

    protected function scopedMerchantId(): ?int
    {
        if ($this->isAdministratorUser()) {
            return null;
        }

        $merchantId = $this->adminUser()?->merchant_id;

        return $merchantId ? (int) $merchantId : -1;
    }

    protected function applyMerchantScope(GridModel $model, string $column = 'merchant_id'): GridModel
    {
        $merchantId = $this->scopedMerchantId();

        if ($merchantId === null) {
            return $model;
        }

        return $model->where($column, $merchantId);
    }

    protected function applyMerchantScopeById(GridModel $model, string $column = 'id'): GridModel
    {
        $merchantId = $this->scopedMerchantId();

        if ($merchantId === null) {
            return $model;
        }

        return $model->where($column, $merchantId);
    }

    protected function ensureMerchantAccess(?int $resourceMerchantId): void
    {
        $merchantId = $this->scopedMerchantId();

        if ($merchantId === null) {
            return;
        }

        if ($resourceMerchantId === null || (int) $resourceMerchantId !== $merchantId) {
            abort(403, '无权访问该商户数据。');
        }
    }

    protected function authorizeAdministratorOnly(): void
    {
        if (! $this->isAdministratorUser()) {
            abort(403, '仅管理员可执行此操作。');
        }
    }

    protected function restrictGridToViewOnly(Grid $grid): void
    {
        if (! $this->isMerchantUser()) {
            return;
        }

        $grid->disableCreateButton();
        $grid->disableEditButton();
        $grid->disableDeleteButton();

        $grid->actions(function (Grid\Displayers\Actions $actions): void {
            $actions->disableEdit();
            $actions->disableDelete();
            $actions->disableQuickEdit();
        });
    }
}
