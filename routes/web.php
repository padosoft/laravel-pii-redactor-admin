<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Padosoft\PiiRedactorAdmin\Http\Controllers\AdminShellController;
use Padosoft\PiiRedactorAdmin\Http\Controllers\PackageAssetController;

Route::get('/assets/{path}', PackageAssetController::class)
    ->where('path', '.*')
    ->name('asset');

Route::get('/{path?}', AdminShellController::class)
    ->where('path', '.*')
    ->name('shell');
