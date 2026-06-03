<script setup>
import { ref, watch, computed, onMounted, onBeforeUnmount, nextTick } from 'vue';
import { Link, router, useForm, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { usePermissions } from '@/Composables/usePermissions.js';
import { useConfirm } from '@/Composables/useConfirm.js';

const { can } = usePermissions();
const { confirm } = useConfirm();

const page = usePage();
const locale = computed(() => page.props.locale || 'ar');
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const props = defineProps({
    attendances: Object,
    users: Array,
    filters: Object,
    todayRecords: Object,
});

/* ── Filters ── */
const employeeFilter = ref(props.filters?.user_id || '');
const statusFilter = ref(props.filters?.status || '');
const dateFrom = ref(props.filters?.date_from || '');
const dateTo = ref(props.filters?.date_to || '');

function applyFilters() {
    router.get('/admin/attendances', {
        user_id: employeeFilter.value || undefined,
        status: statusFilter.value || undefined,
        date_from: dateFrom.value || undefined,
        date_to: dateTo.value || undefined,
    }, { preserveState: true, replace: true });
}

watch([employeeFilter, statusFilter], () => applyFilters());

let dateTimeout = null;
watch([dateFrom, dateTo], () => {
    clearTimeout(dateTimeout);
    dateTimeout = setTimeout(() => applyFilters(), 400);
});

/* ── Mark Attendance Form ── */
const showForm = ref(false);
const editingId = ref(null);

const form = useForm({
    user_id: '',
    date: new Date().toISOString().slice(0, 10),
    check_in: '',
    check_in_lat: null,
    check_in_lng: null,
    check_out: '',
    check_out_lat: null,
    check_out_lng: null,
    status: 'present',
    overtime_hours: '',
    notes: '',
});

/* ── GPS Location ── */
const gettingCheckInLoc = ref(false);
const gettingCheckOutLoc = ref(false);
const checkInLocOk = ref(false);
const checkOutLocOk = ref(false);
const locError = ref('');

function captureLocation(type) {
    if (!navigator.geolocation) {
        locError.value = isRtl.value ? 'المتصفح لا يدعم تحديد الموقع' : 'Geolocation not supported';
        return;
    }
    locError.value = '';
    if (type === 'in') { gettingCheckInLoc.value = true; checkInLocOk.value = false; }
    else { gettingCheckOutLoc.value = true; checkOutLocOk.value = false; }

    navigator.geolocation.getCurrentPosition(
        (pos) => {
            if (type === 'in') {
                form.check_in_lat = pos.coords.latitude;
                form.check_in_lng = pos.coords.longitude;
                gettingCheckInLoc.value = false;
                checkInLocOk.value = true;
            } else {
                form.check_out_lat = pos.coords.latitude;
                form.check_out_lng = pos.coords.longitude;
                gettingCheckOutLoc.value = false;
                checkOutLocOk.value = true;
            }
        },
        (err) => {
            locError.value = isRtl.value ? 'فشل تحديد الموقع — تأكد من السماح بالوصول' : 'Location failed — check permissions';
            if (type === 'in') gettingCheckInLoc.value = false;
            else gettingCheckOutLoc.value = false;
        },
        { enableHighAccuracy: true, timeout: 10000 }
    );
}

/* ── Map Modal ── */
const mapModal = ref(false);
const mapRecord = ref(null);
const mapContainerRef = ref(null);
let leafletMap = null;
let leafletLoaded = false;

function loadLeaflet() {
    return new Promise((resolve) => {
        if (leafletLoaded) return resolve();
        const link = document.createElement('link');
        link.rel = 'stylesheet';
        link.href = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css';
        document.head.appendChild(link);
        const script = document.createElement('script');
        script.src = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';
        script.onload = () => { leafletLoaded = true; resolve(); };
        document.head.appendChild(script);
    });
}

async function openMap(record) {
    mapRecord.value = record;
    mapModal.value = true;
    await loadLeaflet();
    await nextTick();
    setTimeout(() => initMap(record), 100);
}

function initMap(record) {
    if (!mapContainerRef.value || !window.L) return;
    if (leafletMap) { leafletMap.remove(); leafletMap = null; }

    const hasIn = record.check_in_lat && record.check_in_lng;
    const hasOut = record.check_out_lat && record.check_out_lng;
    const center = hasIn ? [record.check_in_lat, record.check_in_lng] : hasOut ? [record.check_out_lat, record.check_out_lng] : [24.7136, 46.6753];

    leafletMap = window.L.map(mapContainerRef.value).setView(center, 15);
    window.L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap'
    }).addTo(leafletMap);

    const greenIcon = window.L.divIcon({ className: '', html: '<div style="width:28px;height:28px;border-radius:50%;background:linear-gradient(135deg,#10b981,#059669);border:3px solid white;box-shadow:0 2px 8px rgba(0,0,0,0.3);display:flex;align-items:center;justify-content:center"><svg width="14" height="14" fill="white" viewBox="0 0 24 24"><path d="M11 16l-4-4m0 0l4-4m-4 4h14"/></svg></div>', iconSize: [28, 28], iconAnchor: [14, 14] });
    const redIcon = window.L.divIcon({ className: '', html: '<div style="width:28px;height:28px;border-radius:50%;background:linear-gradient(135deg,#ef4444,#dc2626);border:3px solid white;box-shadow:0 2px 8px rgba(0,0,0,0.3);display:flex;align-items:center;justify-content:center"><svg width="14" height="14" fill="white" viewBox="0 0 24 24"><path d="M17 16l4-4m0 0l-4-4m4 4H7"/></svg></div>', iconSize: [28, 28], iconAnchor: [14, 14] });

    const bounds = [];
    if (hasIn) {
        window.L.marker([record.check_in_lat, record.check_in_lng], { icon: greenIcon })
            .addTo(leafletMap)
            .bindPopup(`<b>${isRtl.value ? 'موقع الحضور' : 'Check-in Location'}</b><br>${formatTime(record.check_in)}`);
        bounds.push([record.check_in_lat, record.check_in_lng]);
    }
    if (hasOut) {
        window.L.marker([record.check_out_lat, record.check_out_lng], { icon: redIcon })
            .addTo(leafletMap)
            .bindPopup(`<b>${isRtl.value ? 'موقع الانصراف' : 'Check-out Location'}</b><br>${formatTime(record.check_out)}`);
        bounds.push([record.check_out_lat, record.check_out_lng]);
    }
    if (bounds.length === 2) {
        leafletMap.fitBounds(bounds, { padding: [50, 50] });
        window.L.polyline(bounds, { color: '#C4A265', weight: 3, dashArray: '8,8', opacity: 0.7 }).addTo(leafletMap);
    }
}

function closeMap() {
    mapModal.value = false;
    if (leafletMap) { leafletMap.remove(); leafletMap = null; }
}

function hasLocation(record) {
    return (record.check_in_lat && record.check_in_lng) || (record.check_out_lat && record.check_out_lng);
}

function submitAttendance() {
    if (editingId.value) {
        form.post(`/admin/attendances/${editingId.value}/update`, {
            preserveScroll: true,
            onSuccess: () => { form.reset(); showForm.value = false; editingId.value = null; },
        });
    } else {
        form.post('/admin/attendances', {
            preserveScroll: true,
            onSuccess: () => { form.reset(); showForm.value = false; },
        });
    }
}

function editRecord(record) {
    editingId.value = record.id;
    form.user_id = record.user_id;
    form.date = record.date?.slice(0, 10) || '';
    form.check_in = record.check_in?.slice(0, 5) || '';
    form.check_in_lat = record.check_in_lat;
    form.check_in_lng = record.check_in_lng;
    form.check_out = record.check_out?.slice(0, 5) || '';
    form.check_out_lat = record.check_out_lat;
    form.check_out_lng = record.check_out_lng;
    form.status = record.status;
    form.overtime_hours = record.overtime_hours || '';
    form.notes = record.notes || '';
    checkInLocOk.value = !!(record.check_in_lat && record.check_in_lng);
    checkOutLocOk.value = !!(record.check_out_lat && record.check_out_lng);
    showForm.value = true;
}

function cancelForm() {
    showForm.value = false;
    editingId.value = null;
    checkInLocOk.value = false;
    checkOutLocOk.value = false;
    locError.value = '';
    form.reset();
}

function deleteAttendance(id) {
    const msg = isRtl.value ? 'هل أنت متأكد من حذف هذا السجل؟' : 'Are you sure you want to delete this record?';
    confirm(msg, () => {
        router.post(`/admin/attendances/${id}/delete`, { preserveScroll: true });
    });
}

/* ── Custom Employee Picker ── */
const formPickerOpen = ref(false);
const formPickerSearch = ref('');
const formPickerRef = ref(null);
const formSearchRef = ref(null);

const filterPickerOpen = ref(false);
const filterPickerSearch = ref('');
const filterPickerRef = ref(null);
const filterSearchRef = ref(null);

const avatarColors = [
    'from-amber-400 to-[#C4A265]', 'from-slate-400 to-[#1B365D]', 'from-slate-400 to-[#1B365D]',
    'from-slate-400 to-teal-500', 'from-emerald-400 to-emerald-500', 'from-amber-400 to-[#C4A265]',
    'from-red-400 to-[#C4A265]', 'from-slate-400 to-[#C4A265]', 'from-slate-400 to-[#1B365D]',
    'from-lime-400 to-emerald-500',
];
function getUserColor(id) { return avatarColors[(id || 0) % avatarColors.length]; }
function getUserInitials(name) {
    if (!name) return '?';
    const parts = name.trim().split(/\s+/);
    return parts.length > 1 ? (parts[0][0] + parts[1][0]).toUpperCase() : name.slice(0, 2).toUpperCase();
}

const formFilteredUsers = computed(() => {
    const q = formPickerSearch.value.toLowerCase();
    if (!q) return props.users || [];
    return (props.users || []).filter(u => u.name?.toLowerCase().includes(q));
});

const filterFilteredUsers = computed(() => {
    const q = filterPickerSearch.value.toLowerCase();
    if (!q) return props.users || [];
    return (props.users || []).filter(u => u.name?.toLowerCase().includes(q));
});

const selectedFormUser = computed(() => (props.users || []).find(u => u.id == form.user_id));
const selectedFilterUser = computed(() => (props.users || []).find(u => u.id == employeeFilter.value));

function selectFormUser(user) {
    form.user_id = user.id;
    formPickerOpen.value = false;
    formPickerSearch.value = '';
}

function selectFilterUser(user) {
    employeeFilter.value = user ? user.id : '';
    filterPickerOpen.value = false;
    filterPickerSearch.value = '';
}

function toggleFormPicker() {
    if (editingId.value) return;
    formPickerOpen.value = !formPickerOpen.value;
    if (formPickerOpen.value) nextTick(() => formSearchRef.value?.focus());
}

function toggleFilterPicker() {
    filterPickerOpen.value = !filterPickerOpen.value;
    if (filterPickerOpen.value) nextTick(() => filterSearchRef.value?.focus());
}

function handleClickOutside(e) {
    if (formPickerRef.value && !formPickerRef.value.contains(e.target)) formPickerOpen.value = false;
    if (filterPickerRef.value && !filterPickerRef.value.contains(e.target)) filterPickerOpen.value = false;
}
onMounted(() => document.addEventListener('click', handleClickOutside));
onBeforeUnmount(() => document.removeEventListener('click', handleClickOutside));

/* ── Configs ── */
const statusConfig = {
    present: { key: 'a_present',  label_ar: 'حاضر',   label_en: 'Present', bg: 'bg-emerald-50',  text: 'text-emerald-700', border: 'border-emerald-200', dot: 'bg-emerald-500', icon: 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',          gradient: 'from-emerald-500 to-emerald-600' },
    absent:  { key: 'a_absent',   label_ar: 'غائب',   label_en: 'Absent',  bg: 'bg-red-50',      text: 'text-red-700',     border: 'border-red-200',     dot: 'bg-red-500',     icon: 'M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z', gradient: 'from-red-500 to-red-600' },
    late:    { key: 'a_late',     label_ar: 'متأخر',  label_en: 'Late',    bg: 'bg-amber-50',    text: 'text-amber-700',   border: 'border-amber-200',   dot: 'bg-amber-500',   icon: 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z',          gradient: 'from-amber-500 to-amber-600' },
    leave:   { key: 'a_leave',    label_ar: 'إجازة',  label_en: 'Leave',   bg: 'bg-slate-50',      text: 'text-[#1B365D]',     border: 'border-slate-200',     dot: 'bg-[#1B365D]',     icon: 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z', gradient: 'from-[#1B365D] to-[#1B365D]' },
};

function getStatus(s) { return statusConfig[s] || { key: s, label_ar: s, label_en: s, bg: 'bg-gray-50', text: 'text-gray-700', border: 'border-gray-200', dot: 'bg-gray-500', icon: '', gradient: 'from-gray-500 to-gray-600' }; }

function formatDate(d) {
    if (!d) return '-';
    return new Date(d).toLocaleDateString(isRtl.value ? 'ar-EG' : 'en-US', { year: 'numeric', month: 'short', day: 'numeric' });
}

function formatTime(t) {
    if (!t) return null;
    const parts = t.split(':');
    const h = parseInt(parts[0]);
    const m = parts[1];
    if (isRtl.value) {
        return `${h > 12 ? h - 12 : h || 12}:${m} ${h >= 12 ? 'م' : 'ص'}`;
    }
    return `${h > 12 ? h - 12 : h || 12}:${m} ${h >= 12 ? 'PM' : 'AM'}`;
}

function calcWorkingHours(checkIn, checkOut) {
    if (!checkIn || !checkOut) return null;
    const [h1, m1] = checkIn.split(':').map(Number);
    const [h2, m2] = checkOut.split(':').map(Number);
    const diff = (h2 * 60 + m2) - (h1 * 60 + m1);
    if (diff <= 0) return null;
    const hours = Math.floor(diff / 60);
    const mins = diff % 60;
    return { hours, mins, total: diff };
}

/* ── Stats ── */
const stats = computed(() => {
    const data = props.attendances?.data || [];
    return {
        total: props.attendances?.total || data.length,
        present: data.filter(r => r.status === 'present').length,
        absent: data.filter(r => r.status === 'absent').length,
        late: data.filter(r => r.status === 'late').length,
        leave: data.filter(r => r.status === 'leave').length,
    };
});

/* ── Quick Check-In/Out ── */
const quickPanel = ref(true);
const quickBusy = ref({});
const quickLocStatus = ref('');

function getTodayRecord(userId) {
    return props.todayRecords?.[userId] || null;
}

function userCheckedIn(userId) {
    return !!getTodayRecord(userId)?.check_in;
}

function userCheckedOut(userId) {
    return !!getTodayRecord(userId)?.check_out;
}

function quickCheckIn(userId) {
    quickBusy.value[userId] = 'in';
    quickLocStatus.value = isRtl.value ? 'جاري تحديد الموقع...' : 'Getting location...';

    getQuickLocation((lat, lng) => {
        router.post('/admin/attendances/quick-check-in', { user_id: userId, lat, lng }, {
            preserveScroll: true,
            onFinish: () => { delete quickBusy.value[userId]; quickLocStatus.value = ''; },
        });
    }, () => {
        router.post('/admin/attendances/quick-check-in', { user_id: userId, lat: null, lng: null }, {
            preserveScroll: true,
            onFinish: () => { delete quickBusy.value[userId]; quickLocStatus.value = ''; },
        });
    });
}

function quickCheckOut(userId) {
    quickBusy.value[userId] = 'out';
    quickLocStatus.value = isRtl.value ? 'جاري تحديد الموقع...' : 'Getting location...';

    getQuickLocation((lat, lng) => {
        router.post('/admin/attendances/quick-check-out', { user_id: userId, lat, lng }, {
            preserveScroll: true,
            onFinish: () => { delete quickBusy.value[userId]; quickLocStatus.value = ''; },
        });
    }, () => {
        router.post('/admin/attendances/quick-check-out', { user_id: userId, lat: null, lng: null }, {
            preserveScroll: true,
            onFinish: () => { delete quickBusy.value[userId]; quickLocStatus.value = ''; },
        });
    });
}

function getQuickLocation(onSuccess, onFail) {
    if (!navigator.geolocation) { onFail(); return; }
    navigator.geolocation.getCurrentPosition(
        (pos) => onSuccess(pos.coords.latitude, pos.coords.longitude),
        () => onFail(),
        { enableHighAccuracy: true, timeout: 8000 }
    );
}

function formatTimeShort(t) {
    if (!t) return '';
    const parts = t.split(':');
    let h = parseInt(parts[0]); const m = parts[1];
    const ampm = h >= 12 ? (isRtl.value ? 'م' : 'PM') : (isRtl.value ? 'ص' : 'AM');
    h = h % 12 || 12;
    return `${h}:${m} ${ampm}`;
}

/* ── Live Clock ── */
const liveTime = ref('');
const liveDate = ref('');
function updateLiveClock() {
    const now = new Date();
    liveTime.value = now.toLocaleTimeString(isRtl.value ? 'ar-EG' : 'en-GB', { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: true });
    liveDate.value = now.toLocaleDateString(isRtl.value ? 'ar-EG' : 'en-GB', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
}
updateLiveClock();

/* ── Animation ── */
const mounted = ref(false);
onMounted(() => {
    setTimeout(() => mounted.value = true, 50);
    setInterval(updateLiveClock, 1000);
});
</script>

<template>
    <AdminLayout :title="$t('a_attendance')">
        <div class="space-y-6">

            <!-- ═══════════ HEADER ═══════════ -->
            <div
                class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 transition-all duration-700"
                :class="mounted ? 'opacity-100 translate-y-0' : 'opacity-0 -translate-y-4'"
            >
                <div>
                    <h1 class="text-xl md:text-2xl font-bold text-gray-900">{{ $t('a_attendance') }}</h1>
                    <p class="text-sm text-gray-500 mt-1">{{ isRtl ? 'إدارة سجلات الحضور والانصراف للموظفين' : 'Manage employee attendance and time tracking' }}</p>
                </div>
                <button
                    v-if="can('attendances.create')"
                    @click="showForm ? cancelForm() : (showForm = true)"
                    class="group inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-white text-sm font-semibold shadow-sm hover:shadow-md transition-all duration-300 hover:scale-[1.02] active:scale-[0.98]"
                    style="background: linear-gradient(135deg, #C4A265 0%, #B08D4C 100%);"
                >
                    <svg
                        class="w-4 h-4 transition-transform duration-300"
                        :class="showForm ? 'rotate-45' : 'group-hover:rotate-90'"
                        fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"
                    >
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                    </svg>
                    {{ showForm ? (isRtl ? 'إغلاق' : 'Close') : $t('a_mark_attendance') }}
                </button>
            </div>

            <!-- ═══════════ STAT CARDS ═══════════ -->
            <div class="grid grid-cols-2 sm:grid-cols-5 gap-4">
                <div
                    v-for="(stat, idx) in [
                        { label: isRtl ? 'الإجمالي' : 'Total',  value: stats.total,   color: 'gray',    icon: 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01' },
                        { label: isRtl ? 'حاضر' : 'Present',    value: stats.present, color: 'emerald', icon: 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z' },
                        { label: isRtl ? 'غائب' : 'Absent',     value: stats.absent,  color: 'red',     icon: 'M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z' },
                        { label: isRtl ? 'متأخر' : 'Late',      value: stats.late,    color: 'amber',   icon: 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z' },
                        { label: isRtl ? 'إجازة' : 'On Leave',  value: stats.leave,   color: 'sky',     icon: 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z' },
                    ]"
                    :key="idx"
                    class="bg-white rounded-2xl border p-4 shadow-sm hover:shadow-md transition-all duration-500 hover:-translate-y-0.5"
                    :class="[
                        mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4',
                        `border-${stat.color}-100`
                    ]"
                    :style="{ transitionDelay: `${idx * 80}ms` }"
                >
                    <div class="flex items-center justify-between mb-2">
                        <span
                            class="text-[11px] font-semibold uppercase tracking-wider"
                            :class="`text-${stat.color}-500`"
                        >{{ stat.label }}</span>
                        <div
                            class="w-8 h-8 rounded-lg flex items-center justify-center"
                            :class="`bg-${stat.color}-50`"
                        >
                            <svg class="w-4 h-4" :class="`text-${stat.color}-500`" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="stat.icon" />
                            </svg>
                        </div>
                    </div>
                    <p class="text-xl md:text-2xl font-bold" :class="stat.color === 'gray' ? 'text-gray-900' : `text-${stat.color}-600`">{{ stat.value }}</p>
                </div>
            </div>

            <!-- ═══════════ QUICK CHECK-IN/OUT PANEL ═══════════ -->
            <div
                v-if="can('attendances.create')"
                class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden transition-all duration-700"
                :class="mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'"
            >
                <!-- Panel Header -->
                <div class="px-4 md:px-6 py-4 border-b border-gray-100 bg-gradient-to-r from-gray-50 to-white">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-emerald-500 to-emerald-600 flex items-center justify-center shadow-sm">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            </div>
                            <div>
                                <h2 class="text-base font-bold text-gray-900">{{ isRtl ? 'تسجيل سريع' : 'Quick Check-In/Out' }}</h2>
                                <p class="text-xs text-gray-400">{{ isRtl ? 'سجّل حضور وانصراف الموظفين بضغطة زر' : 'One-tap attendance for all employees' }}</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            <div class="text-end hidden sm:block">
                                <p class="text-xs text-gray-400">{{ liveDate }}</p>
                                <p class="text-lg font-bold text-gray-800 tabular-nums" dir="ltr">{{ liveTime }}</p>
                            </div>
                            <button @click="quickPanel = !quickPanel" class="p-2 rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-100 transition-all">
                                <svg class="w-5 h-5 transition-transform duration-300" :class="quickPanel ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Employee Grid -->
                <transition
                    enter-active-class="transition-all duration-400 ease-out"
                    leave-active-class="transition-all duration-300 ease-in"
                    enter-from-class="opacity-0 max-h-0"
                    enter-to-class="opacity-100 max-h-[800px]"
                    leave-from-class="opacity-100 max-h-[800px]"
                    leave-to-class="opacity-0 max-h-0"
                >
                    <div v-if="quickPanel" class="overflow-hidden">
                        <!-- Flash Message -->
                        <div v-if="$page.props.flash?.success" class="mx-6 mt-4 text-xs text-emerald-600 font-semibold bg-emerald-50 rounded-xl px-4 py-2.5 flex items-center gap-2">
                            <svg class="w-4 h-4 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" /></svg>
                            {{ $page.props.flash.success }}
                        </div>
                        <div v-if="$page.props.flash?.error" class="mx-6 mt-4 text-xs text-red-600 font-semibold bg-red-50 rounded-xl px-4 py-2.5">
                            {{ $page.props.flash.error }}
                        </div>

                        <div class="p-4 md:p-6 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                            <div
                                v-for="user in users"
                                :key="user.id"
                                class="flex items-center gap-3 p-3 rounded-xl border transition-all duration-300 hover:shadow-md"
                                :class="userCheckedOut(user.id)
                                    ? 'bg-gray-50 border-gray-200'
                                    : userCheckedIn(user.id)
                                        ? 'bg-emerald-50/50 border-emerald-200'
                                        : 'bg-white border-gray-100 hover:border-gray-200'"
                            >
                                <!-- Avatar -->
                                <div :class="['w-10 h-10 rounded-xl bg-gradient-to-br flex-shrink-0 flex items-center justify-center text-white text-xs font-bold shadow-sm', getUserColor(user.id)]">
                                    {{ getUserInitials(user.name) }}
                                </div>

                                <!-- Info -->
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-semibold text-gray-800 truncate">{{ user.name }}</p>
                                    <div class="flex items-center gap-2 mt-0.5">
                                        <template v-if="userCheckedIn(user.id)">
                                            <span class="inline-flex items-center gap-1 text-[10px] font-medium text-emerald-600">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14" /></svg>
                                                {{ formatTimeShort(getTodayRecord(user.id)?.check_in) }}
                                            </span>
                                        </template>
                                        <template v-if="userCheckedOut(user.id)">
                                            <span class="inline-flex items-center gap-1 text-[10px] font-medium text-red-500">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7" /></svg>
                                                {{ formatTimeShort(getTodayRecord(user.id)?.check_out) }}
                                            </span>
                                        </template>
                                        <template v-if="getTodayRecord(user.id)?.check_in_lat">
                                            <span class="text-[9px] text-emerald-400">
                                                <svg class="w-2.5 h-2.5 inline" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd" /></svg>
                                            </span>
                                        </template>
                                        <span v-if="!userCheckedIn(user.id)" class="text-[10px] text-gray-400">{{ isRtl ? 'لم يسجل بعد' : 'Not checked in' }}</span>
                                    </div>
                                </div>

                                <!-- Action Buttons -->
                                <div class="flex items-center gap-1.5 flex-shrink-0">
                                    <!-- Check In -->
                                    <button
                                        v-if="!userCheckedIn(user.id)"
                                        @click="quickCheckIn(user.id)"
                                        :disabled="!!quickBusy[user.id]"
                                        class="px-3 py-1.5 rounded-lg text-xs font-semibold transition-all duration-300 flex items-center gap-1.5"
                                        :class="quickBusy[user.id] === 'in'
                                            ? 'bg-emerald-100 text-emerald-500 cursor-wait'
                                            : 'bg-emerald-500 text-white hover:bg-emerald-600 hover:shadow-md active:scale-95'"
                                    >
                                        <svg v-if="quickBusy[user.id] === 'in'" class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                                        <svg v-else class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14" /></svg>
                                        {{ isRtl ? 'حضور' : 'In' }}
                                    </button>

                                    <!-- Checked In Badge -->
                                    <span v-else-if="!userCheckedOut(user.id)" class="inline-flex items-center gap-1 px-2 py-1 rounded-lg bg-emerald-100 text-emerald-700 text-[10px] font-bold">
                                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" /></svg>
                                        {{ isRtl ? 'حاضر' : 'In' }}
                                    </span>

                                    <!-- Check Out -->
                                    <button
                                        v-if="userCheckedIn(user.id) && !userCheckedOut(user.id)"
                                        @click="quickCheckOut(user.id)"
                                        :disabled="!!quickBusy[user.id]"
                                        class="px-3 py-1.5 rounded-lg text-xs font-semibold transition-all duration-300 flex items-center gap-1.5"
                                        :class="quickBusy[user.id] === 'out'
                                            ? 'bg-red-100 text-red-400 cursor-wait'
                                            : 'bg-red-500 text-white hover:bg-red-600 hover:shadow-md active:scale-95'"
                                    >
                                        <svg v-if="quickBusy[user.id] === 'out'" class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                                        <svg v-else class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7" /></svg>
                                        {{ isRtl ? 'انصراف' : 'Out' }}
                                    </button>

                                    <!-- All Done -->
                                    <span v-if="userCheckedOut(user.id)" class="inline-flex items-center gap-1 px-2 py-1 rounded-lg bg-gray-100 text-gray-500 text-[10px] font-bold">
                                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" /></svg>
                                        {{ isRtl ? 'مكتمل' : 'Done' }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </transition>
            </div>

            <!-- ═══════════ MARK ATTENDANCE FORM ═══════════ -->
            <transition
                enter-active-class="transition-all duration-500 ease-out"
                leave-active-class="transition-all duration-300 ease-in"
                enter-from-class="opacity-0 -translate-y-6 scale-[0.97]"
                enter-to-class="opacity-100 translate-y-0 scale-100"
                leave-from-class="opacity-100 translate-y-0 scale-100"
                leave-to-class="opacity-0 -translate-y-6 scale-[0.97]"
            >
                <div v-if="showForm" class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                    <!-- Form Header -->
                    <div class="px-4 md:px-6 py-4 border-b border-gray-100 bg-gradient-to-r from-gray-50 to-white">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-xl flex items-center justify-center text-white" style="background: linear-gradient(135deg, #C4A265 0%, #B08D4C 100%);">
                                <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                </svg>
                            </div>
                            <div>
                                <h2 class="text-base font-bold text-gray-900">
                                    {{ editingId ? (isRtl ? 'تعديل سجل الحضور' : 'Edit Attendance') : $t('a_mark_attendance') }}
                                </h2>
                                <p class="text-xs text-gray-400">{{ isRtl ? 'أدخل بيانات الحضور للموظف' : 'Enter employee attendance details' }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Form Body -->
                    <form @submit.prevent="submitAttendance" class="p-6">
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
                            <!-- Employee (Custom Picker) -->
                            <div ref="formPickerRef" class="relative">
                                <label class="block text-[11px] font-semibold text-gray-400 uppercase tracking-wider mb-1.5">
                                    {{ $t('a_employee') }} <span class="text-red-400">*</span>
                                </label>
                                <button
                                    type="button"
                                    @click="toggleFormPicker"
                                    class="w-full flex items-center gap-3 bg-gray-50/80 border border-gray-200 rounded-xl px-3 py-2 text-start text-sm transition-all duration-200 cursor-pointer"
                                    :class="[
                                        formPickerOpen ? 'ring-2 ring-amber-200/50 border-amber-300 bg-white shadow-md' : 'hover:border-gray-300 hover:bg-white',
                                        editingId ? 'opacity-60 cursor-not-allowed' : ''
                                    ]"
                                >
                                    <template v-if="selectedFormUser">
                                        <div :class="['w-8 h-8 rounded-lg bg-gradient-to-br flex-shrink-0 flex items-center justify-center text-white text-[11px] font-bold shadow-sm', getUserColor(selectedFormUser.id)]">
                                            {{ getUserInitials(selectedFormUser.name) }}
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <p class="text-sm font-semibold text-gray-800 truncate">{{ selectedFormUser.name }}</p>
                                        </div>
                                    </template>
                                    <template v-else>
                                        <div class="w-8 h-8 rounded-lg bg-gray-100 flex-shrink-0 flex items-center justify-center">
                                            <svg class="w-4 h-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                                        </div>
                                        <span class="text-sm text-gray-400 font-medium">{{ isRtl ? 'اختر الموظف...' : 'Select employee...' }}</span>
                                    </template>
                                    <svg class="w-4 h-4 text-gray-400 flex-shrink-0 transition-transform duration-200 ms-auto" :class="formPickerOpen ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                                </button>

                                <!-- Dropdown -->
                                <transition
                                    enter-active-class="transition duration-200 ease-out"
                                    leave-active-class="transition duration-150 ease-in"
                                    enter-from-class="opacity-0 scale-95 -translate-y-2"
                                    enter-to-class="opacity-100 scale-100 translate-y-0"
                                    leave-from-class="opacity-100 scale-100 translate-y-0"
                                    leave-to-class="opacity-0 scale-95 -translate-y-2"
                                >
                                    <div v-if="formPickerOpen" class="absolute z-50 mt-2 w-full bg-white rounded-xl border border-gray-200 shadow-xl overflow-hidden">
                                        <!-- Search -->
                                        <div class="p-2.5 border-b border-gray-100">
                                            <div class="relative">
                                                <svg class="w-4 h-4 text-gray-300 absolute start-3 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                                                <input
                                                    ref="formSearchRef"
                                                    v-model="formPickerSearch"
                                                    type="text"
                                                    :placeholder="isRtl ? 'ابحث عن موظف...' : 'Search employee...'"
                                                    class="doctorato-input w-full bg-gray-50 border-0 rounded-lg ps-9 pe-3 py-2 text-sm text-gray-700 placeholder:text-gray-300 focus:ring-2 focus:ring-[#C4A265]/30/50 focus:bg-white transition-all duration-150"
                                                />
                                            </div>
                                        </div>
                                        <!-- User List -->
                                        <div class="max-h-52 overflow-y-auto overscroll-contain">
                                            <button
                                                v-for="user in formFilteredUsers"
                                                :key="user.id"
                                                type="button"
                                                @click="selectFormUser(user)"
                                                class="w-full flex items-center gap-3 px-3 py-2.5 text-start transition-all duration-150 hover:bg-amber-50/60"
                                                :class="form.user_id == user.id ? 'bg-amber-50/80' : ''"
                                            >
                                                <div :class="['w-8 h-8 rounded-lg bg-gradient-to-br flex-shrink-0 flex items-center justify-center text-white text-[11px] font-bold shadow-sm transition-transform duration-200 hover:scale-110', getUserColor(user.id)]">
                                                    {{ getUserInitials(user.name) }}
                                                </div>
                                                <span class="text-sm font-medium text-gray-700 truncate flex-1">{{ user.name }}</span>
                                                <svg v-if="form.user_id == user.id" class="w-4 h-4 text-amber-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" /></svg>
                                            </button>
                                            <div v-if="formFilteredUsers.length === 0" class="px-4 py-4 md:py-6 text-center">
                                                <svg class="w-8 h-8 text-gray-200 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4-4v2m22-4h.01M12 7a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
                                                <p class="text-xs text-gray-400">{{ isRtl ? 'لا توجد نتائج' : 'No results found' }}</p>
                                            </div>
                                        </div>
                                    </div>
                                </transition>
                                <p v-if="form.errors.user_id" class="mt-1.5 text-xs text-red-500 font-medium">{{ form.errors.user_id }}</p>
                            </div>

                            <!-- Date -->
                            <div>
                                <label class="block text-[11px] font-semibold text-gray-400 uppercase tracking-wider mb-1.5">
                                    {{ $t('a_date') }} <span class="text-red-400">*</span>
                                </label>
                                <input
                                    v-model="form.date"
                                    type="date"
                                    class="doctorato-input w-full bg-gray-50/80 border border-gray-200 rounded-xl px-4 py-2.5 text-sm text-gray-700 font-medium focus:bg-white focus:ring-2 focus:ring-[#C4A265]/30/50 focus:border-[#1B365D] transition-all duration-200"
                                />
                                <p v-if="form.errors.date" class="mt-1.5 text-xs text-red-500 font-medium">{{ form.errors.date }}</p>
                            </div>

                            <!-- Status -->
                            <div>
                                <label class="block text-[11px] font-semibold text-gray-400 uppercase tracking-wider mb-1.5">
                                    {{ $t('a_status') }} <span class="text-red-400">*</span>
                                </label>
                                <div class="grid grid-cols-2 gap-2">
                                    <button
                                        v-for="(cfg, key) in statusConfig"
                                        :key="key"
                                        type="button"
                                        @click="form.status = key"
                                        class="relative flex items-center gap-1.5 px-3 py-2 rounded-xl text-xs font-bold border-2 transition-all duration-200"
                                        :class="form.status === key
                                            ? `${cfg.bg} ${cfg.text} ${cfg.border} shadow-sm scale-[1.02]`
                                            : 'bg-white border-gray-100 text-gray-400 hover:border-gray-200 hover:text-gray-600'"
                                    >
                                        <span :class="cfg.dot" class="w-2 h-2 rounded-full transition-all duration-200" :style="form.status !== key ? 'opacity: 0.3' : ''" />
                                        {{ isRtl ? cfg.label_ar : cfg.label_en }}
                                        <svg v-if="form.status === key" class="w-3 h-3 absolute top-1 end-1" :class="cfg.text" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                        </svg>
                                    </button>
                                </div>
                                <p v-if="form.errors.status" class="mt-1.5 text-xs text-red-500 font-medium">{{ form.errors.status }}</p>
                            </div>

                            <!-- Check In + Location -->
                            <div>
                                <label class="block text-[11px] font-semibold text-gray-400 uppercase tracking-wider mb-1.5">
                                    {{ $t('a_check_in') }}
                                </label>
                                <div class="flex gap-2">
                                    <div class="relative flex-1">
                                        <div class="pointer-events-none absolute inset-y-0 start-0 flex items-center ps-3">
                                            <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" /></svg>
                                        </div>
                                        <input
                                            v-model="form.check_in"
                                            type="time"
                                            class="doctorato-input w-full bg-gray-50/80 border border-gray-200 rounded-xl ps-10 pe-4 py-2.5 text-sm text-gray-700 font-medium focus:bg-white focus:ring-2 focus:ring-[#C4A265]/30/50 focus:border-[#1B365D] transition-all duration-200"
                                        />
                                    </div>
                                    <button
                                        type="button"
                                        @click="captureLocation('in')"
                                        :disabled="gettingCheckInLoc"
                                        class="flex-shrink-0 w-10 h-10 rounded-xl border-2 flex items-center justify-center transition-all duration-300"
                                        :class="checkInLocOk ? 'border-emerald-300 bg-emerald-50 text-emerald-600' : gettingCheckInLoc ? 'border-amber-300 bg-amber-50 text-amber-500' : 'border-gray-200 bg-gray-50 text-gray-400 hover:border-emerald-300 hover:bg-emerald-50 hover:text-emerald-500'"
                                        :title="isRtl ? 'التقاط موقع الحضور' : 'Capture check-in location'"
                                    >
                                        <svg v-if="gettingCheckInLoc" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                                        <svg v-else-if="checkInLocOk" class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" /></svg>
                                        <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                                    </button>
                                </div>
                                <p v-if="checkInLocOk" class="mt-1 text-[10px] text-emerald-500 font-medium flex items-center gap-1">
                                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd" /></svg>
                                    {{ isRtl ? 'تم التقاط الموقع' : 'Location captured' }}
                                </p>
                                <p v-if="form.errors.check_in" class="mt-1.5 text-xs text-red-500 font-medium">{{ form.errors.check_in }}</p>
                            </div>

                            <!-- Check Out + Location -->
                            <div>
                                <label class="block text-[11px] font-semibold text-gray-400 uppercase tracking-wider mb-1.5">
                                    {{ $t('a_check_out') }}
                                </label>
                                <div class="flex gap-2">
                                    <div class="relative flex-1">
                                        <div class="pointer-events-none absolute inset-y-0 start-0 flex items-center ps-3">
                                            <svg class="w-4 h-4 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" /></svg>
                                        </div>
                                        <input
                                            v-model="form.check_out"
                                            type="time"
                                            class="doctorato-input w-full bg-gray-50/80 border border-gray-200 rounded-xl ps-10 pe-4 py-2.5 text-sm text-gray-700 font-medium focus:bg-white focus:ring-2 focus:ring-[#C4A265]/30/50 focus:border-red-300 transition-all duration-200"
                                        />
                                    </div>
                                    <button
                                        type="button"
                                        @click="captureLocation('out')"
                                        :disabled="gettingCheckOutLoc"
                                        class="flex-shrink-0 w-10 h-10 rounded-xl border-2 flex items-center justify-center transition-all duration-300"
                                        :class="checkOutLocOk ? 'border-red-300 bg-red-50 text-red-500' : gettingCheckOutLoc ? 'border-amber-300 bg-amber-50 text-amber-500' : 'border-gray-200 bg-gray-50 text-gray-400 hover:border-red-300 hover:bg-red-50 hover:text-red-500'"
                                        :title="isRtl ? 'التقاط موقع الانصراف' : 'Capture check-out location'"
                                    >
                                        <svg v-if="gettingCheckOutLoc" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                                        <svg v-else-if="checkOutLocOk" class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" /></svg>
                                        <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                                    </button>
                                </div>
                                <p v-if="checkOutLocOk" class="mt-1 text-[10px] text-red-400 font-medium flex items-center gap-1">
                                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd" /></svg>
                                    {{ isRtl ? 'تم التقاط الموقع' : 'Location captured' }}
                                </p>
                                <p v-if="form.errors.check_out" class="mt-1.5 text-xs text-red-500 font-medium">{{ form.errors.check_out }}</p>
                            </div>

                            <!-- Location Error -->
                            <div v-if="locError" class="sm:col-span-2 lg:col-span-3">
                                <p class="text-xs text-red-500 font-medium flex items-center gap-1.5 bg-red-50 rounded-lg px-3 py-2">
                                    <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z" /></svg>
                                    {{ locError }}
                                </p>
                            </div>

                            <!-- Overtime -->
                            <div>
                                <label class="block text-[11px] font-semibold text-gray-400 uppercase tracking-wider mb-1.5">
                                    {{ $t('a_overtime') }}
                                </label>
                                <div class="relative">
                                    <div class="pointer-events-none absolute inset-y-0 start-0 flex items-center ps-3">
                                        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                    </div>
                                    <input
                                        v-model="form.overtime_hours"
                                        type="number"
                                        step="0.5"
                                        min="0"
                                        :placeholder="isRtl ? 'ساعات إضافية' : 'Hours'"
                                        class="doctorato-input w-full bg-gray-50/80 border border-gray-200 rounded-xl ps-10 pe-4 py-2.5 text-sm text-gray-700 font-medium focus:bg-white focus:ring-2 focus:ring-slate-200/50 focus:border-slate-300 transition-all duration-200 placeholder:text-gray-300"
                                    />
                                </div>
                            </div>

                            <!-- Notes (full width) -->
                            <div class="sm:col-span-2 lg:col-span-3">
                                <label class="block text-[11px] font-semibold text-gray-400 uppercase tracking-wider mb-1.5">
                                    {{ $t('a_notes') }}
                                </label>
                                <div class="relative">
                                    <div class="pointer-events-none absolute top-3 start-3">
                                        <svg class="w-4 h-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z" /></svg>
                                    </div>
                                    <textarea
                                        v-model="form.notes"
                                        rows="2"
                                        :placeholder="isRtl ? 'ملاحظات اختيارية...' : 'Optional notes...'"
                                        class="doctorato-input w-full bg-gray-50/80 border border-gray-200 rounded-xl ps-10 pe-4 py-2.5 text-sm text-gray-700 font-medium focus:bg-white focus:ring-2 focus:ring-[#C4A265]/30/50 focus:border-[#1B365D] transition-all duration-200 placeholder:text-gray-300 resize-none"
                                    />
                                </div>
                            </div>
                        </div>

                        <!-- Form Actions -->
                        <div :class="['flex items-center gap-3 mt-6 pt-5 border-t border-gray-100', isRtl ? 'flex-row' : 'flex-row-reverse justify-start']">
                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="inline-flex items-center gap-2 px-4 md:px-6 py-2.5 rounded-xl text-white text-sm font-semibold shadow-sm hover:shadow-md transition-all duration-300 hover:scale-[1.02] active:scale-[0.98] disabled:opacity-50 disabled:hover:scale-100"
                                style="background: linear-gradient(135deg, #C4A265 0%, #B08D4C 100%);"
                            >
                                <svg v-if="form.processing" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                                <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                                {{ form.processing ? (isRtl ? 'جاري الحفظ...' : 'Saving...') : editingId ? (isRtl ? 'تحديث' : 'Update') : (isRtl ? 'حفظ السجل' : 'Save Record') }}
                            </button>
                            <button
                                type="button"
                                @click="cancelForm"
                                class="px-5 py-2.5 rounded-xl border border-gray-200 text-sm font-medium text-gray-500 hover:bg-gray-50 hover:border-gray-300 transition-all duration-200"
                            >
                                {{ isRtl ? 'إلغاء' : 'Cancel' }}
                            </button>
                        </div>
                    </form>
                </div>
            </transition>

            <!-- ═══════════ FILTERS ═══════════ -->
            <div
                class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 transition-all duration-700"
                :class="mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'"
                :style="{ transitionDelay: '200ms' }"
            >
                <div class="flex items-center gap-2 mb-4">
                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" /></svg>
                    <span class="text-sm font-semibold text-gray-600">{{ isRtl ? 'تصفية النتائج' : 'Filter Results' }}</span>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <!-- Employee (Custom Picker) -->
                    <div ref="filterPickerRef" class="relative">
                        <label class="block text-[11px] font-semibold text-gray-400 uppercase tracking-wider mb-1.5">{{ $t('a_employee') }}</label>
                        <button
                            type="button"
                            @click="toggleFilterPicker"
                            class="w-full flex items-center gap-3 bg-gray-50/80 border border-gray-200 rounded-xl px-3 py-2 text-start text-sm transition-all duration-200 cursor-pointer"
                            :class="filterPickerOpen ? 'ring-2 ring-amber-200/50 border-amber-300 bg-white shadow-md' : 'hover:border-gray-300 hover:bg-white'"
                        >
                            <template v-if="selectedFilterUser">
                                <div :class="['w-7 h-7 rounded-lg bg-gradient-to-br flex-shrink-0 flex items-center justify-center text-white text-[10px] font-bold shadow-sm', getUserColor(selectedFilterUser.id)]">
                                    {{ getUserInitials(selectedFilterUser.name) }}
                                </div>
                                <span class="text-sm font-semibold text-gray-800 truncate flex-1">{{ selectedFilterUser.name }}</span>
                                <button type="button" @click.stop="selectFilterUser(null)" class="p-0.5 rounded-md hover:bg-gray-100 transition-colors">
                                    <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                                </button>
                            </template>
                            <template v-else>
                                <div class="w-7 h-7 rounded-lg bg-gray-100 flex-shrink-0 flex items-center justify-center">
                                    <svg class="w-3.5 h-3.5 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                                </div>
                                <span class="text-sm text-gray-400 font-medium flex-1">{{ $t('a_all_employees') }}</span>
                            </template>
                            <svg class="w-4 h-4 text-gray-400 flex-shrink-0 transition-transform duration-200 ms-auto" :class="filterPickerOpen ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                        </button>

                        <!-- Dropdown -->
                        <transition
                            enter-active-class="transition duration-200 ease-out"
                            leave-active-class="transition duration-150 ease-in"
                            enter-from-class="opacity-0 scale-95 -translate-y-2"
                            enter-to-class="opacity-100 scale-100 translate-y-0"
                            leave-from-class="opacity-100 scale-100 translate-y-0"
                            leave-to-class="opacity-0 scale-95 -translate-y-2"
                        >
                            <div v-if="filterPickerOpen" class="absolute z-50 mt-2 w-full bg-white rounded-xl border border-gray-200 shadow-xl overflow-hidden">
                                <div class="p-2.5 border-b border-gray-100">
                                    <div class="relative">
                                        <svg class="w-4 h-4 text-gray-300 absolute start-3 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                                        <input
                                            ref="filterSearchRef"
                                            v-model="filterPickerSearch"
                                            type="text"
                                            :placeholder="isRtl ? 'ابحث عن موظف...' : 'Search employee...'"
                                            class="doctorato-input w-full bg-gray-50 border-0 rounded-lg ps-9 pe-3 py-2 text-sm text-gray-700 placeholder:text-gray-300 focus:ring-2 focus:ring-[#C4A265]/30/50 focus:bg-white transition-all duration-150"
                                        />
                                    </div>
                                </div>
                                <div class="max-h-52 overflow-y-auto overscroll-contain">
                                    <!-- All employees option -->
                                    <button
                                        type="button"
                                        @click="selectFilterUser(null)"
                                        class="w-full flex items-center gap-3 px-3 py-2.5 text-start transition-all duration-150 hover:bg-gray-50"
                                        :class="!employeeFilter ? 'bg-amber-50/80' : ''"
                                    >
                                        <div class="w-7 h-7 rounded-lg bg-gray-100 flex-shrink-0 flex items-center justify-center">
                                            <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                                        </div>
                                        <span class="text-sm font-medium text-gray-500">{{ $t('a_all_employees') }}</span>
                                        <svg v-if="!employeeFilter" class="w-4 h-4 text-amber-500 flex-shrink-0 ms-auto" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" /></svg>
                                    </button>
                                    <button
                                        v-for="user in filterFilteredUsers"
                                        :key="user.id"
                                        type="button"
                                        @click="selectFilterUser(user)"
                                        class="w-full flex items-center gap-3 px-3 py-2.5 text-start transition-all duration-150 hover:bg-amber-50/60"
                                        :class="employeeFilter == user.id ? 'bg-amber-50/80' : ''"
                                    >
                                        <div :class="['w-7 h-7 rounded-lg bg-gradient-to-br flex-shrink-0 flex items-center justify-center text-white text-[10px] font-bold shadow-sm transition-transform duration-200 hover:scale-110', getUserColor(user.id)]">
                                            {{ getUserInitials(user.name) }}
                                        </div>
                                        <span class="text-sm font-medium text-gray-700 truncate flex-1">{{ user.name }}</span>
                                        <svg v-if="employeeFilter == user.id" class="w-4 h-4 text-amber-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" /></svg>
                                    </button>
                                    <div v-if="filterFilteredUsers.length === 0" class="px-4 py-4 md:py-6 text-center">
                                        <p class="text-xs text-gray-400">{{ isRtl ? 'لا توجد نتائج' : 'No results found' }}</p>
                                    </div>
                                </div>
                            </div>
                        </transition>
                    </div>
                    <!-- Status -->
                    <div>
                        <label class="block text-[11px] font-semibold text-gray-400 uppercase tracking-wider mb-1.5">{{ $t('a_status') }}</label>
                        <div class="relative">
                            <select
                                v-model="statusFilter"
                                class="doctorato-input w-full appearance-none bg-gray-50/80 border border-gray-200 rounded-xl px-4 py-2.5 pe-10 text-sm text-gray-700 font-medium focus:bg-white focus:ring-2 focus:ring-[#C4A265]/30/50 focus:border-[#1B365D] transition-all duration-200 cursor-pointer"
                            >
                                <option value="">{{ $t('a_all_status') }}</option>
                                <option v-for="(cfg, key) in statusConfig" :key="key" :value="key">{{ isRtl ? cfg.label_ar : cfg.label_en }}</option>
                            </select>
                            <div class="pointer-events-none absolute inset-y-0 end-0 flex items-center pe-3">
                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                            </div>
                        </div>
                    </div>
                    <!-- Date From -->
                    <div>
                        <label class="block text-[11px] font-semibold text-gray-400 uppercase tracking-wider mb-1.5">{{ isRtl ? 'من تاريخ' : 'From Date' }}</label>
                        <input
                            v-model="dateFrom"
                            type="date"
                            :max="dateTo || undefined"
                            class="doctorato-input w-full bg-gray-50/80 border border-gray-200 rounded-xl px-4 py-2.5 text-sm text-gray-700 font-medium focus:bg-white focus:ring-2 focus:ring-[#C4A265]/30/50 focus:border-[#1B365D] transition-all duration-200"
                        />
                    </div>
                    <!-- Date To -->
                    <div>
                        <label class="block text-[11px] font-semibold text-gray-400 uppercase tracking-wider mb-1.5">{{ isRtl ? 'إلى تاريخ' : 'To Date' }}</label>
                        <input
                            v-model="dateTo"
                            type="date"
                            :min="dateFrom || undefined"
                            class="doctorato-input w-full bg-gray-50/80 border border-gray-200 rounded-xl px-4 py-2.5 text-sm text-gray-700 font-medium focus:bg-white focus:ring-2 focus:ring-[#C4A265]/30/50 focus:border-[#1B365D] transition-all duration-200"
                        />
                    </div>
                </div>
            </div>

            <!-- ═══════════ ATTENDANCE CARDS ═══════════ -->
            <div v-if="attendances.data && attendances.data.length > 0" class="space-y-3">
                <div
                    v-for="(record, idx) in attendances.data"
                    :key="record.id"
                    class="bg-white rounded-2xl border border-gray-100 shadow-sm hover:shadow-md transition-all duration-500 overflow-hidden hover:-translate-y-0.5"
                    :class="mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-6'"
                    :style="{ transitionDelay: `${300 + idx * 60}ms` }"
                >
                    <div class="flex flex-col sm:flex-row">
                        <!-- Status Accent Bar -->
                        <div
                            :class="[
                                'w-full sm:w-1.5 h-1.5 sm:h-auto flex-shrink-0 transition-all duration-300',
                                getStatus(record.status).dot
                            ]"
                        />

                        <div class="flex-1 p-5">
                            <div class="flex flex-col lg:flex-row lg:items-center gap-4">

                                <!-- Employee Info -->
                                <div class="flex items-center gap-3 lg:w-[180px] flex-shrink-0">
                                    <div
                                        class="w-10 h-10 rounded-xl flex items-center justify-center text-white text-sm font-bold shadow-sm flex-shrink-0 transition-transform duration-300 hover:scale-110"
                                        style="background: linear-gradient(135deg, #C4A265 0%, #B08D4C 100%);"
                                    >
                                        {{ record.user?.name?.charAt(0)?.toUpperCase() }}
                                    </div>
                                    <div class="min-w-0">
                                        <p class="text-sm font-bold text-gray-900 truncate">{{ record.user?.name }}</p>
                                        <p class="text-[11px] text-gray-400">{{ formatDate(record.date) }}</p>
                                    </div>
                                </div>

                                <!-- Details Grid -->
                                <div class="flex-1 grid grid-cols-2 sm:grid-cols-5 gap-3">
                                    <!-- Status -->
                                    <div>
                                        <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wider mb-1">{{ $t('a_status') }}</p>
                                        <span
                                            :class="[getStatus(record.status).bg, getStatus(record.status).text, getStatus(record.status).border]"
                                            class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-[11px] font-bold border"
                                        >
                                            <span :class="getStatus(record.status).dot" class="w-1.5 h-1.5 rounded-full" />
                                            {{ isRtl ? getStatus(record.status).label_ar : getStatus(record.status).label_en }}
                                        </span>
                                    </div>

                                    <!-- Check In -->
                                    <div>
                                        <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wider mb-1">{{ $t('a_check_in') }}</p>
                                        <div v-if="formatTime(record.check_in)" class="flex items-center gap-1.5">
                                            <svg class="w-3.5 h-3.5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14" /></svg>
                                            <span class="text-sm font-semibold text-gray-800">{{ formatTime(record.check_in) }}</span>
                                        </div>
                                        <span v-else class="text-sm text-gray-300">--:--</span>
                                        <!-- Location indicator -->
                                        <button
                                            v-if="record.check_in_lat && record.check_in_lng"
                                            @click="openMap(record)"
                                            class="mt-1 inline-flex items-center gap-1 text-[10px] text-emerald-500 hover:text-emerald-700 font-medium transition-colors cursor-pointer"
                                        >
                                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd" /></svg>
                                            {{ isRtl ? 'الموقع مسجل' : 'Location recorded' }}
                                        </button>
                                        <span v-else-if="formatTime(record.check_in)" class="mt-1 inline-flex items-center gap-1 text-[10px] text-gray-300">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                                            {{ isRtl ? 'بدون موقع' : 'No location' }}
                                        </span>
                                    </div>

                                    <!-- Check Out -->
                                    <div>
                                        <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wider mb-1">{{ $t('a_check_out') }}</p>
                                        <div v-if="formatTime(record.check_out)" class="flex items-center gap-1.5">
                                            <svg class="w-3.5 h-3.5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7" /></svg>
                                            <span class="text-sm font-semibold text-gray-800">{{ formatTime(record.check_out) }}</span>
                                        </div>
                                        <span v-else class="text-sm text-gray-300">--:--</span>
                                        <!-- Location indicator -->
                                        <button
                                            v-if="record.check_out_lat && record.check_out_lng"
                                            @click="openMap(record)"
                                            class="mt-1 inline-flex items-center gap-1 text-[10px] text-red-400 hover:text-red-600 font-medium transition-colors cursor-pointer"
                                        >
                                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd" /></svg>
                                            {{ isRtl ? 'الموقع مسجل' : 'Location recorded' }}
                                        </button>
                                        <span v-else-if="formatTime(record.check_out)" class="mt-1 inline-flex items-center gap-1 text-[10px] text-gray-300">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                                            {{ isRtl ? 'بدون موقع' : 'No location' }}
                                        </span>
                                    </div>

                                    <!-- Working Hours -->
                                    <div>
                                        <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wider mb-1">{{ isRtl ? 'ساعات العمل' : 'Work Hours' }}</p>
                                        <template v-if="calcWorkingHours(record.check_in, record.check_out)">
                                            <p class="text-sm font-bold text-gray-800">
                                                {{ calcWorkingHours(record.check_in, record.check_out).hours }}<span class="text-[10px] text-gray-400 font-normal">{{ isRtl ? 'س' : 'h' }}</span>
                                                {{ calcWorkingHours(record.check_in, record.check_out).mins }}<span class="text-[10px] text-gray-400 font-normal">{{ isRtl ? 'د' : 'm' }}</span>
                                            </p>
                                        </template>
                                        <span v-else class="text-sm text-gray-300">-</span>
                                    </div>

                                    <!-- Overtime -->
                                    <div>
                                        <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wider mb-1">{{ $t('a_overtime') }}</p>
                                        <span v-if="record.overtime_hours && parseFloat(record.overtime_hours) > 0" class="inline-flex items-center gap-1 px-2 py-0.5 rounded-lg bg-slate-50 text-[#1B365D] text-[11px] font-bold border border-slate-200">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3" /></svg>
                                            {{ record.overtime_hours }} {{ isRtl ? 'ساعة' : 'hrs' }}
                                        </span>
                                        <span v-else class="text-sm text-gray-300">-</span>
                                    </div>
                                </div>

                                <!-- Actions -->
                                <div class="flex items-center gap-1.5 lg:w-auto flex-shrink-0">
                                    <button
                                        v-if="hasLocation(record)"
                                        @click="openMap(record)"
                                        class="p-2 rounded-xl text-slate-400 hover:text-[#1B365D] hover:bg-slate-50 transition-all duration-200 relative"
                                        :title="isRtl ? 'عرض الموقع على الخريطة' : 'View on map'"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                                        <span class="absolute -top-0.5 -end-0.5 w-2 h-2 bg-[#1B365D] rounded-full animate-pulse" />
                                    </button>
                                    <button
                                        v-if="can('attendances.update')"
                                        @click="editRecord(record)"
                                        class="p-2 rounded-xl text-gray-400 hover:text-amber-600 hover:bg-amber-50 transition-all duration-200"
                                        :title="isRtl ? 'تعديل' : 'Edit'"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                                    </button>
                                    <button
                                        v-if="can('attendances.delete')"
                                        @click="deleteAttendance(record.id)"
                                        class="p-2 rounded-xl text-gray-400 hover:text-red-500 hover:bg-red-50 transition-all duration-200"
                                        :title="isRtl ? 'حذف' : 'Delete'"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                    </button>
                                </div>
                            </div>

                            <!-- Notes Row -->
                            <div v-if="record.notes" class="mt-3 pt-3 border-t border-gray-100">
                                <div class="flex items-start gap-2">
                                    <svg class="w-3.5 h-3.5 text-gray-300 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z" /></svg>
                                    <p class="text-sm text-gray-500 leading-relaxed">{{ record.notes }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ═══════════ EMPTY STATE ═══════════ -->
            <div
                v-else
                class="bg-white rounded-2xl border border-gray-100 shadow-sm py-16 transition-all duration-700"
                :class="mounted ? 'opacity-100 scale-100' : 'opacity-0 scale-95'"
            >
                <div class="flex flex-col items-center">
                    <div class="w-16 h-16 rounded-2xl bg-gray-50 flex items-center justify-center mb-4">
                        <svg class="w-8 h-8 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" /></svg>
                    </div>
                    <p class="text-sm font-semibold text-gray-500">{{ $t('a_no_attendance_records_found') }}</p>
                    <p class="text-xs text-gray-400 mt-1">{{ isRtl ? 'لا توجد سجلات حضور تطابق الفلتر' : 'No attendance records match your filters' }}</p>
                </div>
            </div>

            <!-- ═══════════ PAGINATION ═══════════ -->
            <div
                v-if="attendances.links && attendances.links.length > 3"
                class="flex flex-col sm:flex-row items-center justify-between gap-4 bg-white rounded-2xl border border-gray-100 shadow-sm px-5 py-4 transition-all duration-700"
                :class="mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'"
            >
                <p class="text-sm text-gray-500">
                    {{ $t('a_showing') }} <span class="font-semibold text-gray-700">{{ attendances.from }}</span>
                    {{ $t('a_to') }} <span class="font-semibold text-gray-700">{{ attendances.to }}</span>
                    {{ $t('a_of') }} <span class="font-semibold text-gray-700">{{ attendances.total }}</span>
                    {{ $t('a_results') }}
                </p>
                <nav :class="['flex', isRtl ? 'space-x-reverse space-x-1' : 'space-x-1']">
                    <template v-for="link in attendances.links" :key="link.label">
                        <Link
                            v-if="link.url"
                            :href="link.url"
                            v-html="link.label"
                            class="px-3 py-1.5 text-sm rounded-lg border transition-all duration-200"
                            :class="link.active ? 'text-white border-transparent shadow-sm' : 'text-gray-600 border-gray-200 hover:bg-gray-50 hover:border-gray-300'"
                            :style="link.active ? 'background: linear-gradient(135deg, #C4A265 0%, #B08D4C 100%);' : ''"
                            preserve-state
                        />
                        <span v-else v-html="link.label" class="px-3 py-1.5 text-sm text-gray-300" />
                    </template>
                </nav>
            </div>
        </div>

        <!-- ═══════════ MAP MODAL ═══════════ -->
        <teleport to="body">
            <transition
                enter-active-class="transition duration-300 ease-out"
                leave-active-class="transition duration-200 ease-in"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div v-if="mapModal" class="fixed inset-0 z-[9999] flex items-center justify-center p-4" @click.self="closeMap">
                    <!-- Backdrop -->
                    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" />

                    <!-- Modal -->
                    <div class="relative w-full max-w-2xl bg-white rounded-2xl shadow-2xl overflow-hidden animate-[modalIn_0.3s_ease-out]">
                        <!-- Header -->
                        <div class="px-4 md:px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 rounded-xl bg-slate-50 flex items-center justify-center">
                                    <svg class="w-5 h-5 text-[#1B365D]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                                </div>
                                <div>
                                    <h3 class="text-base font-bold text-gray-900">{{ isRtl ? 'موقع الحضور والانصراف' : 'Attendance Location' }}</h3>
                                    <p class="text-xs text-gray-400">{{ mapRecord?.user?.name }} - {{ formatDate(mapRecord?.date) }}</p>
                                </div>
                            </div>
                            <button @click="closeMap" class="p-2 rounded-xl text-gray-400 hover:text-gray-600 hover:bg-gray-100 transition-all duration-200">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                            </button>
                        </div>

                        <!-- Map -->
                        <div ref="mapContainerRef" class="w-full h-[400px]" />

                        <!-- Legend -->
                        <div class="px-4 md:px-6 py-3 border-t border-gray-100 flex flex-wrap items-center gap-4">
                            <div v-if="mapRecord?.check_in_lat" class="flex items-center gap-2">
                                <div class="w-4 h-4 rounded-full bg-gradient-to-br from-emerald-500 to-emerald-600 border-2 border-white shadow" />
                                <span class="text-xs font-medium text-gray-600">{{ isRtl ? 'الحضور' : 'Check-in' }} {{ formatTime(mapRecord?.check_in) }}</span>
                            </div>
                            <div v-if="mapRecord?.check_out_lat" class="flex items-center gap-2">
                                <div class="w-4 h-4 rounded-full bg-gradient-to-br from-red-500 to-red-600 border-2 border-white shadow" />
                                <span class="text-xs font-medium text-gray-600">{{ isRtl ? 'الانصراف' : 'Check-out' }} {{ formatTime(mapRecord?.check_out) }}</span>
                            </div>
                            <div v-if="mapRecord?.check_in_lat && mapRecord?.check_out_lat" class="flex items-center gap-2">
                                <div class="w-4 h-0.5 bg-[#C4A265] border-dashed" style="border-top: 2px dashed #C4A265; width: 16px;" />
                                <span class="text-xs font-medium text-gray-600">{{ isRtl ? 'المسار' : 'Path' }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </transition>
        </teleport>
    </AdminLayout>
</template>

<style>
@keyframes modalIn {
    from { opacity: 0; transform: scale(0.95) translateY(10px); }
    to { opacity: 1; transform: scale(1) translateY(0); }
}
</style>
