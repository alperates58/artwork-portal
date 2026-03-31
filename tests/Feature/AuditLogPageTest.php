<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditLogPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_logs_index_renders_when_stock_logs_exist(): void
    {
        $admin = User::factory()->admin()->create();

        AuditLog::create([
            'user_id' => $admin->id,
            'action' => 'stock_card.create',
            'model_type' => 'stock_card',
            'model_id' => 1,
            'ip_address' => '127.0.0.1',
            'payload' => [
                'product_code' => 'STK-001',
                'description' => 'Test stok kartı',
            ],
            'created_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get(route('admin.logs.index'))
            ->assertOk()
            ->assertSee('Stok', false);
    }
}
