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
        Schema::create('fuel_customer_purchase_items', function (Blueprint $table) {
          $table->id();

            $table->foreignId('fuel_customer_purchase_id')
                ->constrained('fuel_customer_purchases')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->string('fuel_product', 50)->nullable();

            $table->decimal('liters', 18, 2)->default(0);

            $table->decimal('freight_alwin', 18, 3)->default(0);
            $table->decimal('freight_tanker', 18, 3)->default(0);
            $table->decimal('freight_040', 18, 3)->default(0.400);

            $table->decimal('amount_per_liter', 18, 3)->default(0);
            $table->decimal('subtotal_without_freight', 18, 2)->default(0);

            $table->decimal('payable_to_supplier', 18, 2)->default(0);
            $table->decimal('subtotal_with_freight', 18, 2)->default(0);

            $table->decimal('selling_price', 18, 3)->default(0);
            $table->decimal('subtotal_selling_price', 18, 2)->default(0);

            $table->decimal('ewt_rate', 18, 5)->default(0.01000);
            $table->decimal('less_ewt_rate', 18, 2)->default(0);

            $table->decimal('payables', 18, 2)->default(0);
            $table->decimal('net_income', 18, 2)->default(0);

            $table->text('remarks')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index('fuel_customer_purchase_id');
            $table->index('fuel_product');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fuel_customer_purchase_items');
    }
};
