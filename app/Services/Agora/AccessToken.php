<?php

namespace App\Services\Agora;

class AccessToken
{
    const K_JOIN_CHANNEL = 1;
    const K_PUBLISH_AUDIO_STREAM = 2;
    const K_PUBLISH_VIDEO_STREAM = 3;
    const K_PUBLISH_DATA_STREAM = 4;

    public string $appId;
    public string $appCertificate;
    public string $channelName;
    public string $uid;
    public int $salt;
    public int $ts;
    public array $messages = [];

    public function __construct(string $appId, string $appCertificate, string $channelName, string $uid)
    {
        $this->appId = $appId;
        $this->appCertificate = $appCertificate;
        $this->channelName = $channelName;
        $this->uid = $uid;
        $this->salt = random_int(1, 99999999);
        $this->ts = time() + 24 * 3600;
    }

    public function addPrivilege(int $key, int $expireTimestamp): void
    {
        $this->messages[$key] = $expireTimestamp;
    }

    public function build(): string
    {
        $version = "006";

        // Pack messages
        $m = pack('v', $this->salt)
           . pack('V', $this->ts)
           . pack('v', count($this->messages));
        foreach ($this->messages as $key => $value) {
            $m .= pack('v', $key) . pack('V', $value);
        }

        $val = $this->appId . $this->channelName . $this->uid . $m;
        $signature = hash_hmac('sha256', $val, $this->appCertificate, true);

        $crcChannel = crc32($this->channelName) & 0xffffffff;
        $crcUid = crc32($this->uid) & 0xffffffff;

        $content = pack('v', strlen($signature)) . $signature
                 . pack('V', $crcChannel)
                 . pack('V', $crcUid)
                 . pack('v', strlen($m)) . $m;

        return $version . $this->appId . base64_encode($content);
    }
}
