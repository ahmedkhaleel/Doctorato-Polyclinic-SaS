<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Multi-branch B3 — assign staff and doctors to branches. Every existing user
 * and doctor is backfilled to the Main Branch (primary), preserving access.
 */
return new class extends Migration
{
    public function up(): void
    {
        $default = (int) config('branches.default_id', 1);

        if (! Schema::hasTable('branch_user')) {
            Schema::create('branch_user', function (Blueprint $table) {
                $table->id();
                $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->boolean('is_primary')->default(false);
                $table->timestamps();
                $table->unique(['branch_id', 'user_id']);
            });

            // Backfill: every user → Main Branch (primary).
            foreach (DB::table('users')->pluck('id') as $uid) {
                DB::table('branch_user')->insert([
                    'branch_id' => $default, 'user_id' => $uid, 'is_primary' => true,
                    'created_at' => now(), 'updated_at' => now(),
                ]);
            }
        }

        if (! Schema::hasTable('branch_doctor')) {
            Schema::create('branch_doctor', function (Blueprint $table) {
                $table->id();
                $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
                $table->foreignId('doctor_id')->constrained('doctors')->cascadeOnDelete();
                $table->timestamps();
                $table->unique(['branch_id', 'doctor_id']);
            });

            foreach (DB::table('doctors')->pluck('id') as $did) {
                DB::table('branch_doctor')->insert([
                    'branch_id' => $default, 'doctor_id' => $did,
                    'created_at' => now(), 'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('branch_user');
        Schema::dropIfExists('branch_doctor');
    }
};
