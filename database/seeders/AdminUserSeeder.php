<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        // Admin
        User::firstOrCreate(['email' => 'admin@portal.local'], [
            'name'      => 'Sistem Admin',
            'password'  => 'Admin1234!',
            'role'      => UserRole::ADMIN,
            'is_active' => true,
        ]);

        // Grafik
        User::firstOrCreate(['email' => 'grafik@portal.local'], [
            'name'      => 'Grafik Kullanıcı',
            'password'  => 'Grafik1234!',
            'role'      => UserRole::GRAPHIC,
            'is_active' => true,
        ]);

        // Satın Alma
        User::firstOrCreate(['email' => 'satin.alma@portal.local'], [
            'name'      => 'Satın Alma',
            'password'  => 'SatinAlma1234!',
            'role'      => UserRole::PURCHASING,
            'is_active' => true,
        ]);

        // Örnek tedarikçi firma
        $supplier = Supplier::firstOrCreate(['code' => 'TED-001'], [
            'name'      => 'Örnek Ambalaj A.Ş.',
            'email'     => 'info@ornekambalaj.com',
            'phone'     => '+90 212 000 00 00',
            'is_active' => true,
        ]);

        // Tedarikçi kullanıcı
        User::firstOrCreate(['email' => 'tedarikci@portal.local'], [
            'name'        => 'Tedarikçi Kullanıcı',
            'password'    => 'Ted1234!',
            'role'        => UserRole::SUPPLIER,
            'supplier_id' => $supplier->id,
            'is_active'   => true,
        ]);

        $this->command->info('─────────────────────────────────────────────');
        $this->command->info('  admin@portal.local        / Admin1234!');
        $this->command->info('  grafik@portal.local       / Grafik1234!');
        $this->command->info('  satin.alma@portal.local   / SatinAlma1234!');
        $this->command->info('  tedarikci@portal.local    / Ted1234!');
        $this->command->warn('  !! Üretimde tüm şifreleri değiştirin !!');
        $this->command->info('─────────────────────────────────────────────');
    }
}
