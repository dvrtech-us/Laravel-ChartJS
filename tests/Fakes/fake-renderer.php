<?php
/**
 * Fake chartjs-renderer used by the test suite in place of the real
 * Windows executable. It mirrors the real CLI contract so that
 * ChartJsRenderer can be exercised end-to-end without Node.js or `pkg`.
 *
 * Behaviour is driven by the JSON input file:
 *   - If the config contains {"__fail_with": "message"} the process exits 1
 *     with that message on stderr.
 *   - If it contains {"__timeout_ms": N} the process sleeps N milliseconds
 *     before writing output (used to exercise timeouts).
 *   - Otherwise it writes a tiny valid image to --output using the format
 *     passed on --format (PNG/JPEG/SVG).
 */

$opts = [
    'input'      => null,
    'output'     => null,
    'format'     => 'png',
    'width'      => '800',
    'height'     => '600',
    'dpr'        => '2',
    'background' => null,
];

$args = array_slice($argv, 1);
for ($i = 0; $i < count($args); $i++) {
    $flag = $args[$i];
    if (strncmp($flag, '--', 2) !== 0) {
        fwrite(STDERR, "Unexpected argument: {$flag}\n");
        exit(2);
    }
    $key = substr($flag, 2);
    $value = $args[$i + 1] ?? null;
    $opts[$key] = $value;
    $i++;
}

if (! $opts['input'] || ! is_file($opts['input'])) {
    fwrite(STDERR, "Missing or unreadable --input\n");
    exit(2);
}
if (! $opts['output']) {
    fwrite(STDERR, "Missing --output\n");
    exit(2);
}

$raw = file_get_contents($opts['input']);
$config = json_decode($raw, true);
if (! is_array($config)) {
    fwrite(STDERR, "Input is not valid JSON\n");
    exit(3);
}

if (isset($config['__fail_with'])) {
    fwrite(STDERR, (string) $config['__fail_with'] . "\n");
    exit(4);
}

if (isset($config['__timeout_ms'])) {
    usleep((int) $config['__timeout_ms'] * 1000);
}

$format = strtolower((string) $opts['format']);

// Minimal 1x1 payloads per format; enough for the PHP side to treat as
// a non-empty rendered image.
$payload = match ($format) {
    'jpeg', 'jpg' => base64_decode(
        '/9j/4AAQSkZJRgABAQEASABIAAD/2wBDAAEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQH/2wBDAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQH/wAARCAABAAEDASIAAhEBAxEB/8QAFQABAQAAAAAAAAAAAAAAAAAAAAn/xAAUEAEAAAAAAAAAAAAAAAAAAAAA/8QAFAEBAAAAAAAAAAAAAAAAAAAAAP/EABQRAQAAAAAAAAAAAAAAAAAAAAD/2gAMAwEAAhEDEQA/AL+AH//Z'
    ),
    'svg' => '<svg xmlns="http://www.w3.org/2000/svg" width="1" height="1"><rect width="1" height="1"/></svg>',
    default => base64_decode(
        'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO3f1aMAAAAASUVORK5CYII='
    ),
};

if (@file_put_contents($opts['output'], $payload) === false) {
    fwrite(STDERR, "Unable to write output\n");
    exit(5);
}

exit(0);
