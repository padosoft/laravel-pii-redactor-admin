<?php

declare(strict_types=1);

namespace Padosoft\PiiRedactorAdmin\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Padosoft\PiiRedactor\TokenStore\TokenResolutionService;
use Padosoft\PiiRedactorAdmin\Http\Controllers\Concerns\AuthorizesAdmin;
use Padosoft\PiiRedactorAdmin\Http\Controllers\Concerns\RecordsAuditEvents;

final class DetokeniseController extends Controller
{
    use AuthorizesAdmin;
    use RecordsAuditEvents;

    public function __invoke(Request $request, TokenResolutionService $tokens): JsonResponse
    {
        $this->authorizeAdmin($request);

        $payload = $request->validate([
            'text' => ['required', 'string', 'max:20000', 'regex:/\\[tok:[A-Za-z0-9_]+:[0-9a-f]+\\]/'],
            'justification' => ['required', 'string', 'min:10', 'max:500'],
            'target_ref' => ['sometimes', 'nullable', 'string', 'max:255'],
        ]);

        if (! $this->adminAllows($request, 'detokenise')) {
            $this->audit($request, 'detokenise.denied', 403, $payload['text'], null, null, null, $payload['justification']);

            return response()->json(['message' => 'Detokenise access is not authorized.'], 403);
        }

        $result = $tokens->detokeniseString($payload['text']);
        $counts = [
            'tokens' => $result->tokenCount,
            'resolved' => $result->resolvedCount,
            'unresolved' => count($result->unresolvedTokens),
        ];
        $this->audit($request, 'detokenise', 200, $payload['text'], $counts, null, $result->tokenCount, $payload['justification']);

        return response()->json([
            'output' => $result->output,
            'token_count' => $result->tokenCount,
            'resolved_count' => $result->resolvedCount,
            'unresolved_tokens' => $result->unresolvedTokens,
        ]);
    }
}
