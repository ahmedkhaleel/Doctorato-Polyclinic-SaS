<?php

namespace App\Services;

use App\Models\OnlineConsultation;
use App\Models\Setting;
use App\Services\Agora\RtcTokenBuilder;

class AgoraService
{
    private string $appId;
    private string $appCertificate;

    public function __construct()
    {
        $this->appId = (string) Setting::get('agora_app_id', '');
        $this->appCertificate = (string) Setting::get('agora_app_certificate', '');
    }

    public function isConfigured(): bool
    {
        return !empty($this->appId) && !empty($this->appCertificate);
    }

    public function getAppId(): string
    {
        return $this->appId;
    }

    public function generateChannelName(OnlineConsultation $consultation): string
    {
        return $consultation->agora_channel_name
            ?: 'consult_' . $consultation->id . '_' . substr(md5($consultation->id . $consultation->created_at), 0, 12);
    }

    public function generateRtcToken(string $channelName, int $uid, string $role = 'publisher'): string
    {
        if (!$this->isConfigured()) {
            throw new \RuntimeException('Agora is not configured. Please configure from Admin Settings.');
        }

        $expiry = (int) Setting::get('agora_token_expiry_seconds', 7200);
        $expireTs = time() + $expiry;
        $roleInt = $role === 'publisher'
            ? RtcTokenBuilder::ROLE_PUBLISHER
            : RtcTokenBuilder::ROLE_SUBSCRIBER;

        return RtcTokenBuilder::buildTokenWithUid(
            $this->appId,
            $this->appCertificate,
            $channelName,
            $uid,
            $roleInt,
            $expireTs
        );
    }

    public function generateTokensForConsultation(OnlineConsultation $consultation): array
    {
        $channel = $this->generateChannelName($consultation);
        $doctorUid = $consultation->doctor_id * 10000 + 1;
        $patientUid = $consultation->patient_id * 10000 + 2;

        $doctorToken = $this->generateRtcToken($channel, $doctorUid, 'publisher');
        $patientToken = $this->generateRtcToken($channel, $patientUid, 'publisher');

        $consultation->update([
            'agora_channel_name' => $channel,
            'agora_doctor_token' => $doctorToken,
            'agora_patient_token' => $patientToken,
            'agora_tokens_expire_at' => now()->addSeconds((int) Setting::get('agora_token_expiry_seconds', 7200)),
        ]);

        return compact('channel', 'doctorToken', 'patientToken', 'doctorUid', 'patientUid');
    }

    public function testConnection(): array
    {
        if (!$this->isConfigured()) {
            return ['success' => false, 'message' => 'Agora credentials not configured'];
        }

        try {
            $token = $this->generateRtcToken('test_channel_' . time(), 12345, 'publisher');
            if (strlen($token) < 50) {
                return ['success' => false, 'message' => 'Token generation returned invalid format'];
            }
            return ['success' => true, 'message' => 'Agora configured correctly. Test token generated.'];
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => 'Agora error: ' . $e->getMessage()];
        }
    }
}
