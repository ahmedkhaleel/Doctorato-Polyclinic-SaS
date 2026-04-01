<script setup>
import { ref, computed } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const page = usePage();
const isRtl = computed(() => page.props.dir === 'rtl' || !page.props.dir);

const props = defineProps({
    backups: {
        type: Array,
        default: () => [],
    },
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
    if (!confirm(isRtl.value ? 'هل أنت متأكد من حذف هذه النسخة الاحتياطية؟' : 'Are you sure you want to delete this backup?')) return;

    loading.value.delete = path;
    router.delete(route('admin.backups.destroy'), {
        data: { path },
        preserveScroll: true,
        onFinish: () => { loading.value.delete = null; },
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
                        class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white shadow hover:bg-blue-700 disabled:opacity-50 transition"
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
        <div v-if="$page.props.flash?.success" class="mb-4 rounded-lg bg-green-50 border border-green-200 p-4 text-green-800">
            {{ $page.props.flash.success }}
        </div>
        <div v-if="$page.props.flash?.error" class="mb-4 rounded-lg bg-red-50 border border-red-200 p-4 text-red-800">
            {{ $page.props.flash.error }}
        </div>

        <!-- Info Card -->
        <div class="mb-6 rounded-xl bg-white p-6 shadow-sm border">
            <div class="flex items-start gap-3">
                <div class="flex-shrink-0 rounded-lg bg-blue-100 p-2">
                    <svg class="h-6 w-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
            <div class="flex items-center justify-between border-b px-6 py-4">
                <h3 class="font-semibold text-gray-800">{{ isRtl ? 'النسخ الاحتياطية المتوفرة' : 'Available Backups' }}</h3>
                <button
                    @click="runCleanup"
                    :disabled="loading.cleanup"
                    class="inline-flex items-center gap-1 rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50 disabled:opacity-50 transition"
                >
                    {{ loading.cleanup ? (isRtl ? 'جاري التنظيف...' : 'Cleaning up...') : (isRtl ? 'تنظيف النسخ القديمة' : 'Clean Old Backups') }}
                </button>
            </div>

            <div v-if="props.backups.length === 0" class="px-6 py-12 text-center text-gray-500">
                <svg class="mx-auto h-12 w-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" />
                </svg>
                <p class="mt-2">{{ isRtl ? 'لا توجد نسخ احتياطية بعد' : 'No backups yet' }}</p>
                <p class="text-sm">{{ isRtl ? 'اضغط على "نسخ احتياطي كامل" لإنشاء أول نسخة' : 'Click "Full Backup" to create the first backup' }}</p>
            </div>

            <table v-else class="w-full text-sm">
                <thead class="bg-gray-50 text-gray-600">
                    <tr>
                        <th class="px-6 py-3 font-medium" :class="isRtl ? 'text-right' : 'text-left'">{{ isRtl ? 'اسم الملف' : 'Filename' }}</th>
                        <th class="px-6 py-3 font-medium" :class="isRtl ? 'text-right' : 'text-left'">{{ isRtl ? 'الحجم' : 'Size' }}</th>
                        <th class="px-6 py-3 font-medium" :class="isRtl ? 'text-right' : 'text-left'">{{ isRtl ? 'التاريخ' : 'Date' }}</th>
                        <th class="px-6 py-3 text-center font-medium">{{ isRtl ? 'إجراءات' : 'Actions' }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    <tr v-for="backup in props.backups" :key="backup.path" class="hover:bg-gray-50 transition">
                        <td class="px-6 py-3">
                            <div class="flex items-center gap-2">
                                <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" />
                                </svg>
                                <span class="font-mono text-xs">{{ backup.filename }}</span>
                            </div>
                        </td>
                        <td class="px-6 py-3 text-gray-600">{{ backup.size }}</td>
                        <td class="px-6 py-3 text-gray-600" dir="ltr">{{ backup.date }}</td>
                        <td class="px-6 py-3 text-center">
                            <div class="flex items-center justify-center gap-2">
                                <button
                                    @click="downloadBackup(backup.path)"
                                    :disabled="loading.download === backup.path"
                                    class="rounded-lg bg-blue-50 px-3 py-1 text-xs font-medium text-blue-700 hover:bg-blue-100 disabled:opacity-50 transition"
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
