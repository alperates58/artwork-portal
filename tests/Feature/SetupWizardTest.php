<?php

namespace Tests\Feature;

use Tests\TestCase;

class SetupWizardTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        putenv('APP_INSTALLED=false');
        $_ENV['APP_INSTALLED'] = 'false';
        $_SERVER['APP_INSTALLED'] = 'false';
    }

    protected function tearDown(): void
    {
        putenv('APP_INSTALLED=true');
        $_ENV['APP_INSTALLED'] = 'true';
        $_SERVER['APP_INSTALLED'] = 'true';

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
}
