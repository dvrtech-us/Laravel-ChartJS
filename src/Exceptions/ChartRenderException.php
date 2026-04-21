<?php

namespace Dvrtech\LaravelChartJs\Exceptions;

use RuntimeException;
use Throwable;

class ChartRenderException extends RuntimeException
{
    public function __construct(
        string $message,
        private readonly ?int $exitCode = null,
        private readonly ?string $stderr = null,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }

    public function exitCode(): ?int
    {
        return $this->exitCode;
    }

    public function stderr(): ?string
    {
        return $this->stderr;
    }
}
