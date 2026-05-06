<?php

declare(strict_types=1);

namespace Padosoft\PiiRedactorAdmin\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class PackageAssetController extends Controller
{
    public function __invoke(Request $request, string $path): BinaryFileResponse
    {
        $base = realpath(__DIR__.'/../../../resources/dist');
        if ($base === false) {
            throw new NotFoundHttpException;
        }

        $asset = realpath($base.DIRECTORY_SEPARATOR.str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path));
        if ($asset === false || ! str_starts_with($asset, $base.DIRECTORY_SEPARATOR) || ! is_file($asset)) {
            throw new NotFoundHttpException;
        }

        return response()->file($asset, [
            'Content-Type' => $this->contentType($asset),
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => str_contains(basename($asset), '-') ? 'public, max-age=31536000, immutable' : 'no-cache',
        ]);
    }

    private function contentType(string $asset): string
    {
        return match (strtolower(pathinfo($asset, PATHINFO_EXTENSION))) {
            'css' => 'text/css; charset=UTF-8',
            'js' => 'application/javascript; charset=UTF-8',
            'json', 'map' => 'application/json; charset=UTF-8',
            default => 'application/octet-stream',
        };
    }
}
