<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Step 1: Add role_id column (nullable temporarily)
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('role_id')->nullable()->after('email')->constrained('roles')->nullOnDelete();
        });

        // Step 2: Migrate existing role string data to role_id
        $roleMap = DB::table('roles')->pluck('id', 'name');
        if ($roleMap->isNotEmpty()) {
            foreach ($roleMap as $name => $id) {
                DB::table('users')->where('role', $name)->update(['role_id' => $id]);
            }
        }

        // Step 3: Set default role_id for any unmapped users
        $defaultRoleId = DB::table('roles')->where('is_system', false)->value('id');
        if ($defaultRoleId) {
            DB::table('users')->whereNull('role_id')->update(['role_id' => $defaultRoleId]);
        }

        // Step 4: Drop old role column
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('role');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('editor')->after('email');
        });

        $roleMap = DB::table('roles')->pluck('name', 'id');
        foreach ($roleMap as $id => $name) {
            DB::table('users')->where('role_id', $id)->update(['role' => $name]);
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['role_id']);
            $table->dropColumn('role_id');
        });
    }
};
