<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\TimeSlotService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TimeSlotController extends Controller
{
    public function __construct(protected TimeSlotService $timeSlotService)
    {
    }

    /**
     * GET /api/time-slots?doctor_id=X&date=Y&duration=Z
     */
    public function available(Request $request): JsonResponse
    {
        $request->validate([
            'doctor_id' => 'required|exists:doctors,id',
            'date' => 'required|date|after_or_equal:today',
            'duration' => 'nullable|integer|min:15|max:180',
        ]);

        $slots = $this->timeSlotService->getAvailableSlots(
            $request->doctor_id,
            $request->date,
            $request->duration ?? 30
        );

        return response()->json(['slots' => $slots]);
    }

    /**
     * GET /api/available-dates?doctor_id=X&from=Y&to=Z&duration=W
     */
    public function availableDates(Request $request): JsonResponse
    {
        $request->validate([
            'doctor_id' => 'required|exists:doctors,id',
            'from' => 'required|date',
            'to' => 'required|date|after_or_equal:from',
            'duration' => 'nullable|integer|min:15|max:180',
        ]);

        $dates = $this->timeSlotService->getAvailableDates(
            $request->doctor_id,
            $request->from,
            $request->to,
            $request->duration ?? 30
        );

        return response()->json(['dates' => $dates]);
    }
}
