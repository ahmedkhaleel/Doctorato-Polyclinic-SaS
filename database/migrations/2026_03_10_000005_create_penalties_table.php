<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('penalties', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->enum('type', ['penalty', 'reward']);
            $table->decimal('amount', 10, 2);
            $table->text('reason')->nullable();
            $table->date('date');
            $table->boolean('applied_to_salary')->default(false);
            $table->foreignId('salary_slip_id')->nullable()->constrained('salary_slips')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('employee_id');
            $table->index('type');
            $table->index('applied_to_salary');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('penalties');
    }
};
