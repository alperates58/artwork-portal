<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\PortalLanguage;
use App\Models\PortalTranslation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class LocalizationManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_language_and_seed_translation_rows(): void
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);

        $this->actingAs($admin)
            ->post(route('admin.localization.languages.store'), [
                'name' => 'English',
                'code' => 'en',
                'is_active' => '1',
            ])
            ->assertRedirect(route('admin.localization.index', ['locale' => 'en']));

        $this->assertDatabaseHas('portal_languages', [
            'code' => 'en',
            'name' => 'English',
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('portal_languages', [
            'code' => 'tr',
            'is_default' => true,
        ]);

        $this->assertDatabaseHas('portal_translations', [
            'locale' => 'tr',
            'key' => 'navigation.dashboard',
        ]);

        $this->assertDatabaseHas('portal_translations', [
            'locale' => 'en',
            'key' => 'navigation.dashboard',
        ]);
    }

    public function test_admin_can_update_language_translations(): void
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);

        $this->actingAs($admin)->post(route('admin.localization.languages.store'), [
            'name' => 'English',
            'code' => 'en',
            'is_active' => '1',
        ]);

        $this->actingAs($admin)
            ->put(route('admin.localization.translations.update', ['locale' => 'en']), [
                'translations' => [
                    'navigation.dashboard' => 'Dashboard EN',
                    'general.search' => 'Search',
                ],
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('portal_translations', [
            'locale' => 'en',
            'key' => 'navigation.dashboard',
            'value' => 'Dashboard EN',
        ]);

        $this->assertDatabaseHas('portal_translations', [
            'locale' => 'en',
            'key' => 'general.search',
            'value' => 'Search',
        ]);
    }

    public function test_admin_can_open_localization_page(): void
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);

        $this->actingAs($admin)
            ->post(route('admin.localization.languages.store'), [
                'name' => 'English',
                'code' => 'en',
                'is_active' => '1',
            ]);

        $this->actingAs($admin)
            ->get(route('admin.localization.index', ['locale' => 'en']))
            ->assertOk()
            ->assertSee('Dil Ayarları')
            ->assertSee('English')
            ->assertSee('Çeviri Editörü');
    }

    public function test_admin_can_import_a_new_language_from_json(): void
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);

        $file = UploadedFile::fake()->createWithContent(
            'translations.json',
            json_encode([
                'meta' => [
                    'locale' => 'de',
                    'name' => 'Deutsch',
                ],
                'translations' => [
                    'navigation.dashboard' => 'Instrumententafel',
                    'general.search' => 'Suchen',
                ],
            ], JSON_UNESCAPED_UNICODE)
        );

        $this->actingAs($admin)
            ->post(route('admin.localization.import'), [
                'locale' => 'de',
                'language_name' => 'Deutsch',
                'translation_file' => $file,
            ])
            ->assertRedirect(route('admin.localization.index', ['locale' => 'de']));

        $this->assertDatabaseHas('portal_languages', [
            'code' => 'de',
            'name' => 'Deutsch',
        ]);

        $this->assertDatabaseHas('portal_translations', [
            'locale' => 'de',
            'key' => 'navigation.dashboard',
            'value' => 'Instrumententafel',
        ]);
    }

    public function test_user_can_switch_active_locale(): void
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);

        PortalLanguage::query()->create([
            'code' => 'tr',
            'name' => 'Türkçe',
            'is_active' => true,
            'is_default' => true,
            'sort_order' => 1,
        ]);

        PortalLanguage::query()->create([
            'code' => 'en',
            'name' => 'English',
            'is_active' => true,
            'is_default' => false,
            'sort_order' => 2,
        ]);

        $this->actingAs($admin)
            ->from(route('dashboard'))
            ->post(route('locale.switch'), ['locale' => 'en'])
            ->assertRedirect(route('dashboard'));

        $this->assertSame('en', session('portal_locale'));
    }
}
