<?php

namespace Tests\Feature\Branch;

use App\Models\Branch;
use App\Models\Doctor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DoctorBranchAssignmentTest extends TestCase
{
    use RefreshDatabase;

    private function doctor(): Doctor
    {
        return Doctor::create(['name_ar' => 'د', 'name_en' => 'Dr', 'status' => 'active']);
    }

    public function test_new_doctor_auto_assigned_to_default_branch(): void
    {
        $d = $this->doctor();
        $this->assertTrue($d->practisesAtBranch(1));
        $this->assertSame([1], $d->branchIds());
    }

    public function test_doctor_can_practise_at_multiple_branches(): void
    {
        $b2 = Branch::create(['name_ar' => 'B2', 'name_en' => 'B2', 'code' => 'B2']);
        $d = $this->doctor();
        $d->branches()->attach($b2->id);

        $this->assertTrue($d->practisesAtBranch(1));
        $this->assertTrue($d->practisesAtBranch($b2->id));
        $this->assertEqualsCanonicalizing([1, $b2->id], $d->branchIds());
    }
}
