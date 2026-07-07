<?php

use App\Models\AuditLog;
use App\Models\BahanBaku;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// ─── AuditLogger::log() ───────────────────────────────────────────────────────

describe('AuditLogger::log', function () {
    it('writes a row to audit_logs with correct actor, action, and subject', function () {
        $actor = User::factory()->create();
        $subject = BahanBaku::factory()->create();

        $log = AuditLogger::log(
            actor: $actor,
            action: 'bahan_baku.create',
            subject: $subject,
            old: null,
            new: $subject->toArray()
        );

        expect($log)->toBeInstanceOf(AuditLog::class);

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $actor->id,
            'action' => 'bahan_baku.create',
            'subject_type' => BahanBaku::class,
            'subject_id' => $subject->id,
        ]);
    });

    it('persists old_values as null for create operations', function () {
        $actor = User::factory()->create();
        $subject = BahanBaku::factory()->create();

        AuditLogger::log($actor, 'bahan_baku.create', $subject, null, ['kode' => 'BB-001']);

        $row = AuditLog::latest('id')->first();

        expect($row)->not->toBeNull()
            ->and($row->old_values)->toBeNull();
    });

    it('persists new_values as null for delete operations', function () {
        $actor = User::factory()->create();
        $subject = BahanBaku::factory()->create();
        $old = $subject->toArray();

        AuditLogger::log($actor, 'bahan_baku.delete', $subject, $old, null);

        $row = AuditLog::latest('id')->first();

        expect($row->new_values)->toBeNull()
            ->and($row->old_values)->toBeArray()
            ->and($row->old_values['id'])->toBe($subject->id);
    });

    it('stores both old and new values for update operations', function () {
        $actor = User::factory()->create();
        $subject = BahanBaku::factory()->create(['harga_satuan' => 9500]);
        $old = ['harga_satuan' => 9500];
        $new = ['harga_satuan' => 11000];

        AuditLogger::log($actor, 'bahan_baku.update', $subject, $old, $new);

        $row = AuditLog::latest('id')->first();

        expect($row->old_values['harga_satuan'])->toBe(9500)
            ->and($row->new_values['harga_satuan'])->toBe(11000);
    });

    it('accepts Eloquent model instances for old/new and converts to array', function () {
        $actor = User::factory()->create();
        $old = BahanBaku::factory()->create();
        $new = BahanBaku::factory()->create(['nama' => 'Updated Name']);

        AuditLogger::log($actor, 'bahan_baku.update', $old, $old, $new);

        $row = AuditLog::latest('id')->first();

        expect($row->old_values)->toBeArray()
            ->and($row->new_values)->toBeArray()
            ->and($row->new_values['nama'])->toBe('Updated Name');
    });

    it('stores the correct subject_type (full class name)', function () {
        $actor = User::factory()->create();
        $subject = BahanBaku::factory()->create();

        AuditLogger::log($actor, 'test.action', $subject, null, null);

        $row = AuditLog::latest('id')->first();

        expect($row->subject_type)->toBe(BahanBaku::class);
    });

    it('handles request context when request is null', function () {
        $actor = User::factory()->create();
        $subject = BahanBaku::factory()->create();

        $log = AuditLogger::log($actor, 'test.action', $subject, null, null, null);

        expect($log->ip_address)->toBeNull()
            ->and($log->user_agent)->toBeNull();
    });

    it('audit log has no updated_at column (immutable by design)', function () {
        $actor = User::factory()->create();
        $subject = BahanBaku::factory()->create();

        AuditLogger::log($actor, 'test.action', $subject, null, null);

        $row = AuditLog::latest('id')->first();

        // The model defines UPDATED_AT = null; the column should not be set
        expect(AuditLog::UPDATED_AT)->toBeNull();
        expect(isset($row->updated_at))->toBeFalse();
    });
});
