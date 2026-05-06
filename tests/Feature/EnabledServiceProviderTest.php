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
        $manifest = json_decode((string) file_get_contents(__DIR__.'/../../resources/dist/.vite/manifest.json'), true);
        $asset = $manifest['resources/js/app.tsx']['file'];

        $this->get('/pii-redactor-admin/assets/'.$asset)
            ->assertOk()
            ->assertHeader('content-type', 'application/javascript; charset=UTF-8')
            ->assertHeader('x-content-type-options', 'nosniff');
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
        $manifest = json_decode((string) file_get_contents(__DIR__.'/../../resources/dist/.vite/manifest.json'), true);
        $jsAsset = $manifest['resources/js/app.tsx']['file'];
        $cssAsset = $manifest['resources/css/admin.css']['file'];

        $this->get('/pii-redactor-admin')
            ->assertOk()
            ->assertSee('/pii-redactor-admin/assets/'.$jsAsset, false)
            ->assertSee('/pii-redactor-admin/assets/'.$cssAsset, false);
    }
}
