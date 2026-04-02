<script setup>
import { computed, ref, onMounted } from 'vue';
import { Link, router, useForm, usePage } from '@inertiajs/vue3';
import SecretaryLayout from '@/Layouts/SecretaryLayout.vue';

defineOptions({ layout: SecretaryLayout });

const page = usePage();
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const props = defineProps({
    leaves: Object,
});

const showModal = ref(false);

const form = useForm({
    leave_type: '',
    start_date: '',
    end_date: '',
    reason: '',
});

function openModal() {
    form.reset();
    form.clearErrors();
    showModal.value = true;
}

function closeModal() {
    showModal.value = false;
}

function submitLeave() {
    form.post('/secretary/my-leaves', {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            showModal.value = false;
        },
    });
}

function formatDate(date) {
    if (!date) return '-';
    return new Date(date).toLocaleDateString(isRtl.value ? 'ar-EG' : 'en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
}

function daysBetween(start, end) {
    if (!start || !end) return 0;
    const d1 = new Date(start);
    const d2 = new Date(end);
    return Math.ceil((d2 - d1) / (1000 * 60 * 60 * 24)) + 1;
}

const typeConfig = {
    annual:   { label: 'Annual',   labelAr: 'سنوية',   bg: 'bg-blue-50',    text: 'text-blue-700',   icon: '☀️' },
    sick:     { label: 'Sick',     labelAr: 'مرضية',   bg: 'bg-red-50',     text: 'text-red-700',    icon: '🏥' },
    personal: { label: 'Personal', labelAr: 'شخصية',   bg: 'bg-purple-50',  text: 'text-purple-700', icon: '👤' },
    unpaid:   { label: 'Unpaid',   labelAr: 'بدون راتب', bg: 'bg-gray-100', text: 'text-gray-700',   icon: '📋' },
};

const statusConfig = {
    pending:  { label: 'Pending',  labelAr: 'معلق',   bg: 'bg-amber-50',  text: 'text-amber-700', dot: 'bg-amber-500' },
    approved: { label: 'Approved', labelAr: 'موافق',  bg: 'bg-green-50',  text: 'text-green-700', dot: 'bg-green-500' },
    rejected: { label: 'Rejected', labelAr: 'مرفوض',  bg: 'bg-red-50',    text: 'text-red-700',   dot: 'bg-red-500' },
};

function getType(type) {
    return typeConfig[type] || { label: type, labelAr: type, bg: 'bg-gray-100', text: 'text-gray-700', icon: '📋' };
}

function getStatusStyle(status) {
    return statusConfig[status] || { label: status, labelAr: status, bg: 'bg-gray-100', text: 'text-gray-700', dot: 'bg-gray-500' };
}

// Stats
const pendingCount = computed(() => props.leaves?.data?.filter(l => l.status === 'pending').length || 0);
const approvedCount = computed(() => props.leaves?.data?.filter(l => l.status === 'approved').length || 0);
const rejectedCount = computed(() => props.leaves?.data?.filter(l => l.status === 'rejected').length || 0);

const headerLoaded = ref(false);
const cardsLoaded = ref(false);
onMounted(() => {
    setTimeout(() => { headerLoaded.value = true; }, 50);
    setTimeout(() => { cardsLoaded.value = true; }, 200);
});

const leaveTypeOptions = [
    { value: 'annual', label: 'Annual', labelAr: 'سنوية' },
    { value: 'sick', label: 'Sick', labelAr: 'مرضية' },
    { value: 'personal', label: 'Personal', labelAr: 'شخصية' },
    { value: 'unpaid', label: 'Unpaid', labelAr: 'بدون راتب' },
];
</script>

<template>
    <div>
        <!-- ═══ HERO HEADER ═══ -->
        <div class="relative -mx-4 sm:-mx-6 lg:-mx-8 -mt-4 sm:-mt-6 mb-8 px-4 sm:px-6 lg:px-8 pt-8 pb-10 bg-gradient-to-br from-gray-900 via-gray-800 to-gray-900 overflow-hidden transition-all duration-700" :class="headerLoaded ? 'opacity-100 translate-y-0' : 'opacity-0 -translate-y-4'">
            <div class="absolute inset-0 opacity-10" style="background: radial-gradient(circle at 20% 60%, #0d9488 0%, transparent 60%)"></div>
            <div class="relative z-10">
                <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4">
                    <div>
                        <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-white/10 backdrop-blur-sm mb-3">
                            <span class="w-2 h-2 rounded-full bg-[#0d9488] animate-pulse"></span>
                            <span class="text-xs font-semibold text-gray-300">{{ isRtl ? 'الموارد البشرية' : 'HR Module' }}</span>
                        </div>
                        <h1 class="text-2xl sm:text-3xl font-bold text-white">{{ isRtl ? 'إجازاتي' : 'My Leaves' }}</h1>
                        <p class="text-sm text-gray-400 mt-1.5">{{ isRtl ? 'عرض وإدارة طلبات الإجازات' : 'View and manage your leave requests' }}</p>
                    </div>
                    <button @click="openModal" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-sm font-semibold text-white bg-[#0d9488] hover:bg-[#0b8278] shadow-lg shadow-[#0d9488]/20 transition-all">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                        {{ isRtl ? 'طلب إجازة' : 'Request Leave' }}
                    </button>
                </div>

                <!-- Stats -->
                <div class="grid grid-cols-3 gap-4 mt-6">
                    <div class="bg-white/5 backdrop-blur-sm rounded-xl p-4 border border-white/10">
                        <p class="text-xs text-gray-400 font-medium">{{ isRtl ? 'معلقة' : 'Pending' }}</p>
                        <p class="text-2xl font-bold text-amber-400 mt-1">{{ pendingCount }}</p>
                    </div>
                    <div class="bg-white/5 backdrop-blur-sm rounded-xl p-4 border border-white/10">
                        <p class="text-xs text-gray-400 font-medium">{{ isRtl ? 'موافق عليها' : 'Approved' }}</p>
                        <p class="text-2xl font-bold text-green-400 mt-1">{{ approvedCount }}</p>
                    </div>
                    <div class="bg-white/5 backdrop-blur-sm rounded-xl p-4 border border-white/10">
                        <p class="text-xs text-gray-400 font-medium">{{ isRtl ? 'مرفوضة' : 'Rejected' }}</p>
                        <p class="text-2xl font-bold text-red-400 mt-1">{{ rejectedCount }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- ═══ LEAVE CARDS ═══ -->
        <div class="space-y-3 transition-all duration-500" :class="cardsLoaded ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'">
            <div
                v-for="leave in leaves.data"
                :key="leave.id"
                class="bg-white rounded-2xl shadow-sm border border-gray-100/80 hover:shadow-md transition-all duration-300 overflow-hidden"
            >
                <div class="flex flex-col sm:flex-row sm:items-center gap-4 p-5">
                    <!-- Type Icon -->
                    <div class="flex items-center gap-3 flex-1 min-w-0">
                        <div :class="[getType(leave.leave_type).bg]" class="w-12 h-12 rounded-xl flex items-center justify-center text-xl flex-shrink-0">
                            {{ getType(leave.leave_type).icon }}
                        </div>
                        <div class="min-w-0">
                            <div class="flex items-center gap-2">
                                <span :class="[getType(leave.leave_type).bg, getType(leave.leave_type).text]" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-bold">
                                    {{ isRtl ? getType(leave.leave_type).labelAr : getType(leave.leave_type).label }}
                                </span>
                                <span :class="[getStatusStyle(leave.status).bg, getStatusStyle(leave.status).text]" class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold">
                                    <span :class="getStatusStyle(leave.status).dot" class="w-1.5 h-1.5 rounded-full"></span>
                                    {{ isRtl ? getStatusStyle(leave.status).labelAr : getStatusStyle(leave.status).label }}
                                </span>
                            </div>
                            <p v-if="leave.reason" class="text-xs text-gray-400 mt-1 truncate max-w-[300px]">{{ leave.reason }}</p>
                        </div>
                    </div>

                    <!-- Date Range -->
                    <div class="flex items-center gap-3">
                        <div class="text-center">
                            <p class="text-[10px] text-gray-400 font-semibold uppercase">{{ isRtl ? 'من' : 'From' }}</p>
                            <p class="text-sm font-semibold text-gray-700 mt-0.5">{{ formatDate(leave.start_date) }}</p>
                        </div>
                        <svg class="w-4 h-4 text-gray-300 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" /></svg>
                        <div class="text-center">
                            <p class="text-[10px] text-gray-400 font-semibold uppercase">{{ isRtl ? 'إلى' : 'To' }}</p>
                            <p class="text-sm font-semibold text-gray-700 mt-0.5">{{ formatDate(leave.end_date) }}</p>
                        </div>
                        <div class="w-10 h-10 rounded-xl bg-[#0d9488]/5 flex flex-col items-center justify-center flex-shrink-0">
                            <span class="text-lg font-black text-[#0d9488] leading-none">{{ daysBetween(leave.start_date, leave.end_date) }}</span>
                            <span class="text-[8px] text-[#0d9488] font-bold">{{ isRtl ? 'يوم' : 'days' }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Empty State -->
        <div v-if="!leaves.data || leaves.data.length === 0" class="py-16 text-center">
            <div class="w-16 h-16 mx-auto mb-4 rounded-2xl bg-teal-50 flex items-center justify-center">
                <svg class="w-8 h-8 text-teal-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
            </div>
            <p class="text-sm font-semibold text-gray-500">{{ isRtl ? 'لا توجد طلبات إجازة' : 'No leave requests found' }}</p>
            <p class="text-xs text-gray-400 mt-1">{{ isRtl ? 'ستظهر طلبات إجازتك هنا' : 'Your leave requests will appear here' }}</p>
        </div>

        <!-- Pagination -->
        <div v-if="leaves.links?.length > 3" class="flex items-center justify-center gap-1 mt-6">
            <template v-for="link in leaves.links" :key="link.label">
                <Link v-if="link.url" :href="link.url" class="px-3 py-1.5 rounded-lg text-xs font-medium transition-colors" :class="link.active ? 'bg-[#0d9488] text-white shadow-sm' : 'text-gray-500 hover:bg-white hover:shadow-sm'" v-html="link.label" preserve-state />
                <span v-else class="px-3 py-1.5 text-xs text-gray-300" v-html="link.label" />
            </template>
        </div>

        <!-- ═══ REQUEST LEAVE MODAL ═══ -->
        <Teleport to="body">
            <Transition enter-active-class="transition-all duration-200" enter-from-class="opacity-0" enter-to-class="opacity-100" leave-active-class="transition-all duration-150" leave-from-class="opacity-100" leave-to-class="opacity-0">
                <div v-if="showModal" class="fixed inset-0 z-[9999] flex items-center justify-center p-4 bg-gray-900/50 backdrop-blur-sm" @click.self="closeModal">
                    <div class="bg-white rounded-2xl shadow-2xl max-w-lg w-full overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-100 bg-gradient-to-r from-[#0d9488]/5 to-transparent">
                            <h3 class="text-lg font-bold text-gray-900">{{ isRtl ? 'طلب إجازة' : 'Request Leave' }}</h3>
                            <p class="text-sm text-gray-500 mt-0.5">{{ isRtl ? 'أدخل تفاصيل طلب الإجازة' : 'Fill in the details to submit a leave request' }}</p>
                        </div>

                        <form @submit.prevent="submitLeave" class="p-6 space-y-4">
                            <!-- Leave Type Visual Selector -->
                            <div>
                                <label class="block text-xs font-semibold text-gray-500 mb-2 uppercase tracking-wide">{{ isRtl ? 'نوع الإجازة *' : 'Leave Type *' }}</label>
                                <div class="grid grid-cols-4 gap-2">
                                    <button v-for="opt in leaveTypeOptions" :key="opt.value" type="button" @click="form.leave_type = opt.value"
                                        class="flex flex-col items-center gap-1 p-3 rounded-xl border-2 transition-all text-center"
                                        :class="form.leave_type === opt.value ? 'border-[#0d9488] bg-[#0d9488]/5 shadow-sm' : 'border-gray-100 hover:border-gray-200'"
                                    >
                                        <span class="text-lg">{{ getType(opt.value).icon }}</span>
                                        <span class="text-[11px] font-semibold" :class="form.leave_type === opt.value ? 'text-[#0d9488]' : 'text-gray-600'">{{ isRtl ? opt.labelAr : opt.label }}</span>
                                    </button>
                                </div>
                                <p v-if="form.errors.leave_type" class="mt-1 text-xs text-red-600">{{ form.errors.leave_type }}</p>
                            </div>

                            <!-- Dates -->
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-semibold text-gray-500 mb-1.5 uppercase tracking-wide">{{ isRtl ? 'تاريخ البداية *' : 'Start Date *' }}</label>
                                    <input v-model="form.start_date" type="date" required :max="form.end_date || undefined" class="w-full rounded-xl border border-gray-200 px-3.5 py-2.5 text-sm focus:border-[#0d9488] focus:ring-2 focus:ring-[#0d9488]/30" />
                                    <p v-if="form.errors.start_date" class="mt-1 text-xs text-red-600">{{ form.errors.start_date }}</p>
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-gray-500 mb-1.5 uppercase tracking-wide">{{ isRtl ? 'تاريخ النهاية *' : 'End Date *' }}</label>
                                    <input v-model="form.end_date" type="date" required :min="form.start_date || undefined" class="w-full rounded-xl border border-gray-200 px-3.5 py-2.5 text-sm focus:border-[#0d9488] focus:ring-2 focus:ring-[#0d9488]/30" />
                                    <p v-if="form.errors.end_date" class="mt-1 text-xs text-red-600">{{ form.errors.end_date }}</p>
                                </div>
                            </div>

                            <!-- Reason -->
                            <div>
                                <label class="block text-xs font-semibold text-gray-500 mb-1.5 uppercase tracking-wide">{{ isRtl ? 'السبب' : 'Reason' }}</label>
                                <textarea v-model="form.reason" rows="3" :placeholder="isRtl ? 'اذكر سبب طلب الإجازة...' : 'Provide a reason for your leave request...'" class="w-full rounded-xl border border-gray-200 px-3.5 py-2.5 text-sm focus:border-[#0d9488] focus:ring-2 focus:ring-[#0d9488]/30 resize-none"></textarea>
                                <p v-if="form.errors.reason" class="mt-1 text-xs text-red-600">{{ form.errors.reason }}</p>
                            </div>

                            <!-- Actions -->
                            <div class="flex items-center justify-end gap-3 pt-2">
                                <button type="button" @click="closeModal" class="px-4 py-2.5 text-sm font-medium text-gray-600 bg-gray-100 hover:bg-gray-200 rounded-xl transition-colors">{{ isRtl ? 'إلغاء' : 'Cancel' }}</button>
                                <button type="submit" :disabled="form.processing" class="px-6 py-2.5 bg-[#0d9488] text-white text-sm font-semibold rounded-xl hover:bg-[#0b8278] transition-colors disabled:opacity-50 shadow-lg shadow-[#0d9488]/20">
                                    {{ form.processing ? (isRtl ? 'جاري الإرسال...' : 'Submitting...') : (isRtl ? 'إرسال الطلب' : 'Submit Request') }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </Transition>
        </Teleport>
    </div>
</template>
