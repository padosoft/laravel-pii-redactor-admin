<?php

declare(strict_types=1);

namespace Padosoft\PiiRedactorAdmin\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Padosoft\PiiRedactorAdmin\Http\Controllers\Concerns\AuthorizesAdmin;

final class SettingsController extends Controller
{
    use AuthorizesAdmin;

    public function __invoke(Request $request): array
    {
        $this->authorizeAdmin($request);

        return [
            'admin' => [
                'enabled' => (bool) config('pii-redactor-admin.enabled'),
                'route_prefix' => (string) config('pii-redactor-admin.route_prefix'),
                'api_prefix' => (string) config('pii-redactor-admin.api_prefix'),
                'middleware' => (array) config('pii-redactor-admin.middleware', []),
                'abilities' => (array) config('pii-redactor-admin.abilities', []),
            ],
            'redactor' => [
                'enabled' => (bool) config('pii-redactor.enabled'),
                'strategy' => (string) config('pii-redactor.strategy'),
                'token_store_driver' => (string) config('pii-redactor.token_store.driver'),
                'audit_trail_enabled' => (bool) config('pii-redactor.audit_trail.enabled', false)
                    || (bool) config('pii-redactor.audit_trail_enabled', false),
                'ner_enabled' => (bool) config('pii-redactor.ner.enabled', false),
                'ner_driver' => (string) config('pii-redactor.ner.driver', 'stub'),
            ],
        ];
    }
}
