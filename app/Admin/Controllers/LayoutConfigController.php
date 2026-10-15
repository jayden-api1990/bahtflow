<?php

namespace App\Admin\Controllers;

use Dcat\Admin\Http\Controllers\LayoutConfigController as BaseLayoutConfigController;
use Illuminate\Http\Request;

class LayoutConfigController extends BaseLayoutConfigController
{
    public function save(Request $request)
    {
        $data = $request->except(['_token']);

        $existing = [];
        try {
            $existing = admin_setting_group('layout_config') ?: [];
        } catch (\Throwable) {
        }
        $data = array_merge($existing, $data);

        $menuStyle = $data['menu_style'] ?? 'default_menu';
        $allowedStyles = ['default_menu', 'horizontal_menu', 'two_col_menu'];
        $data['menu_style'] = in_array($menuStyle, $allowedStyles, true) ? $menuStyle : 'default_menu';
        $data['horizontal_menu'] = ($data['menu_style'] === 'horizontal_menu');

        $booleans = ['sidebar_collapsed', 'dark_mode_switch', 'full_screen', 'show_locale_switch', 'show_help', 'show_notification'];
        foreach ($booleans as $key) {
            $data[$key] = ! empty($data[$key]);
        }

        if (isset($data['body_class']) && is_array($data['body_class'])) {
            $data['body_class'] = array_values(array_filter($data['body_class']));
        }
        if (! isset($data['body_class'])) {
            $data['body_class'] = [];
        }

        $strings = ['color', 'sidebar_style', 'navbar_color', 'home_url'];
        foreach ($strings as $key) {
            if (isset($data[$key])) {
                $data[$key] = trim($data[$key]);
            }
        }

        $allowedLocales = ['zh_CN', 'zh_TW', 'en', 'th'];
        if (isset($data['locale'])) {
            $data['locale'] = in_array($data['locale'], $allowedLocales, true)
                ? $data['locale']
                : config('app.locale', 'zh_CN');
        }

        if (isset($data['active_mode'])) {
            $data['active_mode'] = in_array($data['active_mode'], ['default', 'iframe-tabs'], true)
                ? $data['active_mode']
                : 'default';
        }

        admin_setting_group('layout_config', $data);

        return response()->json([
            'status' => true,
            'message' => '布局配置已保存，页面即将刷新',
        ]);
    }
}
