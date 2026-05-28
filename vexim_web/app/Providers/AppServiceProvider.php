<?php

namespace App\Providers;

use App\Models\Domain;
use App\Models\EximUser;
use App\Models\DomainAlias;
use App\Models\Blocklist;
use App\Models\Setting;
use App\Models\User;
use App\Observers\GlobalActivityObserver;
use Illuminate\Support\ServiceProvider;
use App\Auth\MultiTableUserProvider;
use Illuminate\Support\Facades\Auth;

use Filament\Forms\Components\RichEditor;

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
        Auth::provider('multi_table', function ($app, array $config) {
            return new MultiTableUserProvider();
        });
        
    RichEditor::configureUsing(function (RichEditor $editor): void {
        $editor->extraAttributes([
            'style' => 'min-height: 650px;',
        ]);
    });        
    }
}