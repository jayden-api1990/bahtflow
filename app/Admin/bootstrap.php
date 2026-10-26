<?php

use Dcat\Admin\Admin;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Grid\Filter;
use Dcat\Admin\Show;

/**
 * Dcatplsu-admin - admin builder based on Laravel.
 *
 * @author 杨光 <https://github.com/ycookies/dcat-plus-admin>
 *
 * Bootstraper for Admin.
 *
 * Here you can remove builtin form field:
 *
 * extend custom field:
 * Dcat\Admin\Form::extend('php', PHPEditor::class);
 * Dcat\Admin\Grid\Column::extend('php', PHPEditor::class);
 * Dcat\Admin\Grid\Filter::extend('php', PHPEditor::class);
 *
 * Or require js and css assets:
 * Admin::css('/packages/prettydocs/css/styles.css');
 * Admin::js('/packages/prettydocs/js/main.js');
 */
$allowedLocales = ['zh_CN', 'zh_TW', 'en', 'th'];
try {
    $layoutLocale = admin_setting_group('layout_config')['locale'] ?? null;
    if (is_string($layoutLocale) && in_array($layoutLocale, $allowedLocales, true)) {
        app()->setLocale($layoutLocale);
    }
} catch (Throwable) {
    // ignore setting store errors during bootstrap
}

Admin::style('.main-footer { display: none !important; }');
Admin::style('.dark-mode-switcher { display: none !important; }');
Admin::style('.lc-clear-cache-btn { display: none !important; }');
Admin::style(<<<'CSS'
.flag-icon { background-size: contain; background-position: 50%; background-repeat: no-repeat; position: relative; display: inline-block; width: 1.333333em; line-height: 1em; }
.flag-icon:before { content: "\00a0"; }
.flag-icon-th { background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 900 600'%3E%3Crect fill='%23A51931' width='900' height='600'/%3E%3Crect fill='%23F4F5F8' y='100' width='900' height='400'/%3E%3Crect fill='%232D2A4A' y='200' width='900' height='200'/%3E%3C/svg%3E"); }
.flag-icon-cn { background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 30 20'%3E%3Crect fill='%23de2910' width='30' height='20'/%3E%3C/svg%3E"); }
.flag-icon-us { background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 30 20'%3E%3Crect fill='%23b22234' width='30' height='20'/%3E%3C/svg%3E"); }
.flag-icon-tw { background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 30 20'%3E%3Crect fill='%23fe0000' width='30' height='20'/%3E%3Crect fill='%23000fe0' width='15' height='10'/%3E%3C/svg%3E"); }
CSS);

Admin::style(<<<'CSS'
table.data-table tbody td,
.table tbody td {
    font-size: 0.9rem !important;
}
CSS);

// 可以把注释去掉,自定义全局的 Grid、Show 和 Form 逻辑
// // 统一全局定义数据表格
Grid::resolving(function (Grid $grid) {
    $grid->scrollbarX();
    $grid->disableRowSelector();
    $grid->tableCollapse(true); // 表格布局样式
    $grid->filter(function (Filter $filter) {
        $filter->panel();  // 更改为 panel 布局
        $filter->expand(false); // 展开过滤器
    });
});
// // 统一全局定义表单
Form::resolving(function (Form $form) {
    $form->disableEditingCheck();
    $form->disableCreatingCheck();
    $form->disableViewCheck();
    $form->tools(function (Form\Tools $tools) {
        $tools->disableDelete();
        $tools->disableView();
        $tools->disableList();
    });
});
// // 统一全局定义详情页
Show::resolving(function (Show $show) {
    $show->disableListButton();
});
