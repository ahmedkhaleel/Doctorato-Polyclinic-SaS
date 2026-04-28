<script setup>
import { computed, ref, watch } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import PhoneWithWhatsApp from '@/Components/Patient/PhoneWithWhatsApp.vue';

defineOptions({ layout: AdminLayout });

const props = defineProps({
    patients: Object,        // paginator
    doctors:  Array,
    availableModules: Array,
    filters:  Object,
});

const page = usePage();
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');
const locale = computed(() => page.props.locale || 'ar');

const search   = ref(props.filters?.search || '');
const days     = ref(props.filters?.days || 180);
const module   = ref(props.filters?.module || '');
const doctorId = ref(props.filters?.doctor_id || '');

let timer = null;
function applyFilters() {
    clearTimeout(timer);
    timer = setTimeout(() => {
        router.get('/admin/recall', {
            search: search.value || undefined,
            days: days.value,
            module: module.value || undefined,
            doctor_id: doctorId.value || undefined,
        }, { preserveState: true, preserveScroll: true, replace: true });
    }, 300);
}
watch([search, days, module, doctorId], applyFilters);

function $localized(d, field) {
    if (!d) return '';
    return locale.value === 'ar' ? (d[field + '_ar'] || d[field + '_en']) : (d[field + '_en'] || d[field + '_ar']);
}

function moduleLabel(m) {
    const labels = {
        ar: { derma: 'الجلدية والتجميل', dental: 'الأسنان', pediatric: 'الأطفال' },
        en: { derma: 'Dermatology', dental: 'Dental', pediatric: 'Pediatric' },
    };
    return labels[locale.value]?.[m] || m;
}

function recallMessage(p) {
    const name = (p.full_name || '').split(' ')[0];
    return locale.value === 'ar'
        ? `مرحباً ${name}، نتمنى أن تكون بخير. لاحظنا أنه مر وقت منذ آخر زيارة لك. هل تود حجز موعد للمتابعة؟`
        : `Hi ${name}, hope you're well. It's been a while since your last visit — would you like to book a follow-up?`;
}

const presets = [90, 180, 365, 540];
</script>

<template>
    <div class="p-4 lg:p-6">
        <!-- Header -->
        <div class="mb-6">
            <h1 class="text-2xl font-extrabold text-[#1B365D]">
                {{ isRtl ? 'استعادة المرضى المنقطعين' : 'Patient Recall' }}
            </h1>
            <p class="text-sm text-gray-500 mt-1">
                {{ isRtl
                    ? 'مرضى لم يزوروا العيادة منذ فترة. تواصل معهم لإعادتهم.'
                    : 'Patients who haven\'t visited in a while. Reach out to bring them back.' }}
            </p>
        </div>

        <!-- Filters -->
        <div class="bg-white rounded-xl border border-gray-100 p-4 mb-4 space-y-3">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
                <div class="md:col-span-2">
                    <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1">
                        {{ isRtl ? 'بحث' : 'Search' }}
                    </label>
                    <input v-model="search" type="text"
                           :placeholder="isRtl ? 'الاسم / رقم الملف / الهاتف' : 'Name / file # / phone'"
                           class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-[#1B365D]/20" />
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1">
                        {{ isRtl ? 'القسم' : 'Module' }}
                    </label>
                    <select v-model="module" class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm">
                        <option value="">{{ isRtl ? 'الكل' : 'All' }}</option>
                        <option v-for="m in availableModules" :key="m" :value="m">{{ moduleLabel(m) }}</option>
                    </select>
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1">
                        {{ isRtl ? 'آخر طبيب' : 'Last doctor' }}
                    </label>
                    <select v-model="doctorId" class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm">
                        <option value="">{{ isRtl ? 'الكل' : 'All' }}</option>
                        <option v-for="d in doctors" :key="d.id" :value="d.id">{{ $localized(d, 'name') }}</option>
                    </select>
                </div>
            </div>

            <div class="flex items-center gap-2 flex-wrap pt-2 border-t border-gray-100">
                <span class="text-[11px] font-bold text-gray-500 uppercase tracking-wider">
                    {{ isRtl ? 'لم يزوروا منذ' : 'Not visited in' }}
                </span>
                <button v-for="d in presets" :key="d" type="button" @click="days = d"
                        :class="days == d
                            ? 'bg-[#1B365D] text-white border-[#1B365D]'
                            : 'bg-white text-gray-600 border-gray-200 hover:bg-gray-50'"
                        class="px-3 py-1 rounded-full border text-xs font-semibold transition">
                    {{ d }} {{ isRtl ? 'يوم' : 'days' }}
                </button>
                <input v-model.number="days" type="number" min="30" max="720"
                       class="w-20 px-2 py-1 border border-gray-200 rounded text-xs tabular-nums text-center" />
            </div>
        </div>

        <!-- Result count -->
        <div class="flex items-center justify-between mb-3 text-sm">
            <p class="text-gray-600">
                <span class="font-bold text-[#1B365D] tabular-nums">{{ patients.total.toLocaleString() }}</span>
                {{ isRtl ? 'مريض منقطع' : 'lapsed patients found' }}
            </p>
        </div>

        <!-- Patients table -->
        <div class="bg-white rounded-xl border border-gray-100 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 border-b border-gray-100">
                        <tr class="text-[10px] uppercase tracking-wider text-gray-500">
                            <th class="text-start px-4 py-3">{{ isRtl ? 'المريض' : 'Patient' }}</th>
                            <th class="text-start px-4 py-3 hidden md:table-cell">{{ isRtl ? 'آخر طبيب' : 'Last doctor' }}</th>
                            <th class="text-start px-4 py-3">{{ isRtl ? 'آخر زيارة' : 'Last visit' }}</th>
                            <th class="text-end px-4 py-3 hidden sm:table-cell">{{ isRtl ? 'إجمالي الزيارات' : 'Total visits' }}</th>
                            <th class="text-start px-4 py-3">{{ isRtl ? 'الهاتف' : 'Phone' }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        <tr v-for="p in patients.data" :key="p.id" class="hover:bg-gray-50/50 transition">
                            <td class="px-4 py-3">
                                <Link :href="`/admin/patients/${p.id}`" class="font-medium text-gray-800 hover:text-[#1B365D] hover:underline">
                                    {{ p.full_name }}
                                </Link>
                                <p class="text-[10px] text-gray-400 font-mono mt-0.5">{{ p.file_number || '-' }}</p>
                            </td>
                            <td class="px-4 py-3 text-gray-600 hidden md:table-cell">
                                {{ locale === 'ar' ? p.last_doctor_name_ar : p.last_doctor_name_en }}
                            </td>
                            <td class="px-4 py-3">
                                <p class="text-gray-700 tabular-nums">{{ p.last_visit_date }}</p>
                                <p class="text-[10px] mt-0.5"
                                   :class="p.days_since > 365 ? 'text-red-600 font-bold' : (p.days_since > 180 ? 'text-amber-600' : 'text-gray-400')">
                                    {{ Math.floor(p.days_since) }} {{ isRtl ? 'يوم' : 'days ago' }}
                                </p>
                            </td>
                            <td class="px-4 py-3 text-end font-semibold text-[#1B365D] tabular-nums hidden sm:table-cell">
                                {{ p.total_visits }}
                            </td>
                            <td class="px-4 py-3">
                                <PhoneWithWhatsApp v-if="p.phone" :phone="p.phone" :message="recallMessage(p)" />
                                <span v-else class="text-gray-300">-</span>
                            </td>
                        </tr>
                        <tr v-if="!patients.data.length">
                            <td colspan="5" class="px-4 py-12 text-center text-sm text-gray-400">
                                {{ isRtl ? 'لا يوجد مرضى مطابقين للمعايير' : 'No matching patients' }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-if="patients.last_page > 1" class="p-4 border-t border-gray-100 flex items-center justify-center flex-wrap gap-2">
                <Link v-for="link in patients.links" :key="link.label"
                      :href="link.url || '#'"
                      v-html="link.label"
                      :class="[
                        'px-3 py-1.5 rounded-lg text-xs font-medium border',
                        link.active ? 'bg-[#1B365D] text-white border-[#1B365D]'
                                    : link.url ? 'bg-white text-gray-600 border-gray-200 hover:bg-gray-50'
                                               : 'bg-gray-50 text-gray-300 border-gray-100 cursor-not-allowed'
                      ]" />
            </div>
        </div>
    </div>
</template>
