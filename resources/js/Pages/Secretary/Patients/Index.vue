<script setup>
import { computed, ref, watch, onMounted } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import SecretaryLayout from '@/Layouts/SecretaryLayout.vue';

defineOptions({ layout: SecretaryLayout });

const page = usePage();
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const props = defineProps({
    patients: Object,
    filters: Object,
});

const search = ref(props.filters?.search || '');
const statusFilter = ref(props.filters?.status || '');

let searchTimeout;
watch(search, () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => applyFilters(), 400);
});
watch(statusFilter, () => applyFilters());

function applyFilters() {
    router.get('/secretary/patients', {
        search: search.value || undefined,
        status: statusFilter.value || undefined,
    }, { preserveState: true, replace: true });
}

const genderLabels = { male: isRtl.value ? 'ذكر' : 'Male', female: isRtl.value ? 'أنثى' : 'Female' };

function formatDate(date) {
    if (!date) return '-';
    return new Date(date).toLocaleDateString(isRtl.value ? 'ar-EG' : 'en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
}

const headerLoaded = ref(false);
const cardsLoaded = ref(false);
onMounted(() => {
    setTimeout(() => { headerLoaded.value = true; }, 50);
    setTimeout(() => { cardsLoaded.value = true; }, 200);
});
</script>

<template>
    <div>
        <!-- ═══ HERO HEADER ═══ -->
        <div class="relative -mx-4 sm:-mx-6 lg:-mx-8 -mt-4 sm:-mt-6 mb-8 px-4 sm:px-6 lg:px-8 pt-8 pb-10 bg-gradient-to-br from-gray-900 via-gray-800 to-gray-900 overflow-hidden transition-all duration-700" :class="headerLoaded ? 'opacity-100 translate-y-0' : 'opacity-0 -translate-y-4'">
            <div class="absolute inset-0 opacity-10" style="background: radial-gradient(circle at 70% 50%, #0d9488 0%, transparent 60%)"></div>
            <div class="relative z-10">
                <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4">
                    <div>
                        <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-white/10 backdrop-blur-sm mb-3">
                            <span class="w-2 h-2 rounded-full bg-[#0d9488] animate-pulse"></span>
                            <span class="text-xs font-semibold text-gray-300">{{ isRtl ? 'إدارة المرضى' : 'Patient Management' }}</span>
                        </div>
                        <h1 class="text-2xl sm:text-3xl font-bold text-white">{{ isRtl ? 'المرضى' : 'Patients' }}</h1>
                        <p class="text-sm text-gray-400 mt-1.5">{{ isRtl ? 'إدارة سجلات المرضى ومعلوماتهم' : 'Manage patient records and information' }}</p>
                    </div>
                    <Link
                        href="/secretary/patients/create"
                        class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-sm font-semibold text-white bg-[#0d9488] hover:bg-[#0b8278] transition-all shadow-lg shadow-[#0d9488]/20"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                        {{ isRtl ? 'مريض جديد' : 'New Patient' }}
                    </Link>
                </div>

                <!-- Search & Filters in Hero -->
                <div class="flex flex-wrap items-center gap-3 mt-6">
                    <div class="relative flex-1 min-w-[240px] max-w-md">
                        <svg class="absolute left-3 rtl:left-auto rtl:right-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                        <input
                            v-model="search"
                            type="text"
                            :placeholder="isRtl ? 'بحث بالاسم، الهاتف، رقم الملف...' : 'Search by name, phone, file number...'"
                            class="w-full ltr:pl-10 ltr:pr-4 rtl:pr-10 rtl:pl-4 py-2.5 bg-white/10 border border-white/20 rounded-xl text-sm text-white placeholder-gray-400 focus:ring-2 focus:ring-[#0d9488]/50 focus:border-[#0d9488] transition"
                        />
                    </div>
                    <select
                        v-model="statusFilter"
                        class="px-4 py-2.5 bg-white/10 border border-white/20 rounded-xl text-sm text-white focus:ring-2 focus:ring-[#0d9488]/50 focus:border-[#0d9488] [&>option]:text-gray-900"
                    >
                        <option value="">{{ isRtl ? 'جميع الحالات' : 'All Status' }}</option>
                        <option value="active">{{ isRtl ? 'نشط' : 'Active' }}</option>
                        <option value="inactive">{{ isRtl ? 'غير نشط' : 'Inactive' }}</option>
                    </select>
                </div>

                <!-- Quick Stats -->
                <div class="grid grid-cols-3 gap-3 mt-6">
                    <div class="bg-white/5 backdrop-blur-sm rounded-xl p-3.5 border border-white/10">
                        <p class="text-xs text-gray-400 font-medium">{{ isRtl ? 'الإجمالي' : 'Total' }}</p>
                        <p class="text-2xl font-bold text-white mt-1">{{ patients?.total || 0 }}</p>
                    </div>
                    <div class="bg-white/5 backdrop-blur-sm rounded-xl p-3.5 border border-white/10">
                        <p class="text-xs text-gray-400 font-medium">{{ isRtl ? 'من' : 'From' }}</p>
                        <p class="text-2xl font-bold text-[#0d9488] mt-1">{{ patients?.from || 0 }}</p>
                    </div>
                    <div class="bg-white/5 backdrop-blur-sm rounded-xl p-3.5 border border-white/10">
                        <p class="text-xs text-gray-400 font-medium">{{ isRtl ? 'إلى' : 'To' }}</p>
                        <p class="text-2xl font-bold text-emerald-400 mt-1">{{ patients?.to || 0 }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- ═══ PATIENT CARDS ═══ -->
        <div class="space-y-3 transition-all duration-500" :class="cardsLoaded ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'">
            <div
                v-for="patient in patients.data"
                :key="patient.id"
                class="bg-white rounded-2xl shadow-sm border border-gray-100/80 hover:shadow-md transition-all duration-300 overflow-hidden"
            >
                <div class="flex flex-col sm:flex-row sm:items-center gap-4 p-4 sm:p-5">
                    <!-- Avatar & Info -->
                    <div class="flex items-center gap-4 flex-1 min-w-0">
                        <div v-if="patient.photo" class="w-12 h-12 rounded-xl overflow-hidden flex-shrink-0 ring-2 ring-teal-100">
                            <img
                                :src="patient.photo.startsWith('http') ? patient.photo : `/storage/${patient.photo}`"
                                :alt="patient.full_name"
                                class="w-full h-full object-cover"
                            />
                        </div>
                        <div v-else class="w-12 h-12 rounded-xl flex-shrink-0 flex items-center justify-center text-white text-sm font-bold bg-gradient-to-br from-[#0d9488] to-cyan-500">
                            {{ patient.full_name?.charAt(0) }}
                        </div>
                        <div class="min-w-0">
                            <Link :href="`/secretary/patients/${patient.id}`" class="font-bold text-gray-900 text-[15px] hover:text-[#0d9488] transition-colors">
                                {{ patient.full_name }}
                            </Link>
                            <div class="flex items-center gap-2 mt-1">
                                <span class="text-xs font-mono font-semibold text-[#0d9488]">{{ patient.file_number }}</span>
                                <span
                                    class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold border"
                                    :class="patient.is_active ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-gray-50 text-gray-500 border-gray-200'"
                                >
                                    {{ patient.is_active ? (isRtl ? 'نشط' : 'Active') : (isRtl ? 'غير نشط' : 'Inactive') }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Details -->
                    <div class="flex items-center gap-6 sm:gap-8">
                        <div class="text-center">
                            <p class="text-[10px] text-gray-400 font-semibold uppercase">{{ isRtl ? 'الهاتف' : 'Phone' }}</p>
                            <p class="text-sm font-medium text-gray-700 mt-0.5 dir-ltr">{{ patient.phone || '-' }}</p>
                        </div>
                        <div class="text-center">
                            <p class="text-[10px] text-gray-400 font-semibold uppercase">{{ isRtl ? 'الجنس' : 'Gender' }}</p>
                            <p class="text-sm font-medium text-gray-700 mt-0.5">{{ genderLabels[patient.gender] || '-' }}</p>
                        </div>
                        <div class="text-center hidden sm:block">
                            <p class="text-[10px] text-gray-400 font-semibold uppercase">{{ isRtl ? 'تاريخ الميلاد' : 'DOB' }}</p>
                            <p class="text-sm font-medium text-gray-700 mt-0.5">{{ formatDate(patient.date_of_birth) }}</p>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex items-center gap-2 flex-shrink-0">
                        <Link
                            :href="`/secretary/patients/${patient.id}`"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-[#0d9488] bg-[#0d9488]/5 hover:bg-[#0d9488]/10 rounded-xl transition-colors"
                        >
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                            {{ isRtl ? 'عرض' : 'View' }}
                        </Link>
                        <Link
                            :href="`/secretary/patients/${patient.id}/edit`"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-blue-600 bg-blue-50 hover:bg-blue-100 rounded-xl transition-colors"
                        >
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                            {{ isRtl ? 'تعديل' : 'Edit' }}
                        </Link>
                    </div>
                </div>
            </div>
        </div>

        <!-- Empty State -->
        <div v-if="!patients.data || patients.data.length === 0" class="py-16 text-center">
            <div class="w-16 h-16 mx-auto mb-4 rounded-2xl bg-teal-50 flex items-center justify-center">
                <svg class="w-8 h-8 text-teal-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
            </div>
            <p class="text-sm font-semibold text-gray-500">{{ isRtl ? 'لا يوجد مرضى' : 'No patients found' }}</p>
            <p class="text-xs text-gray-400 mt-1">{{ isRtl ? 'جرب تغيير البحث أو الفلاتر' : 'Try adjusting your search or filters' }}</p>
        </div>

        <!-- Pagination -->
        <div v-if="patients.links && patients.links.length > 3" class="flex flex-col sm:flex-row items-center justify-between gap-3 mt-6">
            <p class="text-xs text-gray-500">
                {{ isRtl ? 'عرض' : 'Showing' }} <span class="font-semibold">{{ patients.from }}</span> {{ isRtl ? 'إلى' : 'to' }} <span class="font-semibold">{{ patients.to }}</span> {{ isRtl ? 'من' : 'of' }} <span class="font-semibold">{{ patients.total }}</span> {{ isRtl ? 'نتيجة' : 'results' }}
            </p>
            <nav class="flex items-center gap-1">
                <template v-for="link in patients.links" :key="link.label">
                    <Link v-if="link.url" :href="link.url" class="px-3 py-1.5 rounded-lg text-xs font-medium transition-colors" :class="link.active ? 'bg-[#0d9488] text-white shadow-sm' : 'text-gray-500 hover:bg-white hover:shadow-sm'" v-html="link.label" preserve-state />
                    <span v-else class="px-3 py-1.5 text-xs text-gray-300" v-html="link.label" />
                </template>
            </nav>
        </div>
    </div>
</template>
