<?php

declare(strict_types=1);

namespace Padosoft\PiiRedactorAdmin\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Validation\Rule;
use Padosoft\PiiRedactor\Facades\Pii;
use Padosoft\PiiRedactor\Reports\DetectionReportFormatter;
use Padosoft\PiiRedactor\Strategies\RedactionStrategyFactory;
use Padosoft\PiiRedactorAdmin\Http\Controllers\Concerns\AuthorizesAdmin;
use Padosoft\PiiRedactorAdmin\Http\Controllers\Concerns\RecordsAuditEvents;

final class PlaygroundController extends Controller
{
    use AuthorizesAdmin;
    use RecordsAuditEvents;

    public function scan(Request $request, DetectionReportFormatter $formatter): JsonResponse
    {
        $this->authorizeAdmin($request);
        $payload = $request->validate([
            'text' => ['required', 'string', 'max:20000'],
            'include_raw_samples' => ['sometimes', 'boolean'],
            'target_ref' => ['sometimes', 'nullable', 'string', 'max:255'],
        ]);

        $includeRaw = (bool) ($payload['include_raw_samples'] ?? false);
        if ($includeRaw && ! $this->adminAllows($request, 'raw_samples')) {
            $this->audit($request, 'scan.raw_samples.denied', 403, $payload['text']);

            return response()->json(['message' => 'Raw samples are not authorized.'], 403);
        }

        $report = Pii::scan($payload['text']);
        $safe = $formatter->safeArray($report, $includeRaw);
        $this->audit($request, $includeRaw ? 'scan.raw_samples' : 'scan', 200, $payload['text'], $safe['counts'], null, $safe['total']);

        return response()->json(['report' => $safe]);
    }

    public function redact(Request $request, DetectionReportFormatter $formatter, RedactionStrategyFactory $strategies): JsonResponse
    {
        $this->authorizeAdmin($request);
        $strategyNames = array_values(array_map('strval', $strategies->names()));
        $payload = $request->validate([
            'text' => ['required', 'string', 'max:20000'],
            'strategy' => ['sometimes', 'nullable', 'string', Rule::in($strategyNames)],
            'include_raw_samples' => ['sometimes', 'boolean'],
            'target_ref' => ['sometimes', 'nullable', 'string', 'max:255'],
        ]);

        $includeRaw = (bool) ($payload['include_raw_samples'] ?? false);
        if ($includeRaw && ! $this->adminAllows($request, 'raw_samples')) {
            $this->audit($request, 'redact.raw_samples.denied', 403, $payload['text'], null, $payload['strategy'] ?? null);

            return response()->json(['message' => 'Raw samples are not authorized.'], 403);
        }

        $strategyName = $payload['strategy'] ?? null;
        $strategy = $strategyName === null ? null : $strategies->make($strategyName);
        $report = Pii::scan($payload['text']);
        $safe = $formatter->safeArray($report, $includeRaw);
        $output = Pii::redact($payload['text'], $strategy);
        $this->audit($request, $includeRaw ? 'redact.raw_samples' : 'redact', 200, $payload['text'], $safe['counts'], $strategyName, $safe['total']);

        return response()->json([
            'output' => $output,
            'strategy' => $strategyName ?? (string) config('pii-redactor.strategy', 'mask'),
            'report' => $safe,
        ]);
    }
}
