<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class ClinicServiceFieldsSeeder extends Seeder
{
    /**
     * Previously handled clinic-specific service fields (prices, sessions, commissions).
     * All logic has been merged into ServiceSeeder. This seeder is kept for backward compatibility.
     */
    public function run(): void
    {
        // Service clinic fields (prices, sessions, supply costs, commissions)
        // are now seeded directly by ServiceSeeder.
        return;
    }
}
