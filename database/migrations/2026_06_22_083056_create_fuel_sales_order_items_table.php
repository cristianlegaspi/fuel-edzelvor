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
        Schema::create('fuel_sales_order_items', function (Blueprint $table) {
              $table->id();

            $table->foreignId('fuel_sales_order_id')
                ->constrained('fuel_sales_orders')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->string('fuel_product', 50);
            $table->decimal('unit_price', 12, 2)->default(0);
            $table->decimal('quantity_liters', 15, 2)->default(0);
            $table->decimal('line_total_amount', 18, 2)->default(0);

            $table->text('remarks')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(
                ['fuel_sales_order_id', 'fuel_product'],
                'fuel_order_product_unique'
            );

            $table->index('fuel_product');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fuel_sales_order_items');
    }
};
