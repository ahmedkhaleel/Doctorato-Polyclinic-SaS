<script setup>
import { ref, computed } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { useConfirm } from '@/Composables/useConfirm.js';

const { confirm } = useConfirm();
const page = usePage();
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const props = defineProps({
    trashedData: Object,
    counts: Object,
    totalCount: Number,
    filters: Object,
});

const activeTab = ref(props.filters?.type || 'all');
const search = ref(props.filters?.search || '');
let searchTimeout = null;

const tabs = [
    { key: 'all', label_ar: 'الكل', label_en: 'All', icon: 'M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16' },
    { key: 'patients', label_ar: 'المرضى', label_en: 'Patients', icon: 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z' },
    { key: 'doctors', label_ar: 'الأطباء', label_en: 'Doctors', icon: 'M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0zm6 2a9 9 0 11-18 0 9 9 0 0118 0z' },
    { key: 'bookings', label_ar: 'الحجوزات', label_en: 'Bookings', icon: 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z' },
    { key: 'visits', label_ar: 'الزيارات', label_en: 'Visits', icon: 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2' },
    { key: 'invoices', label_ar: 'الفواتير', label_en: 'Invoices', icon: 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z' },
    { key: 'payments', label_ar: 'المدفوعات', label_en: 'Payments', icon: 'M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z' },
    { key: 'prescriptions', label_ar: 'الوصفات', label_en: 'Prescriptions', icon: 'M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z' },
];

function switchTab(tab) {
    activeTab.value = tab;
    router.get('/admin/trash', { type: tab, search: search.value || undefined }, {
        preserveState: true,
        replace: true,
    });
}

function doSearch() {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        router.get('/admin/trash', {
            type: activeTab.value,
            search: search.value || undefined,
        }, { preserveState: true, replace: true });
    }, 400);
}

function restoreItem(type, id) {
    confirm({ message: isRtl.value ? 'هل أنت متأكد من استعادة هذا العنصر؟' : 'Are you sure you want to restore this item?', confirmColor: 'green' }, () => {
        router.post(`/admin/trash/${type}/${id}/restore`);
    });
}

function forceDeleteItem(type, id) {
    confirm(isRtl.value ? 'هل أنت متأكد؟ سيتم حذف هذا العنصر نهائياً ولا يمكن استعادته!' : 'Are you sure? This will permanently delete this item and cannot be undone!', () => {
        router.post(`/admin/trash/${type}/${id}/delete`);
    });
}

function restoreAll(type) {
    confirm({ message: isRtl.value ? 'هل تريد استعادة جميع العناصر المحذوفة من هذا النوع؟' : 'Restore all trashed items of this type?', confirmColor: 'green' }, () => {
        router.post(`/admin/trash/${type}/restore-all`);
    });
}

function emptyTrash(type) {
    confirm(isRtl.value ? 'تحذير! سيتم حذف جميع العناصر نهائياً. هل أنت متأكد؟' : 'WARNING! This will permanently delete all items. Are you sure?', () => {
        router.post(`/admin/trash/${type}/empty`);
    });
}

function tabLabel(tab) {
    return isRtl.value ? tab.label_ar : tab.label_en;
}

function getItemsForType(type) {
    if (type === 'all') {
        let all = [];
        for (const [key, items] of Object.entries(props.trashedData || {})) {
            if (Array.isArray(items)) {
                all.push(...items);
            }
        }
        return all.sort((a, b) => new Date(b.deleted_at) - new Date(a.deleted_at));
    }
    return props.trashedData?.[type] || [];
}

const currentItems = computed(() => getItemsForType(activeTab.value));
</script>

<template>
    <AdminLayout :title="isRtl ? 'سلة المحذوفات' : 'Recycle Bin'">
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-xl md:text-2xl font-bold text-gray-800 flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-red-50 flex items-center justify-center">
                            <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                        </div>
                        {{ isRtl ? 'سلة المحذوفات' : 'Recycle Bin' }}
                    </h1>
                    <p class="text-sm text-gray-500 mt-1">
                        <span class="font-semibold text-red-500">{{ totalCount }}</span>
                        {{ isRtl ? 'عنصر محذوف' : 'deleted items' }}
                    </p>
                </div>
            </div>

            <!-- Tabs -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-1.5 flex flex-wrap gap-1">
                <button
                    v-for="tab in tabs"
                    :key="tab.key"
                    @click="switchTab(tab.key)"
                    :class="[
                        'flex items-center gap-2 px-3.5 py-2 rounded-lg text-sm font-medium transition-all duration-200',
                        activeTab === tab.key
                            ? 'bg-[#C4A265] text-white shadow-sm'
                            : 'text-gray-600 hover:bg-gray-50'
                    ]"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="tab.icon"/>
                    </svg>
                    {{ tabLabel(tab) }}
                    <span
                        v-if="tab.key === 'all' ? totalCount > 0 : (counts[tab.key] || 0) > 0"
                        :class="[
                            'px-1.5 py-0.5 rounded-full text-xs font-bold',
                            activeTab === tab.key ? 'bg-white/20 text-white' : 'bg-gray-100 text-gray-600'
                        ]"
                    >
                        {{ tab.key === 'all' ? totalCount : (counts[tab.key] || 0) }}
                    </span>
                </button>
            </div>

            <!-- Search + Bulk Actions -->
            <div class="flex items-center justify-between gap-4">
                <div class="relative flex-1 max-w-md">
                    <svg class="absolute top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" :class="isRtl ? 'right-3' : 'left-3'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <input
                        v-model="search"
                        @input="doSearch"
                        type="text"
                        :placeholder="isRtl ? 'بحث في المحذوفات...' : 'Search in trash...'"
                        class="doctorato-input w-full border border-gray-200 rounded-lg py-2 text-sm focus:ring-[#C4A265] focus:border-[#C4A265]"
                        :class="isRtl ? 'pr-10 pl-4' : 'pl-10 pr-4'"
                    />
                </div>
                <div v-if="activeTab !== 'all' && (counts[activeTab] || 0) > 0" class="flex gap-2">
                    <button @click="restoreAll(activeTab)"
                        class="px-4 py-2 text-sm font-medium text-emerald-700 bg-emerald-50 hover:bg-emerald-100 rounded-lg transition-colors flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        {{ isRtl ? 'استعادة الكل' : 'Restore All' }}
                    </button>
                    <button @click="emptyTrash(activeTab)"
                        class="px-4 py-2 text-sm font-medium text-red-700 bg-red-50 hover:bg-red-100 rounded-lg transition-colors flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                        {{ isRtl ? 'إفراغ السلة' : 'Empty Trash' }}
                    </button>
                </div>
            </div>

            <!-- Items List -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div v-if="currentItems.length === 0" class="py-16 text-center">
                    <div class="w-16 h-16 mx-auto bg-gray-50 rounded-full flex items-center justify-center mb-4">
                        <svg class="w-8 h-8 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                    </div>
                    <p class="text-gray-500 font-medium">{{ isRtl ? 'سلة المحذوفات فارغة' : 'Trash is empty' }}</p>
                    <p class="text-gray-400 text-sm mt-1">{{ isRtl ? 'لا توجد عناصر محذوفة' : 'No deleted items found' }}</p>
                </div>

                <div v-else class="divide-y divide-gray-50">
                    <div
                        v-for="item in currentItems"
                        :key="item.type + '-' + item.id"
                        class="flex items-center justify-between px-5 py-4 hover:bg-gray-50/50 transition-colors"
                    >
                        <div class="flex items-center gap-4 min-w-0">
                            <!-- Type Badge -->
                            <div class="flex-shrink-0 w-10 h-10 rounded-lg flex items-center justify-center"
                                :class="{
                                    'bg-slate-50 text-[#1B365D]': item.type === 'patients',
                                    'bg-slate-50 text-[#1B365D]': item.type === 'doctors',
                                    'bg-amber-50 text-amber-600': item.type === 'bookings',
                                    'bg-teal-50 text-teal-600': item.type === 'visits',
                                    'bg-slate-50 text-[#1B365D]': item.type === 'invoices',
                                    'bg-emerald-50 text-emerald-600': item.type === 'payments',
                                    'bg-amber-50 text-[#C4A265]': item.type === 'prescriptions',
                                }"
                            >
                                <span class="text-xs font-bold uppercase">{{ item.type.slice(0, 2) }}</span>
                            </div>

                            <div class="min-w-0">
                                <p class="font-semibold text-gray-800 truncate">{{ item.title }}</p>
                                <p class="text-sm text-gray-400 truncate">{{ item.subtitle }}</p>
                            </div>
                        </div>

                        <div class="flex items-center gap-3 flex-shrink-0">
                            <span class="text-xs text-gray-400 whitespace-nowrap">{{ item.deleted_ago }}</span>

                            <button @click="restoreItem(item.type, item.id)"
                                class="p-2 text-emerald-600 hover:bg-emerald-50 rounded-lg transition-colors"
                                :title="isRtl ? 'استعادة' : 'Restore'"
                            >
                                <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                </svg>
                            </button>

                            <button @click="forceDeleteItem(item.type, item.id)"
                                class="p-2 text-red-500 hover:bg-red-50 rounded-lg transition-colors"
                                :title="isRtl ? 'حذف نهائي' : 'Delete permanently'"
                            >
                                <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
