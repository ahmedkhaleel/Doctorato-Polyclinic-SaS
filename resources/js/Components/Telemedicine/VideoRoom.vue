<script setup>
import { ref, onMounted, onBeforeUnmount, computed } from 'vue';
import axios from 'axios';
import AgoraRTC from 'agora-rtc-sdk-ng';

const props = defineProps({
    consultationId: { type: [Number, String], required: true },
    role: { type: String, required: true }, // 'doctor' | 'patient'
});

const phase = ref('loading'); // loading, precall, connecting, connected, ended, error
const errorMessage = ref('');
const consultation = ref(null);
const tokenData = ref(null);

// Agora client
let client = null;
let localAudioTrack = null;
let localVideoTrack = null;
let screenTrack = null;

// UI state
const micEnabled = ref(true);
const cameraEnabled = ref(true);
const screenSharing = ref(false);
const chatOpen = ref(false);
const sidePanelOpen = ref(props.role === 'doctor');

const remoteUsers = ref([]);
const sessionTimer = ref(0);
let timerInterval = null;

// Doctor notes
const diagnosis = ref('');
const doctorNotes = ref('');
const endingSession = ref(false);

// Chat
const chatMessages = ref([]);
const chatDraft = ref('');

async function fetchTokenAndJoin() {
    phase.value = 'loading';
    try {
        const { data } = await axios.get(`/api/online-consultations/${props.consultationId}/token`);
        if (!data.success) {
            throw new Error(data.message || 'Cannot get token');
        }
        tokenData.value = data;
        consultation.value = data.consultation;
        phase.value = 'precall';
    } catch (e) {
        phase.value = 'error';
        errorMessage.value = e.response?.data?.message || e.message || 'Failed to connect';
    }
}

async function startCall() {
    phase.value = 'connecting';
    try {
        client = AgoraRTC.createClient({ mode: 'rtc', codec: 'vp8' });

        client.on('user-published', async (user, mediaType) => {
            await client.subscribe(user, mediaType);
            const existing = remoteUsers.value.find(u => u.uid === user.uid);
            if (existing) {
                if (mediaType === 'video') existing.videoTrack = user.videoTrack;
                if (mediaType === 'audio') { existing.audioTrack = user.audioTrack; user.audioTrack?.play(); }
            } else {
                remoteUsers.value.push({
                    uid: user.uid,
                    videoTrack: mediaType === 'video' ? user.videoTrack : null,
                    audioTrack: mediaType === 'audio' ? user.audioTrack : null,
                });
                if (mediaType === 'audio') user.audioTrack?.play();
            }

            setTimeout(() => {
                const el = document.getElementById(`remote-video-${user.uid}`);
                if (el && user.videoTrack) user.videoTrack.play(el);
            }, 100);
        });

        client.on('user-unpublished', (user, mediaType) => {
            const existing = remoteUsers.value.find(u => u.uid === user.uid);
            if (existing && mediaType === 'video') existing.videoTrack = null;
            if (existing && mediaType === 'audio') existing.audioTrack = null;
        });

        client.on('user-left', (user) => {
            remoteUsers.value = remoteUsers.value.filter(u => u.uid !== user.uid);
        });

        await client.join(tokenData.value.app_id, tokenData.value.channel, tokenData.value.token, tokenData.value.uid);

        [localAudioTrack, localVideoTrack] = await AgoraRTC.createMicrophoneAndCameraTracks();
        await client.publish([localAudioTrack, localVideoTrack]);

        setTimeout(() => {
            const el = document.getElementById('local-video');
            if (el) localVideoTrack.play(el);
        }, 100);

        try {
            await axios.post(`/api/online-consultations/${props.consultationId}/join`);
        } catch (e) {
            console.warn('join notify failed', e);
        }

        timerInterval = setInterval(() => sessionTimer.value++, 1000);

        phase.value = 'connected';
    } catch (e) {
        phase.value = 'error';
        errorMessage.value = 'Could not start video: ' + (e.message || 'unknown');
        console.error(e);
    }
}

async function toggleMic() {
    if (!localAudioTrack) return;
    micEnabled.value = !micEnabled.value;
    await localAudioTrack.setEnabled(micEnabled.value);
}

async function toggleCamera() {
    if (!localVideoTrack) return;
    cameraEnabled.value = !cameraEnabled.value;
    await localVideoTrack.setEnabled(cameraEnabled.value);
}

async function toggleScreenShare() {
    if (screenSharing.value) {
        if (screenTrack) {
            await client.unpublish(screenTrack);
            screenTrack.close();
            screenTrack = null;
        }
        await client.publish(localVideoTrack);
        screenSharing.value = false;
    } else {
        try {
            screenTrack = await AgoraRTC.createScreenVideoTrack();
            await client.unpublish(localVideoTrack);
            await client.publish(screenTrack);
            screenSharing.value = true;
        } catch (e) {
            console.error('Screen share error', e);
        }
    }
}

async function endCall() {
    endingSession.value = true;
    try {
        const body = props.role === 'doctor'
            ? { diagnosis: diagnosis.value, doctor_notes: doctorNotes.value }
            : {};
        await axios.post(`/api/online-consultations/${props.consultationId}/end`, body);
    } catch (e) {
        console.error(e);
    }
    await cleanup();
    phase.value = 'ended';
}

async function cleanup() {
    if (timerInterval) { clearInterval(timerInterval); timerInterval = null; }
    if (localAudioTrack) { localAudioTrack.close(); localAudioTrack = null; }
    if (localVideoTrack) { localVideoTrack.close(); localVideoTrack = null; }
    if (screenTrack) { screenTrack.close(); screenTrack = null; }
    if (client) { try { await client.leave(); } catch (e) {} client = null; }
}

function formatTime(seconds) {
    const m = Math.floor(seconds / 60).toString().padStart(2, '0');
    const s = (seconds % 60).toString().padStart(2, '0');
    return `${m}:${s}`;
}

const firstRemoteUser = computed(() => remoteUsers.value[0] || null);

onMounted(fetchTokenAndJoin);
onBeforeUnmount(cleanup);
</script>

<template>
    <div class="fixed inset-0 bg-[#0a1628] text-white z-50 flex flex-col">
        <!-- Loading -->
        <div v-if="phase === 'loading'" class="flex-1 flex items-center justify-center">
            <div class="text-center">
                <svg class="w-12 h-12 animate-spin mx-auto mb-4" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                </svg>
                <p class="text-lg">جاري الاتصال...</p>
            </div>
        </div>

        <!-- Error -->
        <div v-else-if="phase === 'error'" class="flex-1 flex items-center justify-center p-8">
            <div class="bg-red-900/30 border border-red-500 rounded-2xl p-8 max-w-md text-center">
                <svg class="w-16 h-16 text-red-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                </svg>
                <h2 class="text-xl font-bold mb-2">تعذر الاتصال</h2>
                <p class="text-gray-300 mb-4">{{ errorMessage }}</p>
                <a :href="role === 'doctor' ? '/doctor/online-consultations' : '/ar/patient/online-consultations'"
                    class="inline-block px-6 py-2 rounded-xl bg-[#C4A265] text-[#0a1628] font-bold">
                    العودة
                </a>
            </div>
        </div>

        <!-- Pre-call check -->
        <div v-else-if="phase === 'precall'" class="flex-1 flex items-center justify-center p-8">
            <div class="bg-white/10 backdrop-blur-md border border-white/20 rounded-3xl p-8 max-w-lg w-full">
                <h2 class="text-2xl font-bold mb-6 text-center">جاهز للجلسة؟</h2>

                <div v-if="consultation" class="space-y-4 mb-8">
                    <div class="flex items-center gap-4 p-4 bg-white/5 rounded-xl">
                        <div class="w-14 h-14 rounded-full bg-[#C4A265] flex items-center justify-center text-2xl font-bold text-[#0a1628]">
                            {{ role === 'patient' ? (consultation.doctor.name_ar?.charAt(3) || 'د') : (consultation.patient.full_name?.charAt(0) || 'م') }}
                        </div>
                        <div>
                            <div class="text-xs text-gray-300">
                                {{ role === 'patient' ? 'الطبيب' : 'المريض' }}
                            </div>
                            <div class="font-bold">
                                {{ role === 'patient' ? consultation.doctor.name_ar : consultation.patient.full_name }}
                            </div>
                            <div class="text-sm text-gray-400">
                                {{ role === 'patient' ? consultation.doctor.specialization_ar : 'الموعد: ' + consultation.start_time }}
                            </div>
                        </div>
                    </div>

                    <div class="text-sm text-gray-300 p-3 rounded-xl bg-white/5">
                        <div class="font-semibold mb-1">قبل البدء تأكد:</div>
                        <ul class="space-y-1 text-xs">
                            <li>✓ الميكروفون مُتاح</li>
                            <li>✓ الكاميرا مُتاحة</li>
                            <li>✓ اتصال إنترنت جيد</li>
                            <li>✓ مكان هادئ</li>
                        </ul>
                    </div>
                </div>

                <button @click="startCall" class="w-full py-4 rounded-2xl bg-gradient-to-r from-[#C4A265] to-[#B89555] text-[#0a1628] font-black text-lg hover:shadow-xl transition">
                    انضم للجلسة الآن
                </button>
            </div>
        </div>

        <!-- Session ended -->
        <div v-else-if="phase === 'ended'" class="flex-1 flex items-center justify-center">
            <div class="text-center">
                <svg class="w-20 h-20 mx-auto mb-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <h2 class="text-2xl font-bold mb-2">انتهت الجلسة</h2>
                <p class="text-gray-400 mb-6">شكراً لك</p>
                <a :href="role === 'doctor' ? '/doctor/online-consultations' : '/ar/patient/online-consultations'"
                    class="px-6 py-3 rounded-xl bg-[#C4A265] text-[#0a1628] font-bold">
                    العودة للرئيسية
                </a>
            </div>
        </div>

        <!-- Connecting / Connected -->
        <template v-else>
            <!-- Top bar -->
            <div class="bg-[#0f1f3a] border-b border-white/10 px-4 py-3 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-2 h-2 rounded-full animate-pulse" :class="phase === 'connected' ? 'bg-emerald-500' : 'bg-amber-500'"></div>
                    <span class="text-sm font-semibold">{{ phase === 'connected' ? 'متصل' : 'جاري الاتصال...' }}</span>
                    <span v-if="phase === 'connected'" class="text-xs text-gray-400 font-mono">{{ formatTime(sessionTimer) }}</span>
                </div>
                <div class="text-sm text-gray-300" v-if="consultation">
                    {{ role === 'patient' ? consultation.doctor.name_ar : consultation.patient.full_name }}
                </div>
            </div>

            <!-- Main area -->
            <div class="flex-1 flex">
                <!-- Video area -->
                <div class="flex-1 relative bg-[#050d1c]">
                    <!-- Remote video -->
                    <div v-if="firstRemoteUser" class="absolute inset-0">
                        <div :id="`remote-video-${firstRemoteUser.uid}`" class="w-full h-full"></div>
                    </div>
                    <div v-else class="absolute inset-0 flex items-center justify-center">
                        <div class="text-center text-gray-500">
                            <svg class="w-24 h-24 mx-auto mb-4 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                            </svg>
                            <p>في انتظار الطرف الآخر...</p>
                        </div>
                    </div>

                    <!-- Local video (picture-in-picture) -->
                    <div class="absolute bottom-24 end-4 w-32 h-44 md:w-48 md:h-64 rounded-2xl overflow-hidden border-2 border-[#C4A265] shadow-2xl bg-black">
                        <div id="local-video" class="w-full h-full"></div>
                        <div v-if="!cameraEnabled" class="absolute inset-0 bg-black flex items-center justify-center">
                            <svg class="w-10 h-10 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z M3 3l18 18"></path>
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Doctor side panel -->
                <div v-if="role === 'doctor' && sidePanelOpen && consultation" class="w-96 bg-[#0f1f3a] border-s border-white/10 p-5 overflow-y-auto">
                    <h3 class="text-lg font-bold mb-4">معلومات المريض</h3>

                    <div class="bg-white/5 rounded-xl p-4 mb-4">
                        <div class="font-bold">{{ consultation.patient.full_name }}</div>
                        <div class="text-xs text-gray-400 mt-1">
                            {{ consultation.patient.phone }} · {{ consultation.patient.gender === 'male' ? 'ذكر' : 'أنثى' }}
                        </div>
                    </div>

                    <div v-if="consultation.chief_complaint" class="bg-amber-500/10 border border-amber-500/30 rounded-xl p-3 mb-4">
                        <div class="text-xs font-semibold text-amber-300 mb-1">شكوى المريض</div>
                        <p class="text-sm">{{ consultation.chief_complaint }}</p>
                    </div>

                    <div class="space-y-3">
                        <div>
                            <label class="block text-xs font-semibold text-gray-300 mb-1">التشخيص</label>
                            <textarea v-model="diagnosis" rows="3"
                                class="doctorato-input w-full px-3 py-2 bg-white/5 border border-white/10 rounded-lg text-sm"
                                placeholder="ادخل التشخيص..."></textarea>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-300 mb-1">ملاحظات الطبيب</label>
                            <textarea v-model="doctorNotes" rows="5"
                                class="doctorato-input w-full px-3 py-2 bg-white/5 border border-white/10 rounded-lg text-sm"
                                placeholder="ملاحظات..."></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bottom controls -->
            <div class="bg-[#0f1f3a] border-t border-white/10 px-4 py-4">
                <div class="flex items-center justify-center gap-3">
                    <button @click="toggleMic"
                        class="w-14 h-14 rounded-full flex items-center justify-center transition"
                        :class="micEnabled ? 'bg-white/10 hover:bg-white/20' : 'bg-red-600 hover:bg-red-700'">
                        <svg v-if="micEnabled" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"></path>
                        </svg>
                        <svg v-else class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z M17 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2"></path>
                        </svg>
                    </button>

                    <button @click="toggleCamera"
                        class="w-14 h-14 rounded-full flex items-center justify-center transition"
                        :class="cameraEnabled ? 'bg-white/10 hover:bg-white/20' : 'bg-red-600 hover:bg-red-700'">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                        </svg>
                    </button>

                    <button v-if="role === 'doctor'" @click="toggleScreenShare"
                        class="w-14 h-14 rounded-full flex items-center justify-center transition"
                        :class="screenSharing ? 'bg-emerald-600 hover:bg-emerald-700' : 'bg-white/10 hover:bg-white/20'">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                        </svg>
                    </button>

                    <button v-if="role === 'doctor'" @click="sidePanelOpen = !sidePanelOpen"
                        class="w-14 h-14 rounded-full bg-white/10 hover:bg-white/20 flex items-center justify-center transition">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path>
                        </svg>
                    </button>

                    <button @click="endCall" :disabled="endingSession"
                        class="px-8 h-14 rounded-full bg-red-600 hover:bg-red-700 flex items-center gap-2 font-bold transition disabled:opacity-50">
                        <svg class="w-6 h-6 rotate-[135deg]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                        </svg>
                        {{ endingSession ? 'إنهاء...' : 'إنهاء الجلسة' }}
                    </button>
                </div>
            </div>
        </template>
    </div>
</template>
