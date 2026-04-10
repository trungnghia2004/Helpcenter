<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        DB::table('users')->updateOrInsert(
            ['username' => "ngh\u{0129}alc123"],
            [
                'name' => "La Trung Ngh\u{0129}a",
                'email' => 'kurobakarma@gmail.com',
                'street_address' => 'Lao Cai',
                'phone' => '1951941056',
                'password' => Hash::make('Thideptrai@12'),
                'role' => 'customer',
            ]
        );

        DB::table('users')->updateOrInsert(
            ['username' => 'teolc123'],
            [
                'name' => "La Trung Ngh\u{1EBD}o",
                'email' => 'ad@gmail.com',
                'street_address' => 'TQB',
                'phone' => '0709292929',
                'password' => Hash::make('123456789'),
                'role' => 'admin',
            ]
        );

        foreach (['S', 'M', 'L', 'XL', 'XXL', 'XXXL'] as $sizeName) {
            DB::table('sizes')->updateOrInsert(
                ['sizeName' => $sizeName],
                ['sizeName' => $sizeName]
            );
        }

        foreach (['Do', 'Xanh nuoc', 'Hong', 'Tim', 'Den', 'Trang', 'Xanh la', 'Cam'] as $colorName) {
            DB::table('colors')->updateOrInsert(
                ['colorName' => $colorName],
                ['colorName' => $colorName]
            );
        }
    }
}
