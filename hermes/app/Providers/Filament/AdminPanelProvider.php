<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Illuminate\Support\Facades\File;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        $panel = $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->colors([
                'primary' => Color::Indigo,
                'gray' => Color::Slate,
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);

        // Autodiscover Filament components in dynamic Modules
        $modulesPath = app_path('Modules');
        if (File::isDirectory($modulesPath)) {
            $modules = File::directories($modulesPath);
            foreach ($modules as $module) {
                $moduleName = basename($module);
                
                $resourcesPath = $module . '/Filament/Resources';
                if (File::isDirectory($resourcesPath)) {
                    $panel->discoverResources(in: $resourcesPath, for: "App\\Modules\\{$moduleName}\\Filament\\Resources");
                }
                
                $pagesPath = $module . '/Filament/Pages';
                if (File::isDirectory($pagesPath)) {
                    $panel->discoverPages(in: $pagesPath, for: "App\\Modules\\{$moduleName}\\Filament\\Pages");
                }
                
                $widgetsPath = $module . '/Filament/Widgets';
                if (File::isDirectory($widgetsPath)) {
                    $panel->discoverWidgets(in: $widgetsPath, for: "App\\Modules\\{$moduleName}\\Filament\\Widgets");
                }
            }
        }

        return $panel;
    }
}
