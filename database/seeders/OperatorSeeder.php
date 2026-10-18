<?php

namespace Database\Seeders;

use App\Enums\OperatorRole;
use App\Enums\OperatorStatus;
use App\Models\Operator;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class OperatorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Hanya menyisakan 1 akun admin resmi Pekon Margodadi
        Operator::updateOrCreate(
            ['email' => 'admin@margodadi.desa.id'],
            [
                'name' => 'Admin Margodadi',
                'username' => 'admin',
                'email' => 'admin@margodadi.desa.id',
                'password' => Hash::make('123'),
                'phone' => '0821-7516-7314',
                'avatar_url' => null, // Default avatar menggunakan logo/favicon pekon
                'role' => OperatorRole::ADMIN,
                'status' => OperatorStatus::ONLINE,
                'is_active' => true,
            ]
        );
    }
}
