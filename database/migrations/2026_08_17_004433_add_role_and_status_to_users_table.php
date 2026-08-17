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
        Schema::table('users', function (Blueprint $table) {
            // Role-based access control. 'staff' is the safe default for
            // any user created without an explicit role.
            $table->string('role', 20)->default('staff')->after('email');

            // Lets a superadmin disable an account (e.g. an employee who
            // left) without deleting their historical data/attribution.
            // Disabled accounts are force-logged-out on their next request.
            $table->boolean('is_active')->default(true)->after('role');

            // Server-side idle-timeout bookkeeping (belt-and-braces
            // alongside the client-side JS timer).
            $table->timestamp('last_activity_at')->nullable()->after('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['role', 'is_active', 'last_activity_at']);
        });
    }
};
