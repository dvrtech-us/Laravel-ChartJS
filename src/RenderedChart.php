<?php

namespace Dvrtech\LaravelChartJs;

use Dvrtech\LaravelChartJs\Exceptions\ChartRenderException;

class RenderedChart
{
    public function __construct(
        private readonly string $bytes,
        private readonly string $format,
        private readonly int $width,
        private readonly int $height,
    ) {
    }

    public function bytes(): string
    {
        return $this->bytes;
    }

    public function base64(): string
    {
        return base64_encode($this->bytes);
    }

    public function dataUri(): string
    {
        return 'data:' . $this->mimeType() . ';base64,' . $this->base64();
    }

    public function saveTo(string $path): string
    {
        $dir = dirname($path);

        if (! is_dir($dir) && ! @mkdir($dir, 0777, true) && ! is_dir($dir)) {
            throw new ChartRenderException("Unable to create directory: {$dir}");
        }

        if (@file_put_contents($path, $this->bytes) === false) {
            throw new ChartRenderException("Unable to write rendered chart to: {$path}");
        }

        return $path;
    }

    public function format(): string
    {
        return $this->format;
    }

    public function mimeType(): string
    {
        return match (strtolower($this->format)) {
            'jpeg', 'jpg' => 'image/jpeg',
            'svg'         => 'image/svg+xml',
            default       => 'image/png',
        };
    }

    public function width(): int
    {
        return $this->width;
    }

    public function height(): int
    {
        return $this->height;
    }

    public function size(): int
    {
        return strlen($this->bytes);
    }
}
