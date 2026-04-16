<script setup>
import { ref, computed, onMounted } from 'vue';
import { usePage } from '@inertiajs/vue3';
import OverviewTab from './Tabs/OverviewTab.vue';
import DermaTab from './Tabs/DermaTab.vue';
import DentalTab from './Tabs/DentalTab.vue';
import PediatricTab from './Tabs/PediatricTab.vue';
import VisitsTab from './Tabs/VisitsTab.vue';
import InvoicesTab from './Tabs/InvoicesTab.vue';
import PrescriptionsTab from './Tabs/PrescriptionsTab.vue';

const props = defineProps({
    patient: { type: Object, required: true },
    activeSpecialties: { type: Array, default: () => [] },
    dermaData: { type: Object, default: null },
    dentalData: { type: Object, default: null },
    pediatricData: { type: Object, default: null },
    visits: { type: Array, default: () => [] },
    invoices: { type: Array, default: () => [] },
    prescriptions: { type: Array, default: () => [] },
    role: { type: String, default: 'admin' },
    readonly: { type: Boolean, default: false },
    financialSummary: { type: Object, default: null },
});

const page = usePage();
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const TAB_STORAGE_KEY = `patient_tab_${props.patient.id}_${props.role}`;
const activeTab = ref('overview');

const availableTabs = computed(() => {
    const tabs = [
        {
            id: 'overview',
            labelAr: 'نظرة عامة',
            labelEn: 'Overview',
            icon: 'M3.75 3v11.25A2.25 2.25 0 006 16.5h2.25M3.75 3h-1.5m1.5 0h16.5m0 0h1.5m-1.5 0v11.25A2.25 2.25 0 0118 16.5h-2.25m-7.5 0h7.5m-7.5 0l-1 3m8.5-3l1 3m0 0l.5 1.5m-.5-1.5h-9.5m0 0l-.5 1.5M9 11.25v1.5M12 9v3.75m3-6v6',
            color: '#6366f1',
            count: null,
        },
        {
            id: 'visits',
            labelAr: 'الزيارات',
            labelEn: 'Visits',
            icon: 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2',
            color: '#64748b',
            count: props.visits?.length || 0,
        },
    ];

    if (props.activeSpecialties?.includes('derma')) {
        tabs.push({
            id: 'derma',
            labelAr: 'الجلدية',
            labelEn: 'Dermatology',
            icon: 'M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z',
            color: '#C4A265',
            count: props.dermaData?.stats?.total_visits || 0,
        });
    }
    if (props.activeSpecialties?.includes('dental')) {
        tabs.push({
            id: 'dental',
            labelAr: 'الأسنان',
            labelEn: 'Dental',
            icon: 'M12 2.5c-2 0-3.5 1.5-3.5 3.5 0 1 .4 2 .8 2.8.6 1 .7 2.2.7 3.2v4c0 1 .5 2 1.5 2s1.5-1 1.5-2v-2.5c0-.3.2-.5.5-.5s.5.2.5.5V16c0 1 .5 2 1.5 2s1.5-1 1.5-2v-4c0-1 .1-2.2.7-3.2.4-.8.8-1.8.8-2.8 0-2-1.5-3.5-3.5-3.5z',
            color: '#06b6d4',
            count: props.dentalData?.stats?.total_treatments || 0,
        });
    }
    if (props.activeSpecialties?.includes('pediatric')) {
        tabs.push({
            id: 'pediatric',
            labelAr: 'طب الأطفال',
            labelEn: 'Pediatric',
            icon: 'M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z',
            color: '#10b981',
            count: props.pediatricData?.stats?.total_visits || 0,
        });
    }

    tabs.push({
        id: 'invoices',
        labelAr: 'الفواتير',
        labelEn: 'Invoices',
        icon: 'M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z',
        color: '#f59e0b',
        count: props.invoices?.length || 0,
    });
    tabs.push({
        id: 'prescriptions',
        labelAr: 'الوصفات',
        labelEn: 'Prescriptions',
        icon: 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z',
        color: '#8b5cf6',
        count: props.prescriptions?.length || 0,
    });

    return tabs;
});

onMounted(() => {
    try {
        const saved = localStorage.getItem(TAB_STORAGE_KEY);
        if (saved && availableTabs.value.some(t => t.id === saved)) {
            activeTab.value = saved;
        }
    } catch (_) { /* ignore storage errors */ }
});

function setTab(id) {
    activeTab.value = id;
    try { localStorage.setItem(TAB_STORAGE_KEY, id); } catch (_) { /* ignore */ }
}
</script>

<template>
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100/80 overflow-hidden">
        <!-- Tab Navigation -->
        <div class="border-b border-gray-100 overflow-x-auto scrollbar-hide">
            <nav class="flex gap-1 px-2 pt-2 min-w-max">
                <button
                    v-for="tab in availableTabs"
                    :key="tab.id"
                    @click="setTab(tab.id)"
                    class="tab-btn relative flex items-center gap-2 px-4 py-3 text-sm font-semibold transition-all duration-200 rounded-t-xl"
                    :class="activeTab === tab.id
                        ? 'text-white shadow-sm'
                        : 'text-gray-500 hover:text-gray-700 hover:bg-gray-50'"
                    :style="activeTab === tab.id ? { backgroundColor: tab.color } : {}"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" :d="tab.icon" />
                    </svg>
                    <span>{{ isRtl ? tab.labelAr : tab.labelEn }}</span>
                    <span
                        v-if="tab.count !== null && tab.count > 0"
                        class="inline-flex items-center justify-center min-w-[20px] h-5 px-1.5 text-[10px] font-bold rounded-full"
                        :class="activeTab === tab.id ? 'bg-white/20 text-white' : 'bg-gray-100 text-gray-600'"
                    >
                        {{ tab.count }}
                    </span>
                </button>
            </nav>
        </div>

        <!-- Tab Content -->
        <div class="p-4 sm:p-6">
            <OverviewTab
                v-if="activeTab === 'overview'"
                :patient="patient"
                :active-specialties="activeSpecialties"
                :derma-data="dermaData"
                :dental-data="dentalData"
                :pediatric-data="pediatricData"
                :financial-summary="financialSummary"
                :visits="visits"
                @change-tab="setTab"
            />
            <VisitsTab
                v-else-if="activeTab === 'visits'"
                :visits="visits"
                :patient="patient"
                :role="role"
            />
            <DermaTab
                v-else-if="activeTab === 'derma'"
                :data="dermaData"
                :patient="patient"
                :role="role"
                :readonly="readonly"
            />
            <DentalTab
                v-else-if="activeTab === 'dental'"
                :data="dentalData"
                :patient="patient"
                :role="role"
                :readonly="readonly"
            />
            <PediatricTab
                v-else-if="activeTab === 'pediatric'"
                :data="pediatricData"
                :patient="patient"
                :role="role"
                :readonly="readonly"
            />
            <InvoicesTab
                v-else-if="activeTab === 'invoices'"
                :invoices="invoices"
                :patient="patient"
                :financial-summary="financialSummary"
                :role="role"
            />
            <PrescriptionsTab
                v-else-if="activeTab === 'prescriptions'"
                :prescriptions="prescriptions"
                :patient="patient"
                :role="role"
            />
        </div>
    </div>
</template>

<style scoped>
.scrollbar-hide::-webkit-scrollbar { display: none; }
.scrollbar-hide { scrollbar-width: none; }

.tab-btn {
    white-space: nowrap;
}
</style>
