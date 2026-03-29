<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\PortalUpdateEvent;
use App\Models\SystemSetting;
use App\Models\User;
use App\Services\PortalSettings;
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
                'latest' => '1.9.2',
                'releases' => [
                    [
                        'version' => '1.9.2',
                        'title' => 'Yeni release',
                        'summary' => 'Update release ozeti',
                        'release_date' => now()->toDateString(),
                        'changes' => [
                            'Admin ekranina release notlari eklendi.',
                        ],
                        'changed_modules' => [
                            'Admin Ayarlari',
                        ],
                        'migrations_included' => true,
                        'schema_changes' => [
                            'new_tables' => [],
                            'new_columns' => [
                                'portal_update_events.release_title',
                            ],
                        ],
                        'warnings' => [
                            'Yedek alin.',
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
            'to_version' => '1.9.2',
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
            'to_version' => '1.9.2',
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
            'release_summary' => 'Update release ozeti',
            'change_summary' => ['Admin ekranina release notlari eklendi.'],
            'changed_modules' => ['Admin Ayarlari'],
            'migrations_included' => true,
            'schema_changes' => [
                'new_tables' => [],
                'new_columns' => ['portal_update_events.release_title'],
            ],
            'warnings' => ['Yedek alin.'],
            'post_update_notes' => ['php artisan portal:update'],
            'applied_migrations' => ['2026_03_25_010000_add_release_metadata_to_portal_update_events_table'],
            'release_date' => now()->toDateString(),
            'update_available' => true,
            'message' => 'GitHub uzerinde daha yeni bir surum bulundu.',
            'started_at' => now(),
            'completed_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get(route('admin.settings.edit'))
            ->assertOk()
            ->assertSee('Commit Geçmişini Yükle')
            ->assertSee("GitHub'dan Güncelle", false);
    }

    public function test_settings_page_supports_deep_linked_tabs(): void
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);

        $this->actingAs($admin)
            ->get(route('admin.settings.edit', ['tab' => 'mail']))
            ->assertOk()
            ->assertSee('Mail Sunucusu')
            ->assertSee('Mail / Exchange')
            ->assertSee('Mail Sunucusu Aksiyonları');
    }

    public function test_settings_page_supports_portal_tab(): void
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);

        $this->actingAs($admin)
            ->get(route('admin.settings.edit', ['tab' => 'portal']))
            ->assertOk()
            ->assertSee('Portal Ayarları')
            ->assertSee('Portal İşletim Parametreleri')
            ->assertSee('name="tab" value="portal"', false)
            ->assertSee('data-portal-toggle-button', false);
    }

    public function test_admin_can_store_portal_upload_limit_up_to_system_cap(): void
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);

        $this->actingAs($admin)
            ->put(route('admin.settings.update', ['tab' => 'portal']), [
                'tab' => 'portal',
                'settings_section' => 'portal',
                'portal' => [
                    'order_creation_enabled' => '1',
                    'supplier_portal_enabled' => '1',
                    'maintenance_mode' => '0',
                    'allow_manual_artwork' => '1',
                    'require_2fa_for_admin' => '0',
                    'data_transfer_allowed' => '1',
                    'max_upload_size_mb' => 1200,
                    'max_revision_count' => 10,
                    'session_timeout_minutes' => 120,
                    'order_deadline_warning_days' => 7,
                    'max_orders_per_page' => 25,
                    'audit_log_retention_days' => 365,
                ],
            ])
            ->assertRedirect(route('admin.settings.edit', ['tab' => 'portal']));

        $this->assertDatabaseHas('system_settings', [
            'key' => 'portal.max_upload_size_mb',
            'value' => '1200',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.settings.edit', ['tab' => 'portal']))
            ->assertOk()
            ->assertSee('1.200 MB / 1,2 GB', false);
    }

    public function test_admin_can_persist_disabled_portal_toggles(): void
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);

        $this->actingAs($admin)
            ->put(route('admin.settings.update', ['tab' => 'portal']), [
                'tab' => 'portal',
                'settings_section' => 'portal',
                'portal' => [
                    'order_creation_enabled' => '0',
                    'supplier_portal_enabled' => '1',
                    'maintenance_mode' => '0',
                    'allow_manual_artwork' => '1',
                    'require_2fa_for_admin' => '0',
                    'data_transfer_allowed' => '1',
                    'max_upload_size_mb' => 1200,
                    'max_revision_count' => 10,
                    'session_timeout_minutes' => 120,
                    'order_deadline_warning_days' => 7,
                    'max_orders_per_page' => 25,
                    'audit_log_retention_days' => 365,
                ],
            ])
            ->assertRedirect(route('admin.settings.edit', ['tab' => 'portal']));

        $this->assertDatabaseHas('system_settings', [
            'key' => 'portal.order_creation_enabled',
            'value' => '0',
        ]);

        $this->assertFalse(app(PortalSettings::class)->portalConfig()['order_creation_enabled']);
    }

    public function test_portal_tab_hides_spaces_storage_selector_until_spaces_connection_is_complete(): void
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);

        $this->actingAs($admin)
            ->get(route('admin.settings.edit', ['tab' => 'portal']))
            ->assertOk()
            ->assertDontSee('Aktif Depolama')
            ->assertSee('Storage / Spaces sekmesindeki');
    }

    public function test_admin_can_switch_portal_artwork_storage_to_spaces_after_spaces_configuration_is_complete(): void
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);

        foreach ([
            ['group' => 'spaces', 'key' => 'spaces.disk', 'value' => 'local'],
            ['group' => 'spaces', 'key' => 'spaces.key', 'value' => 'key-123', 'is_encrypted' => false],
            ['group' => 'spaces', 'key' => 'spaces.secret', 'value' => 'secret-123', 'is_encrypted' => false],
            ['group' => 'spaces', 'key' => 'spaces.endpoint', 'value' => 'https://nyc3.digitaloceanspaces.com'],
            ['group' => 'spaces', 'key' => 'spaces.region', 'value' => 'nyc3'],
            ['group' => 'spaces', 'key' => 'spaces.bucket', 'value' => 'portal-artworks'],
        ] as $setting) {
            SystemSetting::query()->create($setting);
        }

        $this->actingAs($admin)
            ->put(route('admin.settings.update', ['tab' => 'portal']), [
                'tab' => 'portal',
                'settings_section' => 'portal',
                'portal' => [
                    'order_creation_enabled' => '1',
                    'supplier_portal_enabled' => '1',
                    'maintenance_mode' => '0',
                    'allow_manual_artwork' => '1',
                    'require_2fa_for_admin' => '0',
                    'data_transfer_allowed' => '1',
                    'artwork_storage_disk' => 'spaces',
                    'max_upload_size_mb' => 1200,
                    'max_revision_count' => 10,
                    'session_timeout_minutes' => 120,
                    'order_deadline_warning_days' => 7,
                    'max_orders_per_page' => 25,
                    'audit_log_retention_days' => 365,
                ],
            ])
            ->assertRedirect(route('admin.settings.edit', ['tab' => 'portal']));

        $this->assertDatabaseHas('system_settings', [
            'key' => 'spaces.disk',
            'value' => 'spaces',
        ]);

        $this->assertSame('spaces', app(PortalSettings::class)->filesystemDisk());

        $this->actingAs($admin)
            ->get(route('admin.settings.edit', ['tab' => 'portal']))
            ->assertOk()
            ->assertSee('Aktif Depolama')
            ->assertSee('Depolama: Spaces');
    }

    public function test_portal_storage_selection_requires_spaces_configuration_before_spaces_can_be_selected(): void
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);

        $this->actingAs($admin)
            ->from(route('admin.settings.edit', ['tab' => 'portal']))
            ->put(route('admin.settings.update', ['tab' => 'portal']), [
                'tab' => 'portal',
                'settings_section' => 'portal',
                'portal' => [
                    'order_creation_enabled' => '1',
                    'supplier_portal_enabled' => '1',
                    'maintenance_mode' => '0',
                    'allow_manual_artwork' => '1',
                    'require_2fa_for_admin' => '0',
                    'data_transfer_allowed' => '1',
                    'artwork_storage_disk' => 'spaces',
                    'max_upload_size_mb' => 1200,
                    'max_revision_count' => 10,
                    'session_timeout_minutes' => 120,
                    'order_deadline_warning_days' => 7,
                    'max_orders_per_page' => 25,
                    'audit_log_retention_days' => 365,
                ],
            ])
            ->assertRedirect(route('admin.settings.edit', ['tab' => 'portal']))
            ->assertSessionHasErrors('portal.artwork_storage_disk');
    }

    public function test_update_actions_redirect_back_to_updates_tab(): void
    {
        $this->fakeGithubReleaseResponses();

        $admin = User::factory()->create(['role' => UserRole::ADMIN]);

        $this->actingAs($admin)
            ->post(route('admin.settings.update-check', ['tab' => 'updates']), [
                'tab' => 'updates',
            ])
            ->assertRedirect(route('admin.settings.edit', ['tab' => 'updates']));
    }

    public function test_storage_validation_redirects_back_to_storage_tab(): void
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);

        $this->actingAs($admin)
            ->put(route('admin.settings.update', ['tab' => 'storage']), [
                'tab' => 'storage',
                'settings_section' => 'storage',
                'spaces' => [
                    'disk' => 'spaces',
                    'endpoint' => 'not-a-valid-url',
                    'url' => 'also-invalid',
                ],
            ])
            ->assertRedirect(route('admin.settings.edit', ['tab' => 'storage']));
    }
}
