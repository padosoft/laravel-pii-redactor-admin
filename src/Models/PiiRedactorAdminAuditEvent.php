<?php

declare(strict_types=1);

namespace Padosoft\PiiRedactorAdmin\Models;

use Illuminate\Database\Eloquent\Model;

final class PiiRedactorAdminAuditEvent extends Model
{
    protected $table = 'pii_redactor_admin_audit_events';

    protected $fillable = [
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
    ];

    protected $casts = [
        'counts_json' => 'array',
    ];
}
