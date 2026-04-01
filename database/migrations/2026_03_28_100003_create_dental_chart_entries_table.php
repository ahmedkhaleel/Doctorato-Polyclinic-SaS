<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dental_chart_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('doctor_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('visit_id')->nullable()->constrained()->nullOnDelete();
            $table->tinyInteger('tooth_number');
            $table->string('entry_type', 50); // examination, treatment, note, follow_up, complication, media_only
            $table->string('title', 255)->nullable();
            $table->text('description')->nullable();
            $table->string('condition_before', 50)->nullable();
            $table->string('condition_after', 50)->nullable();
            $table->json('surfaces')->nullable();
            $table->decimal('cost', 10, 2)->default(0);
            $table->string('status', 30)->default('recorded'); // recorded, in_progress, completed
            $table->json('media')->nullable(); // [{type,path,caption,uploaded_at}]
            $table->date('entry_date')->nullable();
            $table->timestamps();

            $table->index(['patient_id', 'tooth_number']);
            $table->index('entry_type');
            $table->index('entry_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dental_chart_entries');
    }
};
