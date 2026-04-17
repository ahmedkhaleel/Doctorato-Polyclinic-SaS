import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

/**
 * Initialize Laravel Echo against a Reverb server.
 * Returns null if config is missing — callers should treat that as
 * "no realtime available" and fall back to polling.
 */
export function initEcho(config) {
    if (!config || !config.key) return null;
    try {
        return new Echo({
            broadcaster: 'reverb',
            key: config.key,
            wsHost: config.host,
            wsPort: config.port,
            wssPort: config.port,
            forceTLS: config.scheme === 'https',
            enabledTransports: ['ws', 'wss'],
        });
    } catch (e) {
        console.warn('Echo init failed:', e);
        return null;
    }
}
