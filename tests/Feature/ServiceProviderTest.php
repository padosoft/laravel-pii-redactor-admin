<?php

declare(strict_types=1);

namespace Padosoft\PiiRedactorAdmin\Tests\Feature;

use Illuminate\Support\Facades\Route;

final class ServiceProviderTest extends TestCase
{
    public function test_admin_routes_are_disabled_by_default(): void
    {
        $this->assertFalse(Route::has('pii-redactor-admin.shell'));
        $this->assertFalse(Route::has('pii-redactor-admin.api.status'));
    }
}
