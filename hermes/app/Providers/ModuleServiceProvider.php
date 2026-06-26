<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

class ModuleServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Discover and merge config files in modules
        $modulesPath = app_path('Modules');

        if (!File::isDirectory($modulesPath)) {
            return;
        }

        $modules = File::directories($modulesPath);

        foreach ($modules as $module) {
            $moduleName = basename($module);
            $configPath = $module . '/Config/config.php';

            if (File::exists($configPath)) {
                $this->mergeConfigFrom($configPath, Str::snake($moduleName));
            }
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $modulesPath = app_path('Modules');

        if (!File::isDirectory($modulesPath)) {
            return;
        }

        $modules = File::directories($modulesPath);

        foreach ($modules as $module) {
            $moduleName = basename($module);
            $lowerName = Str::snake($moduleName);

            // 1. Load Migrations
            $migrationPath = $module . '/Database/Migrations';
            if (File::isDirectory($migrationPath)) {
                $this->loadMigrationsFrom($migrationPath);
            }

            // 2. Load Views
            $viewPath = $module . '/Resources/views';
            if (File::isDirectory($viewPath)) {
                $this->loadViewsFrom($viewPath, $moduleName);
                $this->loadViewsFrom($viewPath, $lowerName);
            }

            // 3. Load Translations
            $translationPath = $module . '/Resources/lang';
            if (File::isDirectory($translationPath)) {
                $this->loadTranslationsFrom($translationPath, $moduleName);
                $this->loadTranslationsFrom($translationPath, $lowerName);
            }

            // 4. Load Routes
            $webRoute = $module . '/Routes/web.php';
            $apiRoute = $module . '/Routes/api.php';

            if (File::exists($webRoute)) {
                Route::middleware('web')
                    ->group($webRoute);
            }

            if (File::exists($apiRoute)) {
                Route::middleware('api')
                    ->prefix('api')
                    ->group($apiRoute);
            }

            // 5. Load Console Commands
            $commandPath = $module . '/Console/Commands';
            if (File::isDirectory($commandPath)) {
                $this->registerCommands($commandPath, "App\\Modules\\{$moduleName}\\Console\\Commands");
            }
        }
    }

    /**
     * Register artisan commands dynamically.
     */
    protected function registerCommands(string $dir, string $namespace): void
    {
        if (!File::isDirectory($dir)) {
            return;
        }

        $files = File::allFiles($dir);
        $commands = [];

        foreach ($files as $file) {
            $class = $namespace . '\\' . Str::replace('.php', '', $file->getFilename());
            if (class_exists($class)) {
                $commands[] = $class;
            }
        }

        if (!empty($commands)) {
            $this->commands($commands);
        }
    }
}
