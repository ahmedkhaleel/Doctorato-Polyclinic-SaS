<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fix an overloaded, too-narrow enum on visits.consultation_type.
 *
 * The column was originally enum('dermatology','cosmetic') to record the
 * consultation specialty. But the code writes more values than that enum
 * allows, so several real flows silently truncated/failed:
 *   - BookingWorkflowService writes 'dermatology' | 'cosmetic' | 'pediatric'
 *     | 'dental'  → pediatric/dental bookings rejected.
 *   - OnlineConsultationService writes 'online'    → every online-consult
 *     completion rejected ("Data truncated for column 'consultation_type'").
 *   - Visit.php / VisitWorkflowService read both specialty and 'online'.
 *
 * Converting to a nullable string ends the enum fragility for good: every
 * value the code already writes is now valid, and all existing rows keep
 * their value verbatim. Data-safe and works on MySQL (prod) + SQLite (CI).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('visits', 'consultation_type')) {
            Schema::table('visits', function (Blueprint $t) {
                $t->string('consultation_type', 30)->nullable()->change();
            });
        }
    }

    public function down(): void
    {
        // Intentionally not reverting to the narrow enum — doing so would
        // truncate any 'pediatric'/'dental'/'online' rows. The wider string
        // is a strict superset, so leaving it is the safe rollback.
    }
};
