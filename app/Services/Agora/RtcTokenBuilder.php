<?php

namespace App\Services\Agora;

class RtcTokenBuilder
{
    const ROLE_PUBLISHER = 1;
    const ROLE_SUBSCRIBER = 2;

    public static function buildTokenWithUid(
        string $appId,
        string $appCertificate,
        string $channelName,
        int $uid,
        int $role,
        int $privilegeExpiredTs
    ): string {
        return self::buildTokenWithUserAccount(
            $appId,
            $appCertificate,
            $channelName,
            (string) $uid,
            $role,
            $privilegeExpiredTs
        );
    }

    public static function buildTokenWithUserAccount(
        string $appId,
        string $appCertificate,
        string $channelName,
        string $userAccount,
        int $role,
        int $privilegeExpiredTs
    ): string {
        $token = new AccessToken($appId, $appCertificate, $channelName, $userAccount);
        $token->addPrivilege(AccessToken::K_JOIN_CHANNEL, $privilegeExpiredTs);
        if ($role == self::ROLE_PUBLISHER) {
            $token->addPrivilege(AccessToken::K_PUBLISH_AUDIO_STREAM, $privilegeExpiredTs);
            $token->addPrivilege(AccessToken::K_PUBLISH_VIDEO_STREAM, $privilegeExpiredTs);
            $token->addPrivilege(AccessToken::K_PUBLISH_DATA_STREAM, $privilegeExpiredTs);
        }
        return $token->build();
    }
}
