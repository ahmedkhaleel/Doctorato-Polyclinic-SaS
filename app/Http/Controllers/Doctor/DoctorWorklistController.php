<?php

namespace App\Http\Controllers\Doctor;

use App\Services\DoctorWorklistService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DoctorWorklistController extends BaseDoctorController
{
    public function __construct(private DoctorWorklistService $worklist) {}

    public function index(Request $request): Response
    {
        $doctorId = $this->doctorId($request);

        return Inertia::render('Doctor/Worklist/Index', [
            'buckets' => $this->worklist->buckets($doctorId, true),
        ]);
    }
}
