<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $password = static function (string $key): string {
            $value = env($key);

            if (!is_string($value) || strlen($value) < 12) {
                throw new \RuntimeException("{$key} wajib diatur dengan minimal 12 karakter sebelum menjalankan seeder.");
            }

            return $value;
        };

        $admin = User::updateOrCreate(
            ['email' => 'admin@celoe.test'],
            [
                'name' => 'Administrator CeLOE',
                'password' => Hash::make($password('SEED_ADMIN_PASSWORD')),
                'role' => 'admin',
                'nomor_induk' => 'ADM001',
            ]
        );
        $admin->markEmailAsVerified();

        $dosen = User::updateOrCreate(
            ['email' => 'dosen@celoe.test'],
            [
                'name' => 'Dosen CeLOE',
                'password' => Hash::make($password('SEED_DOSEN_PASSWORD')),
                'role' => 'dosen',
                'nomor_induk' => '198001001',
            ]
        );
        $dosen->markEmailAsVerified();

        $mahasiswa = User::updateOrCreate(
            ['email' => 'mahasiswa@celoe.test'],
            [
                'name' => 'Mahasiswa CeLOE',
                'password' => Hash::make($password('SEED_MAHASISWA_PASSWORD')),
                'role' => 'mahasiswa',
                'nomor_induk' => '1202220001',
            ]
        );
        $mahasiswa->markEmailAsVerified();
    }
}
