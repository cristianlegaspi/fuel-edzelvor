<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * IDOR hardening for the printable/shareable SOA links
 * (/fuel-customer-purchases/{id}/soa and /fuel-payments/soa/{id}/print).
 *
 * Those URLs get opened in a new tab, printed, and emailed to people
 * outside the app (customers, suppliers) — so the identifier in them
 * needs to not be an easily-incremented auto-increment integer. Anyone
 * who ever received one legitimate link could otherwise just change the
 * number and walk through every other customer's or supplier's
 * financial statement.
 *
 * public_id is a ULID: unique, unguessable, and sortable-by-creation
 * (unlike a random UUID) if that's ever useful for debugging/logs.
 * The numeric primary key is untouched, so nothing else in the app
 * changes — only the two SOA routes bind on this column instead.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fuel_customer_purchases', function (Blueprint $table) {
            $table->ulid('public_id')->nullable()->unique()->after('id');
        });

        Schema::table('fuel_sales_orders', function (Blueprint $table) {
            $table->ulid('public_id')->nullable()->unique()->after('id');
        });

        // Backfill existing rows so nothing is left without a public_id.
        DB::table('fuel_customer_purchases')->whereNull('public_id')->orderBy('id')->chunkById(200, function ($rows) {
            foreach ($rows as $row) {
                DB::table('fuel_customer_purchases')->where('id', $row->id)->update(['public_id' => (string) Str::ulid()]);
            }
        });

        DB::table('fuel_sales_orders')->whereNull('public_id')->orderBy('id')->chunkById(200, function ($rows) {
            foreach ($rows as $row) {
                DB::table('fuel_sales_orders')->where('id', $row->id)->update(['public_id' => (string) Str::ulid()]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('fuel_customer_purchases', function (Blueprint $table) {
            $table->dropColumn('public_id');
        });

        Schema::table('fuel_sales_orders', function (Blueprint $table) {
            $table->dropColumn('public_id');
        });
    }
};
