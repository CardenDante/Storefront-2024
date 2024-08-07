<?php

use Fleetbase\Support\Utils;
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
        Schema::connection(config('storefront.connection.db'))->table('checkouts', function (Blueprint $table) {
            $table->string('merchant_request_id')->nullable()->unique();
            $table->string('checkout_request_id')->nullable()->unique();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection(config('storefront.connection.db'))->table('checkouts', function (Blueprint $table) {
            $table->dropColumn('merchant_request_id');
            $table->dropColumn('checkout_request_id');
        });
    }
};
