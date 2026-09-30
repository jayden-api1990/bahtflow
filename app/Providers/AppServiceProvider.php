<?php

namespace App\Providers;

use Illuminate\Http\Request;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Request::macro('resolvedClientIp', function (): string {
            $header = config('app.client_ip_header');

            if ($header) {
                $ip = trim(explode(',', (string) $this->header($header))[0]);

                if ($ip !== '') {
                    return $ip;
                }
            }

            return (string) $this->getClientIp();
        });
    }
}
