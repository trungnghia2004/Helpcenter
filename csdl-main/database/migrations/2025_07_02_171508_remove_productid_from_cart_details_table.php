<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('cart_details', function (Blueprint $table) {
            // Xóa khóa ngoại trước
            $table->dropForeign(['productID']);

            // Xóa cột productID
            $table->dropColumn('productID');
        });
    }

    public function down(): void
    {
        Schema::table('cart_details', function (Blueprint $table) {
            // Thêm lại cột productID
            $table->unsignedBigInteger('productID')->after('cartID');

            // Khôi phục khóa ngoại
            $table->foreign('productID')->references('productID')->on('products');
        });
    }
};
