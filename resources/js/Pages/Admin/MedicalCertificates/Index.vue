<script setup>
import { ref, computed, watch } from 'vue';
import { Link, useForm, router, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const page = usePage();
const locale = computed(() => page.props.locale || 'ar');
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const props = defineProps({
    certificates: Object,
    stats: Object,
    doctors: Array,
    filters: Object,
});

const search = ref(props.filters?.search || '');
const typeFilter = ref(props.filters?.type || '');
const statusFilter = ref(props.filters?.status || '');

let searchTimeout = null;
watch(search, () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(applyFilters, 400);
});

function applyFilters() {
    router.get('/admin/medical-certificates', {
        search: search.value || undefined,
        type: typeFilter.value || undefined,
        status: statusFilter.value || undefined,
    }, { preserveState: true, replace: true });
}

function setType(t) { typeFilter.value = typeFilter.value === t ? '' : t; applyFilters(); }
function setStatus(s) { statusFilter.value = statusFilter.value === s ? '' : s; applyFilters(); }

/* ── Type Config ────────────────────────────────── */
const typeConfig = {
    sick_leave:      { en: 'Sick Leave',      ar: 'إجازة مرضية',  color: 'bg-red-100 text-red-700' },
    fitness:         { en: 'Fitness',          ar: 'لياقة طبية',   color: 'bg-emerald-100 text-emerald-700' },
    medical_report:  { en: 'Medical Report',   ar: 'تقرير طبي',    color: 'bg-blue-100 text-blue-700' },
    referral_letter: { en: 'Referral Letter',  ar: 'خطاب تحويل',   color: 'bg-purple-100 text-purple-700' },
    follow_up:       { en: 'Follow-up',        ar: 'متابعة',       color: 'bg-amber-100 text-amber-700' },
};

const statusConfig = {
    draft:     { en: 'Draft',     ar: 'مسودة',   color: 'bg-gray-100 text-gray-700' },
    issued:    { en: 'Issued',    ar: 'صادرة',    color: 'bg-emerald-100 text-emerald-700' },
    cancelled: { en: 'Cancelled', ar: 'ملغاة',    color: 'bg-red-100 text-red-700' },
};

/* ── Create Modal ───────────────────────────────── */
const showCreateModal = ref(false);
const patientSearch = ref('');
const patientResults = ref([]);
let patientSearchTimeout = null;

watch(patientSearch, (val) => {
    clearTimeout(patientSearchTimeout);
    if (val.length < 2) { patientResults.value = []; return; }
    patientSearchTimeout = setTimeout(async () => {
        const res = await fetch(`/admin/api/global-search?q=${encodeURIComponent(val)}`);
        const data = await res.json();
        patientResults.value = (data.results || []).filter(r => r.type === 'patient');
    }, 300);
});

const form = useForm({
    patient_id: '',
    doctor_id: '',
    type: 'sick_leave',
    issue_date: new Date().toISOString().split('T')[0],
    start_date: '',
    end_date: '',
    days: '',
    diagnosis: '',
    notes: '',
    recommendations: '',
});

const selectedPatientName = ref('');

function selectPatient(p) {
    form.patient_id = p.id;
    selectedPatientName.value = p.title;
    patientResults.value = [];
    patientSearch.value = '';
}

function submitCreate() {
    form.post('/admin/medical-certificates', {
        preserveScroll: true,
        onSuccess: () => { showCreateModal.value = false; form.reset(); selectedPatientName.value = ''; },
    });
}

function issueCert(id) {
    router.put(`/admin/medical-certificates/${id}/issue`, {}, { preserveState: false });
}

function cancelCert(id) {
    if (!confirm(isRtl.value ? 'هل تريد إلغاء هذه الشهادة؟' : 'Cancel this certificate?')) return;
    router.put(`/admin/medical-certificates/${id}/cancel`, {}, { preserveState: false });
}

/* Auto-calc days */
watch([() => form.start_date, () => form.end_date], ([s, e]) => {
    if (s && e) {
        const diff = Math.ceil((new Date(e) - new Date(s)) / (1000 * 60 * 60 * 24)) + 1;
        if (diff > 0) form.days = diff;
    }
});
</script>

<template>
    <AdminLayout :title="isRtl ? 'الشهادات الطبية' : 'Medical Certificates'">
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">{{ isRtl ? 'الشهادات الطبية' : 'Medical Certificates' }}</h1>
                    <p class="text-sm text-gray-500 mt-1">{{ isRtl ? 'إجازات مرضية وتقارير طبية وشهادات لياقة' : 'Sick leaves, medical reports, and fitness certificates' }}</p>
                </div>
                <button @click="showCreateModal = true"
                    class="px-4 py-2 text-sm font-medium text-white bg-cyan-600 rounded-lg hover:bg-cyan-700 transition">
                    {{ isRtl ? '+ شهادة جديدة' : '+ New Certificate' }}
                </button>
            </div>

            <!-- Stats -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="bg-white rounded-xl border border-gray-200 p-4">
                    <div class="text-2xl font-bold text-gray-900">{{ stats.total }}</div>
                    <div class="text-xs text-gray-500 mt-1">{{ isRtl ? 'إجمالي الشهادات' : 'Total Certificates' }}</div>
                </div>
                <div class="bg-white rounded-xl border border-cyan-200 p-4">
                    <div class="text-2xl font-bold text-cyan-600">{{ stats.this_month }}</div>
                    <div class="text-xs text-gray-500 mt-1">{{ isRtl ? 'هذا الشهر' : 'This Month' }}</div>
                </div>
                <div class="bg-white rounded-xl border border-red-200 p-4">
                    <div class="text-2xl font-bold text-red-600">{{ stats.sick_leaves }}</div>
                    <div class="text-xs text-gray-500 mt-1">{{ isRtl ? 'إجازات مرضية' : 'Sick Leaves' }}</div>
                </div>
                <div class="bg-white rounded-xl border border-emerald-200 p-4">
                    <div class="text-2xl font-bold text-emerald-600">{{ stats.issued }}</div>
                    <div class="text-xs text-gray-500 mt-1">{{ isRtl ? 'صادرة' : 'Issued' }}</div>
                </div>
            </div>

            <!-- Filters -->
            <div class="bg-white rounded-xl border border-gray-200 p-4 space-y-3">
                <div class="flex flex-col sm:flex-row gap-3">
                    <div class="relative flex-1">
                        <svg class="absolute top-2.5 w-4 h-4 text-gray-400" :class="isRtl ? 'right-3' : 'left-3'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8" stroke-width="2"/><path stroke-linecap="round" stroke-width="2" d="m21 21-4.35-4.35"/></svg>
                        <input v-model="search" type="text"
                            :placeholder="isRtl ? 'بحث بالرقم أو اسم المريض...' : 'Search by number or patient name...'"
                            class="w-full rounded-lg border-gray-300 text-sm focus:ring-cyan-500 focus:border-cyan-500"
                            :class="isRtl ? 'pr-10 pl-3' : 'pl-10 pr-3'" />
                    </div>
                </div>
                <div class="flex flex-wrap gap-2">
                    <button v-for="(cfg, key) in typeConfig" :key="key" @click="setType(key)"
                        class="px-3 py-1 text-xs font-medium rounded-full border transition"
                        :class="typeFilter === key ? 'bg-cyan-600 text-white border-cyan-600' : `${cfg.color} border-transparent`">
                        {{ isRtl ? cfg.ar : cfg.en }}
                    </button>
                    <span class="mx-1 border-l border-gray-200"></span>
                    <button v-for="(cfg, key) in statusConfig" :key="key" @click="setStatus(key)"
                        class="px-3 py-1 text-xs font-medium rounded-full border transition"
                        :class="statusFilter === key ? 'bg-cyan-600 text-white border-cyan-600' : `${cfg.color} border-transparent`">
                        {{ isRtl ? cfg.ar : cfg.en }}
                    </button>
                </div>
            </div>

            <!-- Table -->
            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="text-start px-4 py-3 text-xs font-semibold text-gray-500 uppercase">{{ isRtl ? 'الرقم' : 'Number' }}</th>
                            <th class="text-start px-4 py-3 text-xs font-semibold text-gray-500 uppercase">{{ isRtl ? 'المريض' : 'Patient' }}</th>
                            <th class="text-start px-4 py-3 text-xs font-semibold text-gray-500 uppercase">{{ isRtl ? 'الطبيب' : 'Doctor' }}</th>
                            <th class="text-center px-4 py-3 text-xs font-semibold text-gray-500 uppercase">{{ isRtl ? 'النوع' : 'Type' }}</th>
                            <th class="text-center px-4 py-3 text-xs font-semibold text-gray-500 uppercase">{{ isRtl ? 'التاريخ' : 'Date' }}</th>
                            <th class="text-center px-4 py-3 text-xs font-semibold text-gray-500 uppercase">{{ isRtl ? 'المدة' : 'Duration' }}</th>
                            <th class="text-center px-4 py-3 text-xs font-semibold text-gray-500 uppercase">{{ isRtl ? 'الحالة' : 'Status' }}</th>
                            <th class="text-center px-4 py-3 text-xs font-semibold text-gray-500 uppercase">{{ isRtl ? 'إجراءات' : 'Actions' }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="cert in certificates.data" :key="cert.id" class="hover:bg-gray-50/50">
                            <td class="px-4 py-3 text-sm font-mono text-cyan-600">{{ cert.certificate_number }}</td>
                            <td class="px-4 py-3">
                                <div class="text-sm font-medium text-gray-900">{{ cert.patient?.full_name }}</div>
                                <div class="text-xs text-gray-400">{{ cert.patient?.file_number }}</div>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-600">{{ isRtl ? cert.doctor?.name_ar : cert.doctor?.name_en }}</td>
                            <td class="px-4 py-3 text-center">
                                <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium"
                                    :class="typeConfig[cert.type]?.color || 'bg-gray-100 text-gray-700'">
                                    {{ isRtl ? typeConfig[cert.type]?.ar : typeConfig[cert.type]?.en }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-center text-sm text-gray-500">{{ cert.issue_date }}</td>
                            <td class="px-4 py-3 text-center text-sm text-gray-600">
                                <template v-if="cert.days">{{ cert.days }} {{ isRtl ? 'يوم' : 'days' }}</template>
                                <template v-else>—</template>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium"
                                    :class="statusConfig[cert.status]?.color">
                                    {{ isRtl ? statusConfig[cert.status]?.ar : statusConfig[cert.status]?.en }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <div class="flex items-center justify-center gap-1">
                                    <button v-if="cert.status === 'draft'" @click="issueCert(cert.id)"
                                        class="px-2 py-1 text-xs font-medium text-emerald-700 bg-emerald-50 rounded hover:bg-emerald-100 transition">
                                        {{ isRtl ? 'إصدار' : 'Issue' }}
                                    </button>
                                    <button v-if="cert.status !== 'cancelled'" @click="cancelCert(cert.id)"
                                        class="px-2 py-1 text-xs font-medium text-red-600 bg-red-50 rounded hover:bg-red-100 transition">
                                        {{ isRtl ? 'إلغاء' : 'Cancel' }}
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <div v-if="!certificates.data?.length" class="p-16 text-center">
                    <svg class="w-16 h-16 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                    <h3 class="text-lg font-medium text-gray-900">{{ isRtl ? 'لا توجد شهادات' : 'No Certificates Found' }}</h3>
                </div>
            </div>

            <!-- Pagination -->
            <div v-if="certificates.links?.length > 3" class="flex justify-center gap-1">
                <template v-for="link in certificates.links" :key="link.label">
                    <Link v-if="link.url" :href="link.url"
                        class="px-3 py-1.5 text-sm rounded-lg border transition"
                        :class="link.active ? 'bg-cyan-600 text-white border-cyan-600' : 'bg-white text-gray-600 border-gray-300 hover:bg-gray-50'"
                        v-html="link.label" preserve-state />
                    <span v-else class="px-3 py-1.5 text-sm text-gray-400" v-html="link.label" />
                </template>
            </div>
        </div>

        <!-- Create Modal -->
        <Teleport to="body">
            <div v-if="showCreateModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <div class="fixed inset-0 bg-black/40" @click="showCreateModal = false"></div>
                <div class="relative bg-white rounded-2xl shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto p-6 space-y-4">
                    <h3 class="text-lg font-bold text-gray-900">{{ isRtl ? 'شهادة طبية جديدة' : 'New Medical Certificate' }}</h3>
                    <form @submit.prevent="submitCreate" class="space-y-4">
                        <!-- Patient Search -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ isRtl ? 'المريض' : 'Patient' }} *</label>
                            <div v-if="form.patient_id" class="flex items-center gap-2 mb-2">
                                <span class="px-3 py-1.5 bg-cyan-50 text-cyan-700 rounded-lg text-sm font-medium">{{ selectedPatientName }}</span>
                                <button type="button" @click="form.patient_id = ''; selectedPatientName = ''" class="text-gray-400 hover:text-red-500">&times;</button>
                            </div>
                            <div v-else class="relative">
                                <input v-model="patientSearch" type="text"
                                    :placeholder="isRtl ? 'ابحث عن مريض...' : 'Search patient...'"
                                    class="w-full rounded-lg border-gray-300 text-sm focus:ring-cyan-500 focus:border-cyan-500" />
                                <div v-if="patientResults.length" class="absolute z-10 w-full mt-1 bg-white rounded-lg shadow-lg border max-h-48 overflow-y-auto">
                                    <button v-for="p in patientResults" :key="p.id" type="button" @click="selectPatient(p)"
                                        class="w-full text-start px-4 py-2 text-sm hover:bg-gray-50">
                                        <div class="font-medium">{{ p.title }}</div>
                                        <div class="text-xs text-gray-400">{{ p.subtitle }}</div>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <!-- Doctor -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">{{ isRtl ? 'الطبيب' : 'Doctor' }} *</label>
                                <select v-model="form.doctor_id" required
                                    class="w-full rounded-lg border-gray-300 text-sm focus:ring-cyan-500 focus:border-cyan-500">
                                    <option value="">{{ isRtl ? '— اختر —' : '— Select —' }}</option>
                                    <option v-for="doc in doctors" :key="doc.id" :value="doc.id">
                                        {{ isRtl ? doc.name_ar : doc.name_en }}
                                    </option>
                                </select>
                            </div>
                            <!-- Type -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">{{ isRtl ? 'النوع' : 'Type' }} *</label>
                                <select v-model="form.type" required
                                    class="w-full rounded-lg border-gray-300 text-sm focus:ring-cyan-500 focus:border-cyan-500">
                                    <option v-for="(cfg, key) in typeConfig" :key="key" :value="key">
                                        {{ isRtl ? cfg.ar : cfg.en }}
                                    </option>
                                </select>
                            </div>
                        </div>

                        <div class="grid grid-cols-3 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">{{ isRtl ? 'تاريخ الإصدار' : 'Issue Date' }} *</label>
                                <input v-model="form.issue_date" type="date" required
                                    class="w-full rounded-lg border-gray-300 text-sm focus:ring-cyan-500 focus:border-cyan-500" />
                            </div>
                            <div v-if="form.type === 'sick_leave'">
                                <label class="block text-sm font-medium text-gray-700 mb-1">{{ isRtl ? 'من' : 'From' }}</label>
                                <input v-model="form.start_date" type="date"
                                    class="w-full rounded-lg border-gray-300 text-sm focus:ring-cyan-500 focus:border-cyan-500" />
                            </div>
                            <div v-if="form.type === 'sick_leave'">
                                <label class="block text-sm font-medium text-gray-700 mb-1">{{ isRtl ? 'إلى' : 'To' }}</label>
                                <input v-model="form.end_date" type="date"
                                    class="w-full rounded-lg border-gray-300 text-sm focus:ring-cyan-500 focus:border-cyan-500" />
                            </div>
                        </div>

                        <div v-if="form.type === 'sick_leave' && form.days" class="text-sm text-cyan-600 font-medium">
                            {{ isRtl ? `مدة الإجازة: ${form.days} يوم` : `Leave duration: ${form.days} days` }}
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ isRtl ? 'التشخيص' : 'Diagnosis' }}</label>
                            <input v-model="form.diagnosis" type="text" maxlength="500"
                                class="w-full rounded-lg border-gray-300 text-sm focus:ring-cyan-500 focus:border-cyan-500" />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ isRtl ? 'ملاحظات' : 'Notes' }}</label>
                            <textarea v-model="form.notes" rows="2"
                                class="w-full rounded-lg border-gray-300 text-sm focus:ring-cyan-500 focus:border-cyan-500"></textarea>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ isRtl ? 'التوصيات' : 'Recommendations' }}</label>
                            <textarea v-model="form.recommendations" rows="2"
                                class="w-full rounded-lg border-gray-300 text-sm focus:ring-cyan-500 focus:border-cyan-500"></textarea>
                        </div>

                        <div class="flex justify-end gap-2 pt-2">
                            <button type="button" @click="showCreateModal = false"
                                class="px-4 py-2 text-sm text-gray-600 bg-gray-100 rounded-lg hover:bg-gray-200">
                                {{ isRtl ? 'إلغاء' : 'Cancel' }}
                            </button>
                            <button type="submit" :disabled="form.processing || !form.patient_id"
                                class="px-4 py-2 text-sm font-medium text-white bg-cyan-600 rounded-lg hover:bg-cyan-700 disabled:opacity-50">
                                {{ isRtl ? 'إنشاء الشهادة' : 'Create Certificate' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>
    </AdminLayout>
</template>
