<script setup>
import { ref, onMounted, onUnmounted, computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import axios from 'axios';

const page = usePage();
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const show = ref(false);
const action = ref(null); // 'check_in' | 'check_out'
const shift = ref(null);
const late = ref(false);
const submitting = ref(false);
const successMessage = ref('');
const errorMessage = ref('');
const dismissedUntil = ref(0);

let pollTimer = null;

const title = computed(() => isRtl.value ? 'تسجيل الحضور' : 'Check In');

const subtitle = computed(() => {
    if (!shift.value) return '';
    const sh = shift.value;
    const msg = isRtl.value
        ? `وقت البداية: ${sh.start}`
        : `Shift starts: ${sh.start}`;
    return late.value
        ? (isRtl.value ? `${msg} — تأخرت قليلاً ⏰` : `${msg} — You are a bit late ⏰`)
        : msg;
});

async function fetchStatus() {
    // Snooze window — respect the user's "Later" click
    if (Date.now() < dismissedUntil.value) return;

    try {
        const { data } = await axios.get('/api/attendance/status');
        if (data.show && data.action === 'check_in') {
            // Only show popup for CHECK-IN — never for check-out (too intrusive)
            action.value = 'check_in';
            shift.value = data.shift;
            late.value = !!data.late;
            show.value = true;
            // No automatic re-poll — popup stays visible until user acts
        } else if (data.reason === 'already_checked_in') {
            // Already done for today — don't bother polling again this session
            show.value = false;
            stopPolling();
        } else {
            // Outside shift window or no shift today — check again in 30 min
            // in case the shift starts later in the day
            show.value = false;
            scheduleNextPoll(30);
        }
    } catch (e) {
        // silent fail — never block the UI
        scheduleNextPoll(30);
    }
}

function scheduleNextPoll(minutes) {
    if (pollTimer) clearTimeout(pollTimer);
    pollTimer = setTimeout(fetchStatus, minutes * 60 * 1000);
}

function stopPolling() {
    if (pollTimer) clearTimeout(pollTimer);
    pollTimer = null;
}

async function submit() {
    if (submitting.value) return;
    submitting.value = true;
    errorMessage.value = '';
    successMessage.value = '';

    // Best-effort geolocation
    let coords = {};
    try {
        if (navigator.geolocation) {
            await new Promise((resolve) => {
                const timeoutId = setTimeout(resolve, 2000);
                navigator.geolocation.getCurrentPosition(
                    (pos) => {
                        clearTimeout(timeoutId);
                        coords = { lat: pos.coords.latitude, lng: pos.coords.longitude };
                        resolve();
                    },
                    () => { clearTimeout(timeoutId); resolve(); },
                    { timeout: 2000, maximumAge: 60000 }
                );
            });
        }
    } catch (_) { /* ignore */ }

    try {
        // Only check-in is handled by this popup — check-out is manual
        const { data } = await axios.post('/api/attendance/check-in', coords);
        if (data.success) {
            successMessage.value = data.message;
            // Auto-close after 1.5s — then stop polling entirely for the rest
            // of the session (no check-out popup, no further reminders)
            setTimeout(() => {
                show.value = false;
                successMessage.value = '';
                stopPolling();
            }, 1500);
        } else {
            errorMessage.value = data.message || 'فشل تسجيل الحضور';
        }
    } catch (e) {
        errorMessage.value = e.response?.data?.message || (isRtl.value ? 'حدث خطأ أثناء الحفظ' : 'Failed to save');
    } finally {
        submitting.value = false;
    }
}

function dismiss() {
    show.value = false;
    // Respect the user's "Later" click — snooze for a full HOUR, not 5 min.
    // Was annoying every few minutes — now they get peace.
    dismissedUntil.value = Date.now() + 60 * 60 * 1000;
    scheduleNextPoll(60);
}

onMounted(() => {
    // Initial fetch after a short delay (let the page finish loading)
    setTimeout(fetchStatus, 1500);
});

onUnmounted(() => {
    if (pollTimer) clearTimeout(pollTimer);
});
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition ease-out duration-200"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition ease-in duration-150"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div v-if="show" :dir="isRtl ? 'rtl' : 'ltr'"
                 class="fixed inset-0 z-[9999] flex items-end md:items-center justify-center p-0 md:p-4">
                <!-- Backdrop -->
                <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="dismiss"></div>

                <!-- Panel -->
                <Transition
                    enter-active-class="transition ease-out duration-300"
                    enter-from-class="translate-y-full md:translate-y-0 md:opacity-0 md:scale-95"
                    enter-to-class="translate-y-0 md:opacity-100 md:scale-100"
                    leave-active-class="transition ease-in duration-200"
                    leave-from-class="translate-y-0 md:opacity-100 md:scale-100"
                    leave-to-class="translate-y-full md:translate-y-0 md:opacity-0 md:scale-95"
                >
                    <div v-if="show"
                         class="relative w-full md:max-w-md bg-white rounded-t-3xl md:rounded-3xl shadow-2xl overflow-hidden">

                        <!-- Navy header with gold accent -->
                        <div class="relative bg-gradient-to-br from-[#1B365D] via-[#1B365D] to-[#0F2444] p-5 md:p-6">
                            <div class="pointer-events-none absolute -top-14 -end-14 h-44 w-44 rounded-full bg-[#C4A265]/20 blur-3xl"></div>
                            <div class="absolute inset-x-0 top-0 h-[2px] bg-gradient-to-r from-transparent via-[#C4A265] to-transparent"></div>

                            <div class="relative flex items-center gap-4">
                                <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-[#C4A265] to-[#8B7043] flex items-center justify-center shadow-lg">
                                    <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <h3 class="text-lg md:text-xl font-extrabold text-white">{{ title }}</h3>
                                    <p class="text-xs md:text-sm text-white/70 mt-0.5">{{ subtitle }}</p>
                                </div>
                                <button @click="dismiss" class="text-white/60 hover:text-white p-1 -m-1 rounded-lg transition">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                </button>
                            </div>
                        </div>

                        <!-- Body -->
                        <div class="p-5 md:p-6">
                            <div class="flex items-center gap-3 bg-slate-50 rounded-2xl p-4">
                                <div class="w-10 h-10 rounded-xl bg-[#1B365D]/10 flex items-center justify-center flex-shrink-0">
                                    <svg class="w-5 h-5 text-[#1B365D]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                                    </svg>
                                </div>
                                <div class="flex-1">
                                    <p class="text-sm font-semibold text-[#1B365D]">
                                        {{ isRtl ? 'التاريخ والوقت الحالي' : 'Current date & time' }}
                                    </p>
                                    <p class="text-xs text-slate-600 mt-0.5" dir="ltr">
                                        {{ new Date().toLocaleString(isRtl ? 'ar-EG' : 'en-GB', { dateStyle: 'medium', timeStyle: 'short' }) }}
                                    </p>
                                </div>
                            </div>

                            <!-- Success / Error banners -->
                            <div v-if="successMessage" class="mt-4 p-3 rounded-xl bg-emerald-50 border border-emerald-200 text-sm text-emerald-700 flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                {{ successMessage }}
                            </div>
                            <div v-if="errorMessage" class="mt-4 p-3 rounded-xl bg-red-50 border border-red-200 text-sm text-red-700 flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                {{ errorMessage }}
                            </div>

                            <!-- Actions -->
                            <div class="mt-5 flex flex-col-reverse sm:flex-row gap-2 sm:justify-end">
                                <button @click="dismiss"
                                        class="px-5 py-2.5 rounded-xl text-sm font-semibold text-slate-600 hover:bg-slate-100 transition">
                                    {{ isRtl ? 'لاحقاً' : 'Later' }}
                                </button>
                                <button @click="submit"
                                        :disabled="submitting || !!successMessage"
                                        class="inline-flex items-center justify-center gap-2 px-6 py-2.5 rounded-xl text-sm font-bold text-white bg-gradient-to-r from-[#C4A265] to-[#8B7043] hover:from-[#8B7043] hover:to-[#C4A265] shadow-md hover:shadow-lg transition disabled:opacity-60 disabled:cursor-not-allowed">
                                    <svg v-if="submitting" class="w-4 h-4 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                    </svg>
                                    {{ submitting
                                        ? (isRtl ? 'جاري الحفظ...' : 'Saving...')
                                        : (isRtl ? 'سجل حضوري الآن' : 'Check me in') }}
                                </button>
                            </div>
                        </div>
                    </div>
                </Transition>
            </div>
        </Transition>
    </Teleport>
</template>
