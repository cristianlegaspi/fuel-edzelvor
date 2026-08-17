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
        Schema::create('fuel_customer_purchases', function (Blueprint $table) {
            $table->id();
            
            $table->foreignId('fuel_sales_order_id')
                ->nullable()
                ->constrained('fuel_sales_orders')
                ->nullOnDelete()
                ->cascadeOnUpdate();

            $table->date('date_ordered')->nullable();
            $table->string('sales_order_no')->nullable();
            $table->string('supplier')->nullable();

            $table->string('customer')->nullable();
            $table->string('tanker_details')->nullable();
            $table->string('order_no_details')->nullable();

            $table->decimal('total_liters', 18, 2)->default(0);
            $table->decimal('total_subtotal_without_freight', 18, 2)->default(0);
            $table->decimal('total_payable_to_supplier', 18, 2)->default(0);
            $table->decimal('total_subtotal_with_freight', 18, 2)->default(0);
            $table->decimal('total_selling_amount', 18, 2)->default(0);
            $table->decimal('total_less_ewt', 18, 2)->default(0);
            $table->decimal('total_payables', 18, 2)->default(0);

            $table->decimal('garage', 18, 2)->default(0);
            $table->decimal('agent_comm', 18, 2)->default(0);
            $table->decimal('receiver', 18, 2)->default(0);
            $table->decimal('others_amount', 18, 2)->default(0);
            $table->text('others_comment')->nullable();

            $table->decimal('net_income', 18, 2)->default(0);
            $table->decimal('balance_short_over', 18, 2)->default(0);

            $table->decimal('payment_amount', 18, 2)->default(0);
            $table->date('date_of_payment')->nullable();
            $table->string('payment_details')->nullable();
            $table->string('cheque_details')->nullable();

            $table->date('atl_date')->nullable();
            $table->string('atl_no')->nullable();

            $table->enum('status', [
                'unpaid',
                'partial',
                'paid',
                'overpaid',
            ])->default('unpaid');

            $table->text('remarks')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index('fuel_sales_order_id');
            $table->index('date_ordered');
            $table->index('sales_order_no');
            $table->index('supplier');
            $table->index('customer');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fuel_customer_purchases');
    }
};
