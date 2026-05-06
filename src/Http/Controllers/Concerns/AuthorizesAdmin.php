<?php

declare(strict_types=1);

namespace Padosoft\PiiRedactorAdmin\Http\Controllers\Concerns;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpKernel\Exception\HttpException;

trait AuthorizesAdmin
{
    protected function authorizeAdmin(Request $request, string $abilityKey = 'view'): void
    {
        $ability = (string) config("pii-redactor-admin.abilities.$abilityKey", '');
        if ($ability === '') {
            return;
        }

        $response = Gate::forUser($request->user())->inspect($ability);
        if ($response->denied()) {
            throw new HttpException(403, 'This action is not authorized.');
        }
    }

    protected function adminAllows(Request $request, string $abilityKey): bool
    {
        $ability = (string) config("pii-redactor-admin.abilities.$abilityKey", '');

        return $ability === '' || Gate::forUser($request->user())->inspect($ability)->allowed();
    }
}
