<?php

namespace App\Services;

use App\Models\Patient;

class PatientFileNumberGenerator
{
    /**
     * Generate the next patient file number (P-00001 format).
     */
    public function generate(): string
    {
        return Patient::generateFileNumber();
    }
}
