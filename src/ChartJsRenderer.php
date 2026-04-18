<?php

namespace Dvrtech\LaravelChartJs;

use Dvrtech\LaravelChartJs\Exceptions\ChartRenderException;
use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\Process\Process;
use Throwable;

class ChartJsRenderer
{
    private const ALLOWED_FORMATS = ['png', 'jpeg', 'svg'];

    /**
     * @param  array<string, mixed>  $config  The Chart.js config
     *                                        (e.g. ['type' => 'bar', 'data' => [...], 'options' => [...]]).
     * @param  array<string, mixed>  $options Render overrides:
     *                                        format, width, height, devicePixelRatio, background, timeout.
     */
    public function render(array $config, array $options = []): RenderedChart
    {
        $format = strtolower((string) ($options['format'] ?? $this->config('default_format', 'png')));

        if (! in_array($format, self::ALLOWED_FORMATS, true)) {
            throw new ChartRenderException(
                "Unsupported format '{$format}'. Allowed: " . implode(', ', self::ALLOWED_FORMATS) . '.'
            );
        }

        $width  = (int) ($options['width']  ?? $this->config('default_width', 800));
        $height = (int) ($options['height'] ?? $this->config('default_height', 600));
        $dpr    = (float) ($options['devicePixelRatio'] ?? $this->config('device_pixel_ratio', 2));
        $bg     = $options['background'] ?? $this->config('background');
        $timeout = (int) ($options['timeout'] ?? $this->config('timeout', 30));

        $binary = $this->resolveBinary();
        $tempDir = $this->resolveTempDir();

        $inputPath  = $this->makeTempFile($tempDir, 'chartjs-cfg-', '.json');
        $outputPath = $this->makeTempFile($tempDir, 'chartjs-img-', '.' . $format);

        try {
            $json = json_encode($config, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            if ($json === false) {
                throw new ChartRenderException('Failed to encode Chart.js config as JSON: ' . json_last_error_msg());
            }

            if (@file_put_contents($inputPath, $json) === false) {
                throw new ChartRenderException("Unable to write Chart.js config to: {$inputPath}");
            }

            $command = array_merge(
                is_array($binary) ? $binary : [$binary],
                [
                    '--input',  $inputPath,
                    '--output', $outputPath,
                    '--format', $format,
                    '--width',  (string) $width,
                    '--height', (string) $height,
                    '--dpr',    (string) $dpr,
                ]
            );

            if (is_string($bg) && $bg !== '') {
                $command[] = '--background';
                $command[] = $bg;
            }

            $process = new Process($command);
            $process->setTimeout($timeout > 0 ? $timeout : null);

            try {
                $process->run();
            } catch (ProcessTimedOutException $e) {
                throw new ChartRenderException(
                    "Chart renderer timed out after {$timeout}s.",
                    null,
                    $process->getErrorOutput() ?: null,
                    $e,
                );
            }

            if (! $process->isSuccessful()) {
                throw new ChartRenderException(
                    'Chart renderer failed: ' . $this->truncate($process->getErrorOutput() ?: $process->getOutput()),
                    $process->getExitCode(),
                    $process->getErrorOutput() ?: null,
                );
            }

            if (! is_file($outputPath) || filesize($outputPath) === 0) {
                throw new ChartRenderException(
                    'Chart renderer did not produce an output file.',
                    $process->getExitCode(),
                    $process->getErrorOutput() ?: null,
                );
            }

            $bytes = @file_get_contents($outputPath);
            if ($bytes === false) {
                throw new ChartRenderException("Unable to read rendered chart from: {$outputPath}");
            }

            return new RenderedChart($bytes, $format, $width, $height);
        } finally {
            $this->safeUnlink($inputPath);
            $this->safeUnlink($outputPath);
        }
    }

    /**
     * @return string|array<int, string> The resolved binary, either a single path
     *                                   or an array command (useful for tests).
     */
    private function resolveBinary(): string|array
    {
        $binary = $this->config('binary_path');

        if (empty($binary)) {
            throw new ChartRenderException(
                'No Chart.js renderer binary is configured. Set CHARTJS_BINARY_PATH or config("chartjs.binary_path").'
            );
        }

        if (is_array($binary)) {
            return $binary;
        }

        if (! is_file($binary)) {
            throw new ChartRenderException(
                "Chart.js renderer binary not found at: {$binary}. "
                . 'The package ships a Windows (.exe) build; on other platforms '
                . 'set CHARTJS_BINARY_PATH to a renderer binary for your OS.'
            );
        }

        return $binary;
    }

    private function resolveTempDir(): string
    {
        $dir = $this->config('temp_path');

        if (! is_string($dir) || $dir === '') {
            $dir = sys_get_temp_dir();
        }

        if (! is_dir($dir) && ! @mkdir($dir, 0777, true) && ! is_dir($dir)) {
            throw new ChartRenderException("Unable to create temp directory: {$dir}");
        }

        return $dir;
    }

    private function makeTempFile(string $dir, string $prefix, string $suffix): string
    {
        $base = tempnam($dir, $prefix);
        if ($base === false) {
            throw new ChartRenderException("Unable to allocate temp file in: {$dir}");
        }

        $path = $base . $suffix;

        if ($path !== $base) {
            @rename($base, $path);
        }

        return $path;
    }

    private function safeUnlink(string $path): void
    {
        if (is_file($path)) {
            @unlink($path);
        }
    }

    private function truncate(string $message, int $max = 2000): string
    {
        $message = trim($message);

        if (strlen($message) <= $max) {
            return $message;
        }

        return substr($message, 0, $max) . '... [truncated]';
    }

    private function config(string $key, mixed $default = null): mixed
    {
        try {
            if (function_exists('config')) {
                return config('chartjs.' . $key, $default);
            }
        } catch (Throwable) {
            // fall through for non-Laravel contexts / tests
        }

        return $default;
    }
}
