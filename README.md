# Laravel ChartJS

Render [Chart.js](https://www.chartjs.org/) charts to images in PHP / Laravel
and embed them into PDFs. The package ships a Windows executable that performs
the actual rendering out-of-process and returns a PNG / JPEG / SVG the PHP side
can hand directly to any PDF library (dompdf, mPDF, TCPDF, Snappy, ...).

## Why

Chart.js is a client-side library: it needs a `<canvas>` / DOM to render. Most
PHP PDF libraries can embed images but cannot execute JavaScript. This package
bridges the gap with a small bundled renderer so you can keep writing Chart.js
configs in PHP and get back an image suitable for any PDF pipeline.

## Install

```bash
composer require dvrtech/laravel-chartjs
```

Laravel auto-discovery registers the service provider and `ChartJs` facade.

Publish the config (optional):

```bash
php artisan vendor:publish --tag=chartjs-config
```

## Platform support

The package bundles a Windows x64 binary at `bin/chartjs-renderer.exe`. On
other platforms, build your own binary from the sources under `renderer/` (see
`renderer/README.md`) and point the package at it:

```env
CHARTJS_BINARY_PATH=/absolute/path/to/chartjs-renderer
```

## Usage

```php
use Dvrtech\LaravelChartJs\Facades\ChartJs;

$chart = ChartJs::render([
    'type' => 'bar',
    'data' => [
        'labels'   => ['Jan', 'Feb', 'Mar'],
        'datasets' => [[
            'label' => 'Revenue',
            'data'  => [1200, 1900, 3000],
        ]],
    ],
    'options' => [
        'plugins' => [
            'title' => ['display' => true, 'text' => 'Q1 Revenue'],
        ],
    ],
], [
    'format' => 'png',   // png | jpeg | svg
    'width'  => 800,
    'height' => 400,
]);

$chart->bytes();     // raw image bytes
$chart->base64();    // base64-encoded bytes
$chart->dataUri();   // data:image/png;base64,...
$chart->saveTo(storage_path('app/chart.png'));
```

## Embedding in a PDF

### barryvdh/laravel-dompdf

```php
$chart = ChartJs::render($config, ['width' => 800, 'height' => 400]);

$pdf = Pdf::loadView('reports.sales', [
    'chart' => $chart->dataUri(),
]);

return $pdf->download('sales.pdf');
```

```blade
{{-- resources/views/reports/sales.blade.php --}}
<img src="{{ $chart }}" style="width:100%;">
```

### mPDF

```php
$chart = ChartJs::render($config);
$mpdf  = new \Mpdf\Mpdf();

$mpdf->WriteHTML('<h1>Sales</h1>');
$mpdf->WriteHTML('<img src="' . $chart->dataUri() . '" style="width:100%;">');

return $mpdf->Output('sales.pdf', 'D');
```

### TCPDF

```php
$chart = ChartJs::render($config, ['format' => 'png']);
$path  = $chart->saveTo(storage_path('app/tmp/chart.png'));

$pdf = new \TCPDF();
$pdf->AddPage();
$pdf->Image($path, 15, 40, 180);
$pdf->Output('sales.pdf', 'D');
```

## Configuration reference

| Key                  | Env                   | Default                          |
|----------------------|-----------------------|----------------------------------|
| `binary_path`        | `CHARTJS_BINARY_PATH` | `bin/chartjs-renderer.exe`       |
| `temp_path`          | `CHARTJS_TEMP_PATH`   | system temp dir                  |
| `default_format`     | `CHARTJS_FORMAT`      | `png`                            |
| `default_width`      | `CHARTJS_WIDTH`       | `800`                            |
| `default_height`     | `CHARTJS_HEIGHT`      | `600`                            |
| `device_pixel_ratio` | `CHARTJS_DPR`         | `2`                              |
| `background`         | `CHARTJS_BACKGROUND`  | `null` (transparent for PNG/SVG) |
| `timeout`            | `CHARTJS_TIMEOUT`     | `30` seconds                     |

## Errors

All renderer failures surface as
`Dvrtech\LaravelChartJs\Exceptions\ChartRenderException`, which carries the
process exit code and any stderr output:

```php
try {
    $chart = ChartJs::render($config);
} catch (\Dvrtech\LaravelChartJs\Exceptions\ChartRenderException $e) {
    report($e);                  // message + stderr
    $e->exitCode();
    $e->stderr();
}
```

## Building the renderer

See `renderer/README.md` for the Node.js CLI contract and `npm run build`
instructions that produce `bin/chartjs-renderer.exe`.

## License

MIT
