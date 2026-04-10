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
        Schema::create('products', function (Blueprint $table) {
            $table->id('productID');
            $table->string('productCode');
            $table->string('productName');
            $table->decimal('productBuyPrice', 12, 2);
            $table->decimal('productSellPrice', 12, 2);
            $table->boolean('productForGender');
            $table->text('productDesc');
            $table->unsignedBigInteger('cateID');
            $table->boolean('isDeleted')->default(false);
            $table->timestamps();
            $table->foreign('cateID')->references('categoryID')->on('categories');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
