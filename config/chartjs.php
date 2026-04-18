<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Renderer binary path
    |--------------------------------------------------------------------------
    |
    | Absolute path to the executable that renders a Chart.js config into an
    | image file. The package ships a Windows binary at bin/chartjs-renderer.exe;
    | override this value to use a binary built for another platform.
    |
    */

    'binary_path' => env(
        'CHARTJS_BINARY_PATH',
        dirname(__DIR__) . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'chartjs-renderer.exe'
    ),

    /*
    |--------------------------------------------------------------------------
    | Temporary working directory
    |--------------------------------------------------------------------------
    |
    | Directory used for the short-lived config and image files exchanged with
    | the renderer. If the directory does not exist it will be created.
    | Set to null to use the system temp directory.
    |
    */

    'temp_path' => env('CHARTJS_TEMP_PATH', null),

    /*
    |--------------------------------------------------------------------------
    | Default render options
    |--------------------------------------------------------------------------
    */

    'default_format'     => env('CHARTJS_FORMAT', 'png'),
    'default_width'      => (int) env('CHARTJS_WIDTH', 800),
    'default_height'     => (int) env('CHARTJS_HEIGHT', 600),
    'device_pixel_ratio' => (float) env('CHARTJS_DPR', 2),
    'background'         => env('CHARTJS_BACKGROUND', null),

    /*
    |--------------------------------------------------------------------------
    | Process timeout (seconds)
    |--------------------------------------------------------------------------
    */

    'timeout' => (int) env('CHARTJS_TIMEOUT', 30),

];
