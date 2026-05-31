<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Branch\BranchContext;
use App\Services\TimeSlotService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TimeSlotController extends Controller
{
    public function __construct(protected TimeSlotService $timeSlotService)
    {
    }

    /**
     * GET /api/time-slots?doctor_id=X&date=Y&duration=Z&branch_id=B
     */
    public function available(Request $request): JsonResponse
    {
        $request->validate([
            'doctor_id' => 'required|exists:doctors,id',
            'date' => 'required|date|after_or_equal:today',
            'duration' => 'nullable|integer|min:15|max:180',
            'branch_id' => 'nullable|integer|exists:branches,id',
        ]);

        // Slots come from the doctor's schedules, which are branch-scoped — so
        // generate them within the requested branch's context.
        $slots = $this->forBranch($request->input('branch_id'), fn () => $this->timeSlotService->getAvailableSlots(
            $request->doctor_id,
            $request->date,
            $request->duration ?? 30
        ));

        return response()->json(['slots' => $slots]);
    }

    /**
     * GET /api/available-dates?doctor_id=X&from=Y&to=Z&duration=W&branch_id=B
     */
    public function availableDates(Request $request): JsonResponse
    {
        $request->validate([
            'doctor_id' => 'required|exists:doctors,id',
            'from' => 'required|date',
            'to' => 'required|date|after_or_equal:from',
            'duration' => 'nullable|integer|min:15|max:180',
            'branch_id' => 'nullable|integer|exists:branches,id',
        ]);

        $dates = $this->forBranch($request->input('branch_id'), fn () => $this->timeSlotService->getAvailableDates(
            $request->doctor_id,
            $request->from,
            $request->to,
            $request->duration ?? 30
        ));

        return response()->json(['dates' => $dates]);
    }

    /** Run a callback pinned to the requested branch (or as-is when none given). */
    private function forBranch($branchId, callable $callback)
    {
        if (! empty($branchId)) {
            return app(BranchContext::class)->runForBranch((int) $branchId, $callback);
        }

        return $callback();
    }
}
