<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();

            // Who performed the action. Nullable + nullOnDelete so a
            // deleted user's history stays in the trail (shown as
            // "System" / unknown user) instead of being wiped out.
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();

            // e.g. created, updated, deleted, restored, force_deleted,
            // login, logout, login_failed — see App\Models\AuditLog::ACTIONS.
            $table->string('action', 40);

            // Polymorphic link to the affected record (sales order,
            // payment, user, etc). Both nullable because login/logout
            // events aren't tied to any specific row.
            $table->string('auditable_type')->nullable();
            $table->unsignedBigInteger('auditable_id')->nullable();

            $table->string('description');

            // Snapshot of the changed attributes, for the "before / after"
            // view on the audit log page.
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();

            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();

            $table->timestamps();

            $table->index(['auditable_type', 'auditable_id']);
            $table->index('action');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
