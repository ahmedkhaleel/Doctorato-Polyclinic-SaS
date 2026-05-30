<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('contraception_records')) {
            return;
        }

        Schema::create('contraception_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('doctor_id')->nullable()->constrained('doctors')->nullOnDelete();
            $table->string('method');                              // pills / iud / implant / injection / ...
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->date('follow_up_date')->nullable();
            $table->string('status', 12)->default('active');       // active / stopped
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['patient_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contraception_records');
    }
};
