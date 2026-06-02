<script setup>
import { computed, ref, watch } from 'vue';
import { Link, router, useForm, usePage } from '@inertiajs/vue3';
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

// ─── Bulk SMS broadcast ──────────────────────────────────────
const showBulkConfirm = ref(false);
const bulkForm = useForm({
    days: days,
    module: module,
    doctor_id: doctorId,
    search: search,
});

function openBulk() {
    bulkForm.days      = days.value;
    bulkForm.module    = module.value;
    bulkForm.doctor_id = doctorId.value;
    bulkForm.search    = search.value;
    showBulkConfirm.value = true;
}

function sendBulk() {
    bulkForm.post('/admin/recall/send-sms', {
        preserveScroll: true,
        onSuccess: () => { showBulkConfirm.value = false; },
    });
}

const cappedTotal = computed(() => Math.min(props.patients?.total || 0, 500));
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

        <!-- Result count + bulk send -->
        <div class="flex items-center justify-between mb-3 text-sm flex-wrap gap-2">
            <p class="text-gray-600">
                <span class="font-bold text-[#1B365D] tabular-nums">{{ patients.total.toLocaleString() }}</span>
                {{ isRtl ? 'مريض منقطع' : 'lapsed patients found' }}
            </p>
            <button v-if="patients.total > 0" type="button" @click="openBulk"
                    class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-[#1B365D] hover:bg-[#22406F] text-white text-xs font-bold shadow-sm transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                </svg>
                {{ isRtl ? 'إرسال SMS لكل المنقطعين' : 'Send recall SMS to all' }}
            </button>
        </div>

        <!-- Bulk SMS confirmation modal -->
        <div v-if="showBulkConfirm"
             class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4"
             @click.self="showBulkConfirm = false">
            <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full p-6">
                <div class="flex items-start gap-3 mb-4">
                    <div class="w-10 h-10 rounded-xl bg-amber-100 flex items-center justify-center flex-shrink-0">
                        <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    </div>
                    <div class="min-w-0">
                        <h3 class="text-base font-bold text-gray-800">
                            {{ isRtl ? 'تأكيد إرسال الرسائل' : 'Confirm bulk send' }}
                        </h3>
                        <p class="text-xs text-gray-500 mt-0.5">
                            {{ isRtl ? 'هذا الإجراء لا يمكن التراجع عنه' : 'This action cannot be undone' }}
                        </p>
                    </div>
                </div>

                <div class="bg-gray-50 rounded-lg p-3 mb-4 text-sm text-gray-700 space-y-1">
                    <p>
                        {{ isRtl ? 'سيتم إرسال SMS تذكير إلى' : 'A recall SMS will be sent to' }}
                        <span class="font-bold text-[#1B365D]">{{ cappedTotal }}</span>
                        {{ isRtl ? 'مريض كحد أقصى' : 'patients (max)' }}
                    </p>
                    <p class="text-[11px] text-gray-500">
                         {{ isRtl
                            ? 'يتجاهل المرضى الذين لم يفعّلوا الاستقبال التسويقي.'
                            : 'Patients who opted out of marketing SMS will be skipped.' }}
                    </p>
                </div>

                <div class="bg-gradient-to-r from-[#FAF7F0] to-white border border-[#C4A265]/30 rounded-lg p-3 mb-4">
                    <p class="text-[10px] font-bold text-[#8B7043] uppercase tracking-wider mb-1">
                        {{ isRtl ? 'مثال للرسالة' : 'Sample message' }}
                    </p>
                    <p class="text-xs text-gray-700">
                        {{ isRtl
                            ? 'مرحباً [اسم المريض]، حان وقت زيارتك الدورية. احجزي الآن: [رقم العيادة]'
                            : 'Hi [Patient Name], time for your follow-up visit. Book now: [Clinic Phone]' }}
                    </p>
                </div>

                <div class="flex items-center gap-2 justify-end">
                    <button type="button" @click="showBulkConfirm = false"
                            class="px-4 py-2 rounded-lg bg-white border border-gray-200 text-gray-600 text-sm hover:bg-gray-50">
                        {{ isRtl ? 'إلغاء' : 'Cancel' }}
                    </button>
                    <button type="button" @click="sendBulk" :disabled="bulkForm.processing"
                            class="px-4 py-2 rounded-lg bg-[#1B365D] hover:bg-[#22406F] text-white text-sm font-bold disabled:opacity-50">
                        {{ bulkForm.processing
                            ? (isRtl ? 'جارٍ الإرسال...' : 'Sending...')
                            : (isRtl ? 'إرسال الآن' : 'Send now') }}
                    </button>
                </div>
            </div>
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
