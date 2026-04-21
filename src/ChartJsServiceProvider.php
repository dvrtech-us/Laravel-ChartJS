<?php

namespace Dvrtech\LaravelChartJs;

use Illuminate\Support\ServiceProvider;

class ChartJsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom($this->configPath(), 'chartjs');

        $this->app->singleton(ChartJsRenderer::class, fn () => new ChartJsRenderer());
        $this->app->alias(ChartJsRenderer::class, 'chartjs');
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                $this->configPath() => $this->app->configPath('chartjs.php'),
            ], 'chartjs-config');
        }
    }

    private function configPath(): string
    {
        return __DIR__ . '/../config/chartjs.php';
    }
}
