<?php

namespace Database\Seeders;

use App\Models\Attendance;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class AttendanceSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::take(5)->get();

        if ($users->isEmpty()) {
            return;
        }

        // Generate attendance for the past 14 days (excluding Fridays)
        for ($day = 1; $day <= 14; $day++) {
            $date = Carbon::today()->subDays($day);

            // Skip Fridays (day off in Egypt)
            if ($date->isFriday()) {
                continue;
            }

            foreach ($users as $user) {
                // Skip if attendance already exists for this user+date
                if (Attendance::where('user_id', $user->id)->where('date', $date->toDateString())->exists()) {
                    continue;
                }

                $rand = rand(1, 100);

                if ($rand <= 5) {
                    // 5% absent
                    Attendance::create([
                        'user_id' => $user->id,
                        'date' => $date->toDateString(),
                        'status' => 'absent',
                    ]);
                } elseif ($rand <= 10) {
                    // 5% on leave
                    Attendance::create([
                        'user_id' => $user->id,
                        'date' => $date->toDateString(),
                        'status' => 'leave',
                    ]);
                } elseif ($rand <= 25) {
                    // 15% late
                    $lateMinutes = rand(5, 45);
                    Attendance::create([
                        'user_id' => $user->id,
                        'date' => $date->toDateString(),
                        'check_in' => '09:' . str_pad($lateMinutes, 2, '0', STR_PAD_LEFT),
                        'check_out' => rand(0, 1) ? '17:00' : '17:30',
                        'status' => 'late',
                    ]);
                } else {
                    // 75% on time
                    $checkInMinute = rand(0, 9);
                    $overtimeChance = rand(1, 100);
                    $overtime = $overtimeChance <= 20 ? round(rand(30, 120) / 60, 1) : 0;

                    Attendance::create([
                        'user_id' => $user->id,
                        'date' => $date->toDateString(),
                        'check_in' => '08:' . str_pad(50 + $checkInMinute, 2, '0', STR_PAD_LEFT),
                        'check_out' => $overtime > 0 ? '18:00' : '17:00',
                        'status' => 'present',
                        'overtime_hours' => $overtime,
                    ]);
                }
            }
        }
    }
}
