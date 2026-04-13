<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue';
import { Link, router, usePage, useForm } from '@inertiajs/vue3';
import SecretaryLayout from '@/Layouts/SecretaryLayout.vue';

const page = usePage();
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const props = defineProps({
    stats: Object,
    todayFollowUps: Array,
    overdueFollowUps: Array,
    recentLeads: Array,
    statusDistribution: Object,
    recentActivities: Array,
    todayActivityCount: Number,
    conversionRate: Number,
    weeklyStats: Object,
    moduleDistribution: Object,
    activityTrend: Array,
    slaMetrics: Object,
    staleLeads: Array,
    leadSources: Array,
});

const mounted = ref(false);
const loadedAt = ref(null);
const timeSinceLoad = ref('');
let freshnessTimer = null;

function updateFreshness() {
    if (!loadedAt.value) return;
    const diff = Math.floor((Date.now() - loadedAt.value) / 1000);
    if (diff < 60) { timeSinceLoad.value = isRtl.value ? 'الآن' : 'Just now'; return; }
    const mins = Math.floor(diff / 60);
    if (mins < 60) { timeSinceLoad.value = isRtl.value ? `منذ ${mins} د` : `${mins}m ago`; return; }
    const hrs = Math.floor(mins / 60);
    timeSinceLoad.value = isRtl.value ? `منذ ${hrs} س` : `${hrs}h ago`;
}

function refreshDashboard() {
    router.reload({ preserveScroll: true, onSuccess: () => { loadedAt.value = Date.now(); updateFreshness(); } });
}

/* ---------- Auto-refresh interval selector ---------- */
const autoRefreshOptions = [
    { key: 0, en: 'Off', ar: 'إيقاف' },
    { key: 30, en: '30s', ar: '30 ث' },
    { key: 60, en: '1m', ar: '1 د' },
    { key: 300, en: '5m', ar: '5 د' },
];
const savedAutoRefresh = (() => { try { const v = localStorage.getItem('crm_dashboard_autoRefresh'); return v ? parseInt(v) : 0; } catch { return 0; } })();
const autoRefreshInterval = ref(savedAutoRefresh);
const showAutoRefreshPicker = ref(false);
let autoRefreshHandle = null;

function setAutoRefresh(seconds) {
    autoRefreshInterval.value = seconds;
    showAutoRefreshPicker.value = false;
    try { localStorage.setItem('crm_dashboard_autoRefresh', String(seconds)); } catch {}
    clearInterval(autoRefreshHandle);
    if (seconds > 0) {
        autoRefreshHandle = setInterval(refreshDashboard, seconds * 1000);
    }
}

const autoRefreshLabel = computed(() => {
    const opt = autoRefreshOptions.find(o => o.key === autoRefreshInterval.value);
    return opt ? (isRtl.value ? opt.ar : opt.en) : (isRtl.value ? 'إيقاف' : 'Off');
});

function closeAutoRefreshPicker(e) {
    if (showAutoRefreshPicker.value && !e.target.closest('[data-auto-refresh-picker]')) {
        showAutoRefreshPicker.value = false;
    }
}

onMounted(() => {
    setTimeout(() => { mounted.value = true; }, 50);
    startCounters();
    loadedAt.value = Date.now();
    document.addEventListener('click', closeAutoRefreshPicker);
    updateFreshness();
    freshnessTimer = setInterval(updateFreshness, 30000);
    if (autoRefreshInterval.value > 0) {
        autoRefreshHandle = setInterval(refreshDashboard, autoRefreshInterval.value * 1000);
    }
});

onBeforeUnmount(() => { clearInterval(freshnessTimer); clearInterval(autoRefreshHandle); document.removeEventListener('click', closeAutoRefreshPicker); });

/* ---------- Animated counters ---------- */
const counterMyLeads = ref(0);
const counterNewLeads = ref(0);
const counterTodayFU = ref(0);
const counterOverdueFU = ref(0);

function animateCounter(refVal, target, duration = 1200) {
    if (!target || target <= 0) { refVal.value = 0; return; }
    const steps = 40;
    const increment = target / steps;
    let current = 0;
    const interval = setInterval(() => {
        current += increment;
        if (current >= target) {
            refVal.value = target;
            clearInterval(interval);
        } else {
            refVal.value = Math.floor(current);
        }
    }, duration / steps);
}

function startCounters() {
    setTimeout(() => {
        animateCounter(counterMyLeads, props.stats?.my_leads || 0);
        animateCounter(counterNewLeads, props.stats?.new_leads || 0);
        animateCounter(counterTodayFU, props.stats?.today_follow_ups || 0);
        animateCounter(counterOverdueFU, props.stats?.overdue_follow_ups || 0);
    }, 400);
}

/* ---------- Greeting ---------- */
const greeting = computed(() => {
    const hour = new Date().getHours();
    if (hour < 12) return isRtl.value ? 'صباح الخير' : 'Good Morning';
    if (hour < 17) return isRtl.value ? 'مساء الخير' : 'Good Afternoon';
    return isRtl.value ? 'مساء الخير' : 'Good Evening';
});

const motivationalQuote = computed(() => {
    return isRtl.value
        ? 'كل متابعة هي فرصة لبناء علاقة دائمة مع العميل'
        : 'Every follow-up is a chance to build a lasting relationship';
});

/* ---------- Status ---------- */
const statusLabels = {
    new: isRtl.value ? 'جديد' : 'New',
    contacted: isRtl.value ? 'تم التواصل' : 'Contacted',
    qualified: isRtl.value ? 'مؤهل' : 'Qualified',
    appointment_booked: isRtl.value ? 'تم الحجز' : 'Booked',
    consultation_done: isRtl.value ? 'تم الاستشارة' : 'Consulted',
    negotiation: isRtl.value ? 'تفاوض' : 'Negotiation',
    converted: isRtl.value ? 'محوّل' : 'Converted',
    lost: isRtl.value ? 'خسارة' : 'Lost',
    dormant: isRtl.value ? 'خامد' : 'Dormant',
};

const statusColors = {
    new: 'bg-blue-100 text-blue-700 border-blue-200',
    contacted: 'bg-indigo-100 text-indigo-700 border-indigo-200',
    qualified: 'bg-purple-100 text-purple-700 border-purple-200',
    appointment_booked: 'bg-amber-100 text-amber-700 border-amber-200',
    consultation_done: 'bg-teal-100 text-teal-700 border-teal-200',
    negotiation: 'bg-orange-100 text-orange-700 border-orange-200',
    converted: 'bg-green-100 text-green-700 border-green-200',
    lost: 'bg-red-100 text-red-700 border-red-200',
    dormant: 'bg-gray-100 text-gray-600 border-gray-200',
};

const priorityConfig = {
    hot: { icon: 'flame', color: 'text-red-500', label: isRtl.value ? 'ساخن' : 'Hot' },
    warm: { icon: 'flame', color: 'text-orange-400', label: isRtl.value ? 'دافئ' : 'Warm' },
    cold: { icon: 'snowflake', color: 'text-blue-400', label: isRtl.value ? 'بارد' : 'Cold' },
};

/* ---------- Follow-up type icons ---------- */
const followUpTypeConfig = {
    call: {
        label: isRtl.value ? 'اتصال' : 'Call',
        color: 'bg-emerald-100 text-emerald-600',
        iconPath: 'M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z',
    },
    whatsapp: {
        label: isRtl.value ? 'واتساب' : 'WhatsApp',
        color: 'bg-green-100 text-green-600',
        iconPath: 'M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z',
    },
    email: {
        label: isRtl.value ? 'بريد' : 'Email',
        color: 'bg-blue-100 text-blue-600',
        iconPath: 'M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z',
    },
    sms: {
        label: isRtl.value ? 'رسالة' : 'SMS',
        color: 'bg-violet-100 text-violet-600',
        iconPath: 'M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z',
    },
    meeting: {
        label: isRtl.value ? 'اجتماع' : 'Meeting',
        color: 'bg-amber-100 text-amber-600',
        iconPath: 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z',
    },
};

/* ---------- Helpers ---------- */
function formatTime(date) {
    if (!date) return '-';
    return new Date(date).toLocaleTimeString(isRtl.value ? 'ar-SA' : 'en-US', { hour: '2-digit', minute: '2-digit' });
}

function formatDate(date) {
    if (!date) return '-';
    return new Date(date).toLocaleDateString(isRtl.value ? 'ar-SA' : 'en-GB', { day: '2-digit', month: 'short' });
}

function timeAgo(date) {
    if (!date) return '-';
    const diff = Date.now() - new Date(date).getTime();
    const mins = Math.floor(diff / 60000);
    if (mins < 60) return isRtl.value ? `منذ ${mins} د` : `${mins}m ago`;
    const hours = Math.floor(mins / 60);
    if (hours < 24) return isRtl.value ? `منذ ${hours} س` : `${hours}h ago`;
    const days = Math.floor(hours / 24);
    return isRtl.value ? `منذ ${days} ي` : `${days}d ago`;
}

function getInitials(name) {
    if (!name) return '?';
    const parts = name.trim().split(/\s+/);
    return parts.length >= 2
        ? (parts[0][0] + parts[parts.length - 1][0]).toUpperCase()
        : name.substring(0, 2).toUpperCase();
}

/* ---------- Complete follow-up with result ---------- */
const completingFuId = ref(null);
const completionResult = ref('');

function startComplete(fuId) {
    completingFuId.value = fuId;
    completionResult.value = '';
}

function submitComplete(fuId) {
    router.post(`/secretary/crm/follow-ups/${fuId}/complete`, {
        result: completionResult.value.trim(),
    }, {
        preserveScroll: true,
        onFinish: () => { completingFuId.value = null; completionResult.value = ''; },
    });
}

function cancelComplete() {
    completingFuId.value = null;
    completionResult.value = '';
}

function completeFollowUp(fuId) {
    startComplete(fuId);
}

function missFollowUp(fuId) {
    router.post(`/secretary/crm/follow-ups/${fuId}/miss`, {}, { preserveScroll: true });
}

/* ---------- Performance ---------- */
const followUpRate = computed(() => {
    const completed = (props.stats?.today_follow_ups || 0);
    const overdue = (props.stats?.overdue_follow_ups || 0);
    const totalFU = completed + overdue;
    if (totalFU === 0) return 100;
    return Math.round((completed / totalFU) * 100);
});

/* ---------- Funnel ---------- */
const funnelStatuses = [
    { key: 'new', en: 'New', ar: 'جديد', color: '#3b82f6' },
    { key: 'contacted', en: 'Contacted', ar: 'تم التواصل', color: '#06b6d4' },
    { key: 'qualified', en: 'Qualified', ar: 'مؤهل', color: '#8b5cf6' },
    { key: 'appointment_booked', en: 'Booked', ar: 'تم الحجز', color: '#f59e0b' },
    { key: 'consultation_done', en: 'Consulted', ar: 'تم الاستشارة', color: '#0d9488' },
    { key: 'negotiation', en: 'Negotiation', ar: 'تفاوض', color: '#f97316' },
];

const funnelMax = computed(() => {
    const vals = funnelStatuses.map(s => (props.statusDistribution || {})[s.key] || 0);
    return Math.max(...vals, 1);
});

const totalFunnelLeads = computed(() => {
    return Object.values(props.statusDistribution || {}).reduce((a, b) => a + b, 0);
});

/* ---------- Week-at-a-Glance strip ---------- */
const weekGlance = computed(() => {
    const days = [];
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    const allFUs = [...(props.todayFollowUps || []), ...(props.overdueFollowUps || [])];
    const dayNames = isRtl.value
        ? ['أحد', 'إثنين', 'ثلاثاء', 'أربعاء', 'خميس', 'جمعة', 'سبت']
        : ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
    for (let i = 0; i < 7; i++) {
        const d = new Date(today);
        d.setDate(d.getDate() + i);
        const dateStr = `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
        const count = allFUs.filter(fu => fu.scheduled_at && fu.scheduled_at.startsWith(dateStr)).length;
        days.push({
            date: dateStr,
            day: d.getDate(),
            dayName: dayNames[d.getDay()],
            isToday: i === 0,
            count,
        });
    }
    return days;
});

/* ---------- Activity feed filter ---------- */
const activityFilter = ref('all');
const activityFilterOptions = [
    { key: 'all', en: 'All', ar: 'الكل' },
    { key: 'call', en: 'Calls', ar: 'مكالمات' },
    { key: 'whatsapp', en: 'WhatsApp', ar: 'واتساب' },
    { key: 'email', en: 'Emails', ar: 'بريد' },
    { key: 'meeting', en: 'Meetings', ar: 'اجتماعات' },
    { key: 'note', en: 'Notes', ar: 'ملاحظات' },
];

const filteredActivities = computed(() => {
    if (!props.recentActivities?.length) return [];
    if (activityFilter.value === 'all') return props.recentActivities;
    return props.recentActivities.filter(a => a.type === activityFilter.value);
});

/* ---------- Time-grouped activities ---------- */
const activityShowAll = ref(false);
const ACTIVITY_INITIAL_COUNT = 8;

const groupedFeedActivities = computed(() => {
    const acts = filteredActivities.value;
    const shown = activityShowAll.value ? acts : acts.slice(0, ACTIVITY_INITIAL_COUNT);
    const groups = [];
    const today = new Date(); today.setHours(0,0,0,0);
    const yesterday = new Date(today); yesterday.setDate(yesterday.getDate() - 1);
    let currentGroup = null;

    shown.forEach(act => {
        const d = new Date(act.created_at); d.setHours(0,0,0,0);
        let label;
        if (d.getTime() === today.getTime()) label = isRtl.value ? '\u0627\u0644\u064A\u0648\u0645' : 'Today';
        else if (d.getTime() === yesterday.getTime()) label = isRtl.value ? '\u0623\u0645\u0633' : 'Yesterday';
        else label = d.toLocaleDateString(isRtl.value ? 'ar-SA' : 'en-US', { month: 'short', day: 'numeric' });

        if (!currentGroup || currentGroup.label !== label) {
            currentGroup = { label, items: [] };
            groups.push(currentGroup);
        }
        currentGroup.items.push(act);
    });
    return groups;
});

const hasMoreActivities = computed(() => filteredActivities.value.length > ACTIVITY_INITIAL_COUNT);

/* ---------- Dashboard snooze ---------- */
const snoozeOpenId = ref(null);

function toggleSnooze(fuId) {
    snoozeOpenId.value = snoozeOpenId.value === fuId ? null : fuId;
}

function snoozeFollowUp(fuId, key) {
    let scheduledAt;
    const now = new Date();
    if (key === '1h') {
        scheduledAt = new Date(now.getTime() + 60 * 60 * 1000);
    } else if (key === 'tomorrow') {
        scheduledAt = new Date(now);
        scheduledAt.setDate(scheduledAt.getDate() + 1);
        scheduledAt.setHours(9, 0, 0, 0);
    } else if (key === '1w') {
        scheduledAt = new Date(now.getTime() + 7 * 24 * 60 * 60 * 1000);
        scheduledAt.setHours(9, 0, 0, 0);
    }
    const isoStr = scheduledAt.getFullYear() + '-' +
        String(scheduledAt.getMonth() + 1).padStart(2, '0') + '-' +
        String(scheduledAt.getDate()).padStart(2, '0') + 'T' +
        String(scheduledAt.getHours()).padStart(2, '0') + ':' +
        String(scheduledAt.getMinutes()).padStart(2, '0');
    router.post(`/secretary/crm/follow-ups/${fuId}/reschedule`, {
        scheduled_at: isoStr,
        notes: isRtl.value ? 'تم التأجيل' : 'Snoozed',
    }, { preserveScroll: true });
    snoozeOpenId.value = null;
}

/* ---------- Daily progress tracker ---------- */
const dailyProgress = computed(() => {
    const total = (props.todayFollowUps?.length || 0) + (props.overdueFollowUps?.length || 0);
    const completed = props.todayActivityCount || 0;
    const pct = total > 0 ? Math.min(Math.round((completed / total) * 100), 100) : (completed > 0 ? 100 : 0);
    return { total, completed, pct };
});

/* ---------- Conversion rate metric ---------- */
const conversionMetric = computed(() => {
    const dist = props.statusDistribution || {};
    const total = Object.values(dist).reduce((a, b) => a + b, 0);
    const converted = dist.converted || 0;
    const rate = total > 0 ? Math.round((converted / total) * 100) : 0;
    const newLeads = dist.new || 0;
    const contacted = dist.contacted || 0;
    const contactRate = newLeads > 0 ? Math.round((contacted / newLeads) * 100) : 0;
    return { total, converted, rate, contactRate };
});

/* ---------- Quick Add Lead ---------- */
const showQuickAdd = ref(false);
const quickAddForm = useForm({
    full_name: '',
    phone: '',
    lead_source_id: '',
    module: 'derma',
    priority: 3,
});

function submitQuickAdd() {
    quickAddForm.post('/secretary/crm/leads', {
        preserveScroll: true,
        onSuccess: () => {
            quickAddForm.reset();
            showQuickAdd.value = false;
        },
    });
}

/* ---------- Mini sparkline data ---------- */
function generateSparkline(data, count) {
    if (!data || !data.length) {
        // Generate a simple ascending pattern as placeholder
        return Array.from({ length: 7 }, (_, i) => Math.random() * 0.4 + 0.3);
    }
    return data.slice(-7).map(d => d / Math.max(...data, 1));
}

const sparklines = computed(() => {
    // Use recent activities trend data if available
    const recent = props.recentActivities || [];
    const days = {};
    recent.forEach(a => {
        const d = new Date(a.created_at).toISOString().split('T')[0];
        days[d] = (days[d] || 0) + 1;
    });
    const sorted = Object.values(days).slice(-7);
    return {
        leads: sorted.length > 0 ? sorted : [2, 3, 1, 4, 2, 5, 3],
        followups: sorted.length > 0 ? sorted.map(v => Math.max(v - 1, 0)) : [1, 2, 0, 3, 1, 2, 2],
    };
});

/* ---------- Activity type distribution ---------- */
const activityTypeDistribution = computed(() => {
    const acts = props.recentActivities || [];
    if (!acts.length) return [];
    const types = [
        { key: 'call', en: 'Calls', ar: 'مكالمات', color: '#10b981' },
        { key: 'whatsapp', en: 'WhatsApp', ar: 'واتساب', color: '#25d366' },
        { key: 'email', en: 'Email', ar: 'بريد', color: '#3b82f6' },
        { key: 'sms', en: 'SMS', ar: 'رسائل', color: '#8b5cf6' },
        { key: 'meeting', en: 'Meetings', ar: 'اجتماعات', color: '#f59e0b' },
        { key: 'note', en: 'Notes', ar: 'ملاحظات', color: '#64748b' },
    ];
    const total = acts.length;
    return types.map(t => {
        const count = acts.filter(a => a.type === t.key).length;
        return { ...t, count, pct: total > 0 ? Math.round((count / total) * 100) : 0 };
    }).filter(t => t.count > 0);
});

const activityDistMax = computed(() => Math.max(...activityTypeDistribution.value.map(t => t.count), 1));

/* ---------- Keyboard shortcuts ---------- */
function handleDashboardKey(e) {
    if (['INPUT', 'TEXTAREA', 'SELECT'].includes(document.activeElement?.tagName)) return;
    if (e.key === 'n' || e.key === 'N' || e.key === '\u0646') { e.preventDefault(); router.get('/secretary/crm/leads/create'); }
    if (e.key === 'c' || e.key === 'C' || e.key === '\u0634') { e.preventDefault(); router.get('/secretary/crm/calendar'); }
    if (e.key === 'p' || e.key === 'P' || e.key === '\u062D') { e.preventDefault(); router.get('/secretary/crm/pipeline'); }
    if (e.key === 'l' || e.key === 'L' || e.key === '\u0644') { e.preventDefault(); router.get('/secretary/crm/leads'); }
    if (e.key === 'r' || e.key === 'R' || e.key === '\u0642') { e.preventDefault(); router.get('/secretary/crm/reports'); }
}
onMounted(() => { document.addEventListener('keydown', handleDashboardKey); });
onBeforeUnmount(() => { document.removeEventListener('keydown', handleDashboardKey); });

function sparklinePath(data, w, h) {
    if (!data || data.length < 2) return '';
    const max = Math.max(...data, 1);
    const step = w / (data.length - 1);
    return data.map((v, i) => {
        const x = i * step;
        const y = h - (v / max) * (h - 4) - 2;
        return (i === 0 ? 'M' : 'L') + x.toFixed(1) + ',' + y.toFixed(1);
    }).join(' ');
}

/* ---------- Activity Icons ---------- */
const activityTypeConfig = {
    call: { icon: 'M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z', color: 'bg-emerald-100 text-emerald-600' },
    whatsapp: { icon: 'M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z', color: 'bg-green-100 text-green-600' },
    email: { icon: 'M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z', color: 'bg-blue-100 text-blue-600' },
    sms: { icon: 'M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z', color: 'bg-violet-100 text-violet-600' },
    meeting: { icon: 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z', color: 'bg-amber-100 text-amber-600' },
    note: { icon: 'M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z', color: 'bg-slate-100 text-slate-600' },
    status_change: { icon: 'M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15', color: 'bg-indigo-100 text-indigo-600' },
    follow_up_scheduled: { icon: 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z', color: 'bg-teal-100 text-teal-600' },
    follow_up_completed: { icon: 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z', color: 'bg-emerald-100 text-emerald-600' },
    system: { icon: 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z', color: 'bg-gray-100 text-gray-500' },
};
</script>

<template>
    <SecretaryLayout :title="isRtl ? 'لوحة تحكم العملاء' : 'CRM Dashboard'">
        <div class="space-y-6 pb-20 md:pb-8">

            <!-- ============ OVERDUE ALERT BANNER ============ -->
            <Transition
                enter-active-class="transition-all duration-500 ease-out"
                enter-from-class="opacity-0 -translate-y-4 scale-95"
                enter-to-class="opacity-100 translate-y-0 scale-100"
            >
                <div
                    v-if="overdueFollowUps?.length > 0"
                    class="relative overflow-hidden rounded-2xl border border-red-200 shadow-sm"
                    :class="mounted ? 'opacity-100 translate-y-0' : 'opacity-0 -translate-y-4'"
                    style="transition: all 0.5s cubic-bezier(0.16, 1, 0.3, 1)"
                >
                    <!-- Animated gradient background -->
                    <div class="absolute inset-0 bg-gradient-to-r from-red-50 via-rose-50 to-red-50"></div>
                    <!-- Pulsing left accent -->
                    <div class="absolute inset-y-0 start-0 w-1.5 bg-gradient-to-b from-red-500 to-rose-500"></div>

                    <div class="relative px-5 py-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3" :class="isRtl ? 'pr-7' : 'pl-7'">
                        <div class="flex items-center gap-3">
                            <!-- Warning icon with pulse -->
                            <div class="relative flex-shrink-0">
                                <div class="w-11 h-11 rounded-xl bg-gradient-to-br from-red-500 to-rose-500 flex items-center justify-center shadow-lg shadow-red-200">
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z" />
                                    </svg>
                                </div>
                                <span class="absolute -top-1 -right-1 w-3 h-3 rounded-full bg-red-500 animate-ping opacity-50"></span>
                                <span class="absolute -top-1 -right-1 w-3 h-3 rounded-full bg-red-500"></span>
                            </div>
                            <div>
                                <h3 class="text-sm font-bold text-red-800">
                                    {{ isRtl ? 'لديك متابعات متأخرة!' : 'You have overdue follow-ups!' }}
                                </h3>
                                <p class="text-xs text-red-600 mt-0.5">
                                    {{ overdueFollowUps.length }} {{ isRtl ? 'متابعة بحاجة لاهتمامك الفوري' : 'follow-up(s) need your immediate attention' }}
                                </p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2 flex-shrink-0">
                            <Link
                                href="/secretary/crm/calendar"
                                class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-xs font-semibold bg-white text-red-700 border border-red-200 hover:bg-red-50 shadow-sm transition-all duration-200 hover:scale-[1.02]"
                            >
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                {{ isRtl ? 'التقويم' : 'Calendar' }}
                            </Link>
                            <Link
                                href="/secretary/crm/leads?overdue=1"
                                class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-xs font-semibold bg-gradient-to-r from-red-500 to-rose-500 text-white shadow-sm shadow-red-200 hover:shadow-md transition-all duration-200 hover:scale-[1.02]"
                            >
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                                {{ isRtl ? 'عرض المتأخرة' : 'View Overdue' }}
                            </Link>
                        </div>
                    </div>
                </div>
            </Transition>

            <!-- ============ HERO GRADIENT HEADER ============ -->
            <div
                class="relative overflow-hidden rounded-2xl p-6 md:p-8"
                :class="mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'"
                style="background: linear-gradient(135deg, #0d9488 0%, #0f766e 50%, #134e4a 100%); transition: all 0.6s cubic-bezier(0.16, 1, 0.3, 1); transition-delay: 0s"
            >
                <!-- Decorative circles -->
                <div class="absolute -top-10 -right-10 w-40 h-40 rounded-full bg-white/5"></div>
                <div class="absolute -bottom-8 -left-8 w-32 h-32 rounded-full bg-white/5"></div>
                <div class="absolute top-1/2 right-1/4 w-20 h-20 rounded-full bg-white/5"></div>

                <div class="relative z-10">
                    <div class="flex items-center gap-3 mb-2">
                        <div class="w-10 h-10 rounded-xl bg-white/15 backdrop-blur-sm flex items-center justify-center">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                            </svg>
                        </div>
                        <h1 class="text-2xl md:text-3xl font-bold text-white">{{ greeting }} !</h1>
                    </div>
                    <p class="text-teal-100 text-sm md:text-base mt-1 max-w-xl leading-relaxed">
                        {{ motivationalQuote }}
                    </p>
                    <div class="mt-4 flex items-center gap-2 flex-wrap">
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-white/15 backdrop-blur-sm text-white text-xs font-medium">
                            <span class="w-2 h-2 rounded-full bg-emerald-300 animate-pulse"></span>
                            {{ isRtl ? 'متصل' : 'Online' }}
                        </span>
                        <span class="px-3 py-1 rounded-full bg-white/10 text-teal-100 text-xs">
                            {{ new Date().toLocaleDateString(isRtl ? 'ar-SA' : 'en-GB', { weekday: 'long', day: 'numeric', month: 'long' }) }}
                        </span>
                        <button @click="refreshDashboard" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-white/10 hover:bg-white/20 text-teal-200 text-[10px] transition-all" :title="isRtl ? 'تحديث البيانات' : 'Refresh data'">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                            {{ timeSinceLoad }}
                        </button>
                        <!-- Auto-refresh picker -->
                        <div class="relative" data-auto-refresh-picker>
                            <button @click.stop="showAutoRefreshPicker = !showAutoRefreshPicker"
                                class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] transition-all"
                                :class="autoRefreshInterval > 0 ? 'bg-emerald-400/25 text-emerald-200 ring-1 ring-emerald-400/30' : 'bg-white/10 hover:bg-white/20 text-teal-200'"
                                :title="isRtl ? 'تحديث تلقائي' : 'Auto-refresh'">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                <span v-if="autoRefreshInterval > 0" class="w-1.5 h-1.5 rounded-full bg-emerald-300 animate-pulse"></span>
                                {{ autoRefreshLabel }}
                            </button>
                            <Transition
                                enter-active-class="transition-all duration-200"
                                enter-from-class="opacity-0 scale-95 -translate-y-1"
                                enter-to-class="opacity-100 scale-100 translate-y-0"
                                leave-active-class="transition-all duration-150"
                                leave-from-class="opacity-100 scale-100"
                                leave-to-class="opacity-0 scale-95"
                            >
                                <div v-if="showAutoRefreshPicker" class="absolute top-full mt-1.5 start-0 w-32 bg-white rounded-xl shadow-xl border border-gray-200 py-1.5 z-30">
                                    <button v-for="opt in autoRefreshOptions" :key="opt.key"
                                        @click="setAutoRefresh(opt.key)"
                                        class="w-full text-start px-3 py-1.5 text-xs transition-colors flex items-center justify-between"
                                        :class="autoRefreshInterval === opt.key ? 'bg-teal-50 text-teal-700 font-semibold' : 'text-gray-600 hover:bg-gray-50'">
                                        <span>{{ isRtl ? opt.ar : opt.en }}</span>
                                        <svg v-if="autoRefreshInterval === opt.key" class="w-3.5 h-3.5 text-teal-500" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                    </button>
                                </div>
                            </Transition>
                        </div>
                        <span class="hidden md:inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-white/10 text-teal-200 text-[10px]">
                            <kbd class="px-1 py-0.5 rounded bg-white/15 text-white font-mono text-[10px]">N</kbd> {{ isRtl ? 'جديد' : 'New' }}
                            <kbd class="px-1 py-0.5 rounded bg-white/15 text-white font-mono text-[10px] ms-1.5">L</kbd> {{ isRtl ? 'عملاء' : 'Leads' }}
                            <kbd class="px-1 py-0.5 rounded bg-white/15 text-white font-mono text-[10px] ms-1.5">P</kbd> {{ isRtl ? 'أنابيب' : 'Pipeline' }}
                            <kbd class="px-1 py-0.5 rounded bg-white/15 text-white font-mono text-[10px] ms-1.5">C</kbd> {{ isRtl ? 'تقويم' : 'Calendar' }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- ============ STATS CARDS ============ -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- My Active Leads -->
                <Link href="/secretary/crm/leads"
                    class="group relative bg-white rounded-2xl shadow-sm border border-gray-100 p-5 hover:shadow-md hover:scale-[1.02] cursor-pointer block"
                    :class="mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'"
                    style="transition: all 0.6s cubic-bezier(0.16, 1, 0.3, 1); transition-delay: 0.1s"
                >
                    <div class="flex items-start justify-between">
                        <div>
                            <p class="text-xs font-medium text-gray-400 uppercase tracking-wider">{{ isRtl ? 'عملائي النشطين' : 'My Active Leads' }}</p>
                            <p class="text-3xl font-bold text-gray-800 mt-2 tabular-nums">{{ counterMyLeads }}</p>
                        </div>
                        <div class="w-11 h-11 rounded-xl flex items-center justify-center" style="background: linear-gradient(135deg, #0d9488, #14b8a6);">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                        </div>
                    </div>
                    <div class="mt-3 h-8 overflow-hidden">
                        <svg width="100%" height="32" preserveAspectRatio="none" class="opacity-40 group-hover:opacity-70 transition-opacity duration-300">
                            <path :d="sparklinePath(sparklines.leads, 200, 32)" fill="none" stroke="#0d9488" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="transition-all duration-1000"/>
                        </svg>
                    </div>
                </Link>

                <!-- New Leads -->
                <Link href="/secretary/crm/leads?status=new"
                    class="group relative bg-white rounded-2xl shadow-sm border border-gray-100 p-5 hover:shadow-md hover:scale-[1.02] cursor-pointer block"
                    :class="mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'"
                    style="transition: all 0.6s cubic-bezier(0.16, 1, 0.3, 1); transition-delay: 0.15s"
                >
                    <div class="flex items-start justify-between">
                        <div>
                            <p class="text-xs font-medium text-gray-400 uppercase tracking-wider">{{ isRtl ? 'عملاء جدد' : 'New Leads' }}</p>
                            <p class="text-3xl font-bold text-blue-600 mt-2 tabular-nums">{{ counterNewLeads }}</p>
                        </div>
                        <div class="w-11 h-11 rounded-xl flex items-center justify-center" style="background: linear-gradient(135deg, #2563eb, #60a5fa);">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                            </svg>
                        </div>
                    </div>
                    <div class="mt-3 h-8 overflow-hidden">
                        <svg width="100%" height="32" preserveAspectRatio="none" class="opacity-40 group-hover:opacity-70 transition-opacity duration-300">
                            <path :d="sparklinePath([1,3,2,5,4,3,6], 200, 32)" fill="none" stroke="#2563eb" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </div>
                </Link>

                <!-- Today Follow-ups -->
                <Link href="/secretary/crm/calendar"
                    class="group relative bg-white rounded-2xl shadow-sm border border-gray-100 p-5 hover:shadow-md hover:scale-[1.02] cursor-pointer block"
                    :class="mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'"
                    style="transition: all 0.6s cubic-bezier(0.16, 1, 0.3, 1); transition-delay: 0.2s"
                >
                    <div class="flex items-start justify-between">
                        <div>
                            <p class="text-xs font-medium text-gray-400 uppercase tracking-wider">{{ isRtl ? 'متابعات اليوم' : "Today's Follow-ups" }}</p>
                            <p class="text-3xl font-bold text-gray-800 mt-2 tabular-nums">{{ counterTodayFU }}</p>
                        </div>
                        <div class="w-11 h-11 rounded-xl flex items-center justify-center" style="background: linear-gradient(135deg, #0d9488, #5eead4);">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                        </div>
                    </div>
                    <div class="mt-3 h-8 overflow-hidden">
                        <svg width="100%" height="32" preserveAspectRatio="none" class="opacity-40 group-hover:opacity-70 transition-opacity duration-300">
                            <path :d="sparklinePath(sparklines.followups, 200, 32)" fill="none" stroke="#0d9488" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </div>
                </Link>

                <!-- Overdue -->
                <Link href="/secretary/crm/calendar"
                    class="group relative bg-white rounded-2xl shadow-sm border border-gray-100 p-5 hover:shadow-md hover:scale-[1.02] cursor-pointer block"
                    :class="[mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4', stats.overdue_follow_ups > 0 ? 'border-red-200 bg-red-50/30' : '']"
                    style="transition: all 0.6s cubic-bezier(0.16, 1, 0.3, 1); transition-delay: 0.25s"
                >
                    <div class="flex items-start justify-between">
                        <div>
                            <p class="text-xs font-medium uppercase tracking-wider" :class="stats.overdue_follow_ups > 0 ? 'text-red-400' : 'text-gray-400'">{{ isRtl ? 'متأخرة' : 'Overdue' }}</p>
                            <p class="text-3xl font-bold mt-2 tabular-nums" :class="stats.overdue_follow_ups > 0 ? 'text-red-600' : 'text-gray-800'">{{ counterOverdueFU }}</p>
                        </div>
                        <div class="w-11 h-11 rounded-xl flex items-center justify-center" :style="stats.overdue_follow_ups > 0 ? 'background: linear-gradient(135deg, #dc2626, #f87171)' : 'background: linear-gradient(135deg, #6b7280, #9ca3af)'">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                    </div>
                    <div class="mt-3 h-8 overflow-hidden">
                        <svg v-if="stats.overdue_follow_ups > 0" width="100%" height="32" preserveAspectRatio="none" class="opacity-40 group-hover:opacity-70 transition-opacity duration-300">
                            <path :d="sparklinePath([0,1,2,1,3,2,stats.overdue_follow_ups || 1], 200, 32)" fill="none" stroke="#dc2626" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        <svg v-else width="100%" height="32" preserveAspectRatio="none" class="opacity-20">
                            <path d="M0,28 L200,28" fill="none" stroke="#9ca3af" stroke-width="1" stroke-dasharray="4 4"/>
                        </svg>
                    </div>
                </Link>
            </div>

            <!-- ============ CONVERSION METRICS ============ -->
            <div class="grid grid-cols-2 gap-4"
                :class="mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'"
                style="transition: all 0.6s cubic-bezier(0.16, 1, 0.3, 1); transition-delay: 0.27s">
                <!-- Contact Rate -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 flex items-center gap-4">
                    <div class="relative w-14 h-14 flex-shrink-0">
                        <svg viewBox="0 0 100 100" class="w-full h-full -rotate-90">
                            <circle cx="50" cy="50" r="40" fill="none" stroke="#f1f5f9" stroke-width="8"/>
                            <circle cx="50" cy="50" r="40" fill="none"
                                    :stroke="conversionMetric.contactRate >= 50 ? '#0d9488' : '#f59e0b'" stroke-width="8" stroke-linecap="round"
                                    :stroke-dasharray="(conversionMetric.contactRate / 100 * 251) + ' 251'"
                                    class="transition-all duration-1000 ease-out"/>
                        </svg>
                        <span class="absolute inset-0 flex items-center justify-center text-sm font-bold"
                              :class="conversionMetric.contactRate >= 50 ? 'text-teal-700' : 'text-amber-600'">
                            {{ conversionMetric.contactRate }}%
                        </span>
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-gray-700">{{ isRtl ? '\u0645\u0639\u062F\u0644 \u0627\u0644\u062A\u0648\u0627\u0635\u0644' : 'Contact Rate' }}</p>
                        <p class="text-[11px] text-gray-400 mt-0.5">{{ isRtl ? '\u0645\u0646 \u062C\u062F\u064A\u062F \u0625\u0644\u0649 \u062A\u0645 \u0627\u0644\u062A\u0648\u0627\u0635\u0644' : 'New to Contacted' }}</p>
                    </div>
                </div>
                <!-- Conversion Rate -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 flex items-center gap-4">
                    <div class="relative w-14 h-14 flex-shrink-0">
                        <svg viewBox="0 0 100 100" class="w-full h-full -rotate-90">
                            <circle cx="50" cy="50" r="40" fill="none" stroke="#f1f5f9" stroke-width="8"/>
                            <circle cx="50" cy="50" r="40" fill="none"
                                    :stroke="conversionMetric.rate >= 20 ? '#10b981' : conversionMetric.rate >= 10 ? '#0d9488' : '#ef4444'" stroke-width="8" stroke-linecap="round"
                                    :stroke-dasharray="(conversionMetric.rate / 100 * 251) + ' 251'"
                                    class="transition-all duration-1000 ease-out"/>
                        </svg>
                        <span class="absolute inset-0 flex items-center justify-center text-sm font-bold"
                              :class="conversionMetric.rate >= 20 ? 'text-emerald-600' : conversionMetric.rate >= 10 ? 'text-teal-700' : 'text-red-500'">
                            {{ conversionMetric.rate }}%
                        </span>
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-gray-700">{{ isRtl ? '\u0645\u0639\u062F\u0644 \u0627\u0644\u062A\u062D\u0648\u064A\u0644' : 'Conversion Rate' }}</p>
                        <p class="text-[11px] text-gray-400 mt-0.5">{{ conversionMetric.converted }} / {{ conversionMetric.total }} {{ isRtl ? '\u0639\u0645\u064A\u0644' : 'leads' }}</p>
                    </div>
                </div>
            </div>

            <!-- ============ SLA & STALE LEADS ============ -->
            <div v-if="slaMetrics" class="grid grid-cols-2 gap-4"
                :class="mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'"
                style="transition: all 0.6s cubic-bezier(0.16, 1, 0.3, 1); transition-delay: 0.275s">
                <!-- Avg Response Time -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl flex items-center justify-center" style="background: linear-gradient(135deg, #6366f1, #818cf8)">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <div>
                        <p class="text-lg font-bold text-gray-800">{{ slaMetrics.avg_response_display || '-' }}</p>
                        <p class="text-[10px] text-gray-400 font-medium uppercase">{{ isRtl ? 'متوسط وقت الاستجابة' : 'Avg Response' }}</p>
                    </div>
                </div>
                <!-- Awaiting Contact -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 flex items-center gap-3"
                     :class="slaMetrics.awaiting_contact > 0 ? 'border-amber-200 bg-amber-50/30' : ''">
                    <div class="w-10 h-10 rounded-xl flex items-center justify-center"
                         :style="slaMetrics.awaiting_contact > 0 ? 'background: linear-gradient(135deg, #f59e0b, #fbbf24)' : 'background: linear-gradient(135deg, #9ca3af, #d1d5db)'">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                    </div>
                    <div>
                        <p class="text-lg font-bold" :class="slaMetrics.awaiting_contact > 0 ? 'text-amber-600' : 'text-gray-800'">{{ slaMetrics.awaiting_contact }}</p>
                        <p class="text-[10px] text-gray-400 font-medium uppercase">{{ isRtl ? 'بانتظار التواصل' : 'Awaiting Contact' }}</p>
                    </div>
                </div>
            </div>

            <!-- Stale Leads Alert -->
            <div v-if="staleLeads?.length" class="bg-amber-50 border border-amber-200 rounded-2xl p-4"
                :class="mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'"
                style="transition: all 0.6s cubic-bezier(0.16, 1, 0.3, 1); transition-delay: 0.28s">
                <div class="flex items-center gap-2 mb-3">
                    <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4.5c-.77-.833-2.694-.833-3.464 0L3.34 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>
                    <h3 class="text-xs font-bold text-amber-800 uppercase tracking-wider">{{ isRtl ? 'عملاء بحاجة للمتابعة' : 'Stale Leads' }}</h3>
                </div>
                <div class="space-y-2">
                    <Link v-for="lead in staleLeads" :key="lead.id"
                          :href="`/secretary/crm/leads/${lead.id}`"
                          class="flex items-center justify-between bg-white/70 rounded-xl px-3 py-2 hover:bg-white transition">
                        <div>
                            <p class="text-sm font-semibold text-gray-800">{{ lead.full_name }}</p>
                            <p class="text-[10px] text-gray-400">{{ lead.phone }}</p>
                        </div>
                        <span class="text-[10px] text-amber-600 font-medium">
                            {{ Math.floor((Date.now() - new Date(lead.updated_at)) / 86400000) }}{{ isRtl ? ' يوم' : 'd ago' }}
                        </span>
                    </Link>
                </div>
            </div>

            <!-- ============ DAILY PROGRESS TRACKER ============ -->
            <div
                class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 overflow-hidden"
                :class="mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'"
                style="transition: all 0.6s cubic-bezier(0.16, 1, 0.3, 1); transition-delay: 0.28s"
            >
                <div class="flex items-center justify-between mb-3">
                    <div class="flex items-center gap-2.5">
                        <div class="w-8 h-8 rounded-lg flex items-center justify-center" :style="dailyProgress.pct >= 100 ? 'background: linear-gradient(135deg, #10b981, #34d399)' : 'background: linear-gradient(135deg, #0d9488, #14b8a6)'">
                            <svg v-if="dailyProgress.pct >= 100" class="w-4 h-4 text-white" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                            <svg v-else class="w-4 h-4 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-gray-800">{{ isRtl ? 'تقدم اليوم' : "Today's Progress" }}</h3>
                            <p class="text-[11px] text-gray-400">
                                {{ dailyProgress.completed }} {{ isRtl ? 'نشاط مكتمل' : 'activities completed' }}
                                <span v-if="dailyProgress.total > 0"> / {{ dailyProgress.total }} {{ isRtl ? 'مهمة' : 'tasks' }}</span>
                            </p>
                        </div>
                    </div>
                    <span class="text-lg font-bold" :class="dailyProgress.pct >= 100 ? 'text-emerald-600' : dailyProgress.pct >= 50 ? 'text-teal-600' : 'text-gray-500'">{{ dailyProgress.pct }}%</span>
                </div>
                <div class="w-full h-2.5 bg-gray-100 rounded-full overflow-hidden">
                    <div class="h-full rounded-full transition-all duration-1500 ease-out"
                         :style="{ width: mounted ? dailyProgress.pct + '%' : '0%', background: dailyProgress.pct >= 100 ? 'linear-gradient(90deg, #10b981, #34d399)' : dailyProgress.pct >= 50 ? 'linear-gradient(90deg, #0d9488, #14b8a6)' : 'linear-gradient(90deg, #f59e0b, #fbbf24)', transitionDelay: '0.5s' }"></div>
                </div>
                <div v-if="dailyProgress.pct >= 100" class="mt-2 text-center">
                    <span class="text-xs font-medium text-emerald-600">{{ isRtl ? 'عمل رائع! أنجزت كل مهام اليوم' : 'Great job! All tasks completed for today' }}</span>
                </div>
            </div>

            <!-- ============ WEEKLY PERFORMANCE + MODULE DISTRIBUTION ============ -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4"
                :class="mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'"
                style="transition: all 0.6s cubic-bezier(0.16, 1, 0.3, 1); transition-delay: 0.26s">

                <!-- Weekly Performance -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-sm font-semibold text-gray-800 flex items-center gap-2">
                            <svg class="w-4 h-4 text-teal-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                            </svg>
                            {{ isRtl ? 'أداء هذا الأسبوع' : 'This Week' }}
                        </h3>
                        <span class="text-[10px] text-gray-400 font-medium">{{ isRtl ? 'من بداية الأسبوع' : 'Since Monday' }}</span>
                    </div>
                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                        <div class="bg-teal-50/60 rounded-xl p-3 text-center border border-teal-100">
                            <div class="text-xl font-bold text-teal-700">{{ weeklyStats?.leads_added || 0 }}</div>
                            <div class="text-[10px] text-teal-600 font-medium mt-0.5">{{ isRtl ? 'عملاء جدد' : 'New Leads' }}</div>
                        </div>
                        <div class="bg-blue-50/60 rounded-xl p-3 text-center border border-blue-100">
                            <div class="text-xl font-bold text-blue-700">{{ weeklyStats?.activities_logged || 0 }}</div>
                            <div class="text-[10px] text-blue-600 font-medium mt-0.5">{{ isRtl ? 'نشاط' : 'Activities' }}</div>
                        </div>
                        <div class="bg-emerald-50/60 rounded-xl p-3 text-center border border-emerald-100">
                            <div class="text-xl font-bold text-emerald-700">{{ weeklyStats?.follow_ups_completed || 0 }}</div>
                            <div class="text-[10px] text-emerald-600 font-medium mt-0.5">{{ isRtl ? 'متابعات مكتملة' : 'Completed F/U' }}</div>
                        </div>
                        <div class="bg-indigo-50/60 rounded-xl p-3 text-center border border-indigo-100">
                            <div class="text-xl font-bold text-indigo-700">{{ weeklyStats?.calls_made || 0 }}</div>
                            <div class="text-[10px] text-indigo-600 font-medium mt-0.5">{{ isRtl ? 'مكالمات' : 'Calls' }}</div>
                        </div>
                        <div class="bg-green-50/60 rounded-xl p-3 text-center border border-green-100">
                            <div class="text-xl font-bold text-green-700">{{ weeklyStats?.whatsapp_sent || 0 }}</div>
                            <div class="text-[10px] text-green-600 font-medium mt-0.5">{{ isRtl ? 'واتساب' : 'WhatsApp' }}</div>
                        </div>
                        <div class="bg-purple-50/60 rounded-xl p-3 text-center border border-purple-100">
                            <div class="text-xl font-bold text-purple-700">{{ conversionRate || 0 }}%</div>
                            <div class="text-[10px] text-purple-600 font-medium mt-0.5">{{ isRtl ? 'نسبة التحويل' : 'Conv. Rate' }}</div>
                        </div>
                    </div>

                    <!-- Activity Sparkline -->
                    <div v-if="activityTrend?.length" class="mt-4 pt-3 border-t border-gray-100">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-[10px] text-gray-400 font-medium uppercase tracking-wider">{{ isRtl ? 'نشاط آخر 7 أيام' : 'Last 7 Days Activity' }}</span>
                            <span class="text-[10px] font-bold text-gray-600">{{ activityTrend.reduce((s, d) => s + d.count, 0) }} {{ isRtl ? 'إجمالي' : 'total' }}</span>
                        </div>
                        <div class="flex items-end gap-1 h-12">
                            <div v-for="(day, i) in activityTrend" :key="i"
                                class="flex-1 rounded-t transition-all duration-700"
                                :class="mounted ? '' : 'h-0'"
                                :style="{
                                    height: mounted ? Math.max((day.count / Math.max(...activityTrend.map(d => d.count), 1)) * 48, 4) + 'px' : '0px',
                                    backgroundColor: i === activityTrend.length - 1 ? '#0d9488' : '#ccfbf1',
                                    transitionDelay: (i * 80 + 400) + 'ms'
                                }"
                                :title="day.date + ': ' + day.count">
                            </div>
                        </div>
                        <div class="flex gap-1 mt-1">
                            <span v-for="(day, i) in activityTrend" :key="'l'+i" class="flex-1 text-center text-[8px] text-gray-400">
                                {{ new Date(day.date).toLocaleDateString(isRtl ? 'ar-SA' : 'en-US', { weekday: 'narrow' }) }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Module Distribution -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
                    <h3 class="text-sm font-semibold text-gray-800 flex items-center gap-2 mb-4">
                        <svg class="w-4 h-4 text-teal-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z"/>
                        </svg>
                        {{ isRtl ? 'توزيع الأقسام' : 'Department Distribution' }}
                    </h3>

                    <div v-if="moduleDistribution && (moduleDistribution.derma || moduleDistribution.dental)" class="space-y-4">
                        <!-- Visual Bars -->
                        <div class="space-y-3">
                            <div>
                                <div class="flex items-center justify-between mb-1.5">
                                    <span class="flex items-center gap-1.5 text-sm font-medium text-teal-700">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.098 19.902a3.75 3.75 0 005.304 0l6.401-6.402M6.75 21A3.75 3.75 0 013 17.25V4.125C3 3.504 3.504 3 4.125 3h5.25c.621 0 1.125.504 1.125 1.125v4.072M6.75 21a3.75 3.75 0 003.75-3.75V8.197"/></svg>
                                        {{ isRtl ? 'جلدية' : 'Derma' }}
                                    </span>
                                    <span class="text-sm font-bold text-teal-700">{{ moduleDistribution.derma || 0 }}</span>
                                </div>
                                <div class="h-3 rounded-full bg-gray-100 overflow-hidden">
                                    <div class="h-full rounded-full transition-all duration-1000 ease-out"
                                        style="background: linear-gradient(90deg, #0d9488, #14b8a6)"
                                        :style="{ width: mounted ? Math.round(((moduleDistribution.derma || 0) / Math.max((moduleDistribution.derma || 0) + (moduleDistribution.dental || 0), 1)) * 100) + '%' : '0%' }">
                                    </div>
                                </div>
                            </div>
                            <div>
                                <div class="flex items-center justify-between mb-1.5">
                                    <span class="flex items-center gap-1.5 text-sm font-medium text-sky-700">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.182 15.182a4.5 4.5 0 01-6.364 0M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        {{ isRtl ? 'أسنان' : 'Dental' }}
                                    </span>
                                    <span class="text-sm font-bold text-sky-700">{{ moduleDistribution.dental || 0 }}</span>
                                </div>
                                <div class="h-3 rounded-full bg-gray-100 overflow-hidden">
                                    <div class="h-full rounded-full transition-all duration-1000 ease-out"
                                        style="background: linear-gradient(90deg, #0284c7, #38bdf8)"
                                        :style="{ width: mounted ? Math.round(((moduleDistribution.dental || 0) / Math.max((moduleDistribution.derma || 0) + (moduleDistribution.dental || 0), 1)) * 100) + '%' : '0%' }">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Summary -->
                        <div class="bg-gray-50 rounded-xl p-3 flex items-center justify-between">
                            <span class="text-xs text-gray-500">{{ isRtl ? 'إجمالي العملاء النشطين' : 'Total Active Leads' }}</span>
                            <span class="text-lg font-bold text-gray-800">{{ (moduleDistribution.derma || 0) + (moduleDistribution.dental || 0) }}</span>
                        </div>
                    </div>
                    <div v-else class="text-center py-8 text-gray-400">
                        <svg class="w-10 h-10 mx-auto mb-2 text-gray-300" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z"/></svg>
                        <p class="text-xs">{{ isRtl ? 'لا توجد بيانات بعد' : 'No data yet' }}</p>
                    </div>
                </div>
            </div>

            <!-- ============ WEEK AT A GLANCE ============ -->
            <div
                class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4"
                :class="mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'"
                style="transition: all 0.6s cubic-bezier(0.16, 1, 0.3, 1); transition-delay: 0.29s"
            >
                <div class="flex items-center justify-between mb-3">
                    <div class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-teal-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        <h3 class="text-xs font-bold text-gray-700 uppercase tracking-wider">{{ isRtl ? 'نظرة على الأسبوع' : 'Week at a Glance' }}</h3>
                    </div>
                    <Link href="/secretary/crm/calendar" class="text-[10px] font-medium text-teal-600 hover:underline">{{ isRtl ? 'التقويم' : 'Calendar' }}</Link>
                </div>
                <div class="grid grid-cols-7 gap-1.5">
                    <Link v-for="wd in weekGlance" :key="wd.date" href="/secretary/crm/calendar"
                        :class="['flex flex-col items-center rounded-xl py-2.5 transition-all duration-200 border',
                            wd.isToday
                                ? 'bg-teal-50 border-teal-200 shadow-sm'
                                : wd.count > 0
                                    ? 'bg-gray-50 border-gray-100 hover:border-teal-200 hover:bg-teal-50/50'
                                    : 'bg-gray-50/50 border-transparent hover:bg-gray-50']">
                        <span :class="['text-[10px] font-medium', wd.isToday ? 'text-teal-600' : 'text-gray-400']">{{ wd.dayName }}</span>
                        <span :class="['text-sm font-bold mt-0.5', wd.isToday ? 'text-teal-700' : 'text-gray-700']">{{ wd.day }}</span>
                        <span v-if="wd.count > 0"
                              :class="['mt-1 text-[10px] font-bold min-w-[20px] h-[20px] flex items-center justify-center rounded-full',
                                  wd.isToday ? 'bg-teal-500 text-white' : 'bg-gray-200 text-gray-600']">
                            {{ wd.count }}
                        </span>
                        <span v-else class="mt-1 w-1.5 h-1.5 rounded-full bg-gray-200"></span>
                    </Link>
                </div>
            </div>

            <!-- ============ QUICK ACTIONS BAR ============ -->
            <div
                class="flex flex-wrap items-center gap-3"
                :class="mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'"
                style="transition: all 0.6s cubic-bezier(0.16, 1, 0.3, 1); transition-delay: 0.3s"
            >
                <Link href="/secretary/crm/leads"
                    class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-white text-sm font-semibold shadow-sm hover:shadow-md transition-all duration-200 hover:scale-[1.02]"
                    style="background: linear-gradient(135deg, #0d9488, #0f766e);"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    {{ isRtl ? 'عرض كل العملاء' : 'View All Leads' }}
                </Link>

                <Link href="/secretary/crm/calendar"
                    class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-teal-700 text-sm font-semibold bg-teal-50 border border-teal-200 hover:bg-teal-100 transition-all duration-200 hover:scale-[1.02]"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    {{ isRtl ? 'التقويم' : 'Calendar' }}
                </Link>

                <Link href="/secretary/crm/pipeline"
                    class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-teal-700 text-sm font-semibold bg-teal-50 border border-teal-200 hover:bg-teal-100 transition-all duration-200 hover:scale-[1.02]"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7" />
                    </svg>
                    {{ isRtl ? 'خط الأنابيب' : 'Pipeline' }}
                </Link>

                <Link href="/secretary/crm/templates"
                    class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-teal-700 text-sm font-semibold bg-teal-50 border border-teal-200 hover:bg-teal-100 transition-all duration-200 hover:scale-[1.02]"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 12h10" />
                    </svg>
                    {{ isRtl ? 'القوالب' : 'Templates' }}
                </Link>

                <button @click="showQuickAdd = true"
                    class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-teal-700 text-sm font-semibold bg-teal-50 border border-teal-200 hover:bg-teal-100 transition-all duration-200 hover:scale-[1.02]"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                    </svg>
                    {{ isRtl ? 'عميل جديد' : 'New Lead' }}
                </button>
            </div>

            <!-- ============ MAIN CONTENT GRID ============ -->
            <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">

                <!-- LEFT COLUMN: Follow-ups -->
                <div class="xl:col-span-2 space-y-6">

                    <!-- ---- TODAY'S FOLLOW-UPS ---- -->
                    <div
                        class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden"
                        :class="mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'"
                        style="transition: all 0.6s cubic-bezier(0.16, 1, 0.3, 1); transition-delay: 0.35s"
                    >
                        <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between" style="background: linear-gradient(135deg, #f0fdfa, #ccfbf1);">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-lg flex items-center justify-center" style="background: linear-gradient(135deg, #0d9488, #14b8a6);">
                                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                </div>
                                <h3 class="text-sm font-bold text-gray-800 uppercase tracking-wider">{{ isRtl ? 'متابعات اليوم' : "Today's Follow-ups" }}</h3>
                            </div>
                            <span class="px-2.5 py-1 rounded-full text-xs font-bold" style="background-color: #ccfbf1; color: #0d9488;">{{ todayFollowUps?.length || 0 }}</span>
                        </div>

                        <div class="p-4 grid grid-cols-1 md:grid-cols-2 gap-3" v-if="todayFollowUps?.length">
                            <div
                                v-for="(fu, idx) in todayFollowUps" :key="fu.id"
                                class="relative bg-gray-50 hover:bg-white rounded-xl border border-gray-100 hover:border-teal-200 p-4 transition-all duration-300 hover:shadow-md group"
                                :class="mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'"
                                :style="`transition: all 0.5s cubic-bezier(0.16, 1, 0.3, 1); transition-delay: ${0.4 + idx * 0.05}s`"
                            >
                                <div class="flex items-start gap-3">
                                    <!-- Type Icon -->
                                    <div class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0" :class="followUpTypeConfig[fu.type]?.color || 'bg-gray-100 text-gray-500'">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" :d="followUpTypeConfig[fu.type]?.iconPath || 'M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z'" />
                                        </svg>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <Link :href="`/secretary/crm/leads/${fu.lead?.id}`" class="text-sm font-semibold text-gray-800 hover:text-teal-600 transition-colors truncate block">
                                            {{ fu.lead?.full_name }}
                                        </Link>
                                        <div class="flex items-center gap-2 mt-1">
                                            <span class="text-[11px] font-medium px-2 py-0.5 rounded-full" :class="followUpTypeConfig[fu.type]?.color || 'bg-gray-100 text-gray-500'">
                                                {{ followUpTypeConfig[fu.type]?.label || fu.type }}
                                            </span>
                                            <span class="text-[11px] text-gray-400 font-medium">{{ formatTime(fu.scheduled_at) }}</span>
                                        </div>
                                        <p v-if="fu.notes" class="text-xs text-gray-400 mt-1.5 truncate">{{ fu.notes }}</p>
                                    </div>
                                </div>
                                <!-- Action buttons / Inline completion form -->
                                <div class="mt-3 pt-3 border-t border-gray-100">
                                    <!-- Inline result input -->
                                    <Transition enter-active-class="transition-all duration-200" enter-from-class="opacity-0 -translate-y-1" enter-to-class="opacity-100 translate-y-0" leave-active-class="transition-all duration-150" leave-from-class="opacity-100 translate-y-0" leave-to-class="opacity-0 -translate-y-1">
                                        <div v-if="completingFuId === fu.id" class="space-y-2">
                                            <input
                                                v-model="completionResult"
                                                type="text"
                                                :placeholder="isRtl ? 'نتيجة المتابعة (اختياري)...' : 'Follow-up result (optional)...'"
                                                class="w-full px-3 py-2 text-xs border border-emerald-200 rounded-lg bg-emerald-50/50 focus:ring-2 focus:ring-emerald-300 focus:border-emerald-300 outline-none transition-all"
                                                @keyup.enter="submitComplete(fu.id)"
                                                @keyup.escape="cancelComplete()"
                                            />
                                            <div class="flex items-center gap-2">
                                                <button @click="submitComplete(fu.id)" class="flex-1 inline-flex items-center justify-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold bg-emerald-500 text-white hover:bg-emerald-600 transition-all duration-200">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" /></svg>
                                                    {{ isRtl ? 'تأكيد' : 'Confirm' }}
                                                </button>
                                                <button @click="cancelComplete()" class="px-3 py-1.5 rounded-lg text-xs font-semibold text-gray-500 hover:bg-gray-100 border border-gray-200 transition-all duration-200">
                                                    {{ isRtl ? 'إلغاء' : 'Cancel' }}
                                                </button>
                                            </div>
                                        </div>
                                    </Transition>
                                    <!-- Normal buttons -->
                                    <div v-if="completingFuId !== fu.id" class="flex items-center gap-2">
                                    <button
                                        @click="completeFollowUp(fu.id)"
                                        class="flex-1 inline-flex items-center justify-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold bg-emerald-50 text-emerald-600 hover:bg-emerald-100 border border-emerald-200 transition-all duration-200 hover:scale-[1.02]"
                                    >
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" /></svg>
                                        {{ isRtl ? 'مكتمل' : 'Complete' }}
                                    </button>
                                    <button
                                        @click="missFollowUp(fu.id)"
                                        class="flex-1 inline-flex items-center justify-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold bg-red-50 text-red-500 hover:bg-red-100 border border-red-200 transition-all duration-200 hover:scale-[1.02]"
                                    >
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" /></svg>
                                        {{ isRtl ? 'فائت' : 'Missed' }}
                                    </button>
                                    <!-- Snooze -->
                                    <div class="relative">
                                        <button @click="toggleSnooze(fu.id)"
                                            class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-xs font-semibold bg-purple-50 text-purple-500 hover:bg-purple-100 border border-purple-200 transition-all duration-200">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        </button>
                                        <Transition enter-active-class="transition-all duration-200" enter-from-class="opacity-0 scale-95" enter-to-class="opacity-100 scale-100" leave-active-class="transition-all duration-150" leave-from-class="opacity-100 scale-100" leave-to-class="opacity-0 scale-95">
                                            <div v-if="snoozeOpenId === fu.id" class="absolute bottom-full mb-1 end-0 w-40 bg-white rounded-xl shadow-xl border border-gray-200 py-1.5 z-20">
                                                <button @click="snoozeFollowUp(fu.id, '1h')" class="w-full text-start px-3 py-1.5 text-xs text-gray-700 hover:bg-purple-50 transition-colors">{{ isRtl ? 'ساعة واحدة' : '1 Hour' }}</button>
                                                <button @click="snoozeFollowUp(fu.id, 'tomorrow')" class="w-full text-start px-3 py-1.5 text-xs text-gray-700 hover:bg-purple-50 transition-colors">{{ isRtl ? 'غدا 9 صباحا' : 'Tomorrow 9 AM' }}</button>
                                                <button @click="snoozeFollowUp(fu.id, '1w')" class="w-full text-start px-3 py-1.5 text-xs text-gray-700 hover:bg-purple-50 transition-colors">{{ isRtl ? 'اسبوع واحد' : '1 Week' }}</button>
                                            </div>
                                        </Transition>
                                    </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div v-else class="px-6 py-12 text-center">
                            <div class="w-16 h-16 rounded-2xl mx-auto mb-4 flex items-center justify-center" style="background: linear-gradient(135deg, #f0fdfa, #ccfbf1);">
                                <svg class="w-8 h-8 text-teal-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <p class="text-sm font-medium text-gray-500">{{ isRtl ? 'لا توجد متابعات اليوم' : 'No follow-ups scheduled for today' }}</p>
                            <p class="text-xs text-gray-400 mt-1">{{ isRtl ? 'يوم رائع للتخطيط!' : 'Great day for planning ahead!' }}</p>
                        </div>
                    </div>

                    <!-- ---- OVERDUE FOLLOW-UPS ---- -->
                    <div
                        v-if="overdueFollowUps?.length"
                        class="bg-white rounded-2xl shadow-sm border border-red-200 overflow-hidden"
                        :class="mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'"
                        style="transition: all 0.6s cubic-bezier(0.16, 1, 0.3, 1); transition-delay: 0.4s"
                    >
                        <div class="px-6 py-4 border-b border-red-100 flex items-center justify-between bg-gradient-to-r from-red-50 to-rose-50">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-lg flex items-center justify-center bg-gradient-to-br from-red-500 to-rose-500 relative">
                                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z" />
                                    </svg>
                                    <!-- Pulsing dot -->
                                    <span class="absolute -top-1 -right-1 w-3 h-3 rounded-full bg-red-500 animate-ping opacity-75"></span>
                                    <span class="absolute -top-1 -right-1 w-3 h-3 rounded-full bg-red-500"></span>
                                </div>
                                <h3 class="text-sm font-bold text-red-700 uppercase tracking-wider">{{ isRtl ? 'متابعات متأخرة' : 'Overdue Follow-ups' }}</h3>
                            </div>
                            <span class="px-2.5 py-1 rounded-full text-xs font-bold bg-red-100 text-red-600 animate-pulse">{{ overdueFollowUps.length }}</span>
                        </div>

                        <div class="p-4 grid grid-cols-1 md:grid-cols-2 gap-3">
                            <div
                                v-for="(fu, idx) in overdueFollowUps" :key="fu.id"
                                class="relative bg-red-50/50 hover:bg-white rounded-xl border border-red-100 hover:border-red-300 p-4 transition-all duration-300 hover:shadow-md"
                                :class="mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'"
                                :style="`transition: all 0.5s cubic-bezier(0.16, 1, 0.3, 1); transition-delay: ${0.45 + idx * 0.05}s`"
                            >
                                <!-- Urgent stripe -->
                                <div class="absolute top-0 left-0 w-1 h-full rounded-l-xl bg-gradient-to-b from-red-500 to-rose-500"></div>

                                <div class="flex items-start gap-3" :class="isRtl ? 'pr-2' : 'pl-2'">
                                    <div class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0" :class="followUpTypeConfig[fu.type]?.color || 'bg-gray-100 text-gray-500'">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" :d="followUpTypeConfig[fu.type]?.iconPath || 'M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z'" />
                                        </svg>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <Link :href="`/secretary/crm/leads/${fu.lead?.id}`" class="text-sm font-semibold text-gray-800 hover:text-red-600 transition-colors truncate block">
                                            {{ fu.lead?.full_name }}
                                        </Link>
                                        <div class="flex items-center gap-2 mt-1">
                                            <span class="text-[11px] font-bold px-2 py-0.5 rounded-full bg-red-100 text-red-600">
                                                {{ isRtl ? 'متأخر' : 'Overdue' }} - {{ formatDate(fu.scheduled_at) }}
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <div class="mt-3 pt-3 border-t border-red-100" :class="isRtl ? 'pr-2' : 'pl-2'">
                                    <!-- Inline result input -->
                                    <Transition enter-active-class="transition-all duration-200" enter-from-class="opacity-0 -translate-y-1" enter-to-class="opacity-100 translate-y-0" leave-active-class="transition-all duration-150" leave-from-class="opacity-100 translate-y-0" leave-to-class="opacity-0 -translate-y-1">
                                        <div v-if="completingFuId === fu.id" class="space-y-2">
                                            <input
                                                v-model="completionResult"
                                                type="text"
                                                :placeholder="isRtl ? 'نتيجة المتابعة (اختياري)...' : 'Follow-up result (optional)...'"
                                                class="w-full px-3 py-2 text-xs border border-emerald-200 rounded-lg bg-emerald-50/50 focus:ring-2 focus:ring-emerald-300 focus:border-emerald-300 outline-none transition-all"
                                                @keyup.enter="submitComplete(fu.id)"
                                                @keyup.escape="cancelComplete()"
                                            />
                                            <div class="flex items-center gap-2">
                                                <button @click="submitComplete(fu.id)" class="flex-1 inline-flex items-center justify-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold bg-emerald-500 text-white hover:bg-emerald-600 transition-all duration-200">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" /></svg>
                                                    {{ isRtl ? 'تأكيد' : 'Confirm' }}
                                                </button>
                                                <button @click="cancelComplete()" class="px-3 py-1.5 rounded-lg text-xs font-semibold text-gray-500 hover:bg-gray-100 border border-gray-200 transition-all duration-200">
                                                    {{ isRtl ? 'إلغاء' : 'Cancel' }}
                                                </button>
                                            </div>
                                        </div>
                                    </Transition>
                                    <!-- Normal buttons -->
                                    <div v-if="completingFuId !== fu.id" class="flex items-center gap-2">
                                    <button
                                        @click="completeFollowUp(fu.id)"
                                        class="flex-1 inline-flex items-center justify-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold bg-emerald-50 text-emerald-600 hover:bg-emerald-100 border border-emerald-200 transition-all duration-200"
                                    >
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" /></svg>
                                        {{ isRtl ? 'مكتمل' : 'Complete' }}
                                    </button>
                                    <button
                                        @click="missFollowUp(fu.id)"
                                        class="flex-1 inline-flex items-center justify-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold bg-red-50 text-red-500 hover:bg-red-100 border border-red-200 transition-all duration-200"
                                    >
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" /></svg>
                                        {{ isRtl ? 'فائت' : 'Missed' }}
                                    </button>
                                    <!-- Snooze -->
                                    <div class="relative">
                                        <button @click="toggleSnooze(fu.id)"
                                            class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-xs font-semibold bg-purple-50 text-purple-500 hover:bg-purple-100 border border-purple-200 transition-all duration-200">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        </button>
                                        <Transition enter-active-class="transition-all duration-200" enter-from-class="opacity-0 scale-95" enter-to-class="opacity-100 scale-100" leave-active-class="transition-all duration-150" leave-from-class="opacity-100 scale-100" leave-to-class="opacity-0 scale-95">
                                            <div v-if="snoozeOpenId === fu.id" class="absolute bottom-full mb-1 end-0 w-40 bg-white rounded-xl shadow-xl border border-gray-200 py-1.5 z-20">
                                                <button @click="snoozeFollowUp(fu.id, '1h')" class="w-full text-start px-3 py-1.5 text-xs text-gray-700 hover:bg-purple-50 transition-colors">{{ isRtl ? 'ساعة واحدة' : '1 Hour' }}</button>
                                                <button @click="snoozeFollowUp(fu.id, 'tomorrow')" class="w-full text-start px-3 py-1.5 text-xs text-gray-700 hover:bg-purple-50 transition-colors">{{ isRtl ? 'غدا 9 صباحا' : 'Tomorrow 9 AM' }}</button>
                                                <button @click="snoozeFollowUp(fu.id, '1w')" class="w-full text-start px-3 py-1.5 text-xs text-gray-700 hover:bg-purple-50 transition-colors">{{ isRtl ? 'اسبوع واحد' : '1 Week' }}</button>
                                            </div>
                                        </Transition>
                                    </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- RIGHT COLUMN: Recent Leads + Performance -->
                <div class="space-y-6">

                    <!-- ---- PERFORMANCE MINI WIDGET ---- -->
                    <div
                        class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 overflow-hidden"
                        :class="mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'"
                        style="transition: all 0.6s cubic-bezier(0.16, 1, 0.3, 1); transition-delay: 0.35s"
                    >
                        <div class="flex items-center gap-3 mb-4">
                            <div class="w-8 h-8 rounded-lg flex items-center justify-center" style="background: linear-gradient(135deg, #0d9488, #14b8a6);">
                                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                </svg>
                            </div>
                            <h3 class="text-sm font-bold text-gray-800 uppercase tracking-wider">{{ isRtl ? 'الأداء' : 'Performance' }}</h3>
                        </div>

                        <div class="text-center mb-4">
                            <div class="relative w-24 h-24 mx-auto">
                                <svg class="w-24 h-24 transform -rotate-90" viewBox="0 0 100 100">
                                    <circle cx="50" cy="50" r="42" stroke="#e5e7eb" stroke-width="8" fill="none" />
                                    <circle
                                        cx="50" cy="50" r="42"
                                        stroke="url(#perfGrad)"
                                        stroke-width="8"
                                        fill="none"
                                        stroke-linecap="round"
                                        :stroke-dasharray="`${mounted ? (conversionRate || 0) * 2.64 : 0} 264`"
                                        style="transition: stroke-dasharray 1.5s cubic-bezier(0.16, 1, 0.3, 1) 0.6s"
                                    />
                                    <defs>
                                        <linearGradient id="perfGrad" x1="0%" y1="0%" x2="100%" y2="100%">
                                            <stop offset="0%" stop-color="#0d9488" />
                                            <stop offset="100%" stop-color="#14b8a6" />
                                        </linearGradient>
                                    </defs>
                                </svg>
                                <div class="absolute inset-0 flex items-center justify-center">
                                    <span class="text-xl font-bold text-gray-800">{{ conversionRate || 0 }}%</span>
                                </div>
                            </div>
                            <p class="text-xs text-gray-400 mt-2 font-medium">{{ isRtl ? 'معدل التحويل' : 'Conversion Rate' }}</p>
                        </div>

                        <div class="space-y-2.5">
                            <div class="flex items-center justify-between text-xs">
                                <span class="text-gray-500">{{ isRtl ? 'عملاء نشطين' : 'Active Leads' }}</span>
                                <span class="font-bold text-gray-800">{{ stats.my_leads }}</span>
                            </div>
                            <div class="flex items-center justify-between text-xs">
                                <span class="text-gray-500">{{ isRtl ? 'نشاطات اليوم' : "Today's Activities" }}</span>
                                <span class="font-bold text-teal-600">{{ todayActivityCount || 0 }}</span>
                            </div>
                            <div class="flex items-center justify-between text-xs">
                                <span class="text-gray-500">{{ isRtl ? 'متأخرة' : 'Overdue' }}</span>
                                <span class="font-bold" :class="stats.overdue_follow_ups > 0 ? 'text-red-600' : 'text-gray-800'">{{ stats.overdue_follow_ups }}</span>
                            </div>
                        </div>

                        <Link href="/secretary/crm/reports" class="mt-4 block text-center text-xs font-medium text-teal-600 hover:text-teal-700 hover:underline transition-colors">
                            {{ isRtl ? 'عرض التقارير الكاملة' : 'View Full Reports' }}
                        </Link>
                    </div>

                    <!-- ---- RECENT LEADS ---- -->
                    <div
                        class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden"
                        :class="mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'"
                        style="transition: all 0.6s cubic-bezier(0.16, 1, 0.3, 1); transition-delay: 0.4s"
                    >
                        <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-lg flex items-center justify-center" style="background: linear-gradient(135deg, #0d9488, #14b8a6);">
                                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z" />
                                    </svg>
                                </div>
                                <h3 class="text-sm font-bold text-gray-800 uppercase tracking-wider">{{ isRtl ? 'العملاء الأخيرين' : 'Recent Leads' }}</h3>
                            </div>
                            <Link href="/secretary/crm/leads" class="text-xs font-medium hover:underline" style="color: #0d9488;">
                                {{ isRtl ? 'عرض الكل' : 'View All' }}
                            </Link>
                        </div>

                        <div class="divide-y divide-gray-50" v-if="recentLeads?.length">
                            <Link
                                v-for="(lead, idx) in recentLeads" :key="lead.id"
                                :href="`/secretary/crm/leads/${lead.id}`"
                                class="block px-5 py-4 hover:bg-gray-50/70 transition-all duration-200 group"
                                :class="mounted ? 'opacity-100 translate-x-0' : (isRtl ? 'opacity-0 translate-x-4' : 'opacity-0 -translate-x-4')"
                                :style="`transition: all 0.5s cubic-bezier(0.16, 1, 0.3, 1); transition-delay: ${0.45 + idx * 0.06}s`"
                            >
                                <div class="flex items-center gap-3">
                                    <!-- Avatar -->
                                    <div class="w-10 h-10 rounded-xl flex items-center justify-center text-white text-sm font-bold flex-shrink-0" style="background: linear-gradient(135deg, #0d9488, #14b8a6);">
                                        {{ getInitials(lead.full_name) }}
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center gap-2">
                                            <p class="text-sm font-semibold text-gray-800 group-hover:text-teal-600 transition-colors truncate">{{ lead.full_name }}</p>
                                            <!-- Priority icon -->
                                            <span v-if="lead.priority === 'hot'" class="text-red-500 flex-shrink-0" :title="priorityConfig.hot.label">
                                                <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M12.395 2.553a1 1 0 00-1.45-.385c-.345.23-.614.558-.822.88-.214.33-.403.713-.57 1.116-.334.804-.614 1.768-.84 2.734a31.365 31.365 0 00-.613 3.58 2.64 2.64 0 01-.945-1.067c-.328-.68-.398-1.534-.398-2.654A1 1 0 005.05 6.05 6.981 6.981 0 003 11a7 7 0 1011.95-4.95c-.592-.591-.98-.985-1.348-1.467-.363-.476-.724-1.063-1.207-2.03zM12.12 15.12A3 3 0 017 13s.879.5 2.5.5c0-1 .5-4 1.25-4.5.5 1 .786 1.293 1.371 1.879A2.99 2.99 0 0113 13a2.99 2.99 0 01-.879 2.121z" clip-rule="evenodd" /></svg>
                                            </span>
                                            <span v-else-if="lead.priority === 'warm'" class="text-orange-400 flex-shrink-0" :title="priorityConfig.warm.label">
                                                <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M12.395 2.553a1 1 0 00-1.45-.385c-.345.23-.614.558-.822.88-.214.33-.403.713-.57 1.116-.334.804-.614 1.768-.84 2.734a31.365 31.365 0 00-.613 3.58 2.64 2.64 0 01-.945-1.067c-.328-.68-.398-1.534-.398-2.654A1 1 0 005.05 6.05 6.981 6.981 0 003 11a7 7 0 1011.95-4.95c-.592-.591-.98-.985-1.348-1.467-.363-.476-.724-1.063-1.207-2.03zM12.12 15.12A3 3 0 017 13s.879.5 2.5.5c0-1 .5-4 1.25-4.5.5 1 .786 1.293 1.371 1.879A2.99 2.99 0 0113 13a2.99 2.99 0 01-.879 2.121z" clip-rule="evenodd" /></svg>
                                            </span>
                                            <span v-else-if="lead.priority === 'cold'" class="text-blue-400 flex-shrink-0" :title="priorityConfig.cold.label">
                                                <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path d="M10 2a.75.75 0 01.75.75v1.563l1.08-.625a.75.75 0 01.75 1.3L11.5 5.5l1.08.625a.75.75 0 01-.75 1.3L10.75 6.8v1.45a.75.75 0 01-1.5 0V6.8l-1.08.625a.75.75 0 11-.75-1.3L8.5 5.5l-1.08-.513a.75.75 0 01.75-1.3l1.08.625V2.75A.75.75 0 0110 2zM5.5 10a.75.75 0 01.75.75v1.563l1.08-.625a.75.75 0 01.75 1.3l-1.08.512 1.08.625a.75.75 0 01-.75 1.3l-1.08-.625v1.45a.75.75 0 01-1.5 0V14.8l-1.08.625a.75.75 0 01-.75-1.3l1.08-.625-1.08-.513a.75.75 0 01.75-1.3l1.08.626V10.75A.75.75 0 015.5 10zm9 0a.75.75 0 01.75.75v1.563l1.08-.625a.75.75 0 01.75 1.3l-1.08.512 1.08.625a.75.75 0 01-.75 1.3l-1.08-.625v1.45a.75.75 0 01-1.5 0V14.8l-1.08.625a.75.75 0 01-.75-1.3l1.08-.625-1.08-.513a.75.75 0 01.75-1.3l1.08.626V10.75a.75.75 0 01.75-.75z" /></svg>
                                            </span>
                                        </div>
                                        <p class="text-xs text-gray-400 mt-0.5">{{ lead.phone || lead.email || '-' }}</p>
                                    </div>
                                    <!-- Quick hover actions -->
                                    <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity duration-200 flex-shrink-0">
                                        <a v-if="lead.phone" :href="`tel:${lead.phone}`" @click.stop
                                           class="w-7 h-7 rounded-lg bg-teal-50 text-teal-600 hover:bg-teal-100 inline-flex items-center justify-center transition-colors border border-teal-200/60"
                                           :title="isRtl ? 'اتصال' : 'Call'">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                                        </a>
                                        <a v-if="lead.phone" :href="`https://wa.me/${(lead.phone || '').replace(/[^0-9]/g, '')}`" target="_blank" @click.stop
                                           class="w-7 h-7 rounded-lg bg-green-50 text-green-600 hover:bg-green-100 inline-flex items-center justify-center transition-colors border border-green-200/60"
                                           :title="isRtl ? 'واتساب' : 'WhatsApp'">
                                            <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/></svg>
                                        </a>
                                    </div>
                                </div>

                                <!-- Bottom row: status + score + source -->
                                <div class="flex items-center gap-2 mt-2.5" :class="isRtl ? 'pr-13' : 'pl-13'" style="padding-inline-start: 52px;">
                                    <span :class="statusColors[lead.status]" class="px-2 py-0.5 text-[10px] font-bold rounded-full border whitespace-nowrap">
                                        {{ statusLabels[lead.status] || lead.status }}
                                    </span>

                                    <!-- Score bar -->
                                    <div v-if="lead.score != null" class="flex items-center gap-1.5 flex-1 max-w-[100px]">
                                        <div class="flex-1 h-1.5 rounded-full bg-gray-100 overflow-hidden">
                                            <div
                                                class="h-full rounded-full"
                                                :style="{
                                                    width: mounted ? `${Math.min(lead.score, 100)}%` : '0%',
                                                    background: lead.score >= 70 ? 'linear-gradient(90deg, #0d9488, #14b8a6)' : lead.score >= 40 ? 'linear-gradient(90deg, #f59e0b, #fbbf24)' : 'linear-gradient(90deg, #ef4444, #f87171)',
                                                    transition: 'width 1.2s cubic-bezier(0.16, 1, 0.3, 1) 0.8s'
                                                }"
                                            ></div>
                                        </div>
                                        <span class="text-[10px] font-bold text-gray-400 tabular-nums">{{ lead.score }}</span>
                                    </div>

                                    <!-- Source -->
                                    <span v-if="lead.source?.name_en" class="text-[10px] text-gray-400 truncate" :class="isRtl ? 'mr-auto' : 'ml-auto'">
                                        {{ lead.source.name_en }}
                                    </span>
                                </div>

                                <!-- Time -->
                                <div class="mt-1.5" style="padding-inline-start: 52px;">
                                    <span v-if="lead.next_follow_up_at" class="text-[10px] text-teal-500 font-medium">
                                        {{ isRtl ? 'المتابعة القادمة:' : 'Next:' }} {{ formatDate(lead.next_follow_up_at) }}
                                    </span>
                                    <span v-else class="text-[10px] text-gray-300">
                                        {{ isRtl ? 'أضيف' : 'Added' }} {{ timeAgo(lead.created_at) }}
                                    </span>
                                </div>
                            </Link>
                        </div>

                        <div v-else class="px-6 py-12 text-center">
                            <div class="w-16 h-16 rounded-2xl mx-auto mb-4 flex items-center justify-center bg-gray-50">
                                <svg class="w-8 h-8 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                            </div>
                            <p class="text-sm font-medium text-gray-500">{{ isRtl ? 'لم يتم تعيين عملاء لك بعد' : 'No leads assigned yet' }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ============ ACTIVITY TYPE DISTRIBUTION ============ -->
            <div v-if="activityTypeDistribution.length"
                class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 overflow-hidden"
                :class="mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'"
                style="transition: all 0.6s cubic-bezier(0.16, 1, 0.3, 1); transition-delay: 0.48s">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center gap-2.5">
                        <div class="w-8 h-8 rounded-lg flex items-center justify-center" style="background: linear-gradient(135deg, #8b5cf6, #a78bfa);">
                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"/><path stroke-linecap="round" stroke-linejoin="round" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z"/></svg>
                        </div>
                        <h3 class="text-sm font-bold text-gray-800">{{ isRtl ? 'توزيع الأنشطة' : 'Activity Breakdown' }}</h3>
                    </div>
                    <span class="text-xs text-gray-400">{{ (recentActivities || []).length }} {{ isRtl ? 'نشاط' : 'total' }}</span>
                </div>
                <div class="space-y-2.5">
                    <div v-for="(t, idx) in activityTypeDistribution" :key="t.key"
                         class="flex items-center gap-3 group">
                        <span class="text-[11px] font-medium text-gray-500 w-16 truncate">{{ isRtl ? t.ar : t.en }}</span>
                        <div class="flex-1 h-4 bg-gray-50 rounded-lg overflow-hidden">
                            <div class="h-full rounded-lg transition-all duration-1000 ease-out flex items-center justify-end px-1.5"
                                 :style="{
                                     width: mounted ? Math.max((t.count / activityDistMax) * 100, 6) + '%' : '0%',
                                     backgroundColor: t.color,
                                     transitionDelay: (0.55 + idx * 0.08) + 's'
                                 }">
                                <span class="text-[9px] font-bold text-white">{{ t.count }}</span>
                            </div>
                        </div>
                        <span class="text-[10px] font-semibold text-gray-400 w-8 text-end tabular-nums">{{ t.pct }}%</span>
                    </div>
                </div>
            </div>

            <!-- ============ FUNNEL + ACTIVITY FEED ============ -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

                <!-- Mini Conversion Funnel -->
                <div
                    class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 overflow-hidden"
                    :class="mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'"
                    style="transition: all 0.6s cubic-bezier(0.16, 1, 0.3, 1); transition-delay: 0.5s"
                >
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-lg flex items-center justify-center" style="background: linear-gradient(135deg, #0d9488, #14b8a6);">
                                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                                </svg>
                            </div>
                            <h3 class="text-sm font-bold text-gray-800 uppercase tracking-wider">{{ isRtl ? 'قمع التحويل' : 'Lead Funnel' }}</h3>
                        </div>
                        <span class="text-xs text-gray-400">{{ totalFunnelLeads }} {{ isRtl ? 'عميل نشط' : 'active' }}</span>
                    </div>

                    <div class="space-y-2.5">
                        <Link v-for="(stage, idx) in funnelStatuses" :key="stage.key"
                              :href="'/secretary/crm/leads?status=' + stage.key"
                              class="flex items-center gap-3 group cursor-pointer">
                            <span class="text-[11px] font-medium text-gray-500 w-16 truncate group-hover:text-teal-600 transition-colors">{{ isRtl ? stage.ar : stage.en }}</span>
                            <div class="flex-1 h-5 bg-gray-50 rounded-lg overflow-hidden relative group-hover:bg-gray-100 transition-colors">
                                <div class="h-full rounded-lg transition-all duration-1000 ease-out flex items-center justify-end px-2"
                                     :style="{
                                         width: mounted ? Math.max(((statusDistribution?.[stage.key] || 0) / funnelMax) * 100, 3) + '%' : '0%',
                                         background: stage.color,
                                         transitionDelay: (0.6 + idx * 0.1) + 's'
                                     }">
                                    <span v-if="(statusDistribution?.[stage.key] || 0) > 0" class="text-[10px] font-bold text-white">{{ statusDistribution?.[stage.key] || 0 }}</span>
                                </div>
                            </div>
                            <svg class="w-3 h-3 text-gray-300 group-hover:text-teal-500 transition-colors opacity-0 group-hover:opacity-100 flex-shrink-0" :class="isRtl ? 'rotate-180' : ''" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                        </Link>
                    </div>

                    <Link href="/secretary/crm/pipeline" class="mt-4 flex items-center justify-center gap-2 text-xs font-medium text-teal-600 hover:text-teal-700 transition-colors">
                        {{ isRtl ? 'عرض خط الأنابيب الكامل' : 'View Full Pipeline' }}
                        <svg class="w-3.5 h-3.5" :class="isRtl ? 'rotate-180' : ''" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                    </Link>
                </div>

                <!-- Recent Activity Feed -->
                <div
                    class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden"
                    :class="mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'"
                    style="transition: all 0.6s cubic-bezier(0.16, 1, 0.3, 1); transition-delay: 0.55s"
                >
                    <div class="px-5 py-4 border-b border-gray-100">
                        <div class="flex items-center justify-between mb-3">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-lg flex items-center justify-center" style="background: linear-gradient(135deg, #0d9488, #14b8a6);">
                                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <h3 class="text-sm font-bold text-gray-800 uppercase tracking-wider">{{ isRtl ? 'آخر النشاطات' : 'Recent Activity' }}</h3>
                            </div>
                            <span class="px-2.5 py-1 rounded-full text-xs font-bold bg-teal-50 text-teal-600">{{ todayActivityCount || 0 }} {{ isRtl ? 'اليوم' : 'today' }}</span>
                        </div>
                        <!-- Filter tabs -->
                        <div class="flex items-center gap-1 flex-wrap">
                            <button v-for="opt in activityFilterOptions" :key="opt.key"
                                @click="activityFilter = opt.key"
                                :class="['px-2.5 py-1 rounded-lg text-[11px] font-medium transition-all duration-200',
                                    activityFilter === opt.key
                                        ? 'bg-teal-500 text-white shadow-sm'
                                        : 'bg-gray-50 text-gray-500 hover:bg-gray-100']">
                                {{ isRtl ? opt.ar : opt.en }}
                            </button>
                        </div>
                    </div>

                    <div v-if="filteredActivities.length" class="max-h-96 overflow-y-auto">
                        <div v-for="(group, gIdx) in groupedFeedActivities" :key="group.label">
                            <!-- Time group header -->
                            <div class="px-5 py-1.5 bg-gray-50/80 border-y border-gray-100/80 sticky top-0 z-[1]">
                                <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">{{ group.label }}</span>
                            </div>
                            <div class="divide-y divide-gray-50">
                                <div v-for="(act, idx) in group.items" :key="act.id"
                                     class="px-5 py-3 flex items-start gap-3 hover:bg-gray-50/50 transition-colors"
                                     :class="mounted ? 'opacity-100' : 'opacity-0'"
                                     :style="`transition: opacity 0.5s ease ${0.6 + (gIdx * 3 + idx) * 0.04}s`">
                                    <div class="w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0 mt-0.5"
                                         :class="activityTypeConfig[act.type]?.color || 'bg-gray-100 text-gray-500'">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" :d="activityTypeConfig[act.type]?.icon || activityTypeConfig.system.icon" />
                                        </svg>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm text-gray-700 truncate">{{ act.subject }}</p>
                                        <div class="flex items-center gap-2 mt-0.5">
                                            <Link v-if="act.lead" :href="`/secretary/crm/leads/${act.lead_id}`" class="text-[11px] text-teal-600 hover:underline font-medium truncate">
                                                {{ act.lead?.full_name }}
                                            </Link>
                                            <span class="text-[11px] text-gray-400">{{ timeAgo(act.created_at) }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- Show More / Less toggle -->
                        <div v-if="hasMoreActivities" class="px-5 py-2.5 border-t border-gray-100 text-center">
                            <button @click="activityShowAll = !activityShowAll"
                                class="text-xs font-semibold text-teal-600 hover:text-teal-700 transition-colors inline-flex items-center gap-1">
                                <svg :class="['w-3.5 h-3.5 transition-transform duration-200', activityShowAll ? 'rotate-180' : '']" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                                {{ activityShowAll ? (isRtl ? '\u0639\u0631\u0636 \u0623\u0642\u0644' : 'Show Less') : (isRtl ? '\u0639\u0631\u0636 \u0627\u0644\u0643\u0644 (' + filteredActivities.length + ')' : 'Show All (' + filteredActivities.length + ')') }}
                            </button>
                        </div>
                    </div>

                    <div v-else class="px-5 py-10 text-center">
                        <div class="w-12 h-12 rounded-xl mx-auto mb-3 flex items-center justify-center bg-gray-50">
                            <svg class="w-6 h-6 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <p class="text-sm text-gray-500">{{ activityFilter !== 'all' ? (isRtl ? 'لا توجد نشاطات من هذا النوع' : 'No activities of this type') : (isRtl ? 'لا توجد نشاطات بعد' : 'No recent activities') }}</p>
                        <button v-if="activityFilter !== 'all'" @click="activityFilter = 'all'" class="mt-2 text-xs text-teal-600 hover:underline">{{ isRtl ? 'عرض الكل' : 'Show all' }}</button>
                        <div v-if="activityFilter === 'all' && (todayFollowUps?.length || overdueFollowUps?.length)" class="mt-4 flex flex-col items-center gap-2">
                            <p class="text-xs text-gray-400">{{ isRtl ? 'إجراءات مقترحة:' : 'Suggested actions:' }}</p>
                            <div class="flex items-center gap-2">
                                <Link v-if="overdueFollowUps?.length" href="/secretary/crm/calendar"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-red-50 text-red-600 rounded-lg text-xs font-medium hover:bg-red-100 transition-colors">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    {{ isRtl ? 'متابعات متأخرة' : 'Overdue follow-ups' }} ({{ overdueFollowUps.length }})
                                </Link>
                                <Link v-if="todayFollowUps?.length" href="/secretary/crm/calendar"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-teal-50 text-teal-600 rounded-lg text-xs font-medium hover:bg-teal-100 transition-colors">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                    {{ isRtl ? 'متابعات اليوم' : "Today's follow-ups" }} ({{ todayFollowUps.length }})
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <!-- Mobile Bottom Action Bar -->
    <Teleport to="body">
        <div class="fixed bottom-0 left-0 right-0 z-40 md:hidden">
            <div class="bg-white/95 backdrop-blur-lg border-t border-gray-200 shadow-[0_-4px_20px_rgba(0,0,0,0.08)] px-2" style="padding-bottom: env(safe-area-inset-bottom, 0.5rem);">
                <div class="flex items-center justify-around py-2">
                    <div class="flex flex-col items-center gap-0.5 px-3 py-1.5 rounded-xl text-teal-600 bg-teal-50">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3v11.25A2.25 2.25 0 006 16.5h2.25M3.75 3h-1.5m1.5 0h16.5m0 0h1.5m-1.5 0v11.25A2.25 2.25 0 0118 16.5h-2.25m-7.5 0h7.5m-7.5 0l-1 3m8.5-3l1 3m0 0l.5 1.5m-.5-1.5h-9.5m0 0l-.5 1.5M9 11.25v1.5M12 9v3.75m3-6v6"/></svg>
                        <span class="text-[10px] font-bold">{{ isRtl ? 'لوحة' : 'Dashboard' }}</span>
                    </div>
                    <Link href="/secretary/crm/pipeline" class="flex flex-col items-center gap-0.5 px-3 py-1.5 rounded-xl text-gray-500 hover:text-teal-600 hover:bg-teal-50 transition-all duration-200">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2"/></svg>
                        <span class="text-[10px] font-semibold">{{ isRtl ? 'أنابيب' : 'Pipeline' }}</span>
                    </Link>
                    <Link href="/secretary/crm/leads/create" class="flex flex-col items-center gap-0.5 -mt-5">
                        <div class="w-12 h-12 rounded-full bg-gradient-to-br from-teal-500 to-emerald-500 flex items-center justify-center shadow-lg shadow-teal-500/30 ring-4 ring-white">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                        </div>
                        <span class="text-[10px] font-bold text-teal-600">{{ isRtl ? 'جديد' : 'New' }}</span>
                    </Link>
                    <Link href="/secretary/crm/calendar" class="flex flex-col items-center gap-0.5 px-3 py-1.5 rounded-xl text-gray-500 hover:text-teal-600 hover:bg-teal-50 transition-all duration-200">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5"/></svg>
                        <span class="text-[10px] font-semibold">{{ isRtl ? 'التقويم' : 'Calendar' }}</span>
                    </Link>
                    <Link href="/secretary/crm/leads" class="flex flex-col items-center gap-0.5 px-3 py-1.5 rounded-xl text-gray-500 hover:text-teal-600 hover:bg-teal-50 transition-all duration-200">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477m.94-3.197a5.971 5.971 0 00-.94 3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z"/></svg>
                        <span class="text-[10px] font-semibold">{{ isRtl ? 'العملاء' : 'Leads' }}</span>
                    </Link>
                </div>
            </div>
        </div>
    </Teleport>

        <!-- Quick Add Lead Modal -->
        <Teleport to="body">
            <Transition enter-active-class="transition-all duration-300 ease-out" enter-from-class="opacity-0" enter-to-class="opacity-100" leave-active-class="transition-all duration-200 ease-in" leave-from-class="opacity-100" leave-to-class="opacity-0">
                <div v-if="showQuickAdd" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4" @click.self="showQuickAdd = false">
                    <Transition enter-active-class="transition-all duration-300 ease-out" enter-from-class="opacity-0 scale-95 translate-y-4" enter-to-class="opacity-100 scale-100 translate-y-0" leave-active-class="transition-all duration-200 ease-in" leave-from-class="opacity-100 scale-100" leave-to-class="opacity-0 scale-95">
                        <div v-if="showQuickAdd" class="bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden" :dir="isRtl ? 'rtl' : 'ltr'">
                            <div class="h-1 bg-gradient-to-r from-teal-500 via-teal-400 to-teal-500"></div>
                            <div class="p-6">
                                <div class="flex items-center justify-between mb-5">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-xl bg-teal-50 flex items-center justify-center">
                                            <svg class="w-5 h-5 text-teal-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
                                        </div>
                                        <div>
                                            <h3 class="text-lg font-bold text-gray-900">{{ isRtl ? 'إضافة سريعة' : 'Quick Add Lead' }}</h3>
                                            <p class="text-xs text-gray-400">{{ isRtl ? 'أدخل البيانات الأساسية' : 'Enter basic info' }}</p>
                                        </div>
                                    </div>
                                    <button @click="showQuickAdd = false" class="text-gray-400 hover:text-gray-600 transition">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                                    </button>
                                </div>
                                <form @submit.prevent="submitQuickAdd" class="space-y-4">
                                    <div>
                                        <label class="block text-xs font-semibold text-gray-600 mb-1.5">{{ isRtl ? 'الاسم الكامل' : 'Full Name' }} *</label>
                                        <input v-model="quickAddForm.full_name" type="text" required
                                            class="w-full rounded-xl border-gray-200 text-sm focus:ring-teal-500 focus:border-teal-500 transition"
                                            :placeholder="isRtl ? 'أدخل اسم العميل' : 'Enter lead name'" />
                                        <p v-if="quickAddForm.errors.full_name" class="text-xs text-red-500 mt-1">{{ quickAddForm.errors.full_name }}</p>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-semibold text-gray-600 mb-1.5">{{ isRtl ? 'رقم الهاتف' : 'Phone' }} *</label>
                                        <input v-model="quickAddForm.phone" type="tel" required dir="ltr"
                                            class="w-full rounded-xl border-gray-200 text-sm focus:ring-teal-500 focus:border-teal-500 transition"
                                            placeholder="05xxxxxxxx" />
                                        <p v-if="quickAddForm.errors.phone" class="text-xs text-red-500 mt-1">{{ quickAddForm.errors.phone }}</p>
                                    </div>
                                    <div class="grid grid-cols-2 gap-3">
                                        <div>
                                            <label class="block text-xs font-semibold text-gray-600 mb-1.5">{{ isRtl ? 'المصدر' : 'Source' }}</label>
                                            <select v-model="quickAddForm.lead_source_id"
                                                class="w-full rounded-xl border-gray-200 text-sm focus:ring-teal-500 focus:border-teal-500 transition">
                                                <option value="">{{ isRtl ? 'اختر المصدر' : 'Select source' }}</option>
                                                <option v-for="src in leadSources" :key="src.id" :value="src.id">{{ isRtl ? src.name_ar : src.name_en }}</option>
                                            </select>
                                        </div>
                                        <div>
                                            <label class="block text-xs font-semibold text-gray-600 mb-1.5">{{ isRtl ? 'القسم' : 'Module' }}</label>
                                            <select v-model="quickAddForm.module"
                                                class="w-full rounded-xl border-gray-200 text-sm focus:ring-teal-500 focus:border-teal-500 transition">
                                                <option value="derma">Derma</option>
                                                <option value="dental">Dental</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-semibold text-gray-600 mb-1.5">{{ isRtl ? 'الأولوية' : 'Priority' }}</label>
                                        <div class="flex gap-2">
                                            <button v-for="p in [{v:1,l:'Hot',c:'border-red-300 bg-red-50 text-red-700'},{v:2,l:'Warm',c:'border-amber-300 bg-amber-50 text-amber-700'},{v:3,l:'Cold',c:'border-blue-300 bg-blue-50 text-blue-700'}]"
                                                :key="p.v" type="button" @click="quickAddForm.priority = p.v"
                                                class="flex-1 py-2 text-xs font-semibold rounded-lg border transition-all"
                                                :class="quickAddForm.priority === p.v ? p.c + ' ring-2 ring-offset-1 ring-teal-500/40' : 'border-gray-200 text-gray-500 hover:bg-gray-50'">
                                                {{ p.l }}
                                            </button>
                                        </div>
                                    </div>
                                    <div class="flex items-center justify-between pt-2">
                                        <Link href="/secretary/crm/leads/create" class="text-xs text-teal-600 hover:underline font-medium">
                                            {{ isRtl ? 'النموذج الكامل' : 'Full form' }}
                                            <svg class="w-3 h-3 inline-block" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" :d="isRtl ? 'M15 19l-7-7 7-7' : 'M9 5l7 7-7 7'"/></svg>
                                        </Link>
                                        <button type="submit" :disabled="quickAddForm.processing"
                                            class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-white text-sm font-semibold transition-all duration-200 shadow-md hover:shadow-lg hover:-translate-y-0.5 disabled:opacity-50"
                                            style="background: linear-gradient(135deg, #0d9488, #14b8a6);">
                                            <svg v-if="quickAddForm.processing" class="animate-spin w-4 h-4" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                                            {{ quickAddForm.processing ? (isRtl ? 'جارٍ الحفظ...' : 'Saving...') : (isRtl ? 'حفظ' : 'Save Lead') }}
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </Transition>
                </div>
            </Transition>
        </Teleport>

    </SecretaryLayout>
</template>
