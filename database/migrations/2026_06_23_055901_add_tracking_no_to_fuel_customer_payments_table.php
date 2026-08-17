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
        Schema::table('fuel_customer_payments', function (Blueprint $table) {
            $table->string('customer_payment_tracking_no')
                ->nullable()
                ->unique()
                ->after('id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fuel_customer_payments', function (Blueprint $table) {
            $table->dropUnique(['customer_payment_tracking_no']);
            $table->dropColumn('customer_payment_tracking_no');
        });
    }
};
