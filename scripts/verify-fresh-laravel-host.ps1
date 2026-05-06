param(
    [string] $PackagePath = (Resolve-Path ".").Path,
    [string] $WorkDir = (Join-Path ([System.IO.Path]::GetTempPath()) ("pii-redactor-admin-host-" + [System.Guid]::NewGuid().ToString("N"))),
    [int] $Port = 8137
)

$ErrorActionPreference = "Stop"

function Invoke-Step {
    param(
        [string] $Name,
        [scriptblock] $Command
    )

    Write-Host "==> $Name"
    & $Command
}

$PackagePath = (Resolve-Path $PackagePath).Path
$HostPath = Join-Path $WorkDir "host"

Invoke-Step "Create fresh Laravel host" {
    New-Item -ItemType Directory -Force -Path $WorkDir | Out-Null
    composer create-project "laravel/laravel:^13" "$HostPath" --no-interaction --prefer-dist
}

Invoke-Step "Install admin package from current checkout" {
    composer config repositories.pii-redactor-core vcs https://github.com/padosoft/laravel-pii-redactor --working-dir "$HostPath"
    composer config repositories.pii-redactor-admin path "$PackagePath" --working-dir "$HostPath"
    composer require padosoft/laravel-pii-redactor-admin:@dev --no-interaction --prefer-dist --working-dir "$HostPath"
}

Invoke-Step "Publish config and migrations" {
    php "$HostPath/artisan" vendor:publish --tag=pii-redactor-admin-config --force
    php "$HostPath/artisan" vendor:publish --tag=pii-redactor-admin-migrations --force
}

Invoke-Step "Enable admin package with no-op local smoke-test abilities" {
    Add-Content -Path "$HostPath/.env" -Value @"

PII_REDACTOR_ADMIN_ENABLED=true
PII_REDACTOR_ADMIN_MIDDLEWARE=web
PII_REDACTOR_ADMIN_VIEW_ABILITY=
PII_REDACTOR_ADMIN_DETOKENISE_ABILITY=
PII_REDACTOR_ADMIN_RAW_SAMPLES_ABILITY=
"@
    php "$HostPath/artisan" config:clear
}

Invoke-Step "Run host migrations and verify routes" {
    php "$HostPath/artisan" migrate --force
    $routes = php "$HostPath/artisan" route:list --path=pii-redactor-admin --json | ConvertFrom-Json
    $routeNames = @($routes | ForEach-Object { $_.name })
    foreach ($expected in @(
        "pii-redactor-admin.api.status",
        "pii-redactor-admin.api.scan",
        "pii-redactor-admin.api.detokenise",
        "pii-redactor-admin.asset",
        "pii-redactor-admin.shell"
    )) {
        if ($routeNames -notcontains $expected) {
            throw "Missing expected route: $expected"
        }
    }
}

Invoke-Step "Smoke test shell, API, and package assets" {
    $startArgs = @{
        FilePath = "php"
        ArgumentList = @("-S", "127.0.0.1:$Port", "-t", "public", "public/index.php")
        WorkingDirectory = $HostPath
        PassThru = $true
    }

    if ([System.Runtime.InteropServices.RuntimeInformation]::IsOSPlatform([System.Runtime.InteropServices.OSPlatform]::Windows)) {
        $startArgs.WindowStyle = "Hidden"
    }

    $server = Start-Process @startArgs
    try {
        Start-Sleep -Seconds 3

        $shell = Invoke-WebRequest -Uri "http://127.0.0.1:$Port/pii-redactor-admin" -UseBasicParsing
        if ($shell.StatusCode -ne 200 -or -not $shell.Content.Contains("PII_REDACTOR_ADMIN")) {
            throw "Admin shell did not render expected runtime config."
        }

        $status = Invoke-WebRequest -Uri "http://127.0.0.1:$Port/pii-redactor-admin/api/status" -UseBasicParsing
        if ($status.StatusCode -ne 200 -or -not $status.Content.Contains('"version"')) {
            throw "Status API did not return package metadata."
        }

        $jsPath = [regex]::Match($shell.Content, '/pii-redactor-admin/assets/[^"'']+\.js').Value
        $cssPath = [regex]::Match($shell.Content, '/pii-redactor-admin/assets/[^"'']+\.css').Value
        if ($jsPath -eq "" -or $cssPath -eq "") {
            throw "Admin shell did not reference compiled package JS and CSS assets."
        }

        $js = Invoke-WebRequest -Uri "http://127.0.0.1:$Port$jsPath" -UseBasicParsing
        $css = Invoke-WebRequest -Uri "http://127.0.0.1:$Port$cssPath" -UseBasicParsing
        if (($js.Headers["Content-Type"] -join ",") -notmatch "application/javascript") {
            throw "JS asset was not served as application/javascript."
        }
        if (($css.Headers["Content-Type"] -join ",") -notmatch "text/css") {
            throw "CSS asset was not served as text/css."
        }
        if (($js.Headers["X-Content-Type-Options"] -join ",") -ne "nosniff" -or ($css.Headers["X-Content-Type-Options"] -join ",") -ne "nosniff") {
            throw "Package assets are missing X-Content-Type-Options: nosniff."
        }
    } finally {
        if ($server -and -not $server.HasExited) {
            Stop-Process -Id $server.Id -Force
        }
    }
}

Write-Host "Fresh Laravel host verification passed at $HostPath"
