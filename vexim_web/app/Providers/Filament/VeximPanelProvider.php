<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets\AccountWidget;
use Filament\Widgets\FilamentInfoWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Filament\Auth\MultiFactor\App\AppAuthentication;
use MarcelWeidum\Passkeys\PasskeysPlugin;
use Relaticle\ActivityLog\Filament\ActivityLogPlugin;
use Elemind\FilamentECharts\FilamentEChartsPlugin;
use App\Filament\Widgets\AccountTypesChart;
use App\Filament\Widgets\DomainStats;
use App\Filament\Resources\DomainUsers\DomainUserResource; 
use Filament\Navigation\NavigationItem;
use Filament\Support\Icons\Heroicon;
use App\Models\EximUser;
use FinityLabs\FinMail\FinMailPlugin;

class VeximPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('vexim')
            ->path('')
            ->login()
            ->authGuard('web')
            ->passwordReset()
            ->profile(isSimple: false)
            ->colors([
                'primary' => Color::Amber,
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                \App\Filament\Pages\Dashboard::class,
                // Remove DomainUserResource from here - it's a resource, not a page
            ])
            ->resources([
                DomainUserResource::class,  // Register resources here instead
            ])
            ->navigationItems([
                NavigationItem::make('my-account')
                    ->label('My Account')
                    ->icon(Heroicon::UserCircle)
                    ->url(fn (): string => DomainUserResource::getUrl('index'))  // Use index instead
                    ->visible(fn (): bool => auth()->user() instanceof EximUser)
                    ->sort(1),
            ])            
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                DomainStats::class,
                AccountTypesChart::class,
            ])
            ->plugins([
                PasskeysPlugin::make(),
                ActivityLogPlugin::make(),
                FilamentEChartsPlugin::make(),
                FinMailPlugin::make()->enableSentEmails(false)->navigationGroup('Communications'),
            ])            
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            ->multiFactorAuthentication([
                AppAuthentication::make(),
            ]);
    }
}