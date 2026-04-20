#!/usr/bin/env node
/**
 * chartjs-renderer
 *
 * CLI contract (see Laravel-ChartJS package):
 *   chartjs-renderer --input <config.json> --output <image-path>
 *                    [--format png|jpeg|svg] [--width N] [--height N]
 *                    [--dpr R] [--background <css-color>]
 *
 * Exit code 0 on success, non-zero on failure (human-readable message on stderr).
 */

'use strict';

const fs = require('fs');
const path = require('path');
const minimist = require('minimist');

function fail(message, code = 1) {
    process.stderr.write(String(message) + '\n');
    process.exit(code);
}

const argv = minimist(process.argv.slice(2), {
    string: ['input', 'output', 'format', 'background'],
    default: {
        format: 'png',
        width: 800,
        height: 600,
        dpr: 2,
    },
});

if (!argv.input)  fail('Missing required --input <path-to-config.json>');
if (!argv.output) fail('Missing required --output <path-to-image-file>');

const format = String(argv.format).toLowerCase();
if (!['png', 'jpeg', 'svg'].includes(format)) {
    fail(`Unsupported --format '${format}'. Allowed: png, jpeg, svg.`);
}

const width  = parseInt(argv.width, 10);
const height = parseInt(argv.height, 10);
const dpr    = parseFloat(argv.dpr);

if (!Number.isFinite(width)  || width  <= 0) fail(`Invalid --width '${argv.width}'.`);
if (!Number.isFinite(height) || height <= 0) fail(`Invalid --height '${argv.height}'.`);
if (!Number.isFinite(dpr)    || dpr    <= 0) fail(`Invalid --dpr '${argv.dpr}'.`);

let rawConfig;
try {
    rawConfig = fs.readFileSync(argv.input, 'utf8');
} catch (e) {
    fail(`Unable to read input file '${argv.input}': ${e.message}`);
}

let chartConfig;
try {
    chartConfig = JSON.parse(rawConfig);
} catch (e) {
    fail(`Input file is not valid JSON: ${e.message}`);
}

const { ChartJSNodeCanvas } = require('chartjs-node-canvas');

const background = typeof argv.background === 'string' && argv.background.length > 0
    ? argv.background
    : null;

// chartjs-node-canvas uses different native backends for raster vs vector
// output, so the canvas has to be constructed with the matching `type` for
// SVG. Raster (PNG/JPEG) uses the default backend.
const canvasOpts = {
    width,
    height,
    backgroundColour: background,
    chartCallback: (ChartJS) => {
        ChartJS.defaults.devicePixelRatio = dpr;
        // Disable animations — they are meaningless for a single-frame render
        // and can interfere with synchronous image export.
        ChartJS.defaults.animation = false;
    },
};

if (format === 'svg') {
    canvasOpts.type = 'svg';
}

const canvas = new ChartJSNodeCanvas(canvasOpts);

// chart.js server-side rendering has no DOM; disable responsiveness so the
// requested width/height are honoured exactly.
chartConfig.options = chartConfig.options || {};
if (chartConfig.options.responsive !== false) {
    chartConfig.options.responsive = false;
}
if (chartConfig.options.animation !== false) {
    chartConfig.options.animation = false;
}

(async () => {
    try {
        let buffer;

        if (format === 'svg') {
            buffer = await canvas.renderToBufferSync(chartConfig, 'image/svg+xml');
        } else if (format === 'jpeg') {
            buffer = await canvas.renderToBuffer(chartConfig, 'image/jpeg');
        } else {
            buffer = await canvas.renderToBuffer(chartConfig, 'image/png');
        }

        const outDir = path.dirname(argv.output);
        if (!fs.existsSync(outDir)) {
            fs.mkdirSync(outDir, { recursive: true });
        }

        fs.writeFileSync(argv.output, buffer);
        process.exit(0);
    } catch (e) {
        fail(`Rendering failed: ${e && e.stack ? e.stack : e}`);
    }
})();
