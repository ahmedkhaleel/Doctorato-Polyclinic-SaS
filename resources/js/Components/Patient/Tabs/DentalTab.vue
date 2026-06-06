<script setup>
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';

const props = defineProps({
    data: { type: Object, default: null },
    patient: { type: Object, required: true },
    role: { type: String, default: 'admin' },
    readonly: { type: Boolean, default: false },
});

const page = usePage();
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const stats = computed(() => props.data?.stats || {});
const treatments = computed(() => props.data?.treatments || []);
const plans = computed(() => props.data?.plans || []);
const charts = computed(() => props.data?.charts || []);
const xrays = computed(() => props.data?.xrays || []);
const labOrders = computed(() => props.data?.labOrders || []);
const riskFlags = computed(() => props.data?.riskFlags || []);

const chartLink = computed(() => {
    if (props.role === 'admin') return `/admin/patients/${props.patient.id}/dental-chart`;
    if (props.role === 'doctor') return `/doctor/dental/chart/${props.patient.id}`;
    return null;
});

const plansLink = computed(() => {
    if (props.role === 'admin') return `/admin/dental/treatment-plans?patient_id=${props.patient.id}`;
    if (props.role === 'doctor') return `/doctor/dental/treatment-plans?patient_id=${props.patient.id}`;
    return null;
});

const treatmentsLink = computed(() => {
    if (props.role === 'admin') return `/admin/dental/treatments?patient_id=${props.patient.id}`;
    if (props.role === 'doctor') return `/doctor/dental/treatments?patient_id=${props.patient.id}`;
    return null;
});

const xraysLink = computed(() => {
    if (props.role === 'admin') return `/admin/dental/xrays?patient_id=${props.patient.id}`;
    if (props.role === 'doctor') return `/doctor/dental/xrays/${props.patient.id}`;
    return null;
});

function formatDate(d) {
    if (!d) return '-';
    try {
        return new Date(d).toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' });
    } catch { return d; }
}

function statusLabel(s) {
    if (!s) return '-';
    const en = { completed: 'Completed', planned: 'Planned', in_progress: 'In Progress', cancelled: 'Cancelled', approved: 'Approved', draft: 'Draft', ordered: 'Ordered', in_production: 'In Production', delivered: 'Delivered' };
    const ar = { completed: 'مكتمل', planned: 'مخطط', in_progress: 'جاري', cancelled: 'ملغي', approved: 'معتمد', draft: 'مسودة', ordered: 'مطلوب', in_production: 'قيد الإنتاج', delivered: 'مُسلّم' };
    return (isRtl.value ? ar[s] : en[s]) || s;
}
</script>

<template>
    <div v-if="data" class="space-y-5">
        <!-- Risk Flags Alert -->
        <div v-if="riskFlags?.length" class="bg-amber-50 border border-amber-200 rounded-xl p-4">
            <div class="flex items-start gap-3">
                <svg class="w-5 h-5 text-amber-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                <div class="flex-1">
                    <p class="text-sm font-bold text-amber-800 mb-1">{{ isRtl ? 'تنبيهات طب الأسنان' : 'Dental Safety Alerts' }}</p>
                    <div class="flex flex-wrap gap-1.5">
                        <span v-for="(flag, i) in riskFlags" :key="i"
                            class="inline-flex items-center px-2 py-0.5 rounded bg-amber-100 text-amber-800 text-[11px] font-medium">
                            {{ isRtl ? (flag.label_ar || flag.label) : (flag.label_en || flag.label) }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Stats -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
            <div class="bg-white rounded-xl border border-slate-100 p-4">
                <p class="text-2xl font-bold text-[#1B365D]">{{ stats.total_treatments || 0 }}</p>
                <p class="text-xs text-gray-500">{{ isRtl ? 'إجمالي العلاجات' : 'Treatments' }}</p>
            </div>
            <div class="bg-white rounded-xl border border-emerald-100 p-4">
                <p class="text-2xl font-bold text-emerald-600">{{ stats.completed_treatments || 0 }}</p>
                <p class="text-xs text-gray-500">{{ isRtl ? 'مكتمل' : 'Completed' }}</p>
            </div>
            <div class="bg-white rounded-xl border border-slate-100 p-4">
                <p class="text-2xl font-bold text-[#1B365D]">{{ stats.active_plans || 0 }}</p>
                <p class="text-xs text-gray-500">{{ isRtl ? 'خطط نشطة' : 'Active Plans' }}</p>
            </div>
            <div class="bg-white rounded-xl border border-amber-100 p-4">
                <p class="text-2xl font-bold text-[#C4A265]">{{ stats.pending_lab_orders || 0 }}</p>
                <p class="text-xs text-gray-500">{{ isRtl ? 'طلبات معمل' : 'Pending Lab' }}</p>
            </div>
        </div>

        <!-- Quick Links -->
        <div v-if="!readonly && (chartLink || plansLink || treatmentsLink || xraysLink)" class="grid grid-cols-2 lg:grid-cols-4 gap-3">
            <Link v-if="chartLink" :href="chartLink"
                class="flex items-center gap-3 p-3 bg-white rounded-xl border border-gray-200 hover:border-slate-300 hover:bg-slate-50/50 transition">
                <div class="w-9 h-9 rounded-lg bg-slate-50 flex items-center justify-center">
                    <svg class="w-4 h-4 text-[#1B365D]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4.26 10.147a60.436 60.436 0 00-.491 6.347A48.627 48.627 0 0112 20.904a48.627 48.627 0 018.232-4.41 60.46 60.46 0 00-.491-6.347" /></svg>
                </div>
                <div>
                    <p class="text-xs font-semibold text-gray-800">{{ isRtl ? 'المخطط' : 'Chart' }}</p>
                    <p class="text-[10px] text-gray-400">{{ charts.length }} {{ isRtl ? 'سن' : 'teeth' }}</p>
                </div>
            </Link>
            <Link v-if="plansLink" :href="plansLink"
                class="flex items-center gap-3 p-3 bg-white rounded-xl border border-gray-200 hover:border-slate-300 hover:bg-slate-50/50 transition">
                <div class="w-9 h-9 rounded-lg bg-slate-50 flex items-center justify-center">
                    <svg class="w-4 h-4 text-[#1B365D]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" /></svg>
                </div>
                <div>
                    <p class="text-xs font-semibold text-gray-800">{{ isRtl ? 'الخطط' : 'Plans' }}</p>
                    <p class="text-[10px] text-gray-400">{{ plans.length }}</p>
                </div>
            </Link>
            <Link v-if="treatmentsLink" :href="treatmentsLink"
                class="flex items-center gap-3 p-3 bg-white rounded-xl border border-gray-200 hover:border-teal-300 hover:bg-teal-50/50 transition">
                <div class="w-9 h-9 rounded-lg bg-teal-50 flex items-center justify-center">
                    <svg class="w-4 h-4 text-teal-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" /></svg>
                </div>
                <div>
                    <p class="text-xs font-semibold text-gray-800">{{ isRtl ? 'العلاجات' : 'Treatments' }}</p>
                    <p class="text-[10px] text-gray-400">{{ treatments.length }}</p>
                </div>
            </Link>
            <Link v-if="xraysLink" :href="xraysLink"
                class="flex items-center gap-3 p-3 bg-white rounded-xl border border-gray-200 hover:border-slate-300 hover:bg-slate-50/50 transition">
                <div class="w-9 h-9 rounded-lg bg-slate-50 flex items-center justify-center">
                    <svg class="w-4 h-4 text-[#1B365D]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                </div>
                <div>
                    <p class="text-xs font-semibold text-gray-800">{{ isRtl ? 'الأشعة' : 'X-Rays' }}</p>
                    <p class="text-[10px] text-gray-400">{{ xrays.length }}</p>
                </div>
            </Link>
        </div>

        <!-- Recent Treatments -->
        <div v-if="treatments.length" class="bg-white rounded-xl border border-gray-100 overflow-hidden">
            <div class="px-5 py-3 border-b border-gray-100">
                <h3 class="text-sm font-bold text-gray-800">{{ isRtl ? 'آخر العلاجات' : 'Recent Treatments' }}</h3>
            </div>
            <div class="divide-y divide-gray-50">
                <div v-for="t in treatments" :key="t.id" class="px-5 py-3 flex items-center gap-3">
                    <div class="w-9 h-9 rounded-lg bg-slate-50 flex items-center justify-center text-xs font-bold text-[#1B365D] flex-shrink-0">
                        {{ t.tooth_number }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-gray-800 truncate">{{ t.treatment_type }}</p>
                        <p class="text-xs text-gray-400">
                            {{ formatDate(t.created_at) }}
                            <span v-if="t.doctor"> · {{ isRtl ? (t.doctor.name_ar || t.doctor.name_en) : (t.doctor.name_en || t.doctor.name_ar) }}</span>
                        </p>
                    </div>
                    <span class="text-[10px] font-semibold px-2 py-0.5 rounded-full flex-shrink-0"
                        :class="t.status === 'completed' ? 'bg-emerald-50 text-emerald-600' : t.status === 'cancelled' ? 'bg-gray-50 text-gray-500' : 'bg-amber-50 text-amber-600'">
                        {{ statusLabel(t.status) }}
                    </span>
                </div>
            </div>
        </div>

        <!-- Treatment Plans -->
        <div v-if="plans.length" class="bg-white rounded-xl border border-gray-100 overflow-hidden">
            <div class="px-5 py-3 border-b border-gray-100">
                <h3 class="text-sm font-bold text-gray-800">{{ isRtl ? 'خطط العلاج' : 'Treatment Plans' }}</h3>
            </div>
            <div class="divide-y divide-gray-50">
                <div v-for="p in plans" :key="p.id" class="px-5 py-3">
                    <div class="flex items-center justify-between">
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-semibold text-gray-800 truncate">{{ p.title || p.name || (isRtl ? 'خطة علاج' : 'Plan') }}</p>
                            <p class="text-[10px] text-gray-400">
                                {{ formatDate(p.created_at) }} ·
                                {{ p.treatments_count || 0 }} {{ isRtl ? 'علاج' : 'treatments' }}
                                <span v-if="p.doctor"> · {{ isRtl ? (p.doctor.name_ar || p.doctor.name_en) : (p.doctor.name_en || p.doctor.name_ar) }}</span>
                            </p>
                        </div>
                        <span class="text-[10px] font-semibold px-2 py-0.5 rounded-full"
                            :class="p.status === 'approved' ? 'bg-emerald-50 text-emerald-600' : p.status === 'in_progress' ? 'bg-slate-50 text-[#1B365D]' : 'bg-gray-50 text-gray-500'">
                            {{ statusLabel(p.status) }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- X-Rays -->
        <div v-if="xrays.length" class="bg-white rounded-xl border border-gray-100 overflow-hidden">
            <div class="px-5 py-3 border-b border-gray-100">
                <h3 class="text-sm font-bold text-gray-800">{{ isRtl ? 'الأشعة' : 'X-Rays' }}</h3>
            </div>
            <div class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-6 gap-2 p-5">
                <div v-for="x in xrays" :key="x.id" class="aspect-square rounded-lg overflow-hidden bg-gray-900 relative group">
                    <img v-if="x.image_path || x.url" :src="x.image_url || ('/storage/' + x.image_path)" class="w-full h-full object-cover" />
                    <div v-else class="w-full h-full flex items-center justify-center text-gray-500 text-xs">{{ isRtl ? 'بدون صورة' : 'No image' }}</div>
                    <div class="absolute bottom-0 inset-x-0 bg-black/60 text-white text-[9px] p-1">
                        {{ formatDate(x.taken_date) }}
                    </div>
                </div>
            </div>
        </div>

        <!-- Lab Orders -->
        <div v-if="labOrders?.length" class="bg-white rounded-xl border border-gray-100 overflow-hidden">
            <div class="px-5 py-3 border-b border-gray-100">
                <h3 class="text-sm font-bold text-gray-800">{{ isRtl ? 'طلبات المعمل' : 'Lab Orders' }}</h3>
            </div>
            <div class="divide-y divide-gray-50">
                <div v-for="l in labOrders" :key="l.id" class="px-5 py-3 flex items-center gap-3">
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-gray-800">{{ l.item_type || (isRtl ? 'طلب معمل' : 'Lab Order') }}</p>
                        <p class="text-[10px] text-gray-400">{{ formatDate(l.order_date) }}</p>
                    </div>
                    <span class="text-[10px] font-semibold px-2 py-0.5 rounded-full"
                        :class="l.status === 'delivered' ? 'bg-emerald-50 text-emerald-600' : l.status === 'in_production' ? 'bg-slate-50 text-[#1B365D]' : 'bg-amber-50 text-amber-600'">
                        {{ statusLabel(l.status) }}
                    </span>
                </div>
            </div>
        </div>

        <!-- Empty state -->
        <div v-if="!treatments.length && !plans.length && !xrays.length" class="bg-gray-50 rounded-xl p-6 text-center">
            <p class="text-sm text-gray-500">{{ isRtl ? 'لا توجد بيانات أسنان' : 'No dental data recorded' }}</p>
        </div>
    </div>

    <div v-else class="bg-gray-50 rounded-2xl p-8 text-center">
        <p class="text-sm text-gray-500">{{ isRtl ? 'لا توجد بيانات أسنان' : 'No dental data available' }}</p>
    </div>
</template>
