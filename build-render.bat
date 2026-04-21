@echo off
setlocal

set "ROOT_DIR=%~dp0"
set "RENDERER_DIR=%ROOT_DIR%renderer"
set "OUTPUT_BIN=%ROOT_DIR%bin\chartjs-renderer.exe"

if not exist "%RENDERER_DIR%\package.json" (
    echo [ERROR] Could not find renderer\package.json
    exit /b 1
)

where npx >nul 2>&1
if errorlevel 1 (
    echo [ERROR] npx is required but was not found in PATH.
    echo Install Node.js ^(which provides npx^) and try again.
    exit /b 1
)

pushd "%RENDERER_DIR%" || (
    echo [ERROR] Failed to enter renderer directory.
    exit /b 1
)

echo [1/2] Installing renderer dependencies with Node 18 toolchain...
call npx -y -p node@18 -p npm@10 npm install --legacy-peer-deps
if errorlevel 1 (
    echo [ERROR] npm install failed.
    popd
    exit /b 1
)

echo [2/2] Building renderer executable...
call npx -y -p node@18 -p npm@10 npm run build
if errorlevel 1 (
    echo [ERROR] Renderer build failed.
    popd
    exit /b 1
)

popd

if exist "%OUTPUT_BIN%" (
    echo [OK] Renderer built successfully:
    echo      %OUTPUT_BIN%
    exit /b 0
)

echo [ERROR] Build completed but output binary was not found:
echo         %OUTPUT_BIN%
exit /b 1
