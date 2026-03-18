<?php

namespace Tests\Feature;

use Tests\TestCase;

class SetupWizardTest extends TestCase
{
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
