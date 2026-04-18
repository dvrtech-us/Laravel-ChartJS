<?php

namespace Dvrtech\LaravelChartJs\Tests;

use Dvrtech\LaravelChartJs\ChartJsRenderer;
use Dvrtech\LaravelChartJs\Exceptions\ChartRenderException;
use Dvrtech\LaravelChartJs\RenderedChart;
use Orchestra\Testbench\TestCase;

class ChartJsRendererTest extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return [\Dvrtech\LaravelChartJs\ChartJsServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $fake = realpath(__DIR__ . '/Fakes/fake-renderer.php');

        // Supply the PHP CLI + fake script as an array "command" so we avoid
        // needing a real exe in CI.
        $app['config']->set('chartjs.binary_path', [PHP_BINARY, $fake]);
        $app['config']->set('chartjs.temp_path', sys_get_temp_dir());
        $app['config']->set('chartjs.timeout', 10);
    }

    public function test_renders_a_chart_to_png_bytes_by_default(): void
    {
        /** @var ChartJsRenderer $renderer */
        $renderer = $this->app->make(ChartJsRenderer::class);

        $chart = $renderer->render([
            'type' => 'bar',
            'data' => ['labels' => ['A'], 'datasets' => [['data' => [1]]]],
        ]);

        $this->assertInstanceOf(RenderedChart::class, $chart);
        $this->assertSame('png', $chart->format());
        $this->assertSame('image/png', $chart->mimeType());
        $this->assertStringStartsWith("\x89PNG", $chart->bytes());
        $this->assertStringStartsWith('data:image/png;base64,', $chart->dataUri());
        $this->assertSame(800, $chart->width());
        $this->assertSame(600, $chart->height());
    }

    public function test_honours_format_width_and_height_overrides(): void
    {
        $renderer = $this->app->make(ChartJsRenderer::class);

        $chart = $renderer->render(
            ['type' => 'line', 'data' => []],
            ['format' => 'svg', 'width' => 320, 'height' => 240]
        );

        $this->assertSame('svg', $chart->format());
        $this->assertSame('image/svg+xml', $chart->mimeType());
        $this->assertSame(320, $chart->width());
        $this->assertSame(240, $chart->height());
        $this->assertStringContainsString('<svg', $chart->bytes());
    }

    public function test_throws_on_unsupported_format(): void
    {
        $renderer = $this->app->make(ChartJsRenderer::class);

        $this->expectException(ChartRenderException::class);
        $this->expectExceptionMessageMatches('/Unsupported format/');

        $renderer->render(['type' => 'bar'], ['format' => 'gif']);
    }

    public function test_throws_when_renderer_exits_non_zero(): void
    {
        $renderer = $this->app->make(ChartJsRenderer::class);

        try {
            $renderer->render(['__fail_with' => 'boom']);
            $this->fail('Expected ChartRenderException');
        } catch (ChartRenderException $e) {
            $this->assertStringContainsString('boom', $e->getMessage());
            $this->assertNotSame(0, $e->exitCode());
            $this->assertStringContainsString('boom', (string) $e->stderr());
        }
    }

    public function test_throws_when_binary_path_is_missing(): void
    {
        $this->app['config']->set('chartjs.binary_path', '/definitely/not/a/real/binary-xyz');

        $renderer = $this->app->make(ChartJsRenderer::class);

        $this->expectException(ChartRenderException::class);
        $this->expectExceptionMessageMatches('/not found/');

        $renderer->render(['type' => 'bar']);
    }

    public function test_renders_jpeg_when_requested(): void
    {
        $renderer = $this->app->make(ChartJsRenderer::class);

        $chart = $renderer->render(['type' => 'bar'], ['format' => 'jpeg']);

        $this->assertSame('jpeg', $chart->format());
        $this->assertSame('image/jpeg', $chart->mimeType());
        $this->assertSame("\xFF\xD8", substr($chart->bytes(), 0, 2));
    }

    public function test_save_to_writes_file_to_disk(): void
    {
        $renderer = $this->app->make(ChartJsRenderer::class);

        $chart = $renderer->render(['type' => 'bar']);
        $path = tempnam(sys_get_temp_dir(), 'chartjs-test-') . '.png';

        $chart->saveTo($path);

        $this->assertFileExists($path);
        $this->assertSame($chart->bytes(), file_get_contents($path));

        @unlink($path);
    }
}
