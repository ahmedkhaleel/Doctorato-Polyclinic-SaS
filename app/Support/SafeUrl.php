<?php

namespace App\Support;

/**
 * SSRF guard for admin-configured outbound URLs (SMS gateways, WhatsApp bridges).
 * Rejects non-HTTP(S) schemes and any host that resolves to a private, loopback,
 * link-local, or cloud-metadata address.
 */
class SafeUrl
{
    public static function isSafe(string $url): bool
    {
        $parts = parse_url($url);
        if (! $parts || empty($parts['scheme']) || empty($parts['host'])) {
            return false;
        }
        if (! in_array(strtolower($parts['scheme']), ['http', 'https'], true)) {
            return false;
        }

        $host = strtolower($parts['host']);

        if (in_array($host, ['localhost', 'localhost.localdomain', '127.0.0.1', '::1'], true)) {
            return false;
        }

        $ip = filter_var($host, FILTER_VALIDATE_IP) ?: gethostbyname($host);
        if (! $ip || $ip === $host) {
            if (! filter_var($host, FILTER_VALIDATE_IP)) {
                return false; // unresolved hostname
            }
            $ip = $host;
        }

        // Reject private / reserved ranges (covers 10/8, 172.16/12, 192.168/16,
        // 127/8, 169.254/16 metadata, etc.).
        return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false;
    }
}
