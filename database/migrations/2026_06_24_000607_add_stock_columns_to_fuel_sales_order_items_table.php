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
        Schema::table('fuel_sales_order_items', function (Blueprint $table) {
             $table->decimal('sold_liters', 15, 2)->default(0)->after('quantity_liters');
            $table->decimal('remaining_liters', 15, 2)->default(0)->after('sold_liters');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fuel_sales_order_items', function (Blueprint $table) {
           $table->dropColumn([
                'sold_liters',
                'remaining_liters',
            ]);
        });
    }
};
