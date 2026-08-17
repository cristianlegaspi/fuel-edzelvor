<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fuel_tanker_records', function (Blueprint $table) {
            $table->id();

            $table->foreignId('fuel_customer_purchase_id')
                ->unique()
                ->constrained('fuel_customer_purchases')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->date('date_delivered')->nullable();
            $table->string('driver_name')->nullable();

            $table->decimal('cut_off', 15, 2)->default(0);
            $table->decimal('driver_salary', 15, 2)->default(0);

            $table->date('date_paid_driver')->nullable();

            $table->decimal('other_expenses', 15, 2)->default(0);
            $table->text('other_expenses_details')->nullable();

            $table->decimal('net_income', 15, 2)->default(0);

            $table->text('remarks')->nullable();

            $table->softDeletes();
            $table->timestamps();

            $table->index('date_delivered');
            $table->index('driver_name');
        });

        /*
        |--------------------------------------------------------------------------
        | Automatically create tanker records for existing purchases
        |--------------------------------------------------------------------------
        */
        $now = now();

        DB::table('fuel_customer_purchases')
            ->whereNull('deleted_at')
            ->whereNotNull('tanker_details')
            ->where('tanker_details', '!=', '')
            ->orderBy('id')
            ->chunkById(500, function ($purchases) use ($now): void {
                $records = [];

                foreach ($purchases as $purchase) {
                    $records[] = [
                        'fuel_customer_purchase_id' => $purchase->id,
                        'cut_off' => 0,
                        'driver_salary' => 0,
                        'other_expenses' => 0,
                        'net_income' => 0,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }

                if ($records !== []) {
                    DB::table('fuel_tanker_records')
                        ->insertOrIgnore($records);
                }
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('fuel_tanker_records');
    }
};