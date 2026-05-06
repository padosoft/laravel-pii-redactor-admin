<?php

declare(strict_types=1);

namespace Padosoft\PiiRedactorAdmin\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Auth\GenericUser;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Padosoft\PiiRedactorAdmin\Models\PiiRedactorAdminAuditEvent;

final class AdminApiTest extends TestCase
{
    use RefreshDatabase;

    private static int $viewGateArgumentCount = -1;

    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);
        $app['config']->set('pii-redactor-admin.enabled', true);
    }

    public function test_status_exposes_safe_snapshot(): void
    {
        $this->getJson('/pii-redactor-admin/api/status')
            ->assertOk()
            ->assertJsonMissingPath('snapshot.salt')
            ->assertJsonMissingPath('snapshot.api_key')
            ->assertJson(fn ($json) => $json->whereType('package.version', 'string')->etc());
    }

    public function test_view_gate_receives_request_user_without_duplicate_arguments(): void
    {
        self::$viewGateArgumentCount = -1;
        $this->app['config']->set('pii-redactor-admin.abilities.view', 'viewAdmin');
        Gate::define('viewAdmin', function (?object $user = null, mixed ...$arguments): bool {
            self::$viewGateArgumentCount = 1 + count($arguments);

            return true;
        });

        $this->getJson('/pii-redactor-admin/api/status')->assertOk();

        $this->assertSame(1, self::$viewGateArgumentCount);
    }

    public function test_scan_masks_samples_by_default(): void
    {
        $this->postJson('/pii-redactor-admin/api/scan', [
            'text' => 'mario.rossi@example.test',
        ])->assertOk()
            ->assertJsonMissing(['mario.rossi@example.test']);
    }

    public function test_raw_samples_require_ability(): void
    {
        $this->app['config']->set('pii-redactor-admin.abilities.raw_samples', 'viewRaw');
        Gate::define('viewRaw', fn () => false);

        $this->postJson('/pii-redactor-admin/api/scan', [
            'text' => 'mario.rossi@example.test',
            'include_raw_samples' => true,
        ])->assertForbidden();

        $this->assertDatabaseHas('pii_redactor_admin_audit_events', [
            'event_type' => 'scan.raw_samples.denied',
            'status_code' => 403,
        ]);
    }

    public function test_scan_and_redact_audit_rows_store_hashes_not_payloads(): void
    {
        $rawText = 'Mario Rossi email mario.rossi@example.test';

        $this->postJson('/pii-redactor-admin/api/scan', [
            'text' => $rawText,
        ])->assertOk();

        $this->postJson('/pii-redactor-admin/api/redact', [
            'text' => $rawText,
            'strategy' => 'mask',
        ])->assertOk();

        $events = PiiRedactorAdminAuditEvent::query()->get();
        $this->assertCount(2, $events);
        $this->assertSame([64, 64], $events->pluck('target_hash')->map(fn (?string $hash) => strlen((string) $hash))->all());
        $this->assertNotContains(hash('sha256', $rawText), $events->pluck('target_hash')->all());

        $serialized = $events->toJson();
        $this->assertStringNotContainsString($rawText, $serialized);
        $this->assertStringNotContainsString('mario.rossi@example.test', $serialized);
        $this->assertStringNotContainsString('[REDACTED]', $serialized);
    }

    public function test_redact_accepts_advertised_strategies_from_status(): void
    {
        $strategies = $this->getJson('/pii-redactor-admin/api/status')
            ->assertOk()
            ->json('strategies');

        $this->assertNotEmpty($strategies);

        foreach ($strategies as $strategy) {
            $this->postJson('/pii-redactor-admin/api/redact', [
                'text' => 'mario.rossi@example.test',
                'strategy' => $strategy,
            ])->assertOk()
                ->assertJsonPath('strategy', $strategy);
        }

        $this->postJson('/pii-redactor-admin/api/redact', [
            'text' => 'mario.rossi@example.test',
            'strategy' => 'unsupported',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['strategy']);
    }

    public function test_audit_rows_support_string_actor_identifiers(): void
    {
        $this->actingAs(new GenericUser(['id' => '01HXSTRINGACTORID000000000000']));

        $this->postJson('/pii-redactor-admin/api/scan', [
            'text' => 'mario.rossi@example.test',
        ])->assertOk();

        $this->assertDatabaseHas('pii_redactor_admin_audit_events', [
            'actor_id' => '01HXSTRINGACTORID000000000000',
            'event_type' => 'scan',
        ]);
    }

    public function test_token_maps_never_select_original(): void
    {
        $this->app['config']->set('pii-redactor.token_store.driver', 'database');
        DB::table('pii_token_maps')->insert([
            'token' => '[tok:email:abcdef012345]',
            'original' => 'secret@example.test',
            'detector' => 'email',
        ]);

        $this->getJson('/pii-redactor-admin/api/token-maps')
            ->assertOk()
            ->assertJsonMissingPath('maps.data.0.original')
            ->assertJsonMissing(['original' => 'secret@example.test'])
            ->assertJsonMissing(['secret@example.test']);
    }

    public function test_token_maps_use_stable_empty_shape_when_unavailable(): void
    {
        $this->app['config']->set('pii-redactor.token_store.driver', 'memory');

        $response = $this->getJson('/pii-redactor-admin/api/token-maps')
            ->assertOk()
            ->assertJsonPath('available', false)
            ->assertJsonPath('maps.total', 0)
            ->assertJsonPath('maps.data', []);

        $this->assertArrayNotHasKey('data', $response->json());
        $this->assertArrayHasKey('links', $response->json('maps'));
        $this->assertArrayHasKey('first_page_url', $response->json('maps'));
    }

    public function test_token_map_search_treats_wildcards_as_literal_text(): void
    {
        $this->app['config']->set('pii-redactor.token_store.driver', 'database');
        DB::table('pii_token_maps')->insert([
            [
                'token' => '[tok:email:abc_def]',
                'original' => 'first@example.test',
                'detector' => 'email',
            ],
            [
                'token' => '[tok:email:abcZdef]',
                'original' => 'second@example.test',
                'detector' => 'email',
            ],
        ]);

        $this->getJson('/pii-redactor-admin/api/token-maps?search=abc_def')
            ->assertOk()
            ->assertJsonPath('maps.total', 1)
            ->assertJsonPath('maps.data.0.token', '[tok:email:abc_def]');
    }

    public function test_token_map_filters_are_validated(): void
    {
        $this->app['config']->set('pii-redactor.token_store.driver', 'database');

        $this->getJson('/pii-redactor-admin/api/token-maps?search='.str_repeat('a', 256))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['search']);

        $this->getJson('/pii-redactor-admin/api/token-maps?detector='.str_repeat('a', 65))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['detector']);

        $this->getJson('/pii-redactor-admin/api/token-maps?per_page=101')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['per_page']);
    }

    public function test_detokenise_requires_ability_and_audits_denial(): void
    {
        $this->app['config']->set('pii-redactor-admin.abilities.detokenise', 'detokenise');
        Gate::define('detokenise', fn () => false);

        $this->postJson('/pii-redactor-admin/api/detokenise', [
            'text' => '[tok:email:abcdef012345]',
            'justification' => 'incident response',
        ])->assertForbidden();

        $this->assertSame(1, PiiRedactorAdminAuditEvent::query()->where('event_type', 'detokenise.denied')->count());
    }

    public function test_detokenise_requires_valid_token_pattern_and_justification(): void
    {
        $this->postJson('/pii-redactor-admin/api/detokenise', [
            'text' => 'plain text without a token',
            'justification' => 'incident response',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['text']);

        $this->postJson('/pii-redactor-admin/api/detokenise', [
            'text' => '[tok:email:abcdef012345]',
            'justification' => 'too short',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['justification']);
    }

    public function test_detokenise_success_audits_without_persisting_output_or_original(): void
    {
        $token = '[tok:email:abcdef012345]';
        $original = 'secret@example.test';

        $this->app['config']->set('pii-redactor.token_store.driver', 'database');
        DB::table('pii_token_maps')->insert([
            'token' => $token,
            'original' => $original,
            'detector' => 'email',
        ]);

        $this->postJson('/pii-redactor-admin/api/detokenise', [
            'text' => "Review $token",
            'justification' => 'incident response',
        ])->assertOk()
            ->assertJson([
                'output' => "Review $original",
                'token_count' => 1,
                'resolved_count' => 1,
            ]);

        $event = PiiRedactorAdminAuditEvent::query()->where('event_type', 'detokenise')->firstOrFail();
        $this->assertSame(['tokens' => 1, 'resolved' => 1, 'unresolved' => 0], $event->counts_json);
        $this->assertSame('incident response', $event->justification);

        $serialized = $event->toJson();
        $this->assertStringNotContainsString($token, $serialized);
        $this->assertStringNotContainsString($original, $serialized);
        $this->assertStringNotContainsString("Review $original", $serialized);
    }

    public function test_audit_listing_is_filterable_and_uses_safe_projection(): void
    {
        PiiRedactorAdminAuditEvent::query()->create([
            'event_type' => 'scan',
            'status_code' => 200,
            'counts_json' => ['email' => 1],
            'target_hash' => hash('sha256', 'mario.rossi@example.test'),
        ]);
        PiiRedactorAdminAuditEvent::query()->create([
            'event_type' => 'detokenise.denied',
            'status_code' => 403,
            'justification' => 'incident response',
            'target_hash' => hash('sha256', '[tok:email:abcdef012345]'),
        ]);

        $this->getJson('/pii-redactor-admin/api/audit-events?event_type=detokenise.denied&status_code=403')
            ->assertOk()
            ->assertJsonPath('total', 1)
            ->assertJsonPath('data.0.event_type', 'detokenise.denied')
            ->assertJsonMissingPath('data.0.original')
            ->assertJsonMissingPath('data.0.raw_text')
            ->assertJsonMissingPath('data.0.detokenised_output');
    }
}
