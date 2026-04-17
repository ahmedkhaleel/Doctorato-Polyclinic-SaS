<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('derma_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('doctor_id')->nullable()->constrained('doctors')->nullOnDelete();
            $table->foreignId('visit_id')->nullable()->constrained('visits')->nullOnDelete();
            $table->enum('session_type', ['laser', 'peel', 'phototherapy', 'injection', 'cryotherapy', 'other'])->default('other');
            $table->string('area_treated')->nullable();
            $table->string('product_used')->nullable();
            $table->json('settings_json')->nullable();
            $table->unsignedInteger('session_number')->default(1);
            $table->unsignedInteger('total_sessions')->default(1);
            $table->decimal('cost', 12, 2)->default(0);
            $table->dateTime('completed_at')->nullable();
            $table->date('next_session_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['patient_id', 'session_type']);
            $table->index('completed_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('derma_sessions');
    }
};
