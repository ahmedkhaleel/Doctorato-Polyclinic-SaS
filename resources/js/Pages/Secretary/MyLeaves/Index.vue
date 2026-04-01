<script setup>
import { computed, ref } from 'vue';
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
    return new Date(date).toLocaleDateString('en-GB', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    });
}

const typeConfig = {
    annual:   { label: 'Annual',   bg: 'bg-blue-50',   text: 'text-blue-700' },
    sick:     { label: 'Sick',     bg: 'bg-red-50',    text: 'text-red-700' },
    personal: { label: 'Personal', bg: 'bg-purple-50', text: 'text-purple-700' },
    unpaid:   { label: 'Unpaid',   bg: 'bg-gray-100',  text: 'text-gray-700' },
};

const statusConfig = {
    pending:  { label: isRtl.value ? 'معلق' : 'Pending',  bg: 'bg-amber-50',   text: 'text-amber-700', dot: 'bg-amber-500' },
    approved: { label: 'Approved', bg: 'bg-green-50',   text: 'text-green-700', dot: 'bg-green-500' },
    rejected: { label: 'Rejected', bg: 'bg-red-50',     text: 'text-red-700',   dot: 'bg-red-500' },
};

function getType(type) {
    return typeConfig[type] || { label: type, bg: 'bg-gray-100', text: 'text-gray-700' };
}

function getStatusStyle(status) {
    return statusConfig[status] || { label: status, bg: 'bg-gray-100', text: 'text-gray-700', dot: 'bg-gray-500' };
}
</script>

<template>
    <div class="space-y-6">
        <!-- Header -->
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">{{ isRtl ? 'إجازاتي' : 'My Leaves' }}</h1>
                <p class="text-sm text-gray-500 mt-1">View and manage your leave requests</p>
            </div>
            <button
                @click="openModal"
                class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-semibold text-white bg-[#0d9488] hover:bg-[#0b8278] shadow-sm hover:shadow-md transition-all duration-200"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Request Leave
            </button>
        </div>

        <!-- Leaves Table -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100/80 overflow-hidden">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-gray-50/80">
                        <th class="ltr:text-left rtl:ltr:text-right rtl:text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">{{ isRtl ? 'النوع' : 'Type' }}</th>
                        <th class="ltr:text-left rtl:ltr:text-right rtl:text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">{{ isRtl ? 'تاريخ البداية' : 'Start Date' }}</th>
                        <th class="ltr:text-left rtl:ltr:text-right rtl:text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">{{ isRtl ? 'تاريخ النهاية' : 'End Date' }}</th>
                        <th class="ltr:text-left rtl:ltr:text-right rtl:text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">{{ isRtl ? 'السبب' : 'Reason' }}</th>
                        <th class="ltr:text-left rtl:ltr:text-right rtl:text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">{{ isRtl ? 'الحالة' : 'Status' }}</th>
                        <th class="ltr:text-left rtl:ltr:text-right rtl:text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Submitted</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <tr v-for="leave in leaves.data" :key="leave.id" class="hover:bg-gray-50/50">
                        <td class="px-6 py-3">
                            <span
                                :class="[getType(leave.leave_type).bg, getType(leave.leave_type).text]"
                                class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-semibold"
                            >
                                {{ getType(leave.leave_type).label }}
                            </span>
                        </td>
                        <td class="px-6 py-3 text-gray-700">{{ formatDate(leave.start_date) }}</td>
                        <td class="px-6 py-3 text-gray-700">{{ formatDate(leave.end_date) }}</td>
                        <td class="px-6 py-3 text-gray-500 max-w-[250px] truncate">{{ leave.reason || '-' }}</td>
                        <td class="px-6 py-3">
                            <span
                                :class="[getStatusStyle(leave.status).bg, getStatusStyle(leave.status).text]"
                                class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-semibold"
                            >
                                <span :class="getStatusStyle(leave.status).dot" class="w-1.5 h-1.5 rounded-full"></span>
                                {{ getStatusStyle(leave.status).label }}
                            </span>
                        </td>
                        <td class="px-6 py-3 text-gray-500">{{ formatDate(leave.created_at) }}</td>
                    </tr>
                </tbody>
            </table>

            <div v-if="!leaves.data || leaves.data.length === 0" class="py-16 text-center">
                <div class="flex flex-col items-center">
                    <div class="w-16 h-16 rounded-2xl bg-teal-50 flex items-center justify-center mb-3">
                        <svg class="w-8 h-8 text-teal-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <p class="text-sm font-medium text-gray-400">No leave requests found</p>
                    <p class="text-xs text-gray-300 mt-1">Your leave requests will appear here</p>
                </div>
            </div>

            <!-- Pagination -->
            <div v-if="leaves.links?.length > 3" class="flex items-center justify-center gap-1 px-6 py-4 border-t border-gray-100">
                <template v-for="link in leaves.links" :key="link.label">
                    <Link
                        v-if="link.url"
                        :href="link.url"
                        class="px-3 py-1.5 rounded-lg text-xs font-medium transition-colors"
                        :class="link.active ? 'bg-[#0d9488] text-white' : 'text-gray-500 hover:bg-gray-100'"
                        v-html="link.label"
                        preserve-state
                    />
                    <span v-else class="px-3 py-1.5 text-xs text-gray-300" v-html="link.label" />
                </template>
            </div>
        </div>

        <!-- Request Leave Modal -->
        <Teleport to="body">
            <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center">
                <!-- Backdrop -->
                <div class="absolute inset-0 bg-black/40" @click="closeModal"></div>

                <!-- Modal -->
                <div class="relative bg-white rounded-2xl shadow-xl max-w-lg w-full mx-4 p-6 z-10">
                    <h3 class="text-lg font-bold text-gray-900 mb-1">{{ isRtl ? 'طلب إجازة' : 'Request Leave' }}</h3>
                    <p class="text-sm text-gray-500 mb-5">Fill in the details to submit a leave request</p>

                    <form @submit.prevent="submitLeave" class="space-y-4">
                        <!-- Leave Type -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Leave Type <span class="text-red-500">*</span></label>
                            <select
                                v-model="form.leave_type"
                                required
                                class="w-full rounded-xl border border-gray-300 px-4 py-2.5 text-sm focus:border-[#0d9488] focus:ring-[#0d9488] focus:outline-none"
                            >
                                <option value="" disabled>Select type</option>
                                <option value="annual">Annual</option>
                                <option value="sick">Sick</option>
                                <option value="personal">Personal</option>
                                <option value="unpaid">Unpaid</option>
                            </select>
                            <p v-if="form.errors.leave_type" class="mt-1 text-sm text-red-600">{{ form.errors.leave_type }}</p>
                        </div>

                        <!-- Dates -->
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Start Date <span class="text-red-500">*</span></label>
                                <input
                                    v-model="form.start_date"
                                    type="date"
                                    required
                                    :max="form.end_date || undefined"
                                    class="w-full rounded-xl border border-gray-300 px-4 py-2.5 text-sm focus:border-[#0d9488] focus:ring-[#0d9488] focus:outline-none"
                                />
                                <p v-if="form.errors.start_date" class="mt-1 text-sm text-red-600">{{ form.errors.start_date }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">End Date <span class="text-red-500">*</span></label>
                                <input
                                    v-model="form.end_date"
                                    type="date"
                                    required
                                    :min="form.start_date || undefined"
                                    class="w-full rounded-xl border border-gray-300 px-4 py-2.5 text-sm focus:border-[#0d9488] focus:ring-[#0d9488] focus:outline-none"
                                />
                                <p v-if="form.errors.end_date" class="mt-1 text-sm text-red-600">{{ form.errors.end_date }}</p>
                            </div>
                        </div>

                        <!-- Reason -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ isRtl ? 'السبب' : 'Reason' }}</label>
                            <textarea
                                v-model="form.reason"
                                rows="3"
                                placeholder="Provide a reason for your leave request..."
                                class="w-full rounded-xl border border-gray-300 px-4 py-2.5 text-sm focus:border-[#0d9488] focus:ring-[#0d9488] focus:outline-none resize-none"
                            ></textarea>
                            <p v-if="form.errors.reason" class="mt-1 text-sm text-red-600">{{ form.errors.reason }}</p>
                        </div>

                        <!-- Actions -->
                        <div class="flex items-center justify-end gap-3 pt-2">
                            <button
                                type="button"
                                @click="closeModal"
                                class="px-4 py-2.5 text-sm font-medium text-gray-600 hover:text-gray-800 transition"
                            >
                                {{ isRtl ? 'إلغاء' : 'Cancel' }}
                            </button>
                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="px-5 py-2.5 bg-[#0d9488] text-white text-sm font-semibold rounded-xl hover:bg-[#0b8278] transition disabled:opacity-50"
                            >
                                {{ form.processing ? 'Submitting...' : 'Submit Request' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>
    </div>
</template>
