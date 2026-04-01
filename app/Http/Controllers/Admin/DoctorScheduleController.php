<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Doctor;
use App\Models\DoctorSchedule;
use App\Models\DoctorVacation;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DoctorScheduleController extends Controller
{
    public function index(Request $request): Response
    {
        $doctors = Doctor::with(['schedules', 'vacations' => fn ($q) => $q->where('end_date', '>=', today())])
            ->where('status', 'active')
            ->orderBy('name_en')
            ->get()
            ->map(function (Doctor $d) {
                $scheduleByDay = [];
                foreach ($d->schedules as $s) {
                    $scheduleByDay[$s->day_of_week] = [
                        'id' => $s->id,
                        'start_time' => $s->start_time,
                        'end_time' => $s->end_time,
                        'is_active' => $s->is_active,
                    ];
                }

                return [
                    'id' => $d->id,
                    'name_en' => $d->name_en,
                    'name_ar' => $d->name_ar,
                    'specialty_en' => $d->specialty_en,
                    'specialty_ar' => $d->specialty_ar,
                    'department' => $d->department,
                    'avatar' => $d->avatar,
                    'schedules' => $scheduleByDay,
                    'upcoming_vacations' => $d->vacations->map(fn ($v) => [
                        'id' => $v->id,
                        'start_date' => $v->start_date->format('Y-m-d'),
                        'end_date' => $v->end_date->format('Y-m-d'),
                        'reason' => $v->reason,
                    ]),
                ];
            });

        // Stats
        $totalDoctors = Doctor::where('status', 'active')->count();
        $workingToday = DoctorSchedule::where('day_of_week', $this->todayDayIndex())
            ->where('is_active', true)
            ->distinct('doctor_id')
            ->count('doctor_id');
        $onVacation = DoctorVacation::where('start_date', '<=', today())
            ->where('end_date', '>=', today())
            ->distinct('doctor_id')
            ->count('doctor_id');

        return Inertia::render('Admin/Schedules/Index', [
            'doctors' => $doctors,
            'stats' => [
                'total_doctors' => $totalDoctors,
                'working_today' => $workingToday,
                'on_vacation' => $onVacation,
                'available_today' => max(0, $workingToday - $onVacation),
            ],
            'filters' => $request->only(['department']),
        ]);
    }

    public function update(Request $request, Doctor $doctor): RedirectResponse
    {
        $data = $request->validate([
            'schedules' => 'required|array',
            'schedules.*.day_of_week' => 'required|integer|min:0|max:6',
            'schedules.*.start_time' => 'required|string',
            'schedules.*.end_time' => 'required|string',
            'schedules.*.is_active' => 'required|boolean',
        ]);

        $doctor->schedules()->delete();

        foreach ($data['schedules'] as $schedule) {
            $doctor->schedules()->create([
                'day_of_week' => (int) $schedule['day_of_week'],
                'start_time' => $schedule['start_time'],
                'end_time' => $schedule['end_time'],
                'is_active' => (bool) $schedule['is_active'],
            ]);
        }

        AuditLogger::log('schedule_updated', $doctor);

        return back()->with('success', "Schedule updated for Dr. {$doctor->name_en}.");
    }

    private function todayDayIndex(): int
    {
        // Model uses 0=Saturday, 1=Sunday, ..., 6=Friday
        $map = ['Sat' => 0, 'Sun' => 1, 'Mon' => 2, 'Tue' => 3, 'Wed' => 4, 'Thu' => 5, 'Fri' => 6];

        return $map[now()->format('D')] ?? 0;
    }
}
