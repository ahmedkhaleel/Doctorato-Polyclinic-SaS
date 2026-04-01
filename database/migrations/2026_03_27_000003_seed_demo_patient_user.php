<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

return new class extends Migration
{
    /**
     * Seed a demo patient user.
     * Uses raw DB queries to avoid Eloquent model scopes (e.g. SoftDeletes)
     * which may reference columns that don't exist yet at this migration point.
     */
    public function up(): void
    {
        $patientRole = DB::table('roles')->where('name', 'patient')->first();
        if (! $patientRole) {
            return;
        }

        // Skip if user already exists
        if (DB::table('users')->where('email', 'patient@aura-clinic.net')->exists()) {
            return;
        }

        $userId = DB::table('users')->insertGetId([
            'name' => 'Ahmed Patient',
            'email' => 'patient@aura-clinic.net',
            'password' => Hash::make('123456'),
            'role_id' => $patientRole->id,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Link to first patient if available and unlinked
        $patient = DB::table('patients')->whereNull('user_id')->first();
        if ($patient) {
            DB::table('patients')->where('id', $patient->id)->update(['user_id' => $userId]);
        } else {
            // Generate file number
            $lastFileNumber = DB::table('patients')->max('file_number');
            $nextFileNumber = $lastFileNumber ? str_pad((int) $lastFileNumber + 1, 6, '0', STR_PAD_LEFT) : '000001';

            DB::table('patients')->insert([
                'full_name' => 'Ahmed Patient',
                'phone' => '0000000000',
                'email' => 'patient@aura-clinic.net',
                'user_id' => $userId,
                'file_number' => $nextFileNumber,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        $user = DB::table('users')->where('email', 'patient@aura-clinic.net')->first();
        if ($user) {
            DB::table('patients')->where('user_id', $user->id)->update(['user_id' => null]);
            DB::table('users')->where('id', $user->id)->delete();
        }
    }
};
