<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class StatusSeeder extends Seeder
{
    public function run(): void{
        DB::table("status")->insert([
            "statusValue" => "Đang chờ duyệt",
        ]);
        DB::table("status")->insert([
            "statusValue" => "Đã duyệt",
        ]);
        DB::table("status")->insert([
            "statusValue" => "Đang giao hàng",
        ]);
        DB::table("status")->insert([
            "statusValue" => "Đã giao hàng",
        ]);
        DB::table("status")->insert([
            "statusValue" => "Đã hủy",
        ]);
    }
}
