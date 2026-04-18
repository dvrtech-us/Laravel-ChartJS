<?php

namespace Dvrtech\LaravelChartJs\Facades;

use Dvrtech\LaravelChartJs\ChartJsRenderer;
use Dvrtech\LaravelChartJs\RenderedChart;
use Illuminate\Support\Facades\Facade;

/**
 * @method static RenderedChart render(array $config, array $options = [])
 *
 * @see ChartJsRenderer
 */
class ChartJs extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return ChartJsRenderer::class;
    }
}
