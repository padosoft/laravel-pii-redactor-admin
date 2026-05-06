<?php

declare(strict_types=1);

namespace Padosoft\PiiRedactorAdmin\Http\Controllers\Concerns;

use Illuminate\Http\Request;
use Padosoft\PiiRedactorAdmin\Models\PiiRedactorAdminAuditEvent;

trait RecordsAuditEvents
{
    /**
     * @param array<string, int>|null $counts
     */
    protected function audit(Request $request, string $eventType, int $statusCode, ?string $text = null, ?array $counts = null, ?string $strategy = null, ?int $total = null, ?string $justification = null): void
    {
        PiiRedactorAdminAuditEvent::query()->create([
            'event_type' => $eventType,
            'actor_id' => $this->actorId($request),
            'ip' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 2000),
            'strategy' => $strategy,
            'total' => $total,
            'counts_json' => $this->sanitizeCounts($counts),
            'target_hash' => $text === null ? null : hash_hmac('sha256', $text, $this->auditHashKey()),
            'target_ref' => $this->stringOrNull($request->input('target_ref')),
            'status_code' => $statusCode,
            'justification' => $justification === null ? null : substr($justification, 0, 500),
        ]);
    }

    private function stringOrNull(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : substr($value, 0, 255);
    }

    private function actorId(Request $request): ?string
    {
        $identifier = $request->user()?->getAuthIdentifier();
        if ($identifier === null || $identifier === '') {
            return null;
        }

        return substr((string) $identifier, 0, 255);
    }

    private function auditHashKey(): string
    {
        $key = (string) config('app.key', '');
        if (str_starts_with($key, 'base64:')) {
            $decoded = base64_decode(substr($key, 7), true);
            if (is_string($decoded) && $decoded !== '') {
                return $decoded;
            }
        }

        if ($key !== '') {
            return $key;
        }

        return (string) config('pii-redactor.salt', 'pii-redactor-admin-audit');
    }

    /**
     * @param array<string, int>|null $counts
     * @return array<string, int>|null
     */
    private function sanitizeCounts(?array $counts): ?array
    {
        if ($counts === null) {
            return null;
        }

        $safe = [];
        foreach ($counts as $detector => $count) {
            if (! is_string($detector) || ! is_int($count)) {
                continue;
            }

            $safe[substr($detector, 0, 64)] = max(0, $count);
        }

        return $safe;
    }
}
