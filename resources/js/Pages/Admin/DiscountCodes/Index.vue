<script setup>
import { ref, watch, computed } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { usePermissions } from '@/Composables/usePermissions.js';
import { useCurrency } from '@/Composables/useCurrency.js';
import { useLocale } from '@/Composables/useLocale.js';

const { can } = usePermissions();
const { t } = useLocale();
const { currencyCode } = useCurrency();

const page = usePage();
const locale = computed(() => page.props.locale || 'ar');
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const props = defineProps({
    discountCodes: Object,
    filters: Object,
});

const search = ref(props.filters?.search || '');
const statusFilter = ref(props.filters?.status || '');
let searchTimeout = null;

watch(search, (val) => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        router.get('/admin/discount-codes', {
            search: val || undefined,
            status: statusFilter.value || undefined,
        }, {
            preserveState: true,
            replace: true,
        });
    }, 400);
});

function filterByStatus(status) {
    statusFilter.value = status;
    router.get('/admin/discount-codes', {
        search: search.value || undefined,
        status: status || undefined,
    }, {
        preserveState: true,
        replace: true,
    });
}

function deleteCode(id) {
    if (window.confirm(t('a_confirm_delete_discount'))) {
        router.post(`/admin/discount-codes/${id}/delete`);
    }
}

function formatDate(date) {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('en-GB');
}

function usagePercent(code) {
    if (!code.max_uses) return 0;
    return Math.min(100, Math.round(((code.used_count || 0) / code.max_uses) * 100));
}

function applicableCount(code) {
    const services = Array.isArray(code.applicable_services) ? code.applicable_services.length : 0;
    const packages = Array.isArray(code.applicable_packages) ? code.applicable_packages.length : 0;
    return services + packages;
}
</script>

<template>
    <AdminLayout :title="$t('a_discount_codes')">
        <div class="space-y-6">
            <div class="flex items-center justify-between">
                <h1 class="text-xl md:text-2xl font-bold text-gray-800">{{ $t('a_discount_codes') }}</h1>
                <Link
                    v-if="can('discount_codes.create')"
                    href="/admin/discount-codes/create"
                    class="inline-flex items-center px-4 py-2 rounded-lg text-white text-sm font-medium transition"
                    style="background-color: #C4A265;"
                >
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    {{ $t('a_add_discount_code') }}
                </Link>
            </div>

            <!-- Filters -->
            <div class="bg-white rounded-lg shadow-sm p-4 flex flex-wrap gap-3 items-center">
                <input
                    v-model="search"
                    type="text"
                    :placeholder="$t('a_search_by_code')"
                    class="w-full sm:w-80 px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-amber-200 focus:border-transparent"
                />
                <div class="flex gap-2">
                    <button
                        @click="filterByStatus('')"
                        class="px-3 py-1.5 text-xs font-medium rounded-full border transition"
                        :class="!statusFilter ? 'text-white border-transparent' : 'text-gray-600 border-gray-300 hover:bg-gray-50'"
                        :style="!statusFilter ? 'background-color: #C4A265;' : ''"
                    >{{ $t('a_all') }}</button>
                    <button
                        @click="filterByStatus('active')"
                        class="px-3 py-1.5 text-xs font-medium rounded-full border transition"
                        :class="statusFilter === 'active' ? 'bg-emerald-500 text-white border-transparent' : 'text-gray-600 border-gray-300 hover:bg-gray-50'"
                    >{{ $t('a_active') }}</button>
                    <button
                        @click="filterByStatus('inactive')"
                        class="px-3 py-1.5 text-xs font-medium rounded-full border transition"
                        :class="statusFilter === 'inactive' ? 'bg-gray-500 text-white border-transparent' : 'text-gray-600 border-gray-300 hover:bg-gray-50'"
                    >{{ $t('a_inactive') }}</button>
                    <button
                        @click="filterByStatus('popup')"
                        class="px-3 py-1.5 text-xs font-medium rounded-full border transition"
                        :class="statusFilter === 'popup' ? 'bg-[#1B365D] text-white border-transparent' : 'text-gray-600 border-gray-300 hover:bg-gray-50'"
                    >{{ $t('a_website_popup') }}</button>
                </div>
            </div>

            <!-- Table -->
            <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 md:px-6 py-3 text-start text-xs font-medium text-gray-500 uppercase tracking-wider">{{ $t('a_code') }}</th>
                                <th class="px-4 md:px-6 py-3 text-start text-xs font-medium text-gray-500 uppercase tracking-wider">{{ $t('a_discount') }}</th>
                                <th class="px-4 md:px-6 py-3 text-start text-xs font-medium text-gray-500 uppercase tracking-wider">{{ $t('a_usage') }}</th>
                                <th class="px-4 md:px-6 py-3 text-start text-xs font-medium text-gray-500 uppercase tracking-wider">{{ $t('a_valid_period') }}</th>
                                <th class="px-4 md:px-6 py-3 text-start text-xs font-medium text-gray-500 uppercase tracking-wider">{{ $t('a_scope') }}</th>
                                <th class="px-4 md:px-6 py-3 text-start text-xs font-medium text-gray-500 uppercase tracking-wider">{{ $t('a_status') }}</th>
                                <th class="px-4 md:px-6 py-3 text-end text-xs font-medium text-gray-500 uppercase tracking-wider">{{ $t('a_actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-for="code in discountCodes.data" :key="code.id" class="hover:bg-gray-50">
                                <!-- Code -->
                                <td class="px-4 md:px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center space-x-2">
                                        <span class="text-sm font-mono font-semibold" style="color: #C4A265;">{{ code.code }}</span>
                                        <span v-if="code.show_on_website" class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium bg-slate-100 text-[#1B365D]" title="Shown as website popup">
                                            <svg class="w-3 h-3 mr-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                                            {{ $t('a_popup') }}
                                        </span>
                                    </div>
                                </td>

                                <!-- Discount -->
                                <td class="px-4 md:px-6 py-4 whitespace-nowrap">
                                    <span class="text-sm font-medium text-gray-900">
                                        {{ code.discount_type === 'percentage' ? code.discount_value + '%' : code.discount_value + ' ' + currencyCode }}
                                    </span>
                                    <span v-if="code.max_discount_amount" class="block text-[10px] text-gray-400">{{ $t('a_max') }} {{ code.max_discount_amount }} {{ currencyCode }}</span>
                                </td>

                                <!-- Usage -->
                                <td class="px-4 md:px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center space-x-2">
                                        <span class="text-sm text-gray-700 font-medium">{{ code.used_count ?? 0 }}</span>
                                        <span class="text-xs text-gray-400">/ {{ code.max_uses ?? $t('a_unlimited') }}</span>
                                    </div>
                                    <div v-if="code.max_uses" class="w-20 bg-gray-200 rounded-full h-1 mt-1">
                                        <div
                                            class="h-1 rounded-full transition-all"
                                            :style="{ width: usagePercent(code) + '%', backgroundColor: usagePercent(code) >= 90 ? '#ef4444' : '#C4A265' }"
                                        ></div>
                                    </div>
                                </td>

                                <!-- Valid Period -->
                                <td class="px-4 md:px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ formatDate(code.start_date) }} - {{ formatDate(code.end_date) }}
                                </td>

                                <!-- Scope -->
                                <td class="px-4 md:px-6 py-4 whitespace-nowrap">
                                    <span v-if="applicableCount(code) === 0" class="text-xs text-gray-400">{{ $t('a_all') }}</span>
                                    <div v-else class="flex flex-wrap gap-1">
                                        <span v-if="Array.isArray(code.applicable_services) && code.applicable_services.length" class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium bg-slate-50 text-[#1B365D]">
                                            {{ code.applicable_services.length }} {{ code.applicable_services.length > 1 ? $t('a_services_plural') : $t('a_service') }}
                                        </span>
                                        <span v-if="Array.isArray(code.applicable_packages) && code.applicable_packages.length" class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium bg-teal-50 text-teal-700">
                                            {{ code.applicable_packages.length }} {{ code.applicable_packages.length > 1 ? $t('a_packages_plural') : $t('a_package') }}
                                        </span>
                                    </div>
                                    <div v-if="code.first_booking_only" class="mt-0.5">
                                        <span class="text-[10px] text-amber-600 font-medium">{{ $t('a_first_booking') }}</span>
                                    </div>
                                </td>

                                <!-- Status -->
                                <td class="px-4 md:px-6 py-4 whitespace-nowrap">
                                    <span
                                        :class="code.is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-gray-100 text-gray-800'"
                                        class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full"
                                    >
                                        {{ code.is_active ? $t('a_active') : $t('a_inactive') }}
                                    </span>
                                </td>

                                <!-- Actions -->
                                <td class="px-4 md:px-6 py-4 whitespace-nowrap text-right text-sm space-x-3">
                                    <Link v-if="can('discount_codes.update')" :href="`/admin/discount-codes/${code.id}/edit`" class="font-medium text-[#1B365D] hover:underline">{{ $t('a_edit') }}</Link>
                                    <button v-if="can('discount_codes.delete')" @click="deleteCode(code.id)" class="font-medium text-red-600 hover:underline">{{ $t('a_delete') }}</button>
                                </td>
                            </tr>
                            <tr v-if="!discountCodes.data || discountCodes.data.length === 0">
                                <td colspan="7" class="px-4 md:px-6 py-8 text-center text-sm text-gray-500">{{ $t('a_no_discount_codes_found') }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="discountCodes.links && discountCodes.links.length > 3" class="px-4 md:px-6 py-3 border-t border-gray-200 flex items-center justify-between">
                    <p class="text-sm text-gray-500">{{ $t('a_showing') }} {{ discountCodes.from }} {{ $t('a_to') }} {{ discountCodes.to }} {{ $t('a_of') }} {{ discountCodes.total }} {{ $t('a_results') }}</p>
                    <nav class="flex space-x-1">
                        <template v-for="link in discountCodes.links" :key="link.label">
                            <Link
                                v-if="link.url"
                                :href="link.url"
                                v-html="link.label"
                                class="px-3 py-1 text-sm rounded border transition"
                                :class="link.active ? 'text-white border-transparent' : 'text-gray-600 border-gray-300 hover:bg-gray-50'"
                                :style="link.active ? 'background-color: #C4A265;' : ''"
                                preserve-state
                            />
                            <span v-else v-html="link.label" class="px-3 py-1 text-sm text-gray-400" />
                        </template>
                    </nav>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
