import { ref } from 'vue';

export function useChatPolling(panelPrefix) {
    const unreadCount = ref(0);
    const newMessages = ref([]);
    const sendError = ref(null);
    let unreadInterval = null;
    let lastSeenMessageId = null;

    function csrfToken() {
        return document.querySelector('meta[name="csrf-token"]')?.content || '';
    }

    const headers = {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
    };

    // Poll for new messages in active conversation
    async function pollForMessages(userId, lastMessageId) {
        if (!userId) return [];
        try {
            const url = `/${panelPrefix}/chat/${userId}/poll?after=${lastMessageId || ''}`;
            const res = await fetch(url, { headers, credentials: 'same-origin' });
            if (res.ok) {
                const data = await res.json();
                return data.messages || [];
            }
        } catch (e) {
            // Silently fail for polling
        }
        return [];
    }

    // Poll for unread count (now returns preview data for toasts)
    async function pollUnreadCount() {
        try {
            const res = await fetch(`/${panelPrefix}/chat/unread-count`, { headers, credentials: 'same-origin' });
            if (res.ok) {
                const data = await res.json();
                const previousCount = unreadCount.value;
                unreadCount.value = data.unread_count || 0;

                // Detect new incoming message (count increased)
                const hasNewMessage = unreadCount.value > previousCount && data.latest_message;
                const isNewMessage = data.latest_message && data.latest_message.id !== lastSeenMessageId;

                if (hasNewMessage && isNewMessage) {
                    lastSeenMessageId = data.latest_message.id;
                    // Play receive sound
                    playReceiveSound();
                    // Dispatch toast event with message details
                    window.dispatchEvent(new CustomEvent('chat-new-message-toast', {
                        detail: {
                            sender_name: data.latest_message.sender_name,
                            sender_id: data.latest_message.sender_id,
                            body: data.latest_message.body,
                            has_attachment: data.latest_message.has_attachment,
                        }
                    }));
                }

                window.dispatchEvent(new CustomEvent('chat-unread-update', {
                    detail: { unread_count: unreadCount.value }
                }));
            }
        } catch (e) {
            // Silently fail for unread count
        }
    }

    // Send a message with automatic CSRF retry
    async function sendMessage(userId, body, attachment = null) {
        sendError.value = null;

        function buildFormData() {
            const fd = new FormData();
            if (body) fd.append('body', body);
            if (attachment) fd.append('attachment', attachment);
            return fd;
        }

        async function doSend() {
            return await fetch(`/${panelPrefix}/chat/${userId}`, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrfToken(),
                },
                credentials: 'same-origin',
                body: buildFormData(),
            });
        }

        try {
            let res = await doSend();

            // If CSRF token expired (419), reload page token and retry once
            if (res.status === 419) {
                console.warn('[Chat Send] CSRF token expired (419), retrying...');
                // Make a small GET to refresh cookies/session
                try {
                    await fetch(`/${panelPrefix}/chat/unread-count`, { headers, credentials: 'same-origin' });
                } catch (e) {}
                // Retry the send
                res = await doSend();
            }

            if (res.ok) {
                const data = await res.json();
                // Play send sound on success
                playSendSound();
                return data.message;
            } else {
                let errorDetail = '';
                try { errorDetail = await res.text(); } catch (e) {}
                console.error('[Chat Send] Error:', res.status, errorDetail);
                sendError.value = `Failed to send message (${res.status})`;
            }
        } catch (e) {
            console.error('[Chat Send] Network error:', e.message);
            sendError.value = 'Network error - please check your connection';
        }
        return null;
    }

    // Mark messages as read
    async function markAsRead(userId) {
        try {
            await fetch(`/${panelPrefix}/chat/${userId}/mark-read`, {
                method: 'POST',
                headers: {
                    ...headers,
                    'X-CSRF-TOKEN': csrfToken(),
                },
                credentials: 'same-origin',
            });
        } catch (e) {
            // Silently fail
        }
    }

    // Load older messages
    async function loadOlder(userId, beforeId) {
        try {
            const res = await fetch(`/${panelPrefix}/chat/${userId}/older?before=${beforeId}`, {
                headers,
                credentials: 'same-origin',
            });
            if (res.ok) {
                const data = await res.json();
                return data.messages || [];
            }
        } catch (e) {
            // Silently fail
        }
        return [];
    }

    // Receive sound — two-tone chime (pleasant notification)
    function playReceiveSound() {
        try {
            const ctx = new (window.AudioContext || window.webkitAudioContext)();
            const now = ctx.currentTime;

            // First tone (higher)
            const osc1 = ctx.createOscillator();
            const gain1 = ctx.createGain();
            osc1.connect(gain1);
            gain1.connect(ctx.destination);
            osc1.frequency.value = 880;
            osc1.type = 'sine';
            gain1.gain.setValueAtTime(0.12, now);
            gain1.gain.exponentialRampToValueAtTime(0.01, now + 0.15);
            osc1.start(now);
            osc1.stop(now + 0.15);

            // Second tone (even higher, slight delay)
            const osc2 = ctx.createOscillator();
            const gain2 = ctx.createGain();
            osc2.connect(gain2);
            gain2.connect(ctx.destination);
            osc2.frequency.value = 1175;
            osc2.type = 'sine';
            gain2.gain.setValueAtTime(0.1, now + 0.12);
            gain2.gain.exponentialRampToValueAtTime(0.01, now + 0.35);
            osc2.start(now + 0.12);
            osc2.stop(now + 0.35);
        } catch (e) {
            // Audio not available
        }
    }

    // Send sound — subtle whoosh (soft confirmation)
    function playSendSound() {
        try {
            const ctx = new (window.AudioContext || window.webkitAudioContext)();
            const now = ctx.currentTime;
            const osc = ctx.createOscillator();
            const gain = ctx.createGain();
            osc.connect(gain);
            gain.connect(ctx.destination);
            osc.frequency.setValueAtTime(600, now);
            osc.frequency.exponentialRampToValueAtTime(900, now + 0.1);
            osc.type = 'sine';
            gain.gain.setValueAtTime(0.06, now);
            gain.gain.exponentialRampToValueAtTime(0.01, now + 0.12);
            osc.start(now);
            osc.stop(now + 0.12);
        } catch (e) {
            // Audio not available
        }
    }

    // Legacy alias
    function playNotificationSound() {
        playReceiveSound();
    }

    // Start polling for unread count (used by ChatIcon)
    function startUnreadPolling() {
        pollUnreadCount();
        unreadInterval = setInterval(pollUnreadCount, 15000);
    }

    function stopUnreadPolling() {
        if (unreadInterval) clearInterval(unreadInterval);
    }

    return {
        unreadCount,
        newMessages,
        sendError,
        pollForMessages,
        pollUnreadCount,
        sendMessage,
        markAsRead,
        loadOlder,
        playNotificationSound,
        playReceiveSound,
        playSendSound,
        startUnreadPolling,
        stopUnreadPolling,
    };
}
