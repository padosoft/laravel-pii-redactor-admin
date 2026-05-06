<?php

declare(strict_types=1);

namespace Padosoft\PiiRedactorAdmin\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Padosoft\PiiRedactorAdmin\Http\Controllers\Concerns\AuthorizesAdmin;
use Padosoft\PiiRedactorAdmin\Models\PiiRedactorAdminAuditEvent;

final class AuditEventController extends Controller
{
    use AuthorizesAdmin;

    public function __invoke(Request $request): array
    {
        $this->authorizeAdmin($request);

        $filters = $request->validate([
            'event_type' => ['sometimes', 'string', 'max:64'],
            'status_code' => ['sometimes', 'integer', 'min:100', 'max:599'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);

        $events = PiiRedactorAdminAuditEvent::query()
            ->select([
                'id',
                'event_type',
                'actor_id',
                'ip',
                'user_agent',
                'strategy',
                'total',
                'counts_json',
                'target_hash',
                'target_ref',
                'status_code',
                'justification',
                'created_at',
                'updated_at',
            ])
            ->when($filters['event_type'] ?? null, fn ($query, $type) => $query->where('event_type', (string) $type))
            ->when($filters['status_code'] ?? null, fn ($query, $status) => $query->where('status_code', (int) $status))
            ->latest('id')
            ->paginate($filters['per_page'] ?? 25);

        return $events->toArray();
    }
}
