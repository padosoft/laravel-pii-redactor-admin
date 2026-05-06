<?php

declare(strict_types=1);

namespace Padosoft\PiiRedactorAdmin\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Padosoft\PiiRedactorAdmin\Http\Controllers\Concerns\AuthorizesAdmin;

final class AdminShellController extends Controller
{
    use AuthorizesAdmin;

    public function __invoke(Request $request)
    {
        $this->authorizeAdmin($request);
        $user = $request->user();
        $display = data_get($user, 'name') ?: data_get($user, 'email') ?: 'Operator';

        return view('pii-redactor-admin::app', [
            'config' => [
                'apiBase' => url((string) config('pii-redactor-admin.api_prefix', 'pii-redactor-admin/api')),
                'csrfToken' => csrf_token(),
                'routePrefix' => (string) config('pii-redactor-admin.route_prefix', 'pii-redactor-admin'),
                'userDisplay' => (string) $display,
                'abilities' => [
                    'view' => $this->adminAllows($request, 'view'),
                    'detokenise' => $this->adminAllows($request, 'detokenise'),
                    'rawSamples' => $this->adminAllows($request, 'raw_samples'),
                ],
            ],
            'assets' => $this->assetUrls(),
        ]);
    }

    /**
     * @return array{css: list<string>, js: string|null}
     */
    private function assetUrls(): array
    {
        $manifestPath = __DIR__.'/../../../resources/dist/.vite/manifest.json';
        $manifestPayload = is_file($manifestPath) ? json_decode((string) file_get_contents($manifestPath), true) : [];
        $manifest = is_array($manifestPayload) ? $manifestPayload : [];
        $cssEntry = $manifest['resources/css/admin.css'] ?? null;
        $jsEntry = $manifest['resources/js/app.tsx'] ?? null;

        $css = [];
        if (is_array($cssEntry) && isset($cssEntry['file'])) {
            $css[] = route('pii-redactor-admin.asset', ['path' => (string) $cssEntry['file']]);
        }

        if (is_array($jsEntry)) {
            foreach (($jsEntry['css'] ?? []) as $cssFile) {
                $css[] = route('pii-redactor-admin.asset', ['path' => (string) $cssFile]);
            }
        }

        return [
            'css' => array_values(array_unique($css)),
            'js' => is_array($jsEntry) && isset($jsEntry['file'])
                ? route('pii-redactor-admin.asset', ['path' => (string) $jsEntry['file']])
                : null,
        ];
    }
}
