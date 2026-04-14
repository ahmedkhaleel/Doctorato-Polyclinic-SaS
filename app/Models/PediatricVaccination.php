<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\LogsActivity;

class PediatricVaccination extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    const STATUS_SCHEDULED = 'scheduled';
    const STATUS_GIVEN = 'given';
    const STATUS_MISSED = 'missed';
    const STATUS_POSTPONED = 'postponed';
    const STATUS_CONTRAINDICATED = 'contraindicated';

    /**
     * Standard vaccination schedule (WHO + MOH).
     */
    const VACCINE_SCHEDULE = [
        ['vaccine' => 'BCG', 'vaccine_ar' => 'بي سي جي (السل)', 'age' => 'Birth', 'dose' => 'Single', 'months' => 0],
        ['vaccine' => 'Hepatitis B', 'vaccine_ar' => 'التهاب الكبد ب', 'age' => 'Birth', 'dose' => 'Dose 0', 'months' => 0],
        ['vaccine' => 'OPV', 'vaccine_ar' => 'شلل الأطفال الفموي', 'age' => 'Birth', 'dose' => 'Dose 0', 'months' => 0],
        ['vaccine' => 'Hexavalent (DTaP-IPV-Hib-HepB)', 'vaccine_ar' => 'السداسي', 'age' => '2 months', 'dose' => 'Dose 1', 'months' => 2],
        ['vaccine' => 'PCV13', 'vaccine_ar' => 'المكورات الرئوية', 'age' => '2 months', 'dose' => 'Dose 1', 'months' => 2],
        ['vaccine' => 'Rotavirus', 'vaccine_ar' => 'الروتا', 'age' => '2 months', 'dose' => 'Dose 1', 'months' => 2],
        ['vaccine' => 'Hexavalent (DTaP-IPV-Hib-HepB)', 'vaccine_ar' => 'السداسي', 'age' => '4 months', 'dose' => 'Dose 2', 'months' => 4],
        ['vaccine' => 'PCV13', 'vaccine_ar' => 'المكورات الرئوية', 'age' => '4 months', 'dose' => 'Dose 2', 'months' => 4],
        ['vaccine' => 'Rotavirus', 'vaccine_ar' => 'الروتا', 'age' => '4 months', 'dose' => 'Dose 2', 'months' => 4],
        ['vaccine' => 'Hexavalent (DTaP-IPV-Hib-HepB)', 'vaccine_ar' => 'السداسي', 'age' => '6 months', 'dose' => 'Dose 3', 'months' => 6],
        ['vaccine' => 'PCV13', 'vaccine_ar' => 'المكورات الرئوية', 'age' => '6 months', 'dose' => 'Dose 3', 'months' => 6],
        ['vaccine' => 'Rotavirus', 'vaccine_ar' => 'الروتا', 'age' => '6 months', 'dose' => 'Dose 3', 'months' => 6],
        ['vaccine' => 'Measles', 'vaccine_ar' => 'الحصبة', 'age' => '9 months', 'dose' => 'Dose 1', 'months' => 9],
        ['vaccine' => 'MMR', 'vaccine_ar' => 'الحصبة-النكاف-الحصبة الألمانية', 'age' => '12 months', 'dose' => 'Dose 1', 'months' => 12],
        ['vaccine' => 'Varicella', 'vaccine_ar' => 'الجديري المائي', 'age' => '12 months', 'dose' => 'Dose 1', 'months' => 12],
        ['vaccine' => 'Hepatitis A', 'vaccine_ar' => 'التهاب الكبد أ', 'age' => '12 months', 'dose' => 'Dose 1', 'months' => 12],
        ['vaccine' => 'PCV13', 'vaccine_ar' => 'المكورات الرئوية', 'age' => '12 months', 'dose' => 'Booster', 'months' => 12],
        ['vaccine' => 'DTaP-IPV-Hib', 'vaccine_ar' => 'الخماسي التنشيطي', 'age' => '18 months', 'dose' => 'Booster 1', 'months' => 18],
        ['vaccine' => 'Hepatitis A', 'vaccine_ar' => 'التهاب الكبد أ', 'age' => '18 months', 'dose' => 'Dose 2', 'months' => 18],
        ['vaccine' => 'MMR', 'vaccine_ar' => 'الحصبة-النكاف-الحصبة الألمانية', 'age' => '18 months', 'dose' => 'Dose 2', 'months' => 18],
        ['vaccine' => 'Varicella', 'vaccine_ar' => 'الجديري المائي', 'age' => '18 months', 'dose' => 'Dose 2', 'months' => 18],
        ['vaccine' => 'OPV', 'vaccine_ar' => 'شلل الأطفال الفموي', 'age' => '24 months', 'dose' => 'Booster', 'months' => 24],
        ['vaccine' => 'DTaP', 'vaccine_ar' => 'الثلاثي البكتيري', 'age' => '4-6 years', 'dose' => 'Booster 2', 'months' => 54],
        ['vaccine' => 'OPV', 'vaccine_ar' => 'شلل الأطفال الفموي', 'age' => '4-6 years', 'dose' => 'Booster 2', 'months' => 54],
    ];

    protected $fillable = [
        'patient_id', 'doctor_id', 'vaccine_name', 'vaccine_name_ar',
        'dose_number', 'scheduled_age', 'scheduled_date', 'given_date',
        'batch_number', 'manufacturer', 'site_of_injection', 'status',
        'side_effects', 'notes', 'next_dose_date',
        'reminder_sent_at', 'overdue_reminder_sent_at',
    ];

    protected $casts = [
        'scheduled_date' => 'date',
        'given_date' => 'date',
        'next_dose_date' => 'date',
        'reminder_sent_at' => 'datetime',
        'overdue_reminder_sent_at' => 'datetime',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function doctor()
    {
        return $this->belongsTo(Doctor::class);
    }
}
