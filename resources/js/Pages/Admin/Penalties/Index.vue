<script setup>
import { ref, computed } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { usePermissions } from '@/Composables/usePermissions.js';
import { useCurrency } from '@/Composables/useCurrency.js';
import { useConfirm } from '@/Composables/useConfirm.js';

const { can } = usePermissions();
const { confirm } = useConfirm();

const page = usePage();
const locale = computed(() => page.props.locale || 'ar');
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const props = defineProps({
    penalties: Object,
    employees: Array,
    filters: Object,
    stats: Object,
});

const typeFilter = ref(props.filters?.type || '');
const showModal = ref(false);

const form = ref({
    employee_id: '',
    type: 'penalty',
    amount: '',
    reason: '',
    date: '',
});

const formErrors = ref({});
const formProcessing = ref(false);

function applyFilter(type) {
    typeFilter.value = type;
    router.get('/admin/penalties', {
        type: type || undefined,
    }, {
        preserveState: true,
        replace: true,
    });
}

const { formatCurrency } = useCurrency();

function openModal() {
    form.value = {
        employee_id: '',
        type: 'penalty',
        amount: '',
        reason: '',
        date: '',
    };
    formErrors.value = {};
    showModal.value = true;
}

function closeModal() {
    showModal.value = false;
    formErrors.value = {};
}

function submitRecord() {
    formProcessing.value = true;
    router.post('/admin/penalties', form.value, {
        preserveScroll: true,
        onSuccess: () => {
            closeModal();
            formProcessing.value = false;
        },
        onError: (errors) => {
            formErrors.value = errors;
            formProcessing.value = false;
        },
    });
}

function deleteRecord(id) {
    confirm(isRtl.value ? 'هل أنت متأكد من حذف هذا السجل؟' : 'Are you sure you want to delete this record?', () => {
        router.post(`/admin/penalties/${id}/delete`, { preserveScroll: true });
    });
}
</script>

<template>
    <AdminLayout :title="$t('a_penalties_rewards')">
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <h1 class="text-xl md:text-2xl font-bold text-gray-800">{{ $t('a_penalties_rewards') }}</h1>
                <button
                    v-if="can('penalties.create')"
                    @click="openModal"
                    class="inline-flex items-center px-4 py-2 rounded-lg text-white text-sm font-medium transition"
                    style="background-color: #C4A265;"
                >
                    <svg :class="['w-4 h-4', isRtl ? 'ml-2' : 'mr-2']" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    {{ $t('a_add_record') }}
                </button>
            </div>

            <!-- Stat Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-white rounded-lg shadow-sm p-5 ltr:border-l-4 rtl:border-r-4 border-red-400">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-500">{{ $t('a_total_penalties') }}</p>
                            <p class="text-xl md:text-2xl font-bold text-red-600">{{ stats?.total_penalties || 0 }}</p>
                        </div>
                        <div class="w-10 h-10 rounded-full bg-red-100 flex items-center justify-center">
                            <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4" />
                            </svg>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-lg shadow-sm p-5 ltr:border-l-4 rtl:border-r-4 border-emerald-400">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-500">{{ $t('a_total_rewards') }}</p>
                            <p class="text-xl md:text-2xl font-bold text-emerald-600">{{ stats?.total_rewards || 0 }}</p>
                        </div>
                        <div class="w-10 h-10 rounded-full bg-emerald-100 flex items-center justify-center">
                            <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-lg shadow-sm p-5 ltr:border-l-4 rtl:border-r-4 border-red-400">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-500">{{ $t('a_penalties_amount') }}</p>
                            <p class="text-xl md:text-2xl font-bold text-red-600">{{ formatCurrency(stats?.penalties_amount) }}</p>
                        </div>
                        <div class="w-10 h-10 rounded-full bg-red-100 flex items-center justify-center">
                            <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-lg shadow-sm p-5 ltr:border-l-4 rtl:border-r-4 border-emerald-400">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-500">{{ $t('a_rewards_amount') }}</p>
                            <p class="text-xl md:text-2xl font-bold text-emerald-600">{{ formatCurrency(stats?.rewards_amount) }}</p>
                        </div>
                        <div class="w-10 h-10 rounded-full bg-emerald-100 flex items-center justify-center">
                            <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Type Filter -->
            <div class="bg-white rounded-lg shadow-sm p-4 flex flex-wrap gap-2">
                <button @click="applyFilter('')" class="px-4 py-2 rounded-lg text-sm font-medium transition" :class="typeFilter === '' ? 'text-white' : 'text-gray-600 bg-gray-100 hover:bg-gray-200'" :style="typeFilter === '' ? 'background-color: #C4A265;' : ''">{{ $t('a_all') }}</button>
                <button @click="applyFilter('penalty')" class="px-4 py-2 rounded-lg text-sm font-medium transition" :class="typeFilter === 'penalty' ? 'text-white' : 'text-gray-600 bg-gray-100 hover:bg-gray-200'" :style="typeFilter === 'penalty' ? 'background-color: #C4A265;' : ''">{{ $t('a_penalties') }}</button>
                <button @click="applyFilter('reward')" class="px-4 py-2 rounded-lg text-sm font-medium transition" :class="typeFilter === 'reward' ? 'text-white' : 'text-gray-600 bg-gray-100 hover:bg-gray-200'" :style="typeFilter === 'reward' ? 'background-color: #C4A265;' : ''">{{ $t('a_rewards') }}</button>
            </div>

            <!-- Penalties & Rewards Table -->
            <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 md:px-6 py-3 ltr:text-left rtl:text-right text-xs font-medium text-gray-500 uppercase tracking-wider">{{ $t('a_employee') }}</th>
                                <th class="px-4 md:px-6 py-3 ltr:text-left rtl:text-right text-xs font-medium text-gray-500 uppercase tracking-wider">{{ $t('a_type') }}</th>
                                <th class="px-4 md:px-6 py-3 ltr:text-left rtl:text-right text-xs font-medium text-gray-500 uppercase tracking-wider">{{ $t('a_amount') }}</th>
                                <th class="px-4 md:px-6 py-3 ltr:text-left rtl:text-right text-xs font-medium text-gray-500 uppercase tracking-wider">{{ $t('a_reason') }}</th>
                                <th class="px-4 md:px-6 py-3 ltr:text-left rtl:text-right text-xs font-medium text-gray-500 uppercase tracking-wider">{{ $t('a_date') }}</th>
                                <th class="px-4 md:px-6 py-3 ltr:text-left rtl:text-right text-xs font-medium text-gray-500 uppercase tracking-wider">{{ $t('a_applied_to_salary') }}</th>
                                <th class="px-4 md:px-6 py-3 ltr:text-right rtl:text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ $t('a_actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-for="(record, i) in penalties.data" :key="record.id" class="lst-row hover:bg-gray-50" :style="{ '--row-i': i }">
                                <td class="px-4 md:px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div :class="['w-8 h-8 rounded-full flex-shrink-0 flex items-center justify-center text-white text-xs font-bold', isRtl ? 'ml-3' : 'mr-3']" style="background-color: #C4A265;">
                                            {{ record.employee?.user?.name?.charAt(0) }}
                                        </div>
                                        <div>
                                            <div class="text-sm font-medium text-gray-900">{{ record.employee?.user?.name }}</div>
                                            <div class="text-xs text-gray-500">{{ record.employee?.department?.name_en }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 md:px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full" :class="record.type === 'penalty' ? 'bg-red-100 text-red-700' : 'bg-emerald-100 text-emerald-700'">
                                        {{ record.type === 'penalty' ? $t('a_penalty') : $t('a_reward') }}
                                    </span>
                                </td>
                                <td class="px-4 md:px-6 py-4 whitespace-nowrap text-sm font-semibold" :style="record.type === 'penalty' ? 'color: #dc2626;' : 'color: #16a34a;'">{{ formatCurrency(record.amount) }}</td>
                                <td class="px-4 md:px-6 py-4 text-sm text-gray-500 max-w-[200px] truncate">{{ record.reason || '-' }}</td>
                                <td class="px-4 md:px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ record.date }}</td>
                                <td class="px-4 md:px-6 py-4 whitespace-nowrap text-center">
                                    <svg v-if="record.applied_to_salary" class="w-5 h-5 text-emerald-500 inline-block" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                    </svg>
                                    <span v-else class="text-gray-400">&mdash;</span>
                                </td>
                                <td class="px-4 md:px-6 py-4 whitespace-nowrap ltr:text-right rtl:text-left text-sm">
                                    <button v-if="!record.applied_to_salary && can('penalties.delete')" @click="deleteRecord(record.id)" class="font-medium text-red-600 hover:underline text-xs">{{ $t('a_delete') }}</button>
                                </td>
                            </tr>
                            <tr v-if="!penalties.data || penalties.data.length === 0">
                                <td colspan="7" class="px-4 md:px-6 py-8 text-center text-sm text-gray-500">{{ $t('a_no_records_found') }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="penalties.links && penalties.links.length > 3" class="px-4 md:px-6 py-3 border-t border-gray-200 flex items-center justify-between">
                    <p class="text-sm text-gray-500">{{ $t('a_showing') }} {{ penalties.from }} {{ $t('a_to') }} {{ penalties.to }} {{ $t('a_of') }} {{ penalties.total }} {{ $t('a_results') }}</p>
                    <nav :class="['flex', isRtl ? 'space-x-reverse space-x-1' : 'space-x-1']">
                        <template v-for="link in penalties.links" :key="link.label">
                            <Link v-if="link.url" :href="link.url" v-html="link.label" class="px-3 py-1 text-sm rounded border transition" :class="link.active ? 'text-white border-transparent' : 'text-gray-600 border-gray-300 hover:bg-gray-50'" :style="link.active ? 'background-color: #C4A265;' : ''" preserve-state />
                            <span v-else v-html="link.label" class="px-3 py-1 text-sm text-gray-400" />
                        </template>
                    </nav>
                </div>
            </div>
        </div>

        <!-- Create Record Modal -->
        <Teleport to="body">
            <div v-if="showModal" v-focus-trap="() => (showModal = false)" role="dialog" aria-modal="true" class="fixed inset-0 z-50 overflow-y-auto">
                <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
                    <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="closeModal"></div>
                    <div class="relative bg-white rounded-2xl shadow-xl w-full max-w-full sm:max-w-lg mx-auto z-10 max-h-[90vh] overflow-y-auto">
                        <div class="px-4 md:px-6 py-4 border-b border-gray-200">
                            <div class="flex items-center justify-between">
                                <h3 class="text-lg font-semibold text-gray-800">{{ $t('a_add_record') }}</h3>
                                <button @click="closeModal" class="text-gray-400 hover:text-gray-600" :aria-label="isRtl ? 'إغلاق' : 'Close'" :title="isRtl ? 'إغلاق' : 'Close'">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                                </button>
                            </div>
                        </div>
                        <form @submit.prevent="submitRecord" class="px-4 md:px-6 py-4 space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('a_employee') }} <span class="text-red-500">*</span></label>
                                <select v-model="form.employee_id" class="doctorato-input w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#C4A265]/30 focus:border-transparent">
                                    <option value="">{{ $t('a_select_employee') }}</option>
                                    <option v-for="emp in employees" :key="emp.id" :value="emp.id">{{ emp.name }}</option>
                                </select>
                                <p v-if="formErrors.employee_id" class="mt-1 text-sm text-red-600">{{ formErrors.employee_id }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">{{ $t('a_type') }} <span class="text-red-500">*</span></label>
                                <div class="flex gap-6">
                                    <label class="inline-flex items-center cursor-pointer">
                                        <input type="radio" v-model="form.type" value="penalty" class="form-radio text-red-600 focus:ring-[#C4A265]/30" />
                                        <span :class="isRtl ? 'mr-2' : 'ml-2'" class="text-sm text-gray-700">{{ $t('a_penalty') }}</span>
                                    </label>
                                    <label class="inline-flex items-center cursor-pointer">
                                        <input type="radio" v-model="form.type" value="reward" class="form-radio text-emerald-600 focus:ring-[#C4A265]/30" />
                                        <span :class="isRtl ? 'mr-2' : 'ml-2'" class="text-sm text-gray-700">{{ $t('a_reward') }}</span>
                                    </label>
                                </div>
                                <p v-if="formErrors.type" class="mt-1 text-sm text-red-600">{{ formErrors.type }}</p>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('a_amount') }} <span class="text-red-500">*</span></label>
                                    <input v-model="form.amount" type="number" step="0.01" min="0" class="doctorato-input w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#C4A265]/30 focus:border-transparent" placeholder="0.00" />
                                    <p v-if="formErrors.amount" class="mt-1 text-sm text-red-600">{{ formErrors.amount }}</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('a_date') }} <span class="text-red-500">*</span></label>
                                    <input v-model="form.date" type="date" class="doctorato-input w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#C4A265]/30 focus:border-transparent" />
                                    <p v-if="formErrors.date" class="mt-1 text-sm text-red-600">{{ formErrors.date }}</p>
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('a_reason') }}</label>
                                <textarea v-model="form.reason" rows="3" class="doctorato-input w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#C4A265]/30 focus:border-transparent" :placeholder="$t('a_reason_for_penalty_reward')"></textarea>
                                <p v-if="formErrors.reason" class="mt-1 text-sm text-red-600">{{ formErrors.reason }}</p>
                            </div>
                            <div :class="['flex pt-2 border-t border-gray-200', isRtl ? 'justify-start space-x-reverse space-x-3' : 'justify-end space-x-3']">
                                <button type="button" @click="closeModal" class="px-4 py-2 rounded-lg border border-gray-300 text-gray-600 text-sm font-medium hover:bg-gray-50 transition">{{ $t('a_cancel') }}</button>
                                <button type="submit" :disabled="formProcessing" class="px-4 md:px-6 py-2 rounded-lg text-white text-sm font-medium transition disabled:opacity-50" style="background-color: #C4A265;">{{ formProcessing ? $t('a_saving') : $t('a_create_record') }}</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </Teleport>
    </AdminLayout>
</template>

<style scoped>
.lst-row {
    animation: lstRowIn 0.4s cubic-bezier(0.22, 0.61, 0.36, 1) both;
    animation-delay: calc(var(--row-i, 0) * 35ms);
}
@keyframes lstRowIn {
    from { opacity: 0; transform: translateY(8px); }
    to { opacity: 1; transform: none; }
}
@media (prefers-reduced-motion: reduce) {
    .lst-row { animation: none !important; }
}
</style>
