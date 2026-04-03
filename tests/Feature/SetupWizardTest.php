<?php

namespace Tests\Feature;

use App\Http\Middleware\RedirectIfSetupComplete;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SetupWizardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        putenv('APP_INSTALLED=false');
        $_ENV['APP_INSTALLED'] = 'false';
        $_SERVER['APP_INSTALLED'] = 'false';
        config()->set('app.installed', false);
    }

    protected function tearDown(): void
    {
        putenv('APP_INSTALLED=true');
        $_ENV['APP_INSTALLED'] = 'true';
        $_SERVER['APP_INSTALLED'] = 'true';
        config()->set('app.installed', true);

        parent::tearDown();
    }

    public function test_spaces_step_can_be_skipped_for_local_install(): void
    {
        $response = $this->post(route('setup.save.spaces'), [
            'enable_spaces' => '0',
        ]);

        $response->assertRedirect(route('setup.step', 4));
        $response->assertSessionHas('setup.step_3_done', true);
        $response->assertSessionHas('setup.spaces.enabled', false);
    }

    public function test_database_step_rejects_unsafe_identifier_and_host_input(): void
    {
        $response = $this->from(route('setup.step', 2))->post(route('setup.save.database'), [
            'db_host' => 'mysql;unix_socket=/tmp/mysql.sock',
            'db_port' => 3306,
            'db_database' => 'portal`drop',
            'db_username' => "portal'user",
            'db_password' => 'secret',
        ]);

        $response->assertRedirect(route('setup.step', 2));
        $response->assertSessionHasErrors(['db_host', 'db_database', 'db_username']);
    }

    public function test_setup_installation_check_reads_config_value(): void
    {
        config()->set('app.installed', true);

        $this->assertTrue(RedirectIfSetupComplete::isInstalled());
    }

    public function test_setup_installation_check_fails_closed_when_users_exist(): void
    {
        User::factory()->create();

        $this->assertTrue(RedirectIfSetupComplete::isInstalled());
    }
}
