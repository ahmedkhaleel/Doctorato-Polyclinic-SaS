<script setup>
import { ref, computed, onMounted } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import DoctorLayout from '@/Layouts/DoctorLayout.vue';
import SkeletonLoader from '@/Components/Doctor/SkeletonLoader.vue';

defineOptions({ layout: DoctorLayout });

const page = usePage();
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const props = defineProps({
    records: Array,
    summary: Object,
    filters: Object,
    today: Object,
});

/* ── Clock ── */
const currentTime = ref('');
const currentDate = ref('');

function updateClock() {
    const now = new Date();
    currentTime.value = now.toLocaleTimeString(isRtl.value ? 'ar-EG' : 'en-GB', { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: true });
    currentDate.value = now.toLocaleDateString(isRtl.value ? 'ar-EG' : 'en-GB', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
}
updateClock();
let clockInterval;
onMounted(() => { clockInterval = setInterval(updateClock, 1000); });

/* ── Check In / Out ── */
const checkingIn = ref(false);
const checkingOut = ref(false);
const locationStatus = ref('');
const actionError = ref('');

function doCheckIn() {
    checkingIn.value = true;
    actionError.value = '';
    locationStatus.value = isRtl.value ? 'جاري تحديد الموقع...' : 'Getting location...';

    getLocation((lat, lng) => {
        locationStatus.value = isRtl.value ? 'جاري التسجيل...' : 'Recording...';
        router.post('/doctor/my-attendance/check-in', { lat, lng }, {
            preserveScroll: true,
            onFinish: () => { checkingIn.value = false; locationStatus.value = ''; },
            onError: () => { actionError.value = isRtl.value ? 'حدث خطأ' : 'An error occurred'; },
        });
    }, () => {
        locationStatus.value = isRtl.value ? 'جاري التسجيل بدون موقع...' : 'Recording without location...';
        router.post('/doctor/my-attendance/check-in', { lat: null, lng: null }, {
            preserveScroll: true,
            onFinish: () => { checkingIn.value = false; locationStatus.value = ''; },
        });
    });
}

function doCheckOut() {
    checkingOut.value = true;
    actionError.value = '';
    locationStatus.value = isRtl.value ? 'جاري تحديد الموقع...' : 'Getting location...';

    getLocation((lat, lng) => {
        locationStatus.value = isRtl.value ? 'جاري التسجيل...' : 'Recording...';
        router.post('/doctor/my-attendance/check-out', { lat, lng }, {
            preserveScroll: true,
            onFinish: () => { checkingOut.value = false; locationStatus.value = ''; },
            onError: () => { actionError.value = isRtl.value ? 'حدث خطأ' : 'An error occurred'; },
        });
    }, () => {
        locationStatus.value = isRtl.value ? 'جاري التسجيل بدون موقع...' : 'Recording without location...';
        router.post('/doctor/my-attendance/check-out', { lat: null, lng: null }, {
            preserveScroll: true,
            onFinish: () => { checkingOut.value = false; locationStatus.value = ''; },
        });
    });
}

function getLocation(onSuccess, onFail) {
    if (!navigator.geolocation) { onFail(); return; }
    navigator.geolocation.getCurrentPosition(
        (pos) => onSuccess(pos.coords.latitude, pos.coords.longitude),
        () => onFail(),
        { enableHighAccuracy: true, timeout: 8000 }
    );
}

/* ── Filters ── */
const monthsEn = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
const monthsAr = ['يناير', 'فبراير', 'مارس', 'أبريل', 'مايو', 'يونيو', 'يوليو', 'أغسطس', 'سبتمبر', 'أكتوبر', 'نوفمبر', 'ديسمبر'];
const months = computed(() => monthsEn.map((en, i) => ({ value: i + 1, label: isRtl.value ? monthsAr[i] : en })));

const currentYear = new Date().getFullYear();
const years = Array.from({ length: 5 }, (_, i) => currentYear - i);
const selectedMonth = ref(props.filters?.month || new Date().getMonth() + 1);
const selectedYear = ref(props.filters?.year || currentYear);

function applyFilter() {
    router.get('/doctor/my-attendance', { month: selectedMonth.value, year: selectedYear.value }, { preserveState: true, replace: true });
}

/* ── Helpers ── */
function formatTime(time) {
    if (!time) return '--:--';
    const parts = time.split(':');
    if (parts.length < 2) return time;
    let h = parseInt(parts[0]); const m = parts[1];
    const ampm = h >= 12 ? (isRtl.value ? 'م' : 'PM') : (isRtl.value ? 'ص' : 'AM');
    h = h % 12 || 12;
    return `${h}:${m} ${ampm}`;
}

const statusConfig = {
    present: { label: isRtl.value ? 'حاضر' : 'Present', bg: 'bg-emerald-50', text: 'text-emerald-700', dot: 'bg-emerald-500' },
    absent: { label: isRtl.value ? 'غائب' : 'Absent', bg: 'bg-red-50', text: 'text-red-700', dot: 'bg-red-500' },
    late: { label: isRtl.value ? 'متأخر' : 'Late', bg: 'bg-amber-50', text: 'text-amber-700', dot: 'bg-amber-500' },
    leave: { label: isRtl.value ? 'إجازة' : 'On Leave', bg: 'bg-slate-50', text: 'text-[#1B365D]', dot: 'bg-[#1B365D]' },
};

const hasCheckedIn = computed(() => !!props.today?.check_in);
const hasCheckedOut = computed(() => !!props.today?.check_out);

const headerLoaded = ref(false);
const cardsLoaded = ref(false);
const dataLoading = ref(true);
onMounted(() => {
    setTimeout(() => headerLoaded.value = true, 50);
    setTimeout(() => cardsLoaded.value = true, 200);
    setTimeout(() => dataLoading.value = false, 600);
});
</script>

<template>
    <div class="space-y-6">
        <!-- ═══ Hero Header ═══ -->
        <div
            class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-[#1B365D] via-[#1B365D] to-[#0F2444] p-6 sm:p-8 transition-all duration-700"
            :class="headerLoaded ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'"
        >
            <div class="absolute top-0 right-0 w-96 h-96 bg-gradient-radial from-emerald-500/10 to-transparent rounded-full -translate-y-1/2 translate-x-1/2"></div>

            <div class="relative z-10">
                <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                    <div class="flex items-center gap-4">
                        <div class="w-14 h-14 rounded-xl bg-gradient-to-br from-emerald-400 to-emerald-600 flex items-center justify-center shadow-lg shadow-emerald-500/20">
                            <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9h.01M9 16h.01M12 12h3m-3 4h3" /></svg>
                        </div>
                        <div>
                            <h1 class="text-2xl font-bold text-white">{{ $t('a_my_attendance') }}</h1>
                            <p class="text-sm text-gray-400 mt-0.5">{{ isRtl ? 'سجّل حضورك وانصرافك بضغطة زر' : 'Check in and out with one tap' }}</p>
                        </div>
                    </div>

                    <!-- Filter -->
                    <div class="flex items-center gap-2 flex-wrap">
                        <select v-model="selectedMonth" @change="applyFilter" class="px-3 py-2 bg-white/10 border border-white/20 rounded-xl text-sm text-white focus:ring-2 focus:ring-emerald-400/50 [&>option]:text-gray-900">
                            <option v-for="m in months" :key="m.value" :value="m.value">{{ m.label }}</option>
                        </select>
                        <select v-model="selectedYear" @change="applyFilter" class="px-3 py-2 bg-white/10 border border-white/20 rounded-xl text-sm text-white focus:ring-2 focus:ring-emerald-400/50 [&>option]:text-gray-900">
                            <option v-for="y in years" :key="y" :value="y">{{ y }}</option>
                        </select>
                    </div>
                </div>

                <!-- Summary Stats -->
                <div class="grid grid-cols-3 sm:grid-cols-5 gap-3 mt-6">
                    <div class="bg-white/5 backdrop-blur-sm rounded-xl px-4 py-3 border border-white/10">
                        <p class="text-[10px] text-gray-400 uppercase font-semibold">{{ isRtl ? 'حاضر' : 'Present' }}</p>
                        <p class="text-xl font-bold text-emerald-400">{{ summary?.present ?? 0 }}</p>
                    </div>
                    <div class="bg-white/5 backdrop-blur-sm rounded-xl px-4 py-3 border border-white/10">
                        <p class="text-[10px] text-gray-400 uppercase font-semibold">{{ isRtl ? 'غائب' : 'Absent' }}</p>
                        <p class="text-xl font-bold text-red-400">{{ summary?.absent ?? 0 }}</p>
                    </div>
                    <div class="bg-white/5 backdrop-blur-sm rounded-xl px-4 py-3 border border-white/10">
                        <p class="text-[10px] text-gray-400 uppercase font-semibold">{{ isRtl ? 'متأخر' : 'Late' }}</p>
                        <p class="text-xl font-bold text-amber-400">{{ summary?.late ?? 0 }}</p>
                    </div>
                    <div class="bg-white/5 backdrop-blur-sm rounded-xl px-4 py-3 border border-white/10">
                        <p class="text-[10px] text-gray-400 uppercase font-semibold">{{ isRtl ? 'إجازة' : 'Leave' }}</p>
                        <p class="text-xl font-bold text-slate-400">{{ summary?.leave ?? 0 }}</p>
                    </div>
                    <div class="bg-white/5 backdrop-blur-sm rounded-xl px-4 py-3 border border-white/10 hidden sm:block">
                        <p class="text-[10px] text-gray-400 uppercase font-semibold">{{ isRtl ? 'إضافي' : 'Overtime' }}</p>
                        <p class="text-xl font-bold text-slate-400">{{ summary?.overtime_hours ?? 0 }}<span class="text-xs text-gray-500">h</span></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- ═══ TODAY'S CHECK-IN/OUT ═══ -->
        <div
            class="bg-white rounded-2xl border border-gray-100 shadow-lg overflow-hidden transition-all duration-700"
            :class="headerLoaded ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'"
        >
            <!-- Live Clock -->
            <div class="bg-gradient-to-r from-gray-50 to-white px-4 sm:px-6 py-4 border-b border-gray-100">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">{{ isRtl ? 'اليوم' : 'Today' }}</p>
                        <p class="text-sm font-medium text-gray-600 mt-0.5">{{ currentDate }}</p>
                    </div>
                    <div class="text-end">
                        <p class="text-3xl font-bold text-gray-800 tabular-nums tracking-tight" dir="ltr">{{ currentTime }}</p>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="p-4 sm:p-6">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <!-- Check In -->
                    <button
                        @click="doCheckIn"
                        :disabled="checkingIn || hasCheckedIn"
                        class="w-full group relative overflow-hidden rounded-2xl p-6 text-center transition-all duration-500 border-2"
                        :class="hasCheckedIn
                            ? 'bg-emerald-50 border-emerald-200 cursor-default'
                            : checkingIn
                                ? 'bg-emerald-50 border-emerald-300 cursor-wait'
                                : 'bg-white border-gray-200 hover:border-emerald-400 hover:shadow-xl hover:shadow-emerald-100/50 hover:-translate-y-1 active:translate-y-0 cursor-pointer'"
                    >
                        <div v-if="!hasCheckedIn && !checkingIn" class="absolute inset-0 flex items-center justify-center pointer-events-none">
                            <div class="w-20 h-20 rounded-full border-2 border-emerald-300/40 animate-ping"></div>
                        </div>

                        <div class="relative mx-auto w-16 h-16 rounded-2xl flex items-center justify-center mb-3 transition-all duration-300"
                            :class="hasCheckedIn ? 'bg-emerald-500' : 'bg-emerald-100 group-hover:bg-emerald-500'">
                            <svg v-if="checkingIn" class="w-8 h-8 text-emerald-600 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                            <svg v-else-if="hasCheckedIn" class="w-8 h-8 text-white" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" /></svg>
                            <svg v-else class="w-8 h-8 text-emerald-600 group-hover:text-white transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" /></svg>
                        </div>

                        <h3 class="text-lg font-bold" :class="hasCheckedIn ? 'text-emerald-700' : 'text-gray-800'">
                            {{ hasCheckedIn ? (isRtl ? 'تم تسجيل الحضور' : 'Checked In') : (isRtl ? 'تسجيل الحضور' : 'Check In') }}
                        </h3>
                        <p v-if="hasCheckedIn" class="text-sm font-semibold text-emerald-600 mt-1" dir="ltr">{{ formatTime(today?.check_in) }}</p>
                        <p v-else-if="checkingIn" class="text-xs text-emerald-500 mt-1">{{ locationStatus }}</p>
                        <p v-else class="text-xs text-gray-400 mt-1">{{ isRtl ? 'اضغط لتسجيل الحضور + الموقع تلقائياً' : 'Tap to auto record time + location' }}</p>
                        <div v-if="hasCheckedIn && today?.check_in_lat" class="mt-2 inline-flex items-center gap-1 text-[10px] text-emerald-500 font-medium">
                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd" /></svg>
                            {{ isRtl ? 'الموقع مسجل' : 'Location recorded' }}
                        </div>
                    </button>

                    <!-- Check Out -->
                    <button
                        @click="doCheckOut"
                        :disabled="checkingOut || !hasCheckedIn || hasCheckedOut"
                        class="w-full group relative overflow-hidden rounded-2xl p-6 text-center transition-all duration-500 border-2"
                        :class="hasCheckedOut
                            ? 'bg-red-50 border-red-200 cursor-default'
                            : !hasCheckedIn
                                ? 'bg-gray-50 border-gray-200 opacity-50 cursor-not-allowed'
                                : checkingOut
                                    ? 'bg-red-50 border-red-300 cursor-wait'
                                    : 'bg-white border-gray-200 hover:border-red-400 hover:shadow-xl hover:shadow-red-100/50 hover:-translate-y-1 active:translate-y-0 cursor-pointer'"
                    >
                        <div v-if="hasCheckedIn && !hasCheckedOut && !checkingOut" class="absolute inset-0 flex items-center justify-center pointer-events-none">
                            <div class="w-20 h-20 rounded-full border-2 border-red-300/40 animate-ping"></div>
                        </div>

                        <div class="relative mx-auto w-16 h-16 rounded-2xl flex items-center justify-center mb-3 transition-all duration-300"
                            :class="hasCheckedOut ? 'bg-red-500' : hasCheckedIn ? 'bg-red-100 group-hover:bg-red-500' : 'bg-gray-100'">
                            <svg v-if="checkingOut" class="w-8 h-8 text-red-600 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                            <svg v-else-if="hasCheckedOut" class="w-8 h-8 text-white" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" /></svg>
                            <svg v-else class="w-8 h-8 transition-colors" :class="hasCheckedIn ? 'text-red-600 group-hover:text-white' : 'text-gray-300'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" /></svg>
                        </div>

                        <h3 class="text-lg font-bold" :class="hasCheckedOut ? 'text-red-700' : hasCheckedIn ? 'text-gray-800' : 'text-gray-400'">
                            {{ hasCheckedOut ? (isRtl ? 'تم تسجيل الانصراف' : 'Checked Out') : (isRtl ? 'تسجيل الانصراف' : 'Check Out') }}
                        </h3>
                        <p v-if="hasCheckedOut" class="text-sm font-semibold text-red-600 mt-1" dir="ltr">{{ formatTime(today?.check_out) }}</p>
                        <p v-else-if="checkingOut" class="text-xs text-red-500 mt-1">{{ locationStatus }}</p>
                        <p v-else-if="!hasCheckedIn" class="text-xs text-gray-400 mt-1">{{ isRtl ? 'سجّل الحضور أولاً' : 'Check in first' }}</p>
                        <p v-else class="text-xs text-gray-400 mt-1">{{ isRtl ? 'اضغط لتسجيل الانصراف + الموقع تلقائياً' : 'Tap to auto record time + location' }}</p>
                        <div v-if="hasCheckedOut && today?.check_out_lat" class="mt-2 inline-flex items-center gap-1 text-[10px] text-red-500 font-medium">
                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd" /></svg>
                            {{ isRtl ? 'الموقع مسجل' : 'Location recorded' }}
                        </div>
                    </button>
                </div>

                <!-- Messages -->
                <p v-if="actionError" class="mt-3 text-xs text-red-500 font-medium text-center bg-red-50 rounded-lg py-2">{{ actionError }}</p>
                <p v-if="$page.props.flash?.success" class="mt-3 text-xs text-emerald-600 font-semibold text-center bg-emerald-50 rounded-lg py-2.5 flex items-center justify-center gap-1.5">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" /></svg>
                    {{ $page.props.flash.success }}
                </p>
                <p v-if="$page.props.flash?.error" class="mt-3 text-xs text-red-600 font-semibold text-center bg-red-50 rounded-lg py-2.5">{{ $page.props.flash.error }}</p>
            </div>
        </div>

        <!-- Skeleton Loader -->
        <SkeletonLoader v-if="dataLoading" type="list" :count="5" />

        <!-- ═══ RECORDS ═══ -->
        <div v-else class="transition-all duration-700" :class="cardsLoaded ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-6'">
            <h2 class="text-sm font-bold text-gray-500 uppercase tracking-wider mb-3">{{ isRtl ? 'سجلات الحضور' : 'Attendance Records' }}</h2>

            <div v-if="records && records.length" class="space-y-2">
                <div
                    v-for="record in records"
                    :key="record.id || record.date"
                    class="bg-white rounded-xl border border-gray-100 shadow-sm hover:shadow-md transition-all duration-200 p-4"
                >
                    <div class="flex items-center gap-4">
                        <!-- Date Circle -->
                        <div class="w-12 h-12 rounded-xl flex flex-col items-center justify-center flex-shrink-0"
                            :class="statusConfig[record.status]?.bg || 'bg-gray-50'">
                            <span class="text-sm font-bold leading-none" :class="statusConfig[record.status]?.text || 'text-gray-600'">
                                {{ record.date ? new Date(record.date).getDate() : '-' }}
                            </span>
                            <span class="text-[9px] font-medium mt-0.5" :class="(statusConfig[record.status]?.text || 'text-gray-400') + ' opacity-70'">
                                {{ record.date ? new Date(record.date).toLocaleDateString(isRtl ? 'ar-EG' : 'en', { weekday: 'short' }) : '' }}
                            </span>
                        </div>

                        <!-- Times -->
                        <div class="flex-1 grid grid-cols-2 sm:grid-cols-3 gap-3">
                            <div>
                                <p class="text-[10px] text-gray-400 font-semibold uppercase">{{ isRtl ? 'حضور' : 'In' }}</p>
                                <p class="text-sm font-bold text-gray-700 tabular-nums mt-0.5">{{ formatTime(record.check_in) }}</p>
                                <span v-if="record.check_in_lat" class="inline-flex items-center gap-0.5 text-[9px] text-emerald-500 font-medium">
                                    <svg class="w-2.5 h-2.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd" /></svg>
                                    {{ isRtl ? 'موقع' : 'GPS' }}
                                </span>
                            </div>
                            <div>
                                <p class="text-[10px] text-gray-400 font-semibold uppercase">{{ isRtl ? 'انصراف' : 'Out' }}</p>
                                <p class="text-sm font-bold text-gray-700 tabular-nums mt-0.5">{{ formatTime(record.check_out) }}</p>
                                <span v-if="record.check_out_lat" class="inline-flex items-center gap-0.5 text-[9px] text-red-400 font-medium">
                                    <svg class="w-2.5 h-2.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd" /></svg>
                                    {{ isRtl ? 'موقع' : 'GPS' }}
                                </span>
                            </div>
                            <div class="hidden sm:block">
                                <p class="text-[10px] text-gray-400 font-semibold uppercase">{{ isRtl ? 'إضافي' : 'OT' }}</p>
                                <p class="text-sm font-bold text-[#1B365D] mt-0.5">{{ record.overtime_hours > 0 ? record.overtime_hours + 'h' : '-' }}</p>
                            </div>
                        </div>

                        <!-- Status -->
                        <span v-if="statusConfig[record.status]"
                            class="inline-flex items-center gap-1.5 text-[11px] font-semibold px-3 py-1.5 rounded-full flex-shrink-0"
                            :class="[statusConfig[record.status].bg, statusConfig[record.status].text]">
                            <span class="w-1.5 h-1.5 rounded-full" :class="statusConfig[record.status].dot"></span>
                            {{ statusConfig[record.status].label }}
                        </span>
                    </div>
                    <p v-if="record.notes" class="text-xs text-gray-500 mt-2 ltr:ml-16 rtl:mr-16 bg-gray-50 px-3 py-1.5 rounded-lg">{{ record.notes }}</p>
                </div>
            </div>

            <!-- Empty -->
            <div v-else class="text-center py-16 bg-white rounded-xl border border-gray-100">
                <div class="w-16 h-16 mx-auto bg-gray-100 rounded-full flex items-center justify-center mb-4">
                    <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                </div>
                <p class="text-sm text-gray-500">{{ isRtl ? 'لا توجد سجلات حضور لهذه الفترة' : 'No attendance records for this period' }}</p>
            </div>
        </div>
    </div>
</template>
