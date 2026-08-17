<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fuel_tanker_records', function (Blueprint $table) {
            $table->string('cut_off', 100)
                ->nullable()
                ->change();
        });
    }

    public function down(): void
    {
        Schema::table('fuel_tanker_records', function (Blueprint $table) {
            $table->decimal('cut_off', 15, 2)
                ->default(0)
                ->change();
        });
    }
};