<script setup>
import { computed } from 'vue';
import { usePage, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import BoolIcon from '@/Components/Ui/BoolIcon.vue';

defineOptions({ layout: AdminLayout });

const props = defineProps({
    system: Object,
    telemedicine: Object,
    scheduler: Object,
    notifications: Object,
    log_tail: String,
});

const page = usePage();
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

function refresh() {
    router.reload({ preserveScroll: true });
}

const overallOk = computed(() =>
    props.system?.db_connected &&
    props.system?.storage_writable &&
    props.telemedicine?.is_ready &&
    props.scheduler?.is_healthy
);

const blockerLabels = {
    module_disabled:        { ar: 'الوحدة معطّلة',                    en: 'Module disabled' },
    no_payment_gateway:     { ar: 'لا توجد بوابة دفع مفعّلة',           en: 'No payment gateway' },
    no_online_doctors:      { ar: 'لا يوجد أطباء مفعّل لديهم الأونلاين', en: 'No online doctors' },
    no_bookable_schedules:  { ar: 'لا يوجد جدول مواعيد متاح',          en: 'No bookable schedules' },
    agora_missing:          { ar: 'إعدادات Agora غير مكتملة',           en: 'Agora not configured' },
};
</script>

<template>
    <div class="max-w-6xl mx-auto p-4 md:p-6 space-y-5">
        <!-- Header -->
        <div class="flex items-center justify-between flex-wrap gap-3">
            <div>
                <h1 class="text-2xl font-extrabold text-[#1B365D]">
                    {{ isRtl ? 'التشخيص الفني' : 'System Diagnostics' }}
                </h1>
                <p class="text-sm text-slate-500 mt-1">
                    {{ isRtl ? 'لمحة سريعة عن صحة النظام وحالة الإعداد' : 'Quick snapshot of system health and setup state' }}
                </p>
            </div>
            <div class="flex items-center gap-2">
                <a href="/admin/diagnostics/export"
                   class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-semibold text-slate-700 bg-white border border-slate-200 hover:bg-slate-50 transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M4 16v1a2 2 0 002 2h12a2 2 0 002-2v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                    </svg>
                    {{ isRtl ? 'تصدير JSON' : 'Export JSON' }}
                </a>
                <button @click="refresh"
                    class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-semibold text-white bg-[#1B365D] hover:bg-[#0F2444] transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                    {{ isRtl ? 'تحديث' : 'Refresh' }}
                </button>
            </div>
        </div>

        <!-- Overall Verdict -->
        <div class="rounded-2xl p-5 border-2"
             :class="overallOk
                ? 'bg-gradient-to-br from-emerald-50 to-emerald-100/50 border-emerald-300'
                : 'bg-gradient-to-br from-amber-50 to-amber-100/50 border-amber-300'">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl flex items-center justify-center"
                     :class="overallOk ? 'bg-emerald-500' : 'bg-amber-500'">
                    <svg v-if="overallOk" class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                    </svg>
                    <svg v-else class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01" />
                    </svg>
                </div>
                <h2 class="text-lg font-bold" :class="overallOk ? 'text-emerald-900' : 'text-amber-900'">
                    {{ overallOk
                        ? (isRtl ? 'كل الأنظمة تعمل بكفاءة' : 'All systems operational')
                        : (isRtl ? 'يوجد تحذيرات — راجع التفاصيل أدناه' : 'Warnings detected — review details below') }}
                </h2>
            </div>
        </div>

        <!-- Grid of cards -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
            <!-- System -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
                <div class="flex items-center gap-2 mb-4">
                    <svg class="w-5 h-5 text-[#1B365D]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"/>
                    </svg>
                    <h3 class="font-bold text-slate-800">{{ isRtl ? 'النظام' : 'System' }}</h3>
                </div>
                <dl class="space-y-2 text-sm">
                    <div class="flex items-center justify-between">
                        <dt class="text-slate-500">{{ isRtl ? 'البيئة' : 'Environment' }}</dt>
                        <dd class="font-mono text-slate-800">{{ system.app_env }}</dd>
                    </div>
                    <div class="flex items-center justify-between">
                        <dt class="text-slate-500">Debug</dt>
                        <dd :class="system.app_debug ? 'text-amber-600 font-bold' : 'text-slate-600'" class="inline-flex items-center gap-1">
                            {{ system.app_debug ? 'ON' : 'OFF' }}
                            <svg v-if="system.app_debug" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 9v4m0 4h.01M10.3 3.86l-8.06 14A1 1 0 003.1 19.4h17.8a1 1 0 00.86-1.5l-8.06-14a1 1 0 00-1.74 0z" /></svg>
                        </dd>
                    </div>
                    <div class="flex items-center justify-between">
                        <dt class="text-slate-500">PHP</dt>
                        <dd class="font-mono text-slate-800">{{ system.php_version }}</dd>
                    </div>
                    <div class="flex items-center justify-between">
                        <dt class="text-slate-500">Laravel</dt>
                        <dd class="font-mono text-slate-800">{{ system.laravel_version }}</dd>
                    </div>
                    <div class="flex items-center justify-between">
                        <dt class="text-slate-500">{{ isRtl ? 'قاعدة البيانات' : 'Database' }}</dt>
                        <dd :class="system.db_connected ? 'text-emerald-600 font-semibold' : 'text-red-600 font-semibold'" class="inline-flex items-center gap-1">
                            <BoolIcon :ok="!!system.db_connected" :size="14" />
                            {{ system.db_connected ? (isRtl ? 'متصلة' : 'connected') : (isRtl ? 'مقطوعة' : 'unreachable') }}
                        </dd>
                    </div>
                    <div class="flex items-center justify-between">
                        <dt class="text-slate-500">{{ isRtl ? 'صلاحية الكتابة' : 'Storage writable' }}</dt>
                        <dd :class="system.storage_writable ? 'text-emerald-600 font-semibold' : 'text-red-600 font-semibold'">
                            <BoolIcon :ok="!!system.storage_writable" :size="14" />
                        </dd>
                    </div>
                </dl>
            </div>

            <!-- Telemedicine -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
                <div class="flex items-center gap-2 mb-4">
                    <svg class="w-5 h-5 text-[#C4A265]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                    </svg>
                    <h3 class="font-bold text-slate-800">{{ isRtl ? 'الاستشارات الأونلاين' : 'Telemedicine' }}</h3>
                </div>
                <dl class="space-y-2 text-sm">
                    <div class="flex items-center justify-between">
                        <dt class="text-slate-500">{{ isRtl ? 'الوحدة' : 'Module' }}</dt>
                        <dd :class="telemedicine.module_enabled ? 'text-emerald-600 font-semibold' : 'text-slate-500'">
                            {{ telemedicine.module_enabled ? (isRtl ? 'مفعّلة' : 'enabled') : (isRtl ? 'معطّلة' : 'disabled') }}
                        </dd>
                    </div>
                    <div class="flex items-center justify-between">
                        <dt class="text-slate-500">{{ isRtl ? 'أطباء أونلاين' : 'Online doctors' }}</dt>
                        <dd class="font-mono font-bold text-slate-800">{{ telemedicine.doctors_online }}</dd>
                    </div>
                    <div class="flex items-center justify-between">
                        <dt class="text-slate-500">{{ isRtl ? 'جداول مواعيد' : 'Bookable schedules' }}</dt>
                        <dd class="font-mono font-bold text-slate-800">{{ telemedicine.schedules_bookable }}</dd>
                    </div>
                    <div class="flex items-center justify-between">
                        <dt class="text-slate-500">{{ isRtl ? 'بوابة الدفع' : 'Payment gateway' }}</dt>
                        <dd :class="telemedicine.payment_gateway ? 'text-emerald-600 font-semibold' : 'text-red-600 font-semibold'">
                            {{ telemedicine.payment_gateway || (isRtl ? 'غير مُعد' : 'not configured') }}
                        </dd>
                    </div>
                </dl>
                <div v-if="telemedicine.blockers?.length" class="mt-4 pt-4 border-t border-amber-100">
                    <p class="text-xs font-semibold text-amber-700 uppercase tracking-wider mb-2">
                        {{ isRtl ? 'مطلوب الإصلاح' : 'Needs fixing' }}
                    </p>
                    <ul class="space-y-1">
                        <li v-for="b in telemedicine.blockers" :key="b" class="flex items-center gap-2 text-sm text-amber-800">
                            <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                            {{ isRtl ? (blockerLabels[b]?.ar || b) : (blockerLabels[b]?.en || b) }}
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Scheduler -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
                <div class="flex items-center gap-2 mb-4">
                    <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <h3 class="font-bold text-slate-800">{{ isRtl ? 'الجدول الزمني (Cron)' : 'Scheduler (Cron)' }}</h3>
                </div>
                <dl class="space-y-2 text-sm">
                    <div class="flex items-center justify-between">
                        <dt class="text-slate-500">{{ isRtl ? 'آخر تشغيل' : 'Last run' }}</dt>
                        <dd class="font-mono text-slate-800">{{ scheduler.last_run_at || '—' }}</dd>
                    </div>
                    <div class="flex items-center justify-between">
                        <dt class="text-slate-500">{{ isRtl ? 'منذ' : 'Ago' }}</dt>
                        <dd class="text-slate-800">
                            {{ scheduler.minutes_ago !== null
                                ? (scheduler.minutes_ago + ' ' + (isRtl ? 'دقيقة' : 'min'))
                                : '—' }}
                        </dd>
                    </div>
                    <div class="flex items-center justify-between">
                        <dt class="text-slate-500">{{ isRtl ? 'الحالة' : 'Status' }}</dt>
                        <dd :class="scheduler.is_healthy ? 'text-emerald-600 font-semibold' : 'text-red-600 font-semibold'" class="inline-flex items-center gap-1">
                            <BoolIcon :ok="!!scheduler.is_healthy" :size="14" />
                            {{ scheduler.is_healthy ? (isRtl ? 'يعمل' : 'running') : (isRtl ? 'غير نشط — أضف cron' : 'inactive — add cron job') }}
                        </dd>
                    </div>
                </dl>
                <p v-if="!scheduler.is_healthy" class="text-xs text-amber-700 mt-3 pt-3 border-t border-amber-100">
                    {{ isRtl
                        ? 'أضف هذا الـ cron من cPanel (كل دقيقة):'
                        : 'Add this cron via cPanel (every minute):' }}
                </p>
                <code v-if="!scheduler.is_healthy" class="mt-2 block text-xs p-2 bg-slate-900 text-emerald-300 rounded font-mono overflow-x-auto whitespace-nowrap">
                    * * * * * cd /home/doctoratonet/public_html && /usr/bin/php artisan schedule:run &gt;&gt; /dev/null 2&gt;&amp;1
                </code>
            </div>

            <!-- Notifications pipeline -->
            <div v-if="notifications" class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
                <div class="flex items-center gap-2 mb-4">
                    <svg class="w-5 h-5" :class="notifications.ok ? 'text-emerald-600' : 'text-red-600'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.4-1.4A2 2 0 0118 14.2V11a6 6 0 10-12 0v3.2c0 .5-.2 1-.6 1.4L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                    </svg>
                    <h3 class="font-bold text-slate-800">{{ isRtl ? 'خط الإشعارات' : 'Notifications pipeline' }}</h3>
                </div>
                <dl class="space-y-2 text-sm">
                    <div class="flex items-center justify-between">
                        <dt class="text-slate-500">{{ isRtl ? 'الحالة' : 'Status' }}</dt>
                        <dd :class="notifications.ok ? 'text-emerald-600 font-semibold' : 'text-red-600 font-semibold'" class="inline-flex items-center gap-1">
                            <BoolIcon :ok="!!notifications.ok" :size="14" />
                            {{ notifications.ok ? (isRtl ? 'سليم' : 'healthy') : (isRtl ? 'يحتاج انتباه' : 'needs attention') }}
                        </dd>
                    </div>
                    <div class="flex items-center justify-between">
                        <dt class="text-slate-500">{{ isRtl ? 'متراكم في الطابور (>ساعة)' : 'Queue backlog (>1h)' }}</dt>
                        <dd :class="notifications.queue_backlog > 0 ? 'text-red-600 font-semibold' : 'text-slate-800'">{{ notifications.queue_backlog }}</dd>
                    </div>
                    <div class="flex items-center justify-between">
                        <dt class="text-slate-500">{{ isRtl ? 'أُرسلت / فشلت (24س)' : 'Sent / failed (24h)' }}</dt>
                        <dd class="text-slate-800">{{ notifications.sent_24h }} / <span :class="notifications.failed_24h > 0 ? 'text-red-600' : ''">{{ notifications.failed_24h }}</span> ({{ notifications.failure_rate }}%)</dd>
                    </div>
                    <div v-if="notifications.channels_unconfigured && notifications.channels_unconfigured.length" class="flex items-center justify-between">
                        <dt class="text-slate-500">{{ isRtl ? 'قنوات مفعّلة بلا إعداد' : 'Enabled but unconfigured' }}</dt>
                        <dd class="text-amber-600 font-semibold">{{ notifications.channels_unconfigured.join(', ') }}</dd>
                    </div>
                </dl>
                <p v-if="notifications.queue_backlog > 0" class="text-xs text-amber-700 mt-3 pt-3 border-t border-amber-100">
                    {{ isRtl ? 'تراكم رسائل = عامل الطابور متوقف. شغّل: php artisan queue:work (أو عبر supervisor/cron).' : 'Backlog = the queue worker is down. Run: php artisan queue:work (or via supervisor/cron).' }}
                </p>
            </div>

            <!-- Log tail -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
                <div class="flex items-center gap-2 mb-4">
                    <svg class="w-5 h-5 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                    </svg>
                    <h3 class="font-bold text-slate-800">{{ isRtl ? 'آخر 100 سطر من الـ Log' : 'Last 100 log lines' }}</h3>
                </div>
                <pre v-if="log_tail"
                     class="text-[11px] leading-relaxed bg-slate-900 text-slate-200 rounded-lg p-3 overflow-auto max-h-[320px] font-mono"
                     dir="ltr">{{ log_tail }}</pre>
                <p v-else class="text-sm text-slate-400 italic">
                    {{ isRtl ? 'الـ log فارغ.' : 'Log file is empty.' }}
                </p>
            </div>
        </div>
    </div>
</template>
