<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            $table->string('blood_type')->nullable()->after('gender');
            $table->string('marital_status')->nullable()->after('blood_type');
            $table->string('emergency_contact_name')->nullable()->after('occupation');
            $table->string('emergency_contact_phone')->nullable()->after('emergency_contact_name');
            $table->text('allergies')->nullable()->after('emergency_contact_phone');
            $table->text('chronic_conditions')->nullable()->after('allergies');
            $table->text('current_medications')->nullable()->after('chronic_conditions');
        });
    }

    public function down(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            $table->dropColumn([
                'blood_type',
                'marital_status',
                'emergency_contact_name',
                'emergency_contact_phone',
                'allergies',
                'chronic_conditions',
                'current_medications',
            ]);
        });
    }
};
