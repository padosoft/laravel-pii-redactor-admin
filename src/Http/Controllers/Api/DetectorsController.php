<?php

declare(strict_types=1);

namespace Padosoft\PiiRedactorAdmin\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Padosoft\PiiRedactor\Admin\RedactorAdminInspector;
use Padosoft\PiiRedactorAdmin\Http\Controllers\Concerns\AuthorizesAdmin;

final class DetectorsController extends Controller
{
    use AuthorizesAdmin;

    public function __invoke(Request $request, RedactorAdminInspector $inspector): array
    {
        $this->authorizeAdmin($request);

        $snapshot = $inspector->snapshot();

        return [
            'detectors' => $snapshot['detectors'] ?? [],
            'packs' => $snapshot['packs'] ?? [],
        ];
    }
}
