<script setup>
import { ref, computed } from 'vue';
import { Link, router, useForm, usePage } from '@inertiajs/vue3';
import DoctorLayout from '@/Layouts/DoctorLayout.vue';

defineOptions({ layout: DoctorLayout });

const page = usePage();
const locale = computed(() => page.props.locale || 'ar');
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const props = defineProps({
    leaves: Object,
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
    annual: { label: 'Annual', bg: 'bg-blue-50', text: 'text-blue-700' },
    sick: { label: 'Sick', bg: 'bg-red-50', text: 'text-red-700' },
    personal: { label: 'Personal', bg: 'bg-purple-50', text: 'text-purple-700' },
    unpaid: { label: 'Unpaid', bg: 'bg-gray-100', text: 'text-gray-600' },
};

const statusConfig = {
    pending: { label: 'Pending', bg: 'bg-amber-50', text: 'text-amber-700', dot: 'bg-amber-500' },
    approved: { label: 'Approved', bg: 'bg-emerald-50', text: 'text-emerald-700', dot: 'bg-emerald-500' },
    rejected: { label: 'Rejected', bg: 'bg-red-50', text: 'text-red-700', dot: 'bg-red-500' },
};

function formatDate(dateStr) {
    if (!dateStr) return '-';
    return new Date(dateStr).toLocaleDateString('en-GB', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    });
}
</script>

<template>
    <div class="space-y-6">
        <!-- Header -->
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">{{ $t('a_my_leaves') }}</h1>
                <p class="text-sm text-gray-500 mt-1">{{ isRtl ? 'إدارة طلبات الإجازة' : 'Manage your leave requests' }}</p>
            </div>
            <button @click="showModal = true" class="inline-flex items-center gap-2 px-5 py-2.5 bg-[#4f46e5] text-white text-sm font-semibold rounded-xl hover:bg-[#4338ca] transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                {{ isRtl ? 'طلب إجازة' : 'Request Leave' }}
            </button>
        </div>

        <!-- Leaves Table -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100/80 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-gray-50/80">
                            <th class="ltr:text-left rtl:text-right px-6 py-3 text-[10px] font-semibold text-gray-400 uppercase">{{ isRtl ? 'النوع' : 'Type' }}</th>
                            <th class="ltr:text-left rtl:text-right px-6 py-3 text-[10px] font-semibold text-gray-400 uppercase">{{ isRtl ? 'تاريخ البداية' : 'Start Date' }}</th>
                            <th class="ltr:text-left rtl:text-right px-6 py-3 text-[10px] font-semibold text-gray-400 uppercase">{{ isRtl ? 'تاريخ النهاية' : 'End Date' }}</th>
                            <th class="ltr:text-left rtl:text-right px-6 py-3 text-[10px] font-semibold text-gray-400 uppercase">{{ isRtl ? 'السبب' : 'Reason' }}</th>
                            <th class="text-center px-6 py-3 text-[10px] font-semibold text-gray-400 uppercase">{{ isRtl ? 'الحالة' : 'Status' }}</th>
                            <th class="ltr:text-left rtl:text-right px-6 py-3 text-[10px] font-semibold text-gray-400 uppercase">{{ isRtl ? 'تاريخ التقديم' : 'Submitted' }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="leave in leaves.data" :key="leave.id" class="hover:bg-gray-50/50 transition-colors">
                            <td class="px-6 py-3">
                                <span class="text-[11px] font-semibold px-3 py-1 rounded-full"
                                    :class="[typeConfig[leave.leave_type]?.bg || 'bg-gray-100', typeConfig[leave.leave_type]?.text || 'text-gray-600']"
                                >
                                    {{ typeConfig[leave.leave_type]?.label || leave.leave_type }}
                                </span>
                            </td>
                            <td class="px-6 py-3 text-gray-600">{{ formatDate(leave.start_date) }}</td>
                            <td class="px-6 py-3 text-gray-600">{{ formatDate(leave.end_date) }}</td>
                            <td class="px-6 py-3 text-gray-500 max-w-[250px] truncate">{{ leave.reason || '-' }}</td>
                            <td class="px-6 py-3 text-center">
                                <span v-if="statusConfig[leave.status]"
                                    class="inline-flex items-center gap-1.5 text-[11px] font-semibold px-3 py-1 rounded-full"
                                    :class="[statusConfig[leave.status].bg, statusConfig[leave.status].text]"
                                >
                                    <span class="w-1.5 h-1.5 rounded-full" :class="statusConfig[leave.status].dot"></span>
                                    {{ statusConfig[leave.status].label }}
                                </span>
                                <span v-else class="text-xs text-gray-400">{{ leave.status || '-' }}</span>
                            </td>
                            <td class="px-6 py-3 text-gray-500 text-xs">{{ formatDate(leave.created_at) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-if="!leaves.data || leaves.data.length === 0" class="py-16 text-center">
                <div class="w-16 h-16 mx-auto bg-gray-50 rounded-2xl flex items-center justify-center mb-3 border border-gray-100">
                    <svg class="w-8 h-8 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                </div>
                <p class="text-sm text-gray-400">{{ isRtl ? 'لا توجد طلبات إجازة' : 'No leave requests found' }}</p>
            </div>

            <!-- Pagination -->
            <div v-if="leaves.links?.length > 3" class="flex items-center justify-center gap-1 px-6 py-4 border-t border-gray-100 bg-gray-50/50">
                <template v-for="link in leaves.links" :key="link.label">
                    <Link v-if="link.url" :href="link.url" class="px-3 py-1.5 rounded-lg text-xs font-medium transition-colors" :class="link.active ? 'bg-[#4f46e5] text-white' : 'text-gray-500 hover:bg-gray-100'" v-html="link.label" preserve-state />
                    <span v-else class="px-3 py-1.5 text-xs text-gray-300" v-html="link.label" />
                </template>
            </div>
        </div>

        <!-- Request Leave Modal -->
        <Teleport to="body">
            <Transition enter-active-class="transition duration-200 ease-out" enter-from-class="opacity-0" enter-to-class="opacity-100" leave-active-class="transition duration-150 ease-in" leave-from-class="opacity-100" leave-to-class="opacity-0">
                <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                    <!-- Backdrop -->
                    <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" @click="closeModal"></div>

                    <!-- Modal -->
                    <div class="relative w-full max-w-lg bg-white rounded-2xl shadow-2xl overflow-hidden">
                        <!-- Header -->
                        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
                            <h2 class="text-lg font-bold text-gray-900">{{ isRtl ? 'طلب إجازة' : 'Request Leave' }}</h2>
                            <button @click="closeModal" class="w-8 h-8 rounded-lg bg-gray-100 hover:bg-gray-200 flex items-center justify-center transition-colors">
                                <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                            </button>
                        </div>

                        <!-- Body -->
                        <form @submit.prevent="submitLeave" class="p-6 space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">{{ isRtl ? 'نوع الإجازة' : 'Leave Type' }}</label>
                                <select v-model="form.leave_type" class="w-full px-4 py-2.5 bg-white border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-[#4f46e5]/20 focus:border-[#4f46e5]">
                                    <option value="annual">{{ isRtl ? 'سنوية' : 'Annual' }}</option>
                                    <option value="sick">{{ isRtl ? 'مرضية' : 'Sick' }}</option>
                                    <option value="personal">{{ isRtl ? 'شخصية' : 'Personal' }}</option>
                                    <option value="unpaid">{{ isRtl ? 'بدون راتب' : 'Unpaid' }}</option>
                                </select>
                                <p v-if="form.errors.leave_type" class="text-xs text-red-500 mt-1">{{ form.errors.leave_type }}</p>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ isRtl ? 'تاريخ البداية' : 'Start Date' }}</label>
                                    <input v-model="form.start_date" type="date" class="w-full px-4 py-2.5 bg-white border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-[#4f46e5]/20 focus:border-[#4f46e5]" />
                                    <p v-if="form.errors.start_date" class="text-xs text-red-500 mt-1">{{ form.errors.start_date }}</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ isRtl ? 'تاريخ النهاية' : 'End Date' }}</label>
                                    <input v-model="form.end_date" type="date" :min="form.start_date || undefined" class="w-full px-4 py-2.5 bg-white border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-[#4f46e5]/20 focus:border-[#4f46e5]" />
                                    <p v-if="form.errors.end_date" class="text-xs text-red-500 mt-1">{{ form.errors.end_date }}</p>
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">{{ isRtl ? 'السبب' : 'Reason' }}</label>
                                <textarea v-model="form.reason" rows="3" :placeholder="isRtl ? 'اذكر سبب الإجازة...' : 'Describe the reason for your leave...'" class="w-full px-4 py-2.5 bg-white border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-[#4f46e5]/20 focus:border-[#4f46e5] resize-none"></textarea>
                                <p v-if="form.errors.reason" class="text-xs text-red-500 mt-1">{{ form.errors.reason }}</p>
                            </div>

                            <!-- Footer -->
                            <div class="flex items-center justify-end gap-3 pt-2">
                                <button type="button" @click="closeModal" class="px-5 py-2.5 text-sm font-medium text-gray-600 bg-gray-100 hover:bg-gray-200 rounded-xl transition-colors">
                                    {{ isRtl ? 'إلغاء' : 'Cancel' }}
                                </button>
                                <button type="submit" :disabled="form.processing" class="px-5 py-2.5 text-sm font-semibold text-white bg-[#4f46e5] hover:bg-[#4338ca] rounded-xl transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                                    <span v-if="form.processing">{{ isRtl ? 'جاري التقديم...' : 'Submitting...' }}</span>
                                    <span v-else>{{ isRtl ? 'تقديم الطلب' : 'Submit Request' }}</span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </Transition>
        </Teleport>
    </div>
</template>
