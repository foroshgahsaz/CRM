<?php

namespace App\Providers\Filament;

use App\Filament\Auth\Login;
use App\Filament\Widgets\OperationsOverview;
use App\Filament\Widgets\RequestStatusChart;
use App\Filament\Widgets\RequestsSubmittedChart;
use App\Filament\Widgets\RequestsSubmittedChart;
use App\Filament\Widgets\RequestStatusChart;
use App\Http\Middleware\ConfigureSessionForRequest;
use App\Http\Middleware\EnsureStaffIsActive;
use Filament\FontProviders\LocalFontProvider;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\Width;
use Filament\View\PanelsRenderHook;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login(Login::class)
            ->passwordReset()
            ->profile()
            ->brandName('سامانه درخواست‌ها')
            ->maxContentWidth(Width::Full)
            ->font('YekanBakh', url: asset('fonts/yekan/fonts.css'), provider: LocalFontProvider::class)
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->renderHook(PanelsRenderHook::BODY_END, fn (): string => view('filament.partials.jalali-assets')->render())
            ->databaseNotifications()
            ->colors([
                'primary' => Color::Violet,
                'info' => Color::Sky,
            ])
            ->navigationGroups([
                'عملیات',
                'انبار',
                'سازمان',
                'تنظیمات',
                'نظارت',
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                OperationsOverview::class,
                RequestStatusChart::class,
                RequestsSubmittedChart::class,
            ])
            ->middleware([
                ConfigureSessionForRequest::class,
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
                EnsureStaffIsActive::class,
            ]);
    }
}
