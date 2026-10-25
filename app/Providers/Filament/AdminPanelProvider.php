<?php

namespace App\Providers\Filament;

use App\Domain\Inventory\Filament\Widgets\LowStockWidget;
use App\Filament\Pages\Dashboard;
use App\Filament\Widgets\CashFlowChartWidget;
use App\Filament\Widgets\OverdueInvoiceWidget;
use App\Filament\Widgets\PurchasingSummaryWidget;
use App\Filament\Widgets\SalesChartWidget;
use App\Filament\Widgets\SalesSummaryWidget;
use App\Filament\Widgets\StockValuationWidget;
use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
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
            ->login()
            ->brandName('ERP Samarinda')
            ->strictAuthorization()
            ->globalSearchKeyBindings(['command+k', 'ctrl+k'])
            ->databaseNotifications()
            ->colors([
                'primary' => Color::Amber,
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverResources(in: app_path('Domain/Inventory/Filament/Resources'), for: 'App\Domain\Inventory\Filament\Resources')
            ->discoverResources(in: app_path('Domain/Purchasing/Filament/Resources'), for: 'App\Domain\Purchasing\Filament\Resources')
            ->discoverResources(in: app_path('Domain/Sales/Filament/Resources'), for: 'App\Domain\Sales\Filament\Resources')
            ->discoverResources(in: app_path('Domain/Accounting/Filament/Resources'), for: 'App\Domain\Accounting\Filament\Resources')
            ->discoverResources(in: app_path('Domain/Tax/Filament/Resources'), for: 'App\Domain\Tax\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->discoverWidgets(in: app_path('Domain/Inventory/Filament/Widgets'), for: 'App\Domain\Inventory\Filament\Widgets')
            ->widgets([
                SalesSummaryWidget::class,
                SalesChartWidget::class,
                PurchasingSummaryWidget::class,
                OverdueInvoiceWidget::class,
                CashFlowChartWidget::class,
                StockValuationWidget::class,
                LowStockWidget::class,
            ])
            ->navigationGroups([
                'Inventaris',
                'Pembelian',
                'Penjualan',
                'Akuntansi',
                'Pajak',
                'Laporan',
                'Pengaturan',
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
            ->plugins([
                FilamentShieldPlugin::make()
                    ->navigationGroup('Pengaturan')
                    ->navigationLabel('Peran'),
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
