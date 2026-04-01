<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patients', function (Blueprint $table) {
            $table->id();
            $table->string('file_number')->unique();
            $table->string('full_name');
            $table->string('phone');
            $table->string('phone2')->nullable();
            $table->string('email')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->enum('gender', ['male', 'female'])->nullable();
            $table->string('nationality')->nullable();
            $table->text('address')->nullable();
            $table->string('occupation')->nullable();
            $table->enum('referral_source', ['walk_in', 'social_media', 'google', 'friend', 'doctor', 'advertisement', 'other'])->nullable();
            $table->string('referred_by')->nullable();
            $table->text('medical_notes')->nullable();
            $table->string('photo')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('phone');
            $table->index('full_name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patients');
    }
};
