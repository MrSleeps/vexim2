<?php

namespace App\Providers;

use App\Auth\MultiTableUserProvider;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    public function boot()
    {
        Auth::provider('multi_table', function ($app, array $config) {
            return new MultiTableUserProvider();
        });
    }
}