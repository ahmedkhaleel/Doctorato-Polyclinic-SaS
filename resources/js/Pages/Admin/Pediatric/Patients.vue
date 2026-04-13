<script setup>
import { ref, computed, watch } from 'vue';
import { Link, usePage, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

defineOptions({ layout: AdminLayout });

const page = usePage();
const locale = computed(() => page.props.locale || 'ar');
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');
const translations = computed(() => page.props.translations || {});
function t(key) { return translations.value[key] || key; }

const props = defineProps({
    patients: Object,
    filters: Object,
});

/* ── Search ────────────────────────────────────────────── */
const search = ref(props.filters?.search || '');
let searchTimer = null;

watch(search, (val) => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => {
        router.get('/admin/pediatric/patients', { search: val || undefined }, { preserveState: true, preserveScroll: true });
    }, 400);
});

/* ── Helpers ────────────────────────────────────────────── */
function calcAge(dob) {
    if (!dob) return '-';
    const birth = new Date(dob);
    const now = new Date();
    const months = (now.getFullYear() - birth.getFullYear()) * 12 + (now.getMonth() - birth.getMonth());
    if (months < 1) return isRtl.value ? 'حديث الولادة' : 'Newborn';
    if (months < 12) return `${months} ${isRtl.value ? 'شهر' : 'mo'}`;
    const years = Math.floor(months / 12);
    const rem = months % 12;
    return rem > 0 ? `${years}${isRtl.value ? 'س' : 'y'} ${rem}${isRtl.value ? 'ش' : 'm'}` : `${years} ${isRtl.value ? 'سنة' : 'y'}`;
}

function formatDate(date) {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
}

/* ── Pagination ────────────────────────────────────────── */
const paginationLinks = computed(() => {
    if (!props.patients?.links) return [];
    return props.patients.links;
});
</script>

<template>
    <div class="space-y-8 pb-12">
        <!-- ── Hero ──────────────────────────────────────── -->
        <div class="ped-hero relative overflow-hidden rounded-3xl bg-gradient-to-br from-emerald-600 via-green-600 to-green-500 p-8 md:p-10">
            <div class="absolute -top-20 ltr:-right-20 rtl:-left-20 w-72 h-72 bg-green-400/20 rounded-full blur-3xl"></div>
            <div class="absolute -bottom-16 ltr:-left-16 rtl:-right-16 w-56 h-56 bg-emerald-300/15 rounded-full blur-3xl"></div>

            <div class="absolute ltr:right-8 rtl:left-8 top-8 opacity-10">
                <svg class="w-28 h-28 text-white ped-float" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="0.5" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />
                </svg>
            </div>

            <div class="relative z-10">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div class="ped-hero-up">
                        <div class="flex items-center gap-3">
                            <div class="w-12 h-12 rounded-2xl bg-white/15 backdrop-blur-sm flex items-center justify-center ring-1 ring-white/20">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" /></svg>
                            </div>
                            <div>
                                <h1 class="text-2xl md:text-3xl font-bold text-white">
                                    {{ isRtl ? 'مرضى الأطفال' : 'Pediatric Patients' }}
                                </h1>
                                <p class="text-green-100/80 text-sm mt-0.5">
                                    {{ isRtl ? 'إدارة سجلات مرضى الأطفال' : 'Manage pediatric patient records' }}
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="ped-hero-up" style="animation-delay: 0.15s">
                        <Link
                            href="/admin/pediatric"
                            class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-sm font-semibold text-white bg-white/15 hover:bg-white/25 ring-1 ring-white/30 hover:ring-white/50 shadow-lg transition-all duration-300 backdrop-blur-sm"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" /></svg>
                            {{ isRtl ? 'لوحة التحكم' : 'Dashboard' }}
                        </Link>
                    </div>
                </div>
            </div>
        </div>

        <!-- ── Search Bar ────────────────────────────────── -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100/80 p-5">
            <div class="relative max-w-md">
                <svg class="absolute top-1/2 -translate-y-1/2 ltr:left-3 rtl:right-3 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                <input
                    v-model="search"
                    type="text"
                    :placeholder="isRtl ? 'بحث بالاسم، رقم الملف، ولي الأمر...' : 'Search by name, file number, guardian...'"
                    class="w-full ltr:pl-10 rtl:pr-10 pr-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-green-500/20 focus:border-green-400 transition"
                />
            </div>
        </div>

        <!-- ── Patients Table ────────────────────────────── -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100/80 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50/80">
                        <tr>
                            <th class="text-start px-5 py-3.5 font-semibold text-gray-500">{{ isRtl ? 'الاسم' : 'Name' }}</th>
                            <th class="text-start px-5 py-3.5 font-semibold text-gray-500 hidden sm:table-cell">{{ isRtl ? 'العمر' : 'Age' }}</th>
                            <th class="text-start px-5 py-3.5 font-semibold text-gray-500 hidden lg:table-cell">{{ isRtl ? 'رقم الملف' : 'File #' }}</th>
                            <th class="text-start px-5 py-3.5 font-semibold text-gray-500 hidden md:table-cell">{{ isRtl ? 'ولي الأمر' : 'Guardian' }}</th>
                            <th class="text-center px-5 py-3.5 font-semibold text-gray-500 hidden lg:table-cell">{{ isRtl ? 'الزيارات' : 'Visits' }}</th>
                            <th class="text-center px-5 py-3.5 font-semibold text-gray-500 hidden xl:table-cell">{{ isRtl ? 'التطعيمات' : 'Vaccines' }}</th>
                            <th class="text-center px-5 py-3.5 font-semibold text-gray-500 hidden xl:table-cell">{{ isRtl ? 'الحساسية' : 'Allergies' }}</th>
                            <th class="text-center px-5 py-3.5 font-semibold text-gray-500">{{ isRtl ? 'إجراء' : 'Action' }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="patient in (patients?.data || [])"
                            :key="patient.id"
                            class="border-b border-gray-50 hover:bg-green-50/30 transition-colors cursor-pointer"
                            @click="router.visit(`/secretary/pediatric/patients/${patient.id}`)"
                        >
                            <td class="px-5 py-3.5">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-full flex items-center justify-center text-xs font-bold"
                                        :class="patient.gender === 'male' ? 'bg-blue-100 text-blue-600' : 'bg-pink-100 text-pink-600'">
                                        {{ patient.full_name?.charAt(0)?.toUpperCase() || '?' }}
                                    </div>
                                    <div>
                                        <p class="font-semibold text-gray-800">{{ patient.full_name }}</p>
                                        <p class="text-xs text-gray-400 sm:hidden">{{ calcAge(patient.date_of_birth) }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-5 py-3.5 text-gray-600 hidden sm:table-cell">{{ calcAge(patient.date_of_birth) }}</td>
                            <td class="px-5 py-3.5 text-gray-500 font-mono text-xs hidden lg:table-cell">{{ patient.file_number || '-' }}</td>
                            <td class="px-5 py-3.5 text-gray-600 hidden md:table-cell">{{ patient.guardian_name || '-' }}</td>
                            <td class="px-5 py-3.5 text-center hidden lg:table-cell">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full bg-green-50 text-green-700 text-xs font-semibold">
                                    {{ patient.visits_count ?? patient.visits ?? 0 }}
                                </span>
                            </td>
                            <td class="px-5 py-3.5 text-center hidden xl:table-cell">
                                <span class="text-xs text-gray-600">
                                    <span class="font-semibold text-green-600">{{ patient.vaccines_given ?? 0 }}</span>
                                    /
                                    <span>{{ patient.vaccines_total ?? 0 }}</span>
                                </span>
                            </td>
                            <td class="px-5 py-3.5 text-center hidden xl:table-cell">
                                <span v-if="patient.allergies_count > 0" class="inline-flex items-center px-2.5 py-0.5 rounded-full bg-red-50 text-red-600 text-xs font-semibold">
                                    {{ patient.allergies_count }}
                                </span>
                                <span v-else class="text-xs text-gray-300">-</span>
                            </td>
                            <td class="px-5 py-3.5 text-center">
                                <Link
                                    :href="`/secretary/pediatric/patients/${patient.id}`"
                                    class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-semibold text-green-600 bg-green-50 hover:bg-green-100 transition"
                                    @click.stop
                                >
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                                    {{ isRtl ? 'عرض' : 'View' }}
                                </Link>
                            </td>
                        </tr>
                        <tr v-if="!patients?.data?.length">
                            <td colspan="8" class="px-5 py-12 text-center text-gray-400">
                                <svg class="w-12 h-12 mx-auto text-gray-200 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" /></svg>
                                {{ isRtl ? 'لا يوجد مرضى' : 'No patients found' }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div v-if="paginationLinks.length > 3" class="flex items-center justify-center gap-1 px-5 py-4 border-t border-gray-100">
                <template v-for="(link, i) in paginationLinks" :key="i">
                    <Link
                        v-if="link.url"
                        :href="link.url"
                        class="px-3 py-1.5 rounded-lg text-sm transition"
                        :class="link.active ? 'bg-green-500 text-white font-semibold shadow-sm' : 'text-gray-600 hover:bg-gray-100'"
                        v-html="link.label"
                        preserve-state
                        preserve-scroll
                    />
                    <span
                        v-else
                        class="px-3 py-1.5 text-sm text-gray-300"
                        v-html="link.label"
                    />
                </template>
            </div>
        </div>
    </div>
</template>

<style scoped>
.ped-hero-up {
    animation: pedHeroUp 0.7s cubic-bezier(0.16, 1, 0.3, 1) both;
}
@keyframes pedHeroUp {
    from { opacity: 0; transform: translateY(20px); }
    to   { opacity: 1; transform: translateY(0); }
}
.ped-float {
    animation: pedFloat 6s ease-in-out infinite;
}
@keyframes pedFloat {
    0%, 100% { transform: translateY(0); }
    50%      { transform: translateY(-12px); }
}
</style>
