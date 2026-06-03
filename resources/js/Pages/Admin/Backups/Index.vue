<script setup>
import { ref, computed } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { useConfirm } from '@/Composables/useConfirm.js';

const { confirm } = useConfirm();
const page = usePage();
const isRtl = computed(() => page.props.dir === 'rtl' || !page.props.dir);

const props = defineProps({
    backups: {
        type: Array,
        default: () => [],
    },
    summary: {
        type: Object,
        default: () => ({ count: 0, last_backup_at: null, last_backup_age_hours: null, is_stale: true, max_age_hours: 24, total_size: '0 B' }),
    },
});

// Human-readable age of the most recent backup.
const lastBackupAge = computed(() => {
    const h = props.summary?.last_backup_age_hours;
    if (h === null || h === undefined) return null;
    if (h < 1) return isRtl.value ? 'منذ أقل من ساعة' : 'less than an hour ago';
    if (h < 24) return isRtl.value ? `منذ ${h} ساعة` : `${h}h ago`;
    const d = Math.floor(h / 24);
    return isRtl.value ? `منذ ${d} يوم` : `${d}d ago`;
});

const loading = ref({
    full: false,
    db: false,
    cleanup: false,
    download: null,
    delete: null,
});

function runFullBackup() {
    loading.value.full = true;
    router.post(route('admin.backups.runFull'), {}, {
        preserveScroll: true,
        onFinish: () => { loading.value.full = false; },
    });
}

function runDatabaseBackup() {
    loading.value.db = true;
    router.post(route('admin.backups.runDatabase'), {}, {
        preserveScroll: true,
        onFinish: () => { loading.value.db = false; },
    });
}

function downloadBackup(path) {
    loading.value.download = path;
    // Use form submission for file download
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = route('admin.backups.download');

    const csrfInput = document.createElement('input');
    csrfInput.type = 'hidden';
    csrfInput.name = '_token';
    csrfInput.value = page.props.csrf_token || document.querySelector('meta[name="csrf-token"]')?.content;
    form.appendChild(csrfInput);

    const pathInput = document.createElement('input');
    pathInput.type = 'hidden';
    pathInput.name = 'path';
    pathInput.value = path;
    form.appendChild(pathInput);

    document.body.appendChild(form);
    form.submit();
    document.body.removeChild(form);

    setTimeout(() => { loading.value.download = null; }, 2000);
}

function deleteBackup(path) {
    confirm(isRtl.value ? 'هل أنت متأكد من حذف هذه النسخة الاحتياطية؟' : 'Are you sure you want to delete this backup?', () => {
        loading.value.delete = path;
        router.post(route('admin.backups.destroy'), { path }, {
            preserveScroll: true,
            onFinish: () => { loading.value.delete = null; },
        });
    });
}

function runCleanup() {
    loading.value.cleanup = true;
    router.post(route('admin.backups.cleanup'), {}, {
        preserveScroll: true,
        onFinish: () => { loading.value.cleanup = false; },
    });
}
</script>

<template>
    <AdminLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold text-gray-800">
                    {{ isRtl ? 'النسخ الاحتياطي' : 'Backups' }}
                </h2>
                <div class="flex gap-2">
                    <button
                        @click="runDatabaseBackup"
                        :disabled="loading.db"
                        class="inline-flex items-center gap-2 rounded-lg bg-[#1B365D] px-4 py-2 text-sm font-medium text-white shadow hover:bg-[#1B365D] disabled:opacity-50 transition"
                    >
                        <svg v-if="loading.db" class="h-4 w-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                        </svg>
                        <svg v-else class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4" />
                        </svg>
                        {{ loading.db ? (isRtl ? 'جاري النسخ...' : 'Backing up...') : (isRtl ? 'نسخ قاعدة البيانات' : 'Database Backup') }}
                    </button>
                    <button
                        @click="runFullBackup"
                        :disabled="loading.full"
                        class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white shadow hover:bg-emerald-700 disabled:opacity-50 transition"
                    >
                        <svg v-if="loading.full" class="h-4 w-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                        </svg>
                        <svg v-else class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                        </svg>
                        {{ loading.full ? (isRtl ? 'جاري النسخ...' : 'Backing up...') : (isRtl ? 'نسخ احتياطي كامل' : 'Full Backup') }}
                    </button>
                </div>
            </div>
        </template>

        <!-- Flash Messages -->
        <div v-if="$page.props.flash?.success" class="mb-4 rounded-lg bg-emerald-50 border border-emerald-200 p-4 text-emerald-800">
            {{ $page.props.flash.success }}
        </div>
        <div v-if="$page.props.flash?.error" class="mb-4 rounded-lg bg-red-50 border border-red-200 p-4 text-red-800">
            {{ $page.props.flash.error }}
        </div>

        <!-- Backup Health Banner -->
        <div
            class="mb-6 rounded-xl p-4 md:p-5 shadow-sm border flex items-center gap-4"
            :class="summary.is_stale ? 'bg-red-50 border-red-200' : 'bg-emerald-50 border-emerald-200'"
        >
            <div
                class="flex-shrink-0 rounded-full p-2.5"
                :class="summary.is_stale ? 'bg-red-100' : 'bg-emerald-100'"
            >
                <svg v-if="summary.is_stale" class="h-6 w-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M5.07 19h13.86a2 2 0 001.71-3.03L13.71 4.97a2 2 0 00-3.42 0L3.36 15.97A2 2 0 005.07 19z" />
                </svg>
                <svg v-else class="h-6 w-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <div class="flex-1 min-w-0">
                <h3 class="font-semibold" :class="summary.is_stale ? 'text-red-800' : 'text-emerald-800'">
                    <template v-if="summary.count === 0">
                        {{ isRtl ? 'لا توجد نسخ احتياطية بعد' : 'No backups found yet' }}
                    </template>
                    <template v-else-if="summary.is_stale">
                        {{ isRtl ? 'تحذير: آخر نسخة احتياطية قديمة' : 'Warning: latest backup is stale' }}
                    </template>
                    <template v-else>
                        {{ isRtl ? 'النسخ الاحتياطي يعمل بشكل سليم' : 'Backups are healthy' }}
                    </template>
                </h3>
                <p class="text-sm mt-0.5" :class="summary.is_stale ? 'text-red-700' : 'text-emerald-700'">
                    <template v-if="summary.count === 0">
                        {{ isRtl ? 'لم يتم إنشاء أي نسخة بعد — يُتوقّع تشغيلها تلقائياً يومياً الساعة 2:00 صباحاً.' : 'No backup has run yet — the scheduled job runs daily at 2:00 AM.' }}
                    </template>
                    <template v-else>
                        {{ isRtl ? 'آخر نسخة' : 'Last backup' }}: {{ lastBackupAge }}
                        <span class="text-gray-400 mx-1">•</span>
                        <span dir="ltr">{{ summary.last_backup_at }}</span>
                        <span class="text-gray-400 mx-1">•</span>
                        {{ summary.count }} {{ isRtl ? 'نسخة' : 'backups' }}
                        <span class="text-gray-400 mx-1">•</span>
                        {{ summary.total_size }}
                    </template>
                </p>
                <p v-if="summary.is_stale && summary.count > 0" class="text-xs mt-1 text-red-600">
                    {{ isRtl ? 'لم تُنشأ نسخة خلال آخر 24 ساعة. تحقّق من جدول المهام (cron) أو شغّل نسخة يدوياً الآن.' : 'No backup in the last 24 hours. Check the scheduler (cron) or run one manually now.' }}
                </p>
            </div>
        </div>

        <!-- Info Card -->
        <div class="mb-6 rounded-xl bg-white p-4 md:p-6 shadow-sm border">
            <div class="flex items-start gap-3">
                <div class="flex-shrink-0 rounded-lg bg-slate-100 p-2">
                    <svg class="h-6 w-6 text-[#1B365D]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div>
                    <h3 class="font-medium text-gray-900">{{ isRtl ? 'جدول النسخ الاحتياطي التلقائي' : 'Automatic Backup Schedule' }}</h3>
                    <ul class="mt-1 text-sm text-gray-600 space-y-1">
                        <li>&#x2022; {{ isRtl ? 'نسخ كامل (قاعدة بيانات + ملفات): يومياً الساعة 2:00 صباحاً' : 'Full backup (database + files): Daily at 2:00 AM' }}</li>
                        <li>&#x2022; {{ isRtl ? 'نسخ قاعدة بيانات فقط: كل 6 ساعات' : 'Database only backup: Every 6 hours' }}</li>
                        <li>&#x2022; {{ isRtl ? 'تنظيف تلقائي للنسخ القديمة: يومياً الساعة 3:00 صباحاً' : 'Auto cleanup of old backups: Daily at 3:00 AM' }}</li>
                        <li>&#x2022; {{ isRtl ? 'الاحتفاظ: 7 أيام كاملة، شهر يومي، 3 أشهر أسبوعي، سنة شهري' : 'Retention: 7 days full, 1 month daily, 3 months weekly, 1 year monthly' }}</li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Backups Table -->
        <div class="rounded-xl bg-white shadow-sm border overflow-hidden">
            <div class="flex items-center justify-between border-b px-4 md:px-6 py-4">
                <h3 class="font-semibold text-gray-800">{{ isRtl ? 'النسخ الاحتياطية المتوفرة' : 'Available Backups' }}</h3>
                <button
                    @click="runCleanup"
                    :disabled="loading.cleanup"
                    class="inline-flex items-center gap-1 rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50 disabled:opacity-50 transition"
                >
                    {{ loading.cleanup ? (isRtl ? 'جاري التنظيف...' : 'Cleaning up...') : (isRtl ? 'تنظيف النسخ القديمة' : 'Clean Old Backups') }}
                </button>
            </div>

            <div v-if="props.backups.length === 0" class="px-4 md:px-6 py-12 text-center text-gray-500">
                <svg class="mx-auto h-12 w-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" />
                </svg>
                <p class="mt-2">{{ isRtl ? 'لا توجد نسخ احتياطية بعد' : 'No backups yet' }}</p>
                <p class="text-sm">{{ isRtl ? 'اضغط على "نسخ احتياطي كامل" لإنشاء أول نسخة' : 'Click "Full Backup" to create the first backup' }}</p>
            </div>

            <table v-else class="w-full text-sm">
                <thead class="bg-gray-50 text-gray-600">
                    <tr>
                        <th class="px-4 md:px-6 py-3 font-medium" :class="isRtl ? 'text-right' : 'text-left'">{{ isRtl ? 'اسم الملف' : 'Filename' }}</th>
                        <th class="px-4 md:px-6 py-3 font-medium" :class="isRtl ? 'text-right' : 'text-left'">{{ isRtl ? 'الحجم' : 'Size' }}</th>
                        <th class="px-4 md:px-6 py-3 font-medium" :class="isRtl ? 'text-right' : 'text-left'">{{ isRtl ? 'التاريخ' : 'Date' }}</th>
                        <th class="px-4 md:px-6 py-3 text-center font-medium">{{ isRtl ? 'إجراءات' : 'Actions' }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    <tr v-for="backup in props.backups" :key="backup.path" class="hover:bg-gray-50 transition">
                        <td class="px-4 md:px-6 py-3">
                            <div class="flex items-center gap-2">
                                <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" />
                                </svg>
                                <span class="font-mono text-xs">{{ backup.filename }}</span>
                            </div>
                        </td>
                        <td class="px-4 md:px-6 py-3 text-gray-600">{{ backup.size }}</td>
                        <td class="px-4 md:px-6 py-3 text-gray-600" dir="ltr">{{ backup.date }}</td>
                        <td class="px-4 md:px-6 py-3 text-center">
                            <div class="flex items-center justify-center gap-2">
                                <button
                                    @click="downloadBackup(backup.path)"
                                    :disabled="loading.download === backup.path"
                                    class="rounded-lg bg-slate-50 px-3 py-1 text-xs font-medium text-[#1B365D] hover:bg-slate-100 disabled:opacity-50 transition"
                                >
                                    {{ loading.download === backup.path ? '...' : (isRtl ? 'تحميل' : 'Download') }}
                                </button>
                                <button
                                    @click="deleteBackup(backup.path)"
                                    :disabled="loading.delete === backup.path"
                                    class="rounded-lg bg-red-50 px-3 py-1 text-xs font-medium text-red-700 hover:bg-red-100 disabled:opacity-50 transition"
                                >
                                    {{ loading.delete === backup.path ? '...' : (isRtl ? 'حذف' : 'Delete') }}
                                </button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </AdminLayout>
</template>
