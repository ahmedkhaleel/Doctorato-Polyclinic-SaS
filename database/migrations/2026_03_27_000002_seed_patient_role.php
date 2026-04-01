<?php

use App\Models\Role;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Role::create([
            'name' => 'patient',
            'display_name_en' => 'Patient',
            'display_name_ar' => 'مريض',
            'permissions' => [],
            'is_system' => true,
        ]);
    }

    public function down(): void
    {
        Role::where('name', 'patient')->delete();
    }
};
