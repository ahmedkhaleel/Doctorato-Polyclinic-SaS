<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\OnlineConsultation;
use App\Models\Setting;
use App\Services\AgoraService;
use App\Services\OnlineConsultationService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class OnlineConsultationRoomController extends Controller
{
    public function token(Request $request, OnlineConsultation $consultation, AgoraService $agora)
    {
        $user = $request->user();
        if (!$user) abort(401);

        // Authorize: only the patient or doctor of this consultation
        $role = null;
        if ($user->patient && $user->patient->id === $consultation->patient_id) {
            $role = 'patient';
        } elseif ($user->doctor && $user->doctor->id === $consultation->doctor_id) {
            $role = 'doctor';
        } else {
            abort(403, 'Not authorized for this consultation');
        }

        // Check joinable window
        $windowMinutes = (int) Setting::get('telemedicine_join_window_minutes', 15);
        if (!$consultation->isJoinableNow($windowMinutes)) {
            return response()->json([
                'success' => false,
                'message' => 'Consultation not joinable. Either too early, too late, or not paid.',
                'consultation_status' => $consultation->status,
                'payment_status' => $consultation->payment_status,
                'scheduled_at' => $consultation->scheduled_date->format('Y-m-d') . ' ' . $consultation->start_time,
            ], 403);
        }

        if (!$agora->isConfigured()) {
            return response()->json([
                'success' => false,
                'message' => 'Video service not configured. Contact clinic.',
            ], 503);
        }

        // Regenerate tokens if missing or expired
        if (empty($consultation->agora_doctor_token)
            || empty($consultation->agora_patient_token)
            || !$consultation->agora_tokens_expire_at
            || $consultation->agora_tokens_expire_at->isPast()) {
            $agora->generateTokensForConsultation($consultation);
            $consultation->refresh();
        }

        $consultation->loadMissing('doctor', 'patient');

        $uid = $role === 'doctor'
            ? $consultation->doctor_id * 10000 + 1
            : $consultation->patient_id * 10000 + 2;

        $token = $role === 'doctor'
            ? $consultation->agora_doctor_token
            : $consultation->agora_patient_token;

        return response()->json([
            'success' => true,
            'app_id' => $agora->getAppId(),
            'channel' => $consultation->agora_channel_name,
            'token' => $token,
            'uid' => $uid,
            'role' => $role,
            'consultation' => [
                'id' => $consultation->id,
                'consultation_number' => $consultation->consultation_number,
                'scheduled_date' => $consultation->scheduled_date->format('Y-m-d'),
                'start_time' => $consultation->start_time,
                'end_time' => $consultation->end_time,
                'duration_minutes' => Carbon::parse($consultation->start_time)
                    ->diffInMinutes(Carbon::parse($consultation->end_time)),
                'chief_complaint' => $consultation->chief_complaint,
                'doctor' => [
                    'id' => $consultation->doctor->id,
                    'name_ar' => $consultation->doctor->name_ar,
                    'name_en' => $consultation->doctor->name_en,
                    'specialization_ar' => $consultation->doctor->specialization_ar,
                    'specialization_en' => $consultation->doctor->specialization_en,
                    'photo' => $consultation->doctor->photo,
                ],
                'patient' => [
                    'id' => $consultation->patient->id,
                    'full_name' => $consultation->patient->full_name,
                    'phone' => $consultation->patient->phone,
                    'date_of_birth' => $consultation->patient->date_of_birth?->format('Y-m-d'),
                    'gender' => $consultation->patient->gender,
                ],
            ],
        ]);
    }

    public function join(Request $request, OnlineConsultation $consultation, OnlineConsultationService $service)
    {
        $user = $request->user();
        if (!$user) abort(401);
        $role = $this->authorizeAndGetRole($user, $consultation);

        $service->markParticipantJoined($consultation, $role);

        return response()->json(['success' => true, 'status' => $consultation->fresh()->status]);
    }

    public function end(Request $request, OnlineConsultation $consultation, OnlineConsultationService $service)
    {
        $user = $request->user();
        if (!$user) abort(401);
        $role = $this->authorizeAndGetRole($user, $consultation);

        $sessionData = $request->validate([
            'diagnosis' => 'nullable|string|max:2000',
            'doctor_notes' => 'nullable|string|max:5000',
        ]);

        // Only doctor can end and submit session data
        if ($role !== 'doctor') {
            // Patient just leaves - we record it but don't end the session
            return response()->json(['success' => true, 'status' => 'left']);
        }

        $visit = $service->completeSession($consultation, $sessionData);

        return response()->json([
            'success' => true,
            'status' => 'completed',
            'visit_id' => $visit->id,
        ]);
    }

    private function authorizeAndGetRole($user, OnlineConsultation $consultation): string
    {
        if ($user->patient && $user->patient->id === $consultation->patient_id) return 'patient';
        if ($user->doctor && $user->doctor->id === $consultation->doctor_id) return 'doctor';
        abort(403);
    }
}
