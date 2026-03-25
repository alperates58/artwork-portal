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

    public function test_admin_can_trigger_github_update_check_from_settings(): void
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
        ]);

        $admin = User::factory()->create(['role' => UserRole::ADMIN]);

        $this->actingAs($admin)
            ->post(route('admin.settings.update-check'))
            ->assertRedirect();

        $this->assertDatabaseHas('portal_update_events', [
            'type' => 'check',
            'status' => 'success',
            'trigger_source' => 'admin',
            'actor_id' => $admin->id,
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

    public function test_settings_page_shows_update_history_section(): void
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);

        PortalUpdateEvent::query()->create([
            'type' => 'check',
            'status' => 'success',
            'trigger_source' => 'admin',
            'branch' => 'main',
            'local_version' => 'abc1234',
            'remote_version' => 'def5678',
            'update_available' => true,
            'message' => 'GitHub uzerinde daha yeni bir commit bulundu.',
            'started_at' => now(),
            'completed_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get(route('admin.settings.edit'))
            ->assertOk()
            ->assertSee('GitHub Kontrolu Yap')
            ->assertSee('Update Gecmisi')
            ->assertSee('GitHub Check');
    }
}
