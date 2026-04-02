<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue';
import { Link, router, useForm, usePage } from '@inertiajs/vue3';
import DoctorLayout from '@/Layouts/DoctorLayout.vue';

defineOptions({ layout: DoctorLayout });

const page = usePage();
const locale = computed(() => page.props.locale || 'ar');
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const props = defineProps({
    leaves: Object,
});

const headerLoaded = ref(false);
const cardsLoaded = ref(false);

onMounted(() => {
    setTimeout(() => headerLoaded.value = true, 50);
    setTimeout(() => cardsLoaded.value = true, 200);
});

const showModal = ref(false);

const form = useForm({
    leave_type: 'annual',
    start_date: '',
    end_date: '',
    reason: '',
});

function submitLeave() {
    form.post('/doctor/my-leaves', {
        preserveScroll: true,
        onSuccess: () => {
            showModal.value = false;
            form.reset();
        },
    });
}

function closeModal() {
    showModal.value = false;
    form.reset();
    form.clearErrors();
}

const typeConfig = {
    annual: { label: 'Annual', labelAr: 'سنوية', icon: 'M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z', color: 'bg-blue-500', bg: 'bg-blue-50', text: 'text-blue-700' },
    sick: { label: 'Sick', labelAr: 'مرضية', icon: 'M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z', color: 'bg-red-500', bg: 'bg-red-50', text: 'text-red-700' },
    personal: { label: 'Personal', labelAr: 'شخصية', icon: 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z', color: 'bg-purple-500', bg: 'bg-purple-50', text: 'text-purple-700' },
    unpaid: { label: 'Unpaid', labelAr: 'بدون راتب', icon: 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z', color: 'bg-gray-500', bg: 'bg-gray-100', text: 'text-gray-600' },
};

const statusConfig = {
    pending: { label: 'Pending', labelAr: 'قيد الانتظار', bg: 'bg-amber-50', text: 'text-amber-700', dot: 'bg-amber-500', border: 'border-amber-200' },
    approved: { label: 'Approved', labelAr: 'موافق عليه', bg: 'bg-emerald-50', text: 'text-emerald-700', dot: 'bg-emerald-500', border: 'border-emerald-200' },
    rejected: { label: 'Rejected', labelAr: 'مرفوض', bg: 'bg-red-50', text: 'text-red-700', dot: 'bg-red-500', border: 'border-red-200' },
};

function formatDate(dateStr) {
    if (!dateStr) return '-';
    return new Date(dateStr).toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' });
}

function daysCount(start, end) {
    if (!start || !end) return 0;
    const s = new Date(start);
    const e = new Date(end);
    return Math.max(1, Math.ceil((e - s) / (1000 * 60 * 60 * 24)) + 1);
}

const leavesList = computed(() => props.leaves?.data || []);
const pendingCount = computed(() => leavesList.value.filter(l => l.status === 'pending').length);
const approvedCount = computed(() => leavesList.value.filter(l => l.status === 'approved').length);
const rejectedCount = computed(() => leavesList.value.filter(l => l.status === 'rejected').length);
</script>

<template>
    <div class="space-y-6">
        <!-- Hero Header -->
        <div
            class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-gray-900 via-gray-800 to-gray-900 p-6 sm:p-8 transition-all duration-700"
            :class="headerLoaded ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'"
        >
            <div class="absolute top-0 right-0 w-96 h-96 bg-gradient-radial from-teal-500/10 to-transparent rounded-full -translate-y-1/2 translate-x-1/2"></div>
            <div class="absolute bottom-0 left-0 w-64 h-64 bg-gradient-radial from-[#C4A265]/5 to-transparent rounded-full translate-y-1/2 -translate-x-1/4"></div>

            <div class="relative z-10">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <div class="w-14 h-14 rounded-xl bg-gradient-to-br from-teal-400 to-teal-600 flex items-center justify-center shadow-lg shadow-teal-500/20">
                            <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                        </div>
                        <div>
                            <h1 class="text-2xl font-bold text-white">{{ $t('a_my_leaves') }}</h1>
                            <p class="text-sm text-gray-400 mt-0.5">{{ isRtl ? 'إدارة طلبات الإجازة' : 'Manage your leave requests' }}</p>
                        </div>
                    </div>
                    <button @click="showModal = true" class="inline-flex items-center gap-2 px-5 py-2.5 bg-white/10 hover:bg-white/20 backdrop-blur-sm text-white text-sm font-semibold rounded-xl border border-white/10 transition-all duration-200">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                        {{ isRtl ? 'طلب إجازة' : 'Request Leave' }}
                    </button>
                </div>

                <!-- Stats -->
                <div class="grid grid-cols-3 gap-4 mt-6">
                    <div class="bg-white/5 backdrop-blur-sm rounded-xl px-4 py-3 border border-white/10">
                        <p class="text-xs text-gray-400">{{ isRtl ? 'قيد الانتظار' : 'Pending' }}</p>
                        <p class="text-lg font-bold text-amber-400 mt-0.5">{{ pendingCount }}</p>
                    </div>
                    <div class="bg-white/5 backdrop-blur-sm rounded-xl px-4 py-3 border border-white/10">
                        <p class="text-xs text-gray-400">{{ isRtl ? 'موافق عليها' : 'Approved' }}</p>
                        <p class="text-lg font-bold text-emerald-400 mt-0.5">{{ approvedCount }}</p>
                    </div>
                    <div class="bg-white/5 backdrop-blur-sm rounded-xl px-4 py-3 border border-white/10">
                        <p class="text-xs text-gray-400">{{ isRtl ? 'مرفوضة' : 'Rejected' }}</p>
                        <p class="text-lg font-bold text-red-400 mt-0.5">{{ rejectedCount }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Leave Cards -->
        <div
            class="transition-all duration-700"
            :class="cardsLoaded ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-6'"
        >
            <div v-if="leavesList.length" class="space-y-3">
                <div
                    v-for="leave in leavesList"
                    :key="leave.id"
                    class="bg-white rounded-xl border border-gray-100 shadow-sm hover:shadow-md hover:-translate-y-0.5 transition-all duration-300 overflow-hidden"
                >
                    <div class="flex flex-col sm:flex-row sm:items-center gap-4 p-5">
                        <!-- Type Icon -->
                        <div class="flex items-center gap-3 sm:w-44">
                            <div class="w-11 h-11 rounded-xl flex items-center justify-center" :class="typeConfig[leave.leave_type]?.bg || 'bg-gray-100'">
                                <svg class="w-5 h-5" :class="typeConfig[leave.leave_type]?.text || 'text-gray-500'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="typeConfig[leave.leave_type]?.icon || 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z'" />
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm font-bold text-gray-800">
                                    {{ isRtl ? (typeConfig[leave.leave_type]?.labelAr || leave.leave_type) : (typeConfig[leave.leave_type]?.label || leave.leave_type) }}
                                </p>
                                <p class="text-[10px] text-gray-400 font-medium mt-0.5">
                                    {{ daysCount(leave.start_date, leave.end_date) }} {{ isRtl ? 'يوم' : 'days' }}
                                </p>
                            </div>
                        </div>

                        <!-- Dates -->
                        <div class="flex-1 flex items-center gap-3">
                            <div class="flex items-center gap-2 text-sm">
                                <span class="text-gray-400 text-xs">{{ isRtl ? 'من' : 'From' }}</span>
                                <span class="font-semibold text-gray-700 tabular-nums">{{ formatDate(leave.start_date) }}</span>
                            </div>
                            <svg class="w-4 h-4 text-gray-300 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3" /></svg>
                            <div class="flex items-center gap-2 text-sm">
                                <span class="text-gray-400 text-xs">{{ isRtl ? 'إلى' : 'To' }}</span>
                                <span class="font-semibold text-gray-700 tabular-nums">{{ formatDate(leave.end_date) }}</span>
                            </div>
                        </div>

                        <!-- Reason (truncated) -->
                        <div v-if="leave.reason" class="hidden lg:block max-w-[200px]">
                            <p class="text-xs text-gray-400 truncate" :title="leave.reason">{{ leave.reason }}</p>
                        </div>

                        <!-- Status Badge -->
                        <div class="flex items-center gap-3">
                            <span v-if="statusConfig[leave.status]"
                                class="inline-flex items-center gap-1.5 text-[11px] font-semibold px-3 py-1.5 rounded-lg border"
                                :class="[statusConfig[leave.status].bg, statusConfig[leave.status].text, statusConfig[leave.status].border]"
                            >
                                <span class="w-1.5 h-1.5 rounded-full" :class="statusConfig[leave.status].dot"></span>
                                {{ isRtl ? statusConfig[leave.status].labelAr : statusConfig[leave.status].label }}
                            </span>
                            <span v-else class="text-xs text-gray-400">{{ leave.status || '-' }}</span>
                        </div>
                    </div>

                    <!-- Reason row on mobile -->
                    <div v-if="leave.reason" class="lg:hidden px-5 pb-4 -mt-2">
                        <p class="text-xs text-gray-400"><span class="font-medium text-gray-500">{{ isRtl ? 'السبب:' : 'Reason:' }}</span> {{ leave.reason }}</p>
                    </div>
                </div>
            </div>

            <!-- Empty State -->
            <div v-else class="text-center py-16 bg-white rounded-xl border border-gray-100">
                <div class="w-16 h-16 mx-auto bg-gray-100 rounded-full flex items-center justify-center mb-4">
                    <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                </div>
                <p class="text-sm font-medium text-gray-500">{{ isRtl ? 'لا توجد طلبات إجازة' : 'No leave requests found' }}</p>
                <p class="text-xs text-gray-400 mt-1">{{ isRtl ? 'انقر على "طلب إجازة" لتقديم طلب جديد' : 'Click "Request Leave" to submit a new request' }}</p>
            </div>

            <!-- Pagination -->
            <div v-if="leaves.links?.length > 3" class="flex items-center justify-center gap-1 mt-4">
                <template v-for="link in leaves.links" :key="link.label">
                    <Link v-if="link.url" :href="link.url" class="px-3 py-1.5 rounded-lg text-xs font-medium transition-colors" :class="link.active ? 'bg-teal-600 text-white' : 'text-gray-500 hover:bg-gray-100 bg-white border border-gray-200'" v-html="link.label" preserve-state />
                    <span v-else class="px-3 py-1.5 text-xs text-gray-300" v-html="link.label" />
                </template>
            </div>
        </div>

        <!-- Request Leave Modal -->
        <Teleport to="body">
            <Transition enter-active-class="transition duration-200 ease-out" enter-from-class="opacity-0" enter-to-class="opacity-100" leave-active-class="transition duration-150 ease-in" leave-from-class="opacity-100" leave-to-class="opacity-0">
                <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                    <!-- Backdrop -->
                    <div class="absolute inset-0 bg-gray-900/50 backdrop-blur-sm" @click="closeModal"></div>

                    <!-- Modal -->
                    <Transition enter-active-class="transition duration-200 ease-out" enter-from-class="opacity-0 scale-95" enter-to-class="opacity-100 scale-100" leave-active-class="transition duration-150 ease-in" leave-from-class="opacity-100 scale-100" leave-to-class="opacity-0 scale-95">
                        <div v-if="showModal" class="relative w-full max-w-lg bg-white rounded-2xl shadow-2xl overflow-hidden">
                            <!-- Header -->
                            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 bg-gray-50/50">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-xl bg-teal-50 flex items-center justify-center">
                                        <svg class="w-5 h-5 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                                    </div>
                                    <h2 class="text-lg font-bold text-gray-900">{{ isRtl ? 'طلب إجازة' : 'Request Leave' }}</h2>
                                </div>
                                <button @click="closeModal" class="w-8 h-8 rounded-lg bg-gray-100 hover:bg-gray-200 flex items-center justify-center transition-colors">
                                    <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                                </button>
                            </div>

                            <!-- Body -->
                            <form @submit.prevent="submitLeave" class="p-6 space-y-4">
                                <!-- Leave Type Cards -->
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">{{ isRtl ? 'نوع الإجازة' : 'Leave Type' }}</label>
                                    <div class="grid grid-cols-2 gap-2">
                                        <button
                                            v-for="(config, key) in typeConfig"
                                            :key="key"
                                            type="button"
                                            @click="form.leave_type = key"
                                            class="flex items-center gap-2.5 px-3 py-2.5 rounded-xl border-2 transition-all text-start"
                                            :class="form.leave_type === key ? 'border-teal-500 bg-teal-50/50' : 'border-gray-100 hover:border-gray-200 bg-white'"
                                        >
                                            <div class="w-8 h-8 rounded-lg flex items-center justify-center" :class="config.bg">
                                                <svg class="w-4 h-4" :class="config.text" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="config.icon" /></svg>
                                            </div>
                                            <span class="text-xs font-semibold" :class="form.leave_type === key ? 'text-teal-700' : 'text-gray-600'">
                                                {{ isRtl ? config.labelAr : config.label }}
                                            </span>
                                        </button>
                                    </div>
                                    <p v-if="form.errors.leave_type" class="text-xs text-red-500 mt-1">{{ form.errors.leave_type }}</p>
                                </div>

                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-700 mb-1.5">{{ isRtl ? 'تاريخ البداية' : 'Start Date' }}</label>
                                        <input v-model="form.start_date" type="date" class="w-full px-4 py-2.5 bg-white border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-teal-500/20 focus:border-teal-500 transition" />
                                        <p v-if="form.errors.start_date" class="text-xs text-red-500 mt-1">{{ form.errors.start_date }}</p>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-700 mb-1.5">{{ isRtl ? 'تاريخ النهاية' : 'End Date' }}</label>
                                        <input v-model="form.end_date" type="date" :min="form.start_date || undefined" class="w-full px-4 py-2.5 bg-white border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-teal-500/20 focus:border-teal-500 transition" />
                                        <p v-if="form.errors.end_date" class="text-xs text-red-500 mt-1">{{ form.errors.end_date }}</p>
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">{{ isRtl ? 'السبب' : 'Reason' }}</label>
                                    <textarea v-model="form.reason" rows="3" :placeholder="isRtl ? 'اذكر سبب الإجازة...' : 'Describe the reason for your leave...'" class="w-full px-4 py-2.5 bg-white border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-teal-500/20 focus:border-teal-500 resize-none transition"></textarea>
                                    <p v-if="form.errors.reason" class="text-xs text-red-500 mt-1">{{ form.errors.reason }}</p>
                                </div>

                                <!-- Footer -->
                                <div class="flex items-center justify-end gap-3 pt-2">
                                    <button type="button" @click="closeModal" class="px-5 py-2.5 text-sm font-medium text-gray-600 bg-gray-100 hover:bg-gray-200 rounded-xl transition-colors">
                                        {{ isRtl ? 'إلغاء' : 'Cancel' }}
                                    </button>
                                    <button type="submit" :disabled="form.processing" class="px-5 py-2.5 text-sm font-semibold text-white bg-teal-600 hover:bg-teal-700 rounded-xl transition-colors disabled:opacity-50 disabled:cursor-not-allowed inline-flex items-center gap-2">
                                        <svg v-if="form.processing" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                                        <span v-if="form.processing">{{ isRtl ? 'جاري التقديم...' : 'Submitting...' }}</span>
                                        <span v-else>{{ isRtl ? 'تقديم الطلب' : 'Submit Request' }}</span>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </Transition>
                </div>
            </Transition>
        </Teleport>
    </div>
</template>
