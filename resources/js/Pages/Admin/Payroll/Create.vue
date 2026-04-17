<script setup>
import { ref, computed } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const page = usePage();
const locale = computed(() => page.props.locale || 'ar');
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const props = defineProps({
    currentMonth: Number,
    currentYear: Number,
});

const months = [
    'January', 'February', 'March', 'April', 'May', 'June',
    'July', 'August', 'September', 'October', 'November', 'December',
];

const month = ref(props.currentMonth || new Date().getMonth() + 1);
const year = ref(props.currentYear || new Date().getFullYear());
const processing = ref(false);

function submit() {
    processing.value = true;
    router.post('/admin/payroll/generate', {
        month: month.value,
        year: year.value,
    }, {
        onFinish: () => {
            processing.value = false;
        },
    });
}
</script>

<template>
    <AdminLayout :title="$t('a_generate_payroll')">
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <h1 class="text-xl md:text-2xl font-bold text-gray-800">{{ $t('a_generate_payroll') }}</h1>
                <Link href="/admin/payroll" class="text-sm text-gray-500 hover:underline">{{ isRtl ? '&rarr;' : '&larr;' }} {{ $t('a_back_to_payroll') }}</Link>
            </div>

            <!-- Form -->
            <form @submit.prevent="submit" class="max-w-lg">
                <div class="bg-white rounded-lg shadow-sm p-4 md:p-6 space-y-5">
                    <h2 class="text-lg font-semibold text-gray-700 border-b pb-2">{{ $t('a_payroll_period') }}</h2>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('a_month') }} <span class="text-red-500">*</span></label>
                            <select
                                v-model="month"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-amber-200 focus:border-transparent"
                            >
                                <option v-for="(name, index) in months" :key="index" :value="index + 1">{{ name }}</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('a_year') }} <span class="text-red-500">*</span></label>
                            <input
                                v-model="year"
                                type="number"
                                min="2020"
                                max="2099"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-amber-200 focus:border-transparent"
                            />
                        </div>
                    </div>

                    <div class="rounded-lg bg-gray-50 border border-gray-200 p-4">
                        <p class="text-sm text-gray-600 leading-relaxed">
                            {{ $t('a_generate_payroll_note') }}
                        </p>
                    </div>

                    <div :class="['flex pt-2', isRtl ? 'justify-start space-x-reverse space-x-3' : 'justify-end space-x-3']">
                        <Link
                            href="/admin/payroll"
                            class="px-4 md:px-6 py-3 rounded-lg border border-gray-300 text-gray-600 font-medium text-sm hover:bg-gray-50 transition"
                        >
                            {{ $t('a_cancel') }}
                        </Link>
                        <button
                            type="submit"
                            :disabled="processing"
                            class="px-4 md:px-6 py-3 rounded-lg text-white font-medium text-sm transition disabled:opacity-50"
                            style="background-color: #C4A265;"
                        >
                            {{ processing ? $t('a_generating') : $t('a_generate_payroll') }}
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </AdminLayout>
</template>
