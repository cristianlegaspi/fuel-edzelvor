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
        Schema::create('fuel_sales_orders', function (Blueprint $table) {
              $table->id();

            $table->date('date_ordered');
            $table->string('sales_order_no')->unique();
            $table->string('supplier');

            $table->decimal('ewt_rate', 8, 5)->default(0.005);
            $table->decimal('vat_divisor', 5, 2)->default(1.12);

            $table->decimal('total_liters', 15, 2)->default(0);
            $table->decimal('gross_amount', 18, 2)->default(0);
            $table->decimal('ewt_amount', 18, 2)->default(0);
            $table->decimal('net_amount', 18, 2)->default(0);
            $table->decimal('paid_amount', 18, 2)->default(0);
            $table->decimal('balance_amount', 18, 2)->default(0);

            $table->enum('status', [
                'unpaid',
                'partial',
                'paid',
            ])->default('unpaid');

            $table->text('remarks')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index('date_ordered');
            $table->index('supplier');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fuel_sales_orders');
    }
};
