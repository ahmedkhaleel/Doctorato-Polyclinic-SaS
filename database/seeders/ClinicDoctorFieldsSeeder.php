<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class ClinicDoctorFieldsSeeder extends Seeder
{
    /**
     * Previously handled clinic-specific doctor fields (commissions, fees, schedules, user accounts).
     * All logic has been merged into DoctorSeeder. This seeder is kept for backward compatibility.
     */
    public function run(): void
    {
        // All doctor data (including fees, commissions, user accounts, and schedules)
        // is now seeded directly by DoctorSeeder.
    }
}
