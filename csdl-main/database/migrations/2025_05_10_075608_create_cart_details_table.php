<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('cart_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cartID');
            $table->unsignedBigInteger('productID');
            $table->unsignedBigInteger('productDetailID')->nullable();
            $table->integer('quantity');
            $table->timestamps();
            $table->foreign('cartID')->references('cartID')->on('cart');
            $table->foreign('productID')->references('productID')->on('products');
            $table->foreign('productDetailID')->references('id')->on('product_details');
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cart_details');
    }
};
