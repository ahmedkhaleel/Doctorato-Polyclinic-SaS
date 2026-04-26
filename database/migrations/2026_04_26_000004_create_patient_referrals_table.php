<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Patient referral system.
 *
 *  - Each patient gets a stable referral_code (e.g. SARA-7K9X) on
 *    creation. Sharing this code with a friend lets that friend get
 *    a discount on their first booking and credits the referrer.
 *
 *  - referrals table tracks each successful redemption: who referred
 *    whom, when, what discount was applied.
 *
 *  - Patients can have unlimited referrals (one row per referred friend).
 *
 *  - Anonymous bookings (no patient_id) cannot use referral codes
 *    — the friend has to register to consume the code.
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1. Add referral_code column to patients
        if (Schema::hasTable('patients') && ! Schema::hasColumn('patients', 'referral_code')) {
            Schema::table('patients', function (Blueprint $table) {
                $table->string('referral_code', 20)->nullable()->unique()->after('file_number');
            });

            // Backfill codes for existing patients
            $patients = DB::table('patients')->whereNull('referral_code')->get(['id', 'full_name']);
            foreach ($patients as $p) {
                $code = self::generateCode($p->full_name ?: 'Patient');
                DB::table('patients')->where('id', $p->id)->update(['referral_code' => $code]);
            }
        }

        // 2. Create patient_referrals table (note: 'referrals' already exists for
        //    medical doctor-to-doctor referrals; this is a separate concept).
        if (! Schema::hasTable('patient_referrals')) {
            Schema::create('patient_referrals', function (Blueprint $table) {
                $table->id();
                $table->foreignId('referrer_patient_id')->constrained('patients')->cascadeOnDelete();
                $table->foreignId('referred_patient_id')->constrained('patients')->cascadeOnDelete();
                $table->string('code', 20);            // The code that was used (snapshot)
                $table->decimal('discount_amount', 10, 2)->default(0);
                $table->string('discount_currency', 3)->default('EGP');
                $table->foreignId('first_booking_id')->nullable()->constrained('bookings')->nullOnDelete();
                $table->timestamp('redeemed_at')->useCurrent();
                $table->timestamps();

                $table->unique('referred_patient_id'); // A patient can only be referred once
                $table->index('referrer_patient_id');
                $table->index('redeemed_at');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('patient_referrals');

        Schema::table('patients', function (Blueprint $table) {
            if (Schema::hasColumn('patients', 'referral_code')) {
                $table->dropColumn('referral_code');
            }
        });
    }

    /**
     * Build a memorable referral code: first-name slug + 4 random chars.
     * Example: "SARA-7K9X" — easy to share verbally.
     */
    private static function generateCode(string $name): string
    {
        $prefix = strtoupper(Str::slug(Str::words($name, 1, '')) ?: 'REF');
        $prefix = substr($prefix, 0, 8);

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $suffix = strtoupper(Str::random(4));
            $code = "{$prefix}-{$suffix}";
            if (! DB::table('patients')->where('referral_code', $code)->exists()) {
                return $code;
            }
        }
        // Last resort
        return $prefix . '-' . strtoupper(Str::random(6));
    }
};
