<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Exports\PatientsExport;
use App\Exports\InvoicesExport;
use App\Exports\PaymentsExport;
use App\Exports\VisitsExport;
use App\Exports\CommissionsExport;
use App\Exports\ActivityLogsExport;
use App\Exports\EmployeesExport;
use App\Exports\SalarySlipsExport;
use App\Exports\DentalTreatmentsExport;
use App\Exports\DentalTreatmentPlansExport;
use App\Exports\DentalFollowupsExport;
use App\Exports\DentalLabOrdersExport;
use App\Exports\SuppliesExport;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class ExportController extends Controller
{
    public function patients(Request $request)
    {
        $filename = 'patients-' . now()->format('Y-m-d') . '.xlsx';

        return Excel::download(
            new PatientsExport(
                $request->input('search'),
                $request->input('status')
            ),
            $filename
        );
    }

    public function invoices(Request $request)
    {
        $filename = 'invoices-' . now()->format('Y-m-d') . '.xlsx';

        return Excel::download(
            new InvoicesExport(
                $request->input('date_from'),
                $request->input('date_to'),
                $request->input('status')
            ),
            $filename
        );
    }

    public function payments(Request $request)
    {
        $filename = 'payments-' . now()->format('Y-m-d') . '.xlsx';

        return Excel::download(
            new PaymentsExport(
                $request->input('date_from'),
                $request->input('date_to')
            ),
            $filename
        );
    }

    public function visits(Request $request)
    {
        $filename = 'visits-' . now()->format('Y-m-d') . '.xlsx';

        return Excel::download(
            new VisitsExport(
                $request->input('date_from'),
                $request->input('date_to'),
                $request->input('status'),
                $request->input('doctor_id') ? (int) $request->input('doctor_id') : null
            ),
            $filename
        );
    }

    public function commissions(Request $request)
    {
        $filename = 'commissions-' . now()->format('Y-m-d') . '.xlsx';

        return Excel::download(
            new CommissionsExport(
                $request->input('date_from'),
                $request->input('date_to'),
                $request->input('doctor_id') ? (int) $request->input('doctor_id') : null
            ),
            $filename
        );
    }

    public function employees(Request $request)
    {
        $filename = 'employees-' . now()->format('Y-m-d') . '.xlsx';

        return Excel::download(
            new EmployeesExport(
                $request->input('status'),
                $request->input('department_id') ? (int) $request->input('department_id') : null
            ),
            $filename
        );
    }

    public function salarySlips(Request $request)
    {
        $filename = 'salary-slips-' . now()->format('Y-m-d') . '.xlsx';

        return Excel::download(
            new SalarySlipsExport(
                $request->input('month') ? (int) $request->input('month') : null,
                $request->input('year') ? (int) $request->input('year') : null,
                $request->input('status')
            ),
            $filename
        );
    }

    public function activityLogs(Request $request)
    {
        $filename = 'activity-logs-' . now()->format('Y-m-d') . '.xlsx';

        return Excel::download(
            new ActivityLogsExport(
                $request->input('date_from'),
                $request->input('date_to'),
                $request->input('user_id') ? (int) $request->input('user_id') : null,
                $request->input('action'),
                $request->input('panel')
            ),
            $filename
        );
    }

    public function dentalTreatments(Request $request)
    {
        $filename = 'dental-treatments-' . now()->format('Y-m-d') . '.xlsx';

        return Excel::download(
            new DentalTreatmentsExport(
                $request->input('date_from'),
                $request->input('date_to'),
                $request->input('status'),
                $request->input('doctor_id') ? (int) $request->input('doctor_id') : null,
                $request->input('treatment_type')
            ),
            $filename
        );
    }

    public function dentalLabOrders(Request $request)
    {
        $filename = 'dental-lab-orders-' . now()->format('Y-m-d') . '.xlsx';

        return Excel::download(
            new DentalLabOrdersExport(
                $request->input('date_from'),
                $request->input('date_to'),
                $request->input('status')
            ),
            $filename
        );
    }

    public function dentalTreatmentPlans(Request $request)
    {
        $filename = 'dental-treatment-plans-' . now()->format('Y-m-d') . '.xlsx';

        return Excel::download(
            new DentalTreatmentPlansExport(
                $request->input('date_from'),
                $request->input('date_to'),
                $request->input('status'),
                $request->input('doctor_id') ? (int) $request->input('doctor_id') : null
            ),
            $filename
        );
    }

    public function dentalFollowups(Request $request)
    {
        $filename = 'dental-followups-' . now()->format('Y-m-d') . '.xlsx';

        return Excel::download(
            new DentalFollowupsExport(
                $request->input('date_from'),
                $request->input('date_to'),
                $request->input('status'),
                $request->input('doctor_id') ? (int) $request->input('doctor_id') : null
            ),
            $filename
        );
    }

    public function supplies(Request $request)
    {
        $filename = 'supplies-' . now()->format('Y-m-d') . '.xlsx';

        return Excel::download(
            new SuppliesExport(
                $request->input('search'),
                $request->input('module'),
                $request->input('stock'),
                $request->input('category_id') ? (int) $request->input('category_id') : null
            ),
            $filename
        );
    }
}
