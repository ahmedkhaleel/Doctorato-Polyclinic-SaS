<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Multi-branch B0 — the branches registry. Seeds the "Main Branch" (id=1) that
 * every existing row is backfilled to, so single-clinic behaviour is preserved.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('branches')) {
            Schema::create('branches', function (Blueprint $table) {
                $table->id();
                $table->string('name_ar');
                $table->string('name_en');
                $table->string('code', 20)->unique();
                $table->string('phone', 30)->nullable();
                $table->string('address')->nullable();
                $table->string('timezone', 60)->default('Africa/Cairo');
                $table->boolean('is_active')->default(true);
                $table->boolean('is_default')->default(false);
                $table->timestamps();
            });
        }

        // Seed the Main Branch (id=1) — idempotent.
        DB::table('branches')->updateOrInsert(
            ['id' => 1],
            [
                'name_ar' => 'الفرع الرئيسي',
                'name_en' => 'Main Branch',
                'code' => 'MAIN',
                'timezone' => 'Africa/Cairo',
                'is_active' => true,
                'is_default' => true,
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('branches');
    }
};
