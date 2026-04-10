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
        Schema::create('orders', function (Blueprint $table) {
            $table->id('orderID');
            $table->unsignedBigInteger('cusID');
            $table->unsignedBigInteger('adminID')->nullable();
            $table->unsignedBigInteger('payID');
            $table->unsignedBigInteger('staID');
            $table->string('orderPhoneNumber')->nullable();
            $table->string('shipping_street')->nullable();
            $table->string('shipping_ward')->nullable();
            $table->string('shipping_district')->nullable();
            $table->string('shipping_city')->nullable();
            $table->decimal('totalPrice', 12, 2)->default(0);
            $table->boolean('isPayment')->default(false);
            $table->timestamps();
            $table->foreign('cusID')->references('id')->on('users');
            $table->foreign('payID')->references('paymentID')->on('payments');
            $table->foreign('adminID')->references('id')->on('users');
            $table->foreign('staID')->references('statusID')->on('status');
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
