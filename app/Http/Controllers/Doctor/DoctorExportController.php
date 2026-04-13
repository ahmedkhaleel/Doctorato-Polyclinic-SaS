<?php

namespace App\Http\Controllers\Doctor;

use App\Exports\DoctorSalarySlipsExport;
use App\Exports\DoctorCommissionsExport;
use App\Exports\DoctorVisitsExport;
use App\Models\Employee;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class DoctorExportController extends BaseDoctorController
{
    public function exportSalarySlips(Request $request)
    {
        $user = $request->user();
        $employee = Employee::where('user_id', $user->id)->first();

        if (!$employee) {
            return back()->with('error', 'No employee record found.');
        }

        $filename = 'my-salary-slips-' . now()->format('Y-m-d') . '.xlsx';

        return Excel::download(
            new DoctorSalarySlipsExport(
                $employee->id,
                $request->input('month') ? (int) $request->input('month') : null,
                $request->input('year') ? (int) $request->input('year') : null
            ),
            $filename
        );
    }

    public function exportCommissions(Request $request)
    {
        $doctorId = $this->doctorId($request);
        $filename = 'my-commissions-' . now()->format('Y-m-d') . '.xlsx';

        return Excel::download(
            new DoctorCommissionsExport(
                $doctorId,
                $request->input('date_from'),
                $request->input('date_to')
            ),
            $filename
        );
    }

    public function exportVisits(Request $request)
    {
        $doctorId = $this->doctorId($request);
        $filename = 'my-visits-' . now()->format('Y-m-d') . '.xlsx';

        return Excel::download(
            new DoctorVisitsExport(
                $doctorId,
                $request->input('date_from'),
                $request->input('date_to'),
                $request->input('status')
            ),
            $filename
        );
    }
}
