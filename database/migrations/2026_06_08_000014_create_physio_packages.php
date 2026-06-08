<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * F-D — prepaid physiotherapy session packages. `physio_packages` is the shared
 * catalog (no branch); `physio_package_purchases` is the per-patient enrollment
 * (branch-aware event) that tracks the session balance. A physio session can be
 * drawn against a purchase (physio_sessions.package_purchase_id) instead of being
 * billed individually. Idempotent. Mirrors the cosmetic package model.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('physio_packages')) {
            Schema::create('physio_packages', function (Blueprint $table) {
                $table->id();
                $table->string('name_ar');
                $table->string('name_en');
                $table->unsignedSmallInteger('total_sessions');
                $table->decimal('price', 8, 2)->default(0);
                $table->unsignedSmallInteger('validity_days')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('physio_package_purchases')) {
            Schema::create('physio_package_purchases', function (Blueprint $table) {
                $table->id();
                $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
                $table->unsignedBigInteger('package_id')->nullable();
                $table->unsignedBigInteger('invoice_id')->nullable();
                $table->unsignedBigInteger('doctor_id')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('branch_id')->nullable();
                $table->unsignedSmallInteger('total_sessions');
                $table->unsignedSmallInteger('sessions_used')->default(0);
                $table->decimal('amount', 8, 2)->default(0);
                $table->date('purchased_at')->nullable();
                $table->date('expires_at')->nullable();
                $table->string('status', 20)->default('active');
                $table->text('notes')->nullable();
                $table->timestamps();
                $table->index(['patient_id', 'branch_id']);
                $table->index(['patient_id', 'status']);
            });
        }

        if (Schema::hasTable('physio_sessions') && ! Schema::hasColumn('physio_sessions', 'package_purchase_id')) {
            Schema::table('physio_sessions', function (Blueprint $table) {
                $table->unsignedBigInteger('package_purchase_id')->nullable()->after('treatment_plan_id');
            });
        }

        // Seed a starter package so the feature is usable on enable.
        if (Schema::hasTable('physio_packages')) {
            $now = now();
            foreach ([
                ['name_en' => '10-Session Rehab Package', 'name_ar' => 'باقة تأهيل 10 جلسات', 'total_sessions' => 10, 'price' => 1700, 'validity_days' => 90],
                ['name_en' => '5-Session Package', 'name_ar' => 'باقة 5 جلسات', 'total_sessions' => 5, 'price' => 900, 'validity_days' => 60],
            ] as $row) {
                DB::table('physio_packages')->updateOrInsert(
                    ['name_en' => $row['name_en']],
                    array_merge($row, ['is_active' => 1, 'created_at' => $now, 'updated_at' => $now])
                );
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('physio_sessions') && Schema::hasColumn('physio_sessions', 'package_purchase_id')) {
            Schema::table('physio_sessions', function (Blueprint $table) {
                $table->dropColumn('package_purchase_id');
            });
        }
        Schema::dropIfExists('physio_package_purchases');
        Schema::dropIfExists('physio_packages');
    }
};
