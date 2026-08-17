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
        Schema::create('fuel_payments', function (Blueprint $table) {
              $table->id();

            $table->foreignId('fuel_sales_order_id')
                ->constrained('fuel_sales_orders')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->date('payment_date')->nullable();
            $table->decimal('amount', 18, 2)->default(0);
            $table->string('reference_no')->nullable();
            $table->string('payment_method')->nullable();
            $table->text('remarks')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index('fuel_sales_order_id');
            $table->index('payment_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fuel_payments');
    }
};
