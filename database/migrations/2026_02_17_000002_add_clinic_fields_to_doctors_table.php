<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('doctors', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('id')->constrained('users')->nullOnDelete();
            $table->string('phone')->nullable()->after('qualifications_en');
            $table->string('email')->nullable()->after('phone');
            $table->decimal('default_commission_percentage', 5, 2)->nullable()->default(0)->after('email');
            $table->decimal('consultation_fee', 10, 2)->nullable()->default(0)->after('default_commission_percentage');
            $table->text('clinic_notes')->nullable()->after('consultation_fee');
        });
    }

    public function down(): void
    {
        Schema::table('doctors', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn([
                'user_id', 'phone', 'email',
                'default_commission_percentage', 'consultation_fee', 'clinic_notes',
            ]);
        });
    }
};
