<?php

namespace Modules\CHNetTRAK\Providers;

use App\Contracts\Modules\ServiceProvider;

/**
 * @package $NAMESPACE$
 */
class CHNetTRAKProvider extends ServiceProvider
{
    private $moduleSvc;

    protected $defer = false;

    /**
     * Boot the application events.
     */
    public function boot(): void
    {
        $this->moduleSvc = app('App\Services\ModuleService');

        $this->registerTranslations();
        $this->registerConfig();
        $this->registerViews();

        $this->registerLinks();

        // Uncomment this if you have migrations
        // $this->loadMigrationsFrom(__DIR__ . '/../$MIGRATIONS_PATH$');
    }

    /**
     * Register the service provider.
     */
    public function register()
    {
        //
    }

    /**
     * Add module links here
     */
    public function registerLinks(): void
    {
        // Show this link if logged in
        // $this->moduleSvc->addFrontendLink('CHNetTRAK', '/chnettrak', '', $logged_in=true);

        // Admin links:
        $this->moduleSvc->addAdminLink('CHNetTRAK', '/admin/chnettrak');
    }

    /**
     * Register config.
     */
    protected function registerConfig()
    {
        $this->publishes([
            __DIR__.'/../Config/config.php' => config_path('chnettrak.php'),
        ], 'chnettrak');

        $this->mergeConfigFrom(__DIR__.'/../Config/config.php', 'chnettrak');
    }

    /**
     * Register views.
     */
    public function registerViews()
    {
        $viewPath = resource_path('views/modules/chnettrak');
        $sourcePath = __DIR__.'/../Resources/views';

        $this->publishes([$sourcePath => $viewPath],'views');

        $this->loadViewsFrom(array_merge(array_filter(array_map(function ($path) {
            $path = str_replace('default', setting('general.theme'), $path);
            // Check if the directory exists before adding it
            if (file_exists($path.'/modules/chnettrak') && is_dir($path.'/modules/chnettrak'))
              return $path.'/modules/chnettrak';

            return null;
        }, \Config::get('view.paths'))), [$sourcePath]), 'chnettrak');
    }

    /**
     * Register translations.
     */
    public function registerTranslations()
    {
        $langPath = resource_path('lang/modules/chnettrak');

        if (is_dir($langPath)) {
            $this->loadTranslationsFrom($langPath, 'chnettrak');
        } else {
            $this->loadTranslationsFrom(__DIR__ .'/../Resources/lang', 'chnettrak');
        }
    }
}
