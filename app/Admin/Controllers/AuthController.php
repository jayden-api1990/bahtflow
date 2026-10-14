<?php

namespace App\Admin\Controllers;

use Dcat\Admin\Http\Controllers\AuthController as BaseAuthController;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AuthController extends BaseAuthController
{
    protected $view = 'admin.pages.login';

    /**
     * @var list<string>
     */
    protected array $allowedLocales = ['zh_CN', 'en'];

    public function switchLocale(Request $request): RedirectResponse
    {
        $locale = (string) $request->input('locale', '');
        if (! in_array($locale, $this->allowedLocales, true)) {
            return redirect(admin_url('auth/login'));
        }

        try {
            $config = admin_setting_group('layout_config') ?: [];
            $config['locale'] = $locale;
            admin_setting_group('layout_config', $config);
        } catch (\Throwable) {
            // ignore setting store errors
        }

        app()->setLocale($locale);

        return redirect(admin_url('auth/login'));
    }
}
