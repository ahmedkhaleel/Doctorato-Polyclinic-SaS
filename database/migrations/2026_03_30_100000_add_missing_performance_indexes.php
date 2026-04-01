<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add missing composite indexes for frequently queried columns.
 *
 * These indexes target the most common query patterns:
 * - Dashboard stats: WHERE status = ? AND created_at BETWEEN ...
 * - Patient lookup: WHERE patient_id = ? AND status = ?
 * - Doctor schedules: WHERE doctor_id = ? AND status = ?
 * - Search: WHERE phone = ? / WHERE email = ?
 *
 * Expected improvement: 10-50x on large datasets.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ─── BOOKINGS ────────────────────────────────────────────
        // Existing: index(patient_id), index(source), FK(doctor_id)
        // Missing:  (patient_id, status), (status, created_at), (doctor_id, status)
        Schema::table('bookings', function (Blueprint $table) {
            $table->index(['patient_id', 'status'], 'bookings_patient_status_index');
            $table->index(['status', 'created_at'], 'bookings_status_created_index');
            $table->index(['doctor_id', 'status'], 'bookings_doctor_status_index');
        });

        // ─── INVOICES ────────────────────────────────────────────
        // Existing: index(patient_id), index([status, created_at]), index(booking_id)
        // Missing:  (patient_id, status)
        Schema::table('invoices', function (Blueprint $table) {
            $table->index(['patient_id', 'status'], 'invoices_patient_status_index');
        });

        // ─── VISITS ──────────────────────────────────────────────
        // Existing: index([visit_date, status]), index([doctor_id, visit_date]),
        //           index([module, visit_date]), index([module, status, visit_date]),
        //           index(booking_id)
        // Missing:  (patient_id, status)
        Schema::table('visits', function (Blueprint $table) {
            $table->index(['patient_id', 'status'], 'visits_patient_status_index');
        });

        // ─── PAYMENTS ────────────────────────────────────────────
        // Existing: FK(invoice_id), FK(patient_id), index(payment_date)
        // Missing:  (patient_id, created_at) — for patient payment history queries
        Schema::table('payments', function (Blueprint $table) {
            $table->index(['patient_id', 'created_at'], 'payments_patient_created_index');
        });

        // ─── PATIENTS ────────────────────────────────────────────
        // Existing: index(phone), index(full_name), unique(user_id), unique(file_number)
        // Missing:  (email) — for login lookup and duplicate detection
        Schema::table('patients', function (Blueprint $table) {
            $table->index('email', 'patients_email_index');
        });

        // ─── LEADS ───────────────────────────────────────────────
        // Existing: index(status), index(assigned_to), index(phone), index(email),
        //           index(priority), index(next_follow_up_at)
        // Missing:  (status, assigned_to) — for "my leads by status" dashboard queries
        Schema::table('leads', function (Blueprint $table) {
            $table->index(['status', 'assigned_to'], 'leads_status_assigned_index');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropIndex('bookings_patient_status_index');
            $table->dropIndex('bookings_status_created_index');
            $table->dropIndex('bookings_doctor_status_index');
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropIndex('invoices_patient_status_index');
        });

        Schema::table('visits', function (Blueprint $table) {
            $table->dropIndex('visits_patient_status_index');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex('payments_patient_created_index');
        });

        Schema::table('patients', function (Blueprint $table) {
            $table->dropIndex('patients_email_index');
        });

        Schema::table('leads', function (Blueprint $table) {
            $table->dropIndex('leads_status_assigned_index');
        });
    }
};
