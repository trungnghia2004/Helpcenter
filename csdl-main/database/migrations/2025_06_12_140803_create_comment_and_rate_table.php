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
        Schema::create('comment_and_rate', function (Blueprint $table) {
            $table->id('idComment');
            $table->unsignedBigInteger('cusID');
            $table->unsignedBigInteger('productID');
            $table->text('contentComment');
            $table->integer('rate');
            $table->timestamps();

            $table->foreign('cusID')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('productID')->references('productID')->on('products')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('comment_and_rate');
    }
};
