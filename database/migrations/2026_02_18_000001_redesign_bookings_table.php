<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->foreignId('patient_id')->nullable()->after('id')->constrained('patients')->nullOnDelete();
            $table->string('booking_number')->nullable()->unique()->after('patient_id');
            $table->string('source', 20)->default('website')->after('booking_number');
            $table->foreignId('invoice_id')->nullable()->after('admin_notes')->constrained('invoices')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->after('invoice_id')->constrained('users')->nullOnDelete();

            $table->index('patient_id');
            $table->index('source');
        });

        // Change status from enum to varchar for flexibility
        DB::statement("ALTER TABLE bookings MODIFY COLUMN status VARCHAR(30) DEFAULT 'unconfirmed'");
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropForeign(['patient_id']);
            $table->dropForeign(['invoice_id']);
            $table->dropForeign(['created_by']);
            $table->dropIndex(['patient_id']);
            $table->dropIndex(['source']);
            $table->dropColumn(['patient_id', 'booking_number', 'source', 'invoice_id', 'created_by']);
        });

        DB::statement("ALTER TABLE bookings MODIFY COLUMN status ENUM('new','contacted','confirmed','completed','cancelled') DEFAULT 'new'");
    }
};
