<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * Note: this intentionally does NOT use the WithoutModelEvents trait —
     * the Fuel* models compute their totals, stock levels, tracking numbers,
     * and tanker records inside saving/saved event hooks, so those events
     * need to actually fire while seeding.
     */
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            FuelSalesOrderSeeder::class,
            FuelCustomerPurchaseSeeder::class,
        ]);
    }
}
