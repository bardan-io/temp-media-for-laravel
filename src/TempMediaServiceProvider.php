<?php

declare(strict_types=1);

namespace BardanIO\TempMedia;

use BardanIO\TempMedia\Commands\CleanupTempMediaCommand;
use BardanIO\TempMedia\Contracts\MediaTransferServiceInterface;
use BardanIO\TempMedia\Contracts\TempMediaServiceInterface;
use BardanIO\TempMedia\Services\MediaTransferService;
use BardanIO\TempMedia\Services\TempMediaService;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\ServiceProvider;

final class TempMediaServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/temp-media.php', 'temp-media');

        $this->app->singleton(TempMediaServiceInterface::class, TempMediaService::class);

        $this->app->singleton(MediaTransferServiceInterface::class, function ($app) {
            return new MediaTransferService(
                $app->make(TempMediaServiceInterface::class)
            );
        });

        $this->app->alias(TempMediaServiceInterface::class, 'temp-media');
        $this->app->alias(MediaTransferServiceInterface::class, 'media-transfer');
    }

    public function boot(): void
    {
        if (config('temp-media.auto_discovery', true)) {
            $this->loadRoutesFrom(__DIR__.'/../routes/api.php');
        }

        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/temp-media.php' => config_path('temp-media.php'),
            ], 'temp-media-config');

            $this->publishes([
                __DIR__.'/../database/migrations' => database_path('migrations'),
            ], 'temp-media-migrations');

            $this->commands([
                CleanupTempMediaCommand::class,
            ]);
        }

        if (config('temp-media.enable_auto_cleanup', true)) {
            $this->bootAutoCleanup();
        }

    }

    private function bootAutoCleanup(): void
    {
        $this->app->booted(function () {
            $schedule = $this->app->make(Schedule::class);
            $schedule->command('temp-media:cleanup')
                ->hourly()
                ->withoutOverlapping()
                ->runInBackground();
        });
    }

    public function provides(): array
    {
        return [
            TempMediaServiceInterface::class,
            MediaTransferServiceInterface::class,
            'temp-media',
            'media-transfer',
        ];
    }
}
