<?php

namespace SAHM\ImageOptimizer;

use Illuminate\Support\ServiceProvider;
use SAHM\ImageOptimizer\Services\ImageOptimizationService;
use SAHM\ImageOptimizer\Services\ImageStorageService;
use SAHM\ImageOptimizer\Services\ImageCacheService;
use SAHM\ImageOptimizer\Processors\ProcessorManager;
use SAHM\ImageOptimizer\Console\Commands\OptimizeCommand;
use SAHM\ImageOptimizer\Console\Commands\CleanupCommand;
use SAHM\ImageOptimizer\Console\Commands\InfoCommand;

class ImageOptimizerServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Merge config
        $this->mergeConfigFrom(
            __DIR__.'/../config/image-optimizer.php',
            'image-optimizer'
        );

        // Register Processor Manager
        $this->app->singleton(ProcessorManager::class, function ($app) {
            return new ProcessorManager();
        });

        // Register Storage Service
        $this->app->singleton(ImageStorageService::class, function ($app) {
            return new ImageStorageService(
                config('image-optimizer.storage', [])
            );
        });

        // Register Cache Service
        $this->app->singleton(ImageCacheService::class, function ($app) {
            return new ImageCacheService(
                $app->make('cache.store'),
                config('image-optimizer.cache', [])
            );
        });

        // Register Main Optimization Service
        $this->app->singleton(ImageOptimizationService::class, function ($app) {
            return new ImageOptimizationService(
                $app->make(ImageStorageService::class),
                $app->make(ProcessorManager::class),
                $app->make(ImageCacheService::class),
                config('image-optimizer', [])
            );
        });

        // Alias for facade
        $this->app->alias(ImageOptimizationService::class, 'image-optimizer');
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Publish config
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/image-optimizer.php' => config_path('image-optimizer.php'),
            ], 'image-optimizer-config');

            // Register commands
            $this->commands([
                InfoCommand::class,
                OptimizeCommand::class,
                CleanupCommand::class,
            ]);
        }
    }

    /**
     * Get the services provided by the provider.
     */
    public function provides(): array
    {
        return [
            'image-optimizer',
            ImageOptimizationService::class,
            ImageStorageService::class,
            ImageCacheService::class,
            ProcessorManager::class,
        ];
    }
}
