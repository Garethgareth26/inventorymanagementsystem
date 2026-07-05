<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Cross-cutting audit trail per SAD §7.5 and ADR-004.
     *
     * Written via a single AuditLogger service (not scattered Log::info calls).
     * Captures: actor, action verb, subject type/id, old value, new value, timestamp.
     */
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->comment('Actor who performed the action; nullable in case the user is deleted');
            $table->string('action', 100)
                ->comment('Verb describing the action, e.g. stock.mutate, parameter.apply, po.status_change');
            $table->string('subject_type', 100)
                ->comment('Eloquent model class name, e.g. App\\Models\\BahanBaku');
            $table->unsignedBigInteger('subject_id')
                ->comment('ID of the subject model row');
            $table->json('old_values')->nullable()
                ->comment('Key-value snapshot of changed attributes BEFORE the action');
            $table->json('new_values')->nullable()
                ->comment('Key-value snapshot of changed attributes AFTER the action');
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamp('created_at');

            // No updated_at — audit logs are immutable by design
            $table->index(['subject_type', 'subject_id'], 'idx_audit_subject');
            $table->index(['user_id', 'created_at'], 'idx_audit_user_time');
            $table->index('action');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
