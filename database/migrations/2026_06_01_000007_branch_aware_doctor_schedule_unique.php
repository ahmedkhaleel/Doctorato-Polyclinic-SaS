<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Make the (doctor_id, day_of_week) unique branch-aware so a doctor can work the
 * same day at two branches. The composite LEADS with doctor_id so it still backs
 * the doctor_id foreign key (letting us drop the old unique). Data-safe + idempotent.
 */
return new class extends Migration
{
    private function hasIndex(string $name): bool
    {
        return collect(DB::select('SHOW INDEX FROM doctor_schedules'))->contains(fn ($i) => $i->Key_name === $name);
    }

    public function up(): void
    {
        if (! Schema::hasTable('doctor_schedules')) {
            return;
        }

        // Drop any earlier wrong-order composite so we can re-add it doctor_id-first.
        if ($this->hasIndex('doctor_schedules_branch_doctor_day_unique')) {
            Schema::table('doctor_schedules', fn (Blueprint $t) => $t->dropUnique('doctor_schedules_branch_doctor_day_unique'));
        }

        // doctor_id-leading composite → uniqueness per branch AND backs the FK index.
        Schema::table('doctor_schedules', function (Blueprint $t) {
            $t->unique(['doctor_id', 'day_of_week', 'branch_id'], 'doctor_schedules_branch_doctor_day_unique');
        });

        // Now the old global unique is redundant and its FK index role is covered.
        if ($this->hasIndex('doctor_schedules_doctor_id_day_of_week_unique')) {
            Schema::table('doctor_schedules', fn (Blueprint $t) => $t->dropUnique('doctor_schedules_doctor_id_day_of_week_unique'));
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('doctor_schedules')) {
            return;
        }
        if (! $this->hasIndex('doctor_schedules_doctor_id_day_of_week_unique')) {
            Schema::table('doctor_schedules', fn (Blueprint $t) => $t->unique(['doctor_id', 'day_of_week'], 'doctor_schedules_doctor_id_day_of_week_unique'));
        }
        if ($this->hasIndex('doctor_schedules_branch_doctor_day_unique')) {
            Schema::table('doctor_schedules', fn (Blueprint $t) => $t->dropUnique('doctor_schedules_branch_doctor_day_unique'));
        }
    }
};
