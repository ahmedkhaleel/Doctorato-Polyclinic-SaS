<script setup>
import { computed } from 'vue';
import { useForm, Link, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import SearchableSelect from '@/Components/Admin/SearchableSelect.vue';

const page = usePage();
const locale = computed(() => page.props.locale || 'ar');
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const props = defineProps({
    users: Array,
});

const form = useForm({
    user_id: '',
    leave_type: 'annual',
    start_date: '',
    end_date: '',
    reason: '',
});

const userOptions = computed(() => (props.users || []).map(u => ({ value: u.id, label: u.name })));

function submit() {
    form.post('/admin/leaves');
}
</script>

<template>
    <AdminLayout :title="$t('a_new_leave_request')">
        <div class="space-y-6">
            <div class="flex items-center justify-between">
                <h1 class="text-xl md:text-2xl font-bold text-gray-800">{{ $t('a_new_leave_request') }}</h1>
                <Link href="/admin/leaves" class="text-sm text-gray-500 hover:underline">
                    <span v-if="isRtl">{{ $t('a_back_to_leaves') }} &rarr;</span>
                    <span v-else>&larr; {{ $t('a_back_to_leaves') }}</span>
                </Link>
            </div>

            <form @submit.prevent="submit" class="max-w-2xl">
                <div class="bg-white rounded-lg shadow-sm p-4 md:p-6 space-y-4">
                    <h2 class="text-lg font-semibold text-gray-700 border-b pb-2">{{ $t('a_leave_details') }}</h2>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('a_employee') }} <span class="text-red-500">*</span></label>
                        <SearchableSelect v-model="form.user_id" :options="userOptions" :placeholder="$t('a_select_employee')" :searchPlaceholder="$t('a_search_employees')" :error="form.errors.user_id" />
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('a_leave_type') }} <span class="text-red-500">*</span></label>
                        <select v-model="form.leave_type" class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-amber-200 focus:border-transparent">
                            <option value="annual">{{ $t('a_annual') }}</option>
                            <option value="sick">{{ $t('a_sick') }}</option>
                            <option value="personal">{{ $t('a_personal') }}</option>
                            <option value="unpaid">{{ $t('a_unpaid') }}</option>
                        </select>
                        <p v-if="form.errors.leave_type" class="mt-1 text-sm text-red-600">{{ form.errors.leave_type }}</p>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('a_start_date') }} <span class="text-red-500">*</span></label>
                            <input v-model="form.start_date" type="date" class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-amber-200 focus:border-transparent" />
                            <p v-if="form.errors.start_date" class="mt-1 text-sm text-red-600">{{ form.errors.start_date }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('a_end_date') }} <span class="text-red-500">*</span></label>
                            <input v-model="form.end_date" type="date" class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-amber-200 focus:border-transparent" />
                            <p v-if="form.errors.end_date" class="mt-1 text-sm text-red-600">{{ form.errors.end_date }}</p>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('a_reason') }}</label>
                        <textarea v-model="form.reason" rows="4" class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-amber-200 focus:border-transparent" :placeholder="$t('a_leave_reason_placeholder')"></textarea>
                        <p v-if="form.errors.reason" class="mt-1 text-sm text-red-600">{{ form.errors.reason }}</p>
                    </div>
                </div>

                <div :class="['flex mt-6 space-x-3 rtl:space-x-reverse', isRtl ? 'justify-start' : 'justify-end']">
                    <Link href="/admin/leaves" class="px-4 md:px-6 py-3 rounded-lg border border-gray-300 text-gray-600 font-medium text-sm hover:bg-gray-50 transition">{{ $t('a_cancel') }}</Link>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="px-4 md:px-6 py-3 rounded-lg text-white font-medium text-sm transition disabled:opacity-50"
                        style="background-color: #C4A265;"
                    >
                        {{ form.processing ? $t('a_submitting') : $t('a_submit_leave_request') }}
                    </button>
                </div>
            </form>
        </div>
    </AdminLayout>
</template>
