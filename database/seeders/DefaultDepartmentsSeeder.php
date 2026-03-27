<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Department;
use App\Models\User;
use Illuminate\Database\Seeder;

class DefaultDepartmentsSeeder extends Seeder
{
    public function run(): void
    {
        // Satın Alma departmanı — PURCHASING rol varsayılanları
        $purchasing = Department::firstOrCreate(
            ['name' => 'Satın Alma'],
            ['permissions' => User::defaultPermissionsForRole(UserRole::PURCHASING)]
        );

        // Grafik Departmanı — GRAPHIC rol varsayılanları
        $graphic = Department::firstOrCreate(
            ['name' => 'Grafik Departmanı'],
            ['permissions' => User::defaultPermissionsForRole(UserRole::GRAPHIC)]
        );

        // Mevcut PURCHASING rolündeki kullanıcıları Satın Alma departmanına ata
        // (henüz bir departmana atanmamışsa)
        User::where('role', UserRole::PURCHASING->value)
            ->whereNull('department_id')
            ->update(['department_id' => $purchasing->id]);

        // Mevcut GRAPHIC rolündeki kullanıcıları Grafik Departmanı'na ata
        User::where('role', UserRole::GRAPHIC->value)
            ->whereNull('department_id')
            ->update(['department_id' => $graphic->id]);

        $this->command->info('Varsayılan departmanlar oluşturuldu:');
        $this->command->info('  • Satın Alma (id=' . $purchasing->id . ')');
        $this->command->info('  • Grafik Departmanı (id=' . $graphic->id . ')');
    }
}
