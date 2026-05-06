<?php

declare(strict_types=1);

namespace Padosoft\PiiRedactorAdmin\Http\Controllers\Api;

use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Padosoft\PiiRedactorAdmin\Http\Controllers\Concerns\AuthorizesAdmin;

final class TokenMapController extends Controller
{
    use AuthorizesAdmin;

    public function __invoke(Request $request): array
    {
        $this->authorizeAdmin($request);
        $payload = $request->validate([
            'detector' => ['sometimes', 'nullable', 'string', 'max:64'],
            'search' => ['sometimes', 'nullable', 'string', 'max:255'],
            'per_page' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:100'],
        ]);
        $perPage = (int) ($payload['per_page'] ?? min(max((int) config('pii-redactor-admin.token_maps.per_page', 25), 1), 100));

        $driver = (string) config('pii-redactor.token_store.driver', 'memory');
        if ($driver !== 'database') {
            return [
                'available' => false,
                'driver' => $driver,
                'message' => 'Token metadata listing is only available for the database token store.',
                'maps' => $this->emptyMaps($perPage),
            ];
        }

        $connection = config('pii-redactor.token_store.database.connection');
        $table = (string) config('pii-redactor.token_store.database.table', 'pii_token_maps');
        $database = $connection ? DB::connection((string) $connection) : DB::connection();
        $schema = $database->getSchemaBuilder();
        if (! $schema->hasTable($table)) {
            return [
                'available' => false,
                'driver' => $driver,
                'message' => 'The token map table does not exist.',
                'maps' => $this->emptyMaps($perPage),
            ];
        }

        $query = $database->table($table)
            ->select(['token', 'detector', 'created_at'])
            ->when($payload['detector'] ?? null, fn (Builder $query, $detector) => $query->where('detector', (string) $detector))
            ->when($payload['search'] ?? null, fn (Builder $query, $search) => $query->whereRaw("token LIKE ? ESCAPE '\\'", ['%'.addcslashes((string) $search, '\\%_').'%']))
            ->orderByDesc('created_at')
            ->orderByDesc('token');

        return [
            'available' => true,
            'driver' => $driver,
            'maps' => $query->paginate($perPage)->toArray(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function emptyMaps(int $perPage): array
    {
        return (new LengthAwarePaginator([], 0, $perPage, 1))->toArray();
    }
}
