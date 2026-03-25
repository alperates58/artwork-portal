<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\PortalUpdateEvent;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class UpdateManagementTest extends TestCase
{
    use RefreshDatabase;

    private function fakeGithubReleaseResponses(): void
    {
        Http::fake([
            'https://api.github.com/repos/alperates58/artwork-portal/commits/*' => Http::response([
                'sha' => '1234567890abcdef1234567890abcdef12345678',
                'html_url' => 'https://github.com/alperates58/artwork-portal/commit/1234567',
                'commit' => [
                    'message' => 'Release test commit',
                    'author' => [
                        'date' => now()->toIso8601String(),
                    ],
                ],
            ], 200),
            'https://raw.githubusercontent.com/alperates58/artwork-portal/*/releases/manifest.json' => Http::response([
                'schema_version' => 1,
                'generated_at' => now()->toIso8601String(),
                'latest' => '1.5.0',
                'releases' => [
                    [
                        'version' => '1.5.0',
                        'title' => 'Yeni release',
                        'summary' => 'Update release özeti',
                        'release_date' => now()->toDateString(),
                        'changes' => [
                            'Admin ekranına release notları eklendi.',
                        ],
                        'changed_modules' => [
                            'Admin Ayarları',
                        ],
                        'migrations_included' => true,
                        'schema_changes' => [
                            'new_tables' => [],
                            'new_columns' => [
                                'portal_update_events.release_title',
                            ],
                        ],
                        'warnings' => [
                            'Yedek alın.',
                        ],
                        'post_update_notes' => [
                            'php artisan portal:update',
                        ],
                        'applied_migrations' => [
                            '2026_03_25_010000_add_release_metadata_to_portal_update_events_table',
                        ],
                    ],
                ],
            ], 200),
        ]);
    }

    public function test_admin_can_trigger_github_update_check_from_settings(): void
    {
        $this->fakeGithubReleaseResponses();

        $admin = User::factory()->create(['role' => UserRole::ADMIN]);

        $this->actingAs($admin)
            ->post(route('admin.settings.update-check'))
            ->assertRedirect();

        $this->assertDatabaseHas('portal_update_events', [
            'type' => 'check',
            'status' => 'success',
            'trigger_source' => 'admin',
            'actor_id' => $admin->id,
            'to_version' => '1.5.0',
            'release_title' => 'Yeni release',
        ]);

        $this->assertSame(
            '1234567',
            SystemSetting::query()->where('key', 'system.update.latest_remote_commit')->value('value')
        );
    }

    public function test_update_check_command_records_failed_attempt_gracefully(): void
    {
        Http::fake([
            'https://api.github.com/repos/alperates58/artwork-portal/commits/*' => Http::response([
                'message' => 'API rate limit exceeded',
            ], 403, [
                'X-RateLimit-Remaining' => '0',
            ]),
        ]);

        $exitCode = Artisan::call('portal:update:check');

        $this->assertSame(1, $exitCode);
        $this->assertDatabaseHas('portal_update_events', [
            'type' => 'check',
            'status' => 'failed',
            'trigger_source' => 'cli',
        ]);
    }

    public function test_admin_can_prepare_update_after_release_check(): void
    {
        $this->fakeGithubReleaseResponses();

        $admin = User::factory()->create(['role' => UserRole::ADMIN]);

        $this->actingAs($admin)->post(route('admin.settings.update-check'));

        $this->actingAs($admin)
            ->post(route('admin.settings.update-prepare'))
            ->assertRedirect();

        $this->assertDatabaseHas('portal_update_events', [
            'type' => 'prepare',
            'status' => 'pending',
            'trigger_source' => 'admin',
            'actor_id' => $admin->id,
            'to_version' => '1.5.0',
        ]);
    }

    public function test_settings_page_shows_release_notes_and_update_history_section(): void
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);

        PortalUpdateEvent::query()->create([
            'type' => 'check',
            'status' => 'success',
            'trigger_source' => 'admin',
            'branch' => 'main',
            'local_version' => '1.2.0',
            'remote_version' => '1.3.0',
            'from_version' => '1.2.0',
            'to_version' => '1.3.0',
            'release_title' => 'Yeni release',
            'release_summary' => 'Update release özeti',
            'change_summary' => ['Admin ekranına release notları eklendi.'],
            'changed_modules' => ['Admin Ayarları'],
            'migrations_included' => true,
            'schema_changes' => [
                'new_tables' => [],
                'new_columns' => ['portal_update_events.release_title'],
            ],
            'warnings' => ['Yedek alın.'],
            'post_update_notes' => ['php artisan portal:update'],
            'applied_migrations' => ['2026_03_25_010000_add_release_metadata_to_portal_update_events_table'],
            'release_date' => now()->toDateString(),
            'update_available' => true,
            'message' => 'GitHub üzerinde daha yeni bir sürüm bulundu.',
            'started_at' => now(),
            'completed_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get(route('admin.settings.edit'))
            ->assertOk()
            ->assertSee('GitHub Kontrolü Yap')
            ->assertSee('Gelen Değişiklikler')
            ->assertSee('Update Geçmişi')
            ->assertSee('Yeni release');
    }
}
