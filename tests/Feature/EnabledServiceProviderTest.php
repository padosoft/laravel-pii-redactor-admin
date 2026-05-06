<?php

declare(strict_types=1);

namespace Padosoft\PiiRedactorAdmin\Tests\Feature;

use Illuminate\Auth\GenericUser;
use Illuminate\Support\Facades\Route;

final class EnabledServiceProviderTest extends TestCase
{
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);
        $app['config']->set('pii-redactor-admin.enabled', true);
    }

    public function test_admin_routes_register_when_enabled(): void
    {
        $this->assertTrue(Route::has('pii-redactor-admin.shell'));
        $this->assertTrue(Route::has('pii-redactor-admin.asset'));
        $this->assertTrue(Route::has('pii-redactor-admin.api.status'));
    }

    public function test_package_assets_are_served_from_package_dist(): void
    {
        $asset = $this->distManifest()['resources/js/app.tsx']['file'];

        $response = $this->get('/pii-redactor-admin/assets/'.$asset)
            ->assertOk()
            ->assertHeader('content-type', 'application/javascript; charset=UTF-8')
            ->assertHeader('x-content-type-options', 'nosniff');

        $this->assertImmutableCacheHeader((string) $response->headers->get('cache-control'));
    }

    public function test_package_css_assets_are_served_with_safe_headers(): void
    {
        $asset = $this->distManifest()['resources/css/admin.css']['file'];

        $response = $this->get('/pii-redactor-admin/assets/'.$asset)
            ->assertOk()
            ->assertHeader('content-type', 'text/css; charset=UTF-8')
            ->assertHeader('x-content-type-options', 'nosniff');

        $this->assertImmutableCacheHeader((string) $response->headers->get('cache-control'));
    }

    public function test_package_assets_reject_missing_files_and_path_traversal(): void
    {
        $this->get('/pii-redactor-admin/assets/assets/missing.js')->assertNotFound();
        $this->get('/pii-redactor-admin/assets/../.env')->assertNotFound();
        $this->get('/pii-redactor-admin/assets/%2e%2e/.env')->assertNotFound();
    }

    public function test_shell_renders_for_custom_user_without_name_or_email(): void
    {
        $this->actingAs(new GenericUser(['id' => 'custom-user-id']));

        $this->get('/pii-redactor-admin')
            ->assertOk()
            ->assertSee('Operator');
    }

    public function test_shell_loads_package_assets_from_controller_resolved_manifest(): void
    {
        $manifest = $this->distManifest();
        $jsAsset = $manifest['resources/js/app.tsx']['file'];
        $cssAsset = $manifest['resources/css/admin.css']['file'];

        $this->get('/pii-redactor-admin')
            ->assertOk()
            ->assertSee('/pii-redactor-admin/assets/'.$jsAsset, false)
            ->assertSee('/pii-redactor-admin/assets/'.$cssAsset, false);
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function distManifest(): array
    {
        $manifest = json_decode((string) file_get_contents(__DIR__.'/../../resources/dist/.vite/manifest.json'), true);

        $this->assertIsArray($manifest);

        return $manifest;
    }

    private function assertImmutableCacheHeader(string $header): void
    {
        $this->assertStringContainsString('public', $header);
        $this->assertStringContainsString('max-age=31536000', $header);
        $this->assertStringContainsString('immutable', $header);
    }
}
