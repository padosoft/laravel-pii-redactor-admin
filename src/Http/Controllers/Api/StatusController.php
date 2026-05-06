<?php

declare(strict_types=1);

namespace Padosoft\PiiRedactorAdmin\Http\Controllers\Api;

use Composer\InstalledVersions;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Padosoft\PiiRedactor\Admin\RedactorAdminInspector;
use Padosoft\PiiRedactor\Strategies\RedactionStrategyFactory;
use Padosoft\PiiRedactorAdmin\Http\Controllers\Concerns\AuthorizesAdmin;

final class StatusController extends Controller
{
    use AuthorizesAdmin;

    public function __invoke(Request $request, RedactorAdminInspector $inspector, RedactionStrategyFactory $strategies): array
    {
        $this->authorizeAdmin($request);

        return [
            'package' => [
                'name' => 'padosoft/laravel-pii-redactor-admin',
                'version' => $this->packageVersion(),
                'enabled' => (bool) config('pii-redactor-admin.enabled', false),
            ],
            'strategies' => $strategies->names(),
            'snapshot' => $inspector->snapshot(),
        ];
    }

    private function packageVersion(): string
    {
        if (InstalledVersions::isInstalled('padosoft/laravel-pii-redactor-admin')) {
            return InstalledVersions::getPrettyVersion('padosoft/laravel-pii-redactor-admin') ?? 'dev-main';
        }

        return 'dev-main';
    }
}
