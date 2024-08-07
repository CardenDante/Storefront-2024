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
        Schema::connection(config('storefront.connection.db'))->create('mpesa_transactions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->char('uuid', 36)->nullable()->unique();
            $table->string('merchant_request_id')->unique();
            $table->string('checkout_request_id')->unique();
            $table->double('amount')->nullable();
            $table->string('mpesa_receipt_number')->nullable();
            $table->timestamp('transaction_date')->nullable();
            $table->string('phone_number')->nullable();
            $table->string('status')->nullable()->default('PENDING');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection(config('storefront.connection.db'))->dropIfExists('mpesa_transactions');
    }
};
