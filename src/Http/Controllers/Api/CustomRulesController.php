<?php

declare(strict_types=1);

namespace Padosoft\PiiRedactorAdmin\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Padosoft\PiiRedactor\CustomRules\CustomRulePackInspector;
use Padosoft\PiiRedactorAdmin\Http\Controllers\Concerns\AuthorizesAdmin;

final class CustomRulesController extends Controller
{
    use AuthorizesAdmin;

    public function __invoke(Request $request, CustomRulePackInspector $inspector): array
    {
        $this->authorizeAdmin($request);

        return ['packs' => $inspector->configuredPacks()];
    }
}
