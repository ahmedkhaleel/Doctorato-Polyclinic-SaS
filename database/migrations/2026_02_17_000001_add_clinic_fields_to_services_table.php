<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->decimal('price', 10, 2)->nullable()->after('status');
            $table->decimal('price_after_discount', 10, 2)->nullable()->after('price');
            $table->integer('default_sessions')->nullable()->after('price_after_discount');
            $table->integer('session_duration_minutes')->nullable()->after('default_sessions');
            $table->decimal('supply_cost', 10, 2)->nullable()->default(0)->after('session_duration_minutes');
            $table->decimal('doctor_commission_percentage', 5, 2)->nullable()->after('supply_cost');
            $table->text('clinic_notes')->nullable()->after('doctor_commission_percentage');
        });
    }

    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->dropColumn([
                'price', 'price_after_discount', 'default_sessions',
                'session_duration_minutes', 'supply_cost',
                'doctor_commission_percentage', 'clinic_notes',
            ]);
        });
    }
};
