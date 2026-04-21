# chartjs-renderer

Node.js CLI that renders a Chart.js configuration to an image file. It is the
engine behind the `dvrtech/laravel-chartjs` PHP package and is compiled to a
single Windows executable (`bin/chartjs-renderer.exe`) that the package
invokes via `Symfony\Component\Process\Process`.

## CLI contract

```
chartjs-renderer --input  <path-to-config.json>
                 --output <path-to-image-file>
                 [--format png|jpeg|svg]
                 [--width  <int>]
                 [--height <int>]
                 [--dpr    <float>]
                 [--background <css-color>]
```

- `--input` is a JSON file containing a full Chart.js config:
  `{ "type": "bar", "data": { ... }, "options": { ... } }`.
- `--output` is the path to write the rendered image to.
- Exit code `0` = success; any other value = failure with a message on stderr.

## Build

```bash
cd renderer
npm install
npm run build
```

This produces `../bin/chartjs-renderer.exe` (Windows x64). Build on a machine
that has the native `canvas` dependencies installed (Windows builds typically
need `windows-build-tools` or the MSVC build tools plus the GTK runtime).

To target additional platforms, adjust the `pkg` target in `package.json`
(e.g. `node18-linux-x64`, `node18-macos-arm64`) and rename the output binary.
The PHP package selects the binary via `config('chartjs.binary_path')` /
`CHARTJS_BINARY_PATH`.

## Run locally (without building the exe)

```bash
node index.js --input sample.json --output out.png --format png \
              --width 800 --height 600
```
