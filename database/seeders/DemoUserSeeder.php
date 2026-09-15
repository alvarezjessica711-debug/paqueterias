<?php

namespace Database\Seeders;

use App\Models\Residente;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $maria = Residente::query()->where('nombre', 'María Fernanda López')->first();

        User::query()->updateOrCreate(
            ['email' => 'juan.piedradita@valledesanremo.test'],
            [
                'name' => 'Juan Piedradita',
                'password' => Hash::make('ValleSanRemo2026!'),
                'rol' => 'admin',
                'residente_id' => null,
            ],
        );

        User::query()->updateOrCreate(
            ['email' => 'vigilante@valledesanremo.test'],
            [
                'name' => 'Vigilante Valle de San Remo',
                'password' => Hash::make('ValleSanRemo2026!'),
                'rol' => 'vigilante',
                'residente_id' => null,
            ],
        );

        if ($maria) {
            User::query()->updateOrCreate(
                ['email' => 'maria.lopez@valledesanremo.test'],
                [
                    'name' => 'María Fernanda López',
                    'password' => Hash::make('ValleSanRemo2026!'),
                    'rol' => 'residente',
                    'residente_id' => $maria->id,
                ],
            );
        }
    }
}
