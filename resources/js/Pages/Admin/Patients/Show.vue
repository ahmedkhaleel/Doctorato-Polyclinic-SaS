<script setup>
import { ref, computed, onMounted } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import SpecialtyTabs from '@/Components/Patient/SpecialtyTabs.vue';
import EngagementCard from '@/Components/Patient/EngagementCard.vue';
import PhoneWithWhatsApp from '@/Components/Patient/PhoneWithWhatsApp.vue';
import { usePermissions } from '@/Composables/usePermissions.js';
import { useCurrency } from '@/Composables/useCurrency.js';

const headerLoaded = ref(false);
const cardsLoaded = ref(false);
onMounted(() => {
    setTimeout(() => headerLoaded.value = true, 50);
    setTimeout(() => cardsLoaded.value = true, 200);
});

const { can } = usePermissions();
const { formatCurrency } = useCurrency();

const page = usePage();
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const props = defineProps({
    patient: Object,
    medicalAlerts: { type: Array, default: () => [] },
    financialSummary: Object,
    activeSpecialties: { type: Array, default: () => [] },
    dermaData: { type: Object, default: null },
    dentalData: { type: Object, default: null },
    pediatricData: { type: Object, default: null },
    engagement: { type: Object, default: null },
    doctors: Array,
});

// Safety-critical medical alerts (allergies, blood thinners, heart/HIV/hepatitis,
// diabetes, pregnancy, chronic conditions, current meds). High = red, medium = amber.
const highAlerts = computed(() => (props.medicalAlerts || []).filter(a => a.severity === 'high'));
const mediumAlerts = computed(() => (props.medicalAlerts || []).filter(a => a.severity !== 'high'));
const hasAlerts = computed(() => (props.medicalAlerts || []).length > 0);

const visits = computed(() => props.patient?.visits || []);
const invoices = computed(() => props.patient?.invoices || []);
const prescriptions = computed(() => props.patient?.prescriptions || []);
</script>

<template>
    <AdminLayout :title="(isRtl ? 'المريض: ' : 'Patient: ') + patient.full_name">
        <div class="space-y-6">
            <!-- Hero Header Card -->
            <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-gray-900 via-gray-800 to-gray-900 shadow-xl transition-all duration-700"
                :class="headerLoaded ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'">
                <div class="absolute inset-0 opacity-10">
                    <div class="absolute -top-20 -end-20 w-64 h-64 rounded-full" style="background: radial-gradient(circle, #C4A265, transparent 70%);"></div>
                    <div class="absolute -bottom-10 -start-10 w-48 h-48 rounded-full" style="background: radial-gradient(circle, #C4A265, transparent 70%);"></div>
                </div>

                <div class="relative p-4 md:p-6 sm:p-8">
                    <div class="flex flex-col sm:flex-row items-start sm:items-center gap-6">
                        <!-- Avatar -->
                        <div class="relative group">
                            <div class="absolute -inset-1 rounded-2xl bg-gradient-to-br from-[#C4A265] to-[#8B7043] opacity-60 blur group-hover:opacity-80 transition-opacity duration-300"></div>
                            <div v-if="patient.photo" class="relative w-20 h-20 rounded-2xl overflow-hidden ring-2 ring-white/20">
                                <img :src="patient.photo.startsWith('http') ? patient.photo : `/storage/${patient.photo}`" class="w-full h-full object-cover" />
                            </div>
                            <div v-else class="relative w-20 h-20 rounded-2xl flex items-center justify-center text-xl md:text-2xl font-bold text-white ring-2 ring-white/20" style="background: linear-gradient(135deg, #C4A265, #A68B52);">
                                {{ patient.full_name?.charAt(0) }}
                            </div>
                            <div class="absolute -bottom-1 -end-1 w-6 h-6 rounded-full flex items-center justify-center text-[10px]"
                                :class="patient.is_active ? 'bg-emerald-500 text-white' : 'bg-gray-500 text-white'">
                                <svg v-if="patient.is_active" class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" /></svg>
                                <svg v-else class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" /></svg>
                            </div>
                        </div>

                        <!-- Info -->
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-3 mb-1.5">
                                <h1 class="text-xl md:text-2xl sm:text-3xl font-bold text-white tracking-tight truncate">{{ patient.full_name }}</h1>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider"
                                    :class="patient.is_active ? 'bg-emerald-500/20 text-emerald-300 ring-1 ring-emerald-500/30' : 'bg-gray-500/20 text-gray-400 ring-1 ring-gray-500/30'">
                                    {{ patient.is_active ? $t('a_active') : $t('a_inactive') }}
                                </span>
                            </div>
                            <div class="flex items-center gap-4 text-sm text-white/50">
                                <span class="font-mono font-semibold text-[#C4A265]/80">{{ patient.file_number }}</span>
                                <span v-if="patient.phone" class="flex items-center gap-1.5">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" /></svg>
                                    <PhoneWithWhatsApp :phone="patient.phone" variant="compact" />
                                </span>
                                <span v-if="patient.gender" class="flex items-center gap-1">
                                    <svg v-if="patient.gender === 'male'" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M19 5h-5m5 0v5m0-5l-6 6m-1 8a5 5 0 100-10 5 5 0 000 10z" /></svg>
                                    <svg v-else class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 14a5 5 0 100-10 5 5 0 000 10zm0 0v7m-3-3h6" /></svg>
                                    {{ patient.gender === 'male' ? $t('a_male') : $t('a_female') }}
                                </span>
                                <span v-if="patient.age">{{ patient.age }} {{ isRtl ? 'سنة' : 'yrs' }}</span>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="flex items-center gap-2 flex-shrink-0">
                            <Link
                                :href="`/admin/patients/${patient.id}/timeline`"
                                class="inline-flex items-center gap-1.5 px-4 py-2.5 rounded-xl text-sm font-semibold text-white/80 hover:text-white bg-white/10 hover:bg-white/15 backdrop-blur-sm border border-white/10 transition-all duration-200"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                {{ isRtl ? 'السجل الزمني' : 'Timeline' }}
                            </Link>
                            <Link
                                :href="`/admin/patients/${patient.id}/communications`"
                                class="inline-flex items-center gap-1.5 px-4 py-2.5 rounded-xl text-sm font-semibold text-white/80 hover:text-white bg-white/10 hover:bg-white/15 backdrop-blur-sm border border-white/10 transition-all duration-200"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.86 9.86 0 01-4-.8L3 20l1.3-3.5A7.9 7.9 0 013 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" /></svg>
                                {{ isRtl ? 'المراسلات' : 'Communications' }}
                            </Link>
                            <a
                                :href="`/admin/patients/${patient.id}/export-file`"
                                target="_blank"
                                class="inline-flex items-center gap-1.5 px-4 py-2.5 rounded-xl text-sm font-semibold text-white/80 hover:text-white bg-white/10 hover:bg-white/15 backdrop-blur-sm border border-white/10 transition-all duration-200"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                {{ isRtl ? 'تصدير PDF' : 'Export PDF' }}
                            </a>
                            <Link
                                v-if="can('patients.update')"
                                :href="`/admin/patients/${patient.id}/edit`"
                                class="inline-flex items-center gap-1.5 px-4 py-2.5 rounded-xl text-sm font-semibold text-white/80 hover:text-white bg-white/10 hover:bg-white/15 backdrop-blur-sm border border-white/10 transition-all duration-200"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                                {{ $t('a_edit_patient') }}
                            </Link>
                            <Link
                                v-if="can('visits.create')"
                                :href="`/admin/bookings/create?patient_id=${patient.id}`"
                                class="inline-flex items-center gap-1.5 px-5 py-2.5 rounded-xl text-sm font-bold text-gray-900 shadow-lg hover:shadow-xl hover:scale-[1.02] transition-all duration-200"
                                style="background: linear-gradient(135deg, #C4A265, #D4B275);"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                                {{ $t('a_new_booking') }}
                            </Link>
                        </div>
                    </div>

                    <!-- Quick Stats Bar -->
                    <div v-if="financialSummary" class="mt-6 grid grid-cols-2 sm:grid-cols-4 gap-3">
                        <div class="bg-white/5 backdrop-blur-sm rounded-xl px-4 py-3 border border-white/10 hover:bg-white/10 transition-all duration-200">
                            <p class="text-[10px] font-semibold text-white/40 uppercase tracking-wider">{{ $t('a_total_visits') }}</p>
                            <p class="text-xl font-bold text-white mt-0.5">{{ financialSummary.total_visits }}</p>
                        </div>
                        <div class="bg-white/5 backdrop-blur-sm rounded-xl px-4 py-3 border border-white/10 hover:bg-white/10 transition-all duration-200">
                            <p class="text-[10px] font-semibold text-white/40 uppercase tracking-wider">{{ $t('a_total_invoiced') }}</p>
                            <p class="text-xl font-bold text-[#C4A265] mt-0.5">{{ formatCurrency(financialSummary.total_invoiced) }}</p>
                        </div>
                        <div class="bg-white/5 backdrop-blur-sm rounded-xl px-4 py-3 border border-white/10 hover:bg-white/10 transition-all duration-200">
                            <p class="text-[10px] font-semibold text-white/40 uppercase tracking-wider">{{ $t('a_total_paid') }}</p>
                            <p class="text-xl font-bold text-emerald-400 mt-0.5">{{ formatCurrency(financialSummary.total_paid) }}</p>
                        </div>
                        <div class="bg-white/5 backdrop-blur-sm rounded-xl px-4 py-3 border border-white/10 hover:bg-white/10 transition-all duration-200">
                            <p class="text-[10px] font-semibold text-white/40 uppercase tracking-wider">{{ $t('a_outstanding') }}</p>
                            <p class="text-xl font-bold mt-0.5" :class="financialSummary.outstanding_balance > 0 ? 'text-red-400' : 'text-emerald-400'">{{ formatCurrency(financialSummary.outstanding_balance) }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ═══ Medical alerts banner (patient safety — shown before anything clinical) ═══ -->
            <div v-if="hasAlerts" class="med-alert rounded-2xl border overflow-hidden"
                :class="highAlerts.length ? 'border-red-200 bg-red-50/70' : 'border-amber-200 bg-amber-50/70'">
                <div class="px-4 md:px-5 py-3 flex items-start gap-3">
                    <div class="w-9 h-9 rounded-xl flex items-center justify-center shrink-0 mt-0.5"
                        :class="highAlerts.length ? 'bg-red-100 text-red-600' : 'bg-amber-100 text-amber-600'">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 9v4m0 4h.01M10.3 3.86l-8.06 14A1 1 0 003.1 19.4h17.8a1 1 0 00.86-1.5l-8.06-14a1 1 0 00-1.74 0z" /></svg>
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-bold" :class="highAlerts.length ? 'text-red-800' : 'text-amber-800'">
                            {{ isRtl ? 'تنبيهات طبية' : 'Medical Alerts' }}
                        </p>
                        <div class="mt-1.5 flex flex-wrap gap-1.5">
                            <span v-for="a in highAlerts" :key="a.key"
                                class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-semibold bg-red-100 text-red-700 border border-red-200">
                                <span class="w-1.5 h-1.5 rounded-full bg-red-500"></span>{{ isRtl ? a.ar : a.en }}
                            </span>
                            <span v-for="a in mediumAlerts" :key="a.key"
                                class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-medium bg-amber-100 text-amber-700 border border-amber-200">
                                <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>{{ isRtl ? a.ar : a.en }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Engagement (loyalty + referrals + active codes) -->
            <EngagementCard v-if="engagement" :engagement="engagement" />

            <!-- Unified Specialty Tabs -->
            <div class="transition-all duration-500" :class="cardsLoaded ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'">
                <SpecialtyTabs
                    role="admin"
                    :patient="patient"
                    :active-specialties="activeSpecialties"
                    :derma-data="dermaData"
                    :dental-data="dentalData"
                    :pediatric-data="pediatricData"
                    :visits="visits"
                    :invoices="invoices"
                    :prescriptions="prescriptions"
                    :financial-summary="financialSummary"
                />
            </div>
        </div>
    </AdminLayout>
</template>

<style scoped>
.med-alert {
    animation: medAlertIn 0.45s cubic-bezier(0.22, 0.61, 0.36, 1) both;
}
@keyframes medAlertIn {
    from { opacity: 0; transform: translateY(-6px); }
    to { opacity: 1; transform: none; }
}
@media (prefers-reduced-motion: reduce) {
    .med-alert { animation: none !important; }
}
</style>
