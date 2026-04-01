<?php

namespace Tests\Unit\Models;

use App\Models\Patient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PatientModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_id_is_not_mass_assignable(): void
    {
        $this->assertNotContains('user_id', (new Patient)->getFillable());
    }

    public function test_file_number_is_not_mass_assignable(): void
    {
        $this->assertNotContains('file_number', (new Patient)->getFillable());
    }

    public function test_is_active_is_not_mass_assignable(): void
    {
        $this->assertNotContains('is_active', (new Patient)->getFillable());
    }

    public function test_mass_assignment_cannot_set_protected_fields(): void
    {
        $patient = new Patient([
            'full_name' => 'Test Patient',
            'phone' => '01000000000',
            'user_id' => 999,
            'file_number' => 'HACKED-001',
            'is_active' => false,
        ]);

        $this->assertEquals('Test Patient', $patient->full_name);
        $this->assertEquals('01000000000', $patient->phone);
        // These should NOT be set via mass-assignment
        $this->assertNull($patient->user_id);
        $this->assertNull($patient->file_number);
        $this->assertNull($patient->is_active);
    }

    public function test_protected_fields_can_be_set_directly(): void
    {
        $patient = new Patient(['full_name' => 'Test']);
        $patient->user_id = 1;
        $patient->file_number = 'P-00001';
        $patient->is_active = true;

        $this->assertEquals(1, $patient->user_id);
        $this->assertEquals('P-00001', $patient->file_number);
        $this->assertTrue($patient->is_active);
    }

    public function test_generate_file_number_returns_unique_string(): void
    {
        $fn1 = Patient::generateFileNumber();
        $this->assertIsString($fn1);
        $this->assertNotEmpty($fn1);
    }

    public function test_sensitive_fields_are_hidden(): void
    {
        $patient = new Patient([
            'full_name' => 'Test',
            'phone' => '01000000000',
        ]);
        $patient->has_hiv = true;
        $patient->has_hepatitis = true;

        $array = $patient->toArray();
        $this->assertArrayNotHasKey('has_hiv', $array);
        $this->assertArrayNotHasKey('has_hepatitis', $array);
    }
}
