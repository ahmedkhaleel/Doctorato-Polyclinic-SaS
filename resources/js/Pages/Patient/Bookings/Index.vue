<script setup>
import { computed, ref } from 'vue';
import { usePage, Link, router } from '@inertiajs/vue3';
import PatientLayout from '@/Layouts/PatientLayout.vue';
import { usePatientLocale } from '@/Composables/usePatientLocale';
import { usePatientStatus } from '@/Composables/usePatientStatus';

const { lp } = usePatientLocale();
const { bookingLabel, bookingColor } = usePatientStatus();

const cancellingId = ref(null);

function cancelBooking(booking) {
    if (!confirm(isRtl.value ? 'هل أنت متأكد من إلغاء هذا الحجز؟' : 'Are you sure you want to cancel this booking?')) return;
    cancellingId.value = booking.id;
    router.post(lp(`/bookings/${booking.id}/cancel`), {}, {
        preserveScroll: true,
        onFinish: () => cancellingId.value = null,
    });
}

defineOptions({ layout: PatientLayout });

const props = defineProps({
    bookings: Object,
});

const page = usePage();
const locale = computed(() => page.props.locale || 'ar');
const dir = computed(() => page.props.dir || 'rtl');
const isRtl = computed(() => dir.value === 'rtl');
const translations = computed(() => page.props.translations || {});
function t(key) { return translations.value[key] || key; }

function $localized(obj, field) {
    if (!obj) return '';
    const lang = locale.value === 'ar' ? 'ar' : 'en';
    return obj[field + '_' + lang] || obj[field + '_en'] || obj[field] || '';
}

</script>

<template>
    <div>
        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
            <h1 class="text-2xl font-bold text-gray-800">{{ t('p_my_bookings') }}</h1>
            <Link :href="lp('/bookings/create')" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-sm font-semibold text-white bg-gradient-to-r from-[var(--brand-primary)] to-[var(--brand-secondary)] hover:from-[var(--brand-primary-hover)] hover:to-[var(--brand-primary)] shadow-md shadow-[var(--brand-primary)]/20 transition-all duration-200">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                {{ isRtl ? 'حجز جديد' : 'Book New' }}
            </Link>
        </div>

        <!-- Bookings Table (Desktop) -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <!-- Desktop Table -->
            <div class="hidden md:block overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-100">
                            <th class="text-start px-6 py-4 font-semibold text-gray-500 text-xs uppercase">{{ isRtl ? 'رقم الحجز' : 'Booking #' }}</th>
                            <th class="text-start px-6 py-4 font-semibold text-gray-500 text-xs uppercase">{{ isRtl ? 'الخدمة' : 'Service' }}</th>
                            <th class="text-start px-6 py-4 font-semibold text-gray-500 text-xs uppercase">{{ isRtl ? 'الطبيب' : 'Doctor' }}</th>
                            <th class="text-start px-6 py-4 font-semibold text-gray-500 text-xs uppercase">{{ isRtl ? 'التاريخ' : 'Date' }}</th>
                            <th class="text-start px-6 py-4 font-semibold text-gray-500 text-xs uppercase">{{ isRtl ? 'الوقت' : 'Time' }}</th>
                            <th class="text-start px-6 py-4 font-semibold text-gray-500 text-xs uppercase">{{ isRtl ? 'الحالة' : 'Status' }}</th>
                            <th class="px-6 py-4"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="booking in bookings?.data" :key="booking.id" class="border-b border-gray-50 hover:bg-gray-50/50 transition-colors">
                            <td class="px-6 py-4 font-medium text-gray-800">{{ booking.booking_number }}</td>
                            <td class="px-6 py-4 text-gray-600">{{ $localized(booking.service, 'name') || $localized(booking, 'service_name') }}</td>
                            <td class="px-6 py-4 text-gray-600">{{ $localized(booking.doctor, 'name') || $localized(booking, 'doctor_name') }}</td>
                            <td class="px-6 py-4 text-gray-600">{{ booking.preferred_date }}</td>
                            <td class="px-6 py-4 text-gray-600">{{ booking.preferred_time }}</td>
                            <td class="px-6 py-4">
                                <span :class="bookingColor(booking.status)" class="text-xs font-medium px-2.5 py-1 rounded-full">{{ bookingLabel(booking.status) }}</span>
                            </td>
                            <td class="px-6 py-4">
                                <button v-if="['unconfirmed', 'confirmed'].includes(booking.status)"
                                    @click="cancelBooking(booking)"
                                    :disabled="cancellingId === booking.id"
                                    class="text-xs font-medium text-red-500 hover:text-red-700 transition-colors disabled:opacity-50">
                                    {{ cancellingId === booking.id ? (isRtl ? 'جاري...' : 'Cancelling...') : (isRtl ? 'إلغاء' : 'Cancel') }}
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Mobile Cards -->
            <div class="md:hidden divide-y divide-gray-100">
                <div v-for="booking in bookings?.data" :key="booking.id" class="p-4 hover:bg-gray-50 transition-colors">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm font-medium text-gray-800">{{ booking.booking_number }}</span>
                        <span :class="bookingColor(booking.status)" class="text-xs font-medium px-2.5 py-1 rounded-full">{{ bookingLabel(booking.status) }}</span>
                    </div>
                    <p class="text-sm text-gray-600">{{ $localized(booking.service, 'name') || $localized(booking, 'service_name') }}</p>
                    <div class="flex items-center justify-between mt-1">
                        <p class="text-xs text-gray-400">{{ $localized(booking.doctor, 'name') || $localized(booking, 'doctor_name') }} &middot; {{ booking.preferred_date }} {{ booking.preferred_time }}</p>
                        <button v-if="['unconfirmed', 'confirmed'].includes(booking.status)"
                            @click="cancelBooking(booking)"
                            :disabled="cancellingId === booking.id"
                            class="text-xs font-medium text-red-500 hover:text-red-700 disabled:opacity-50">
                            {{ isRtl ? 'إلغاء' : 'Cancel' }}
                        </button>
                    </div>
                </div>
            </div>

            <!-- Empty State -->
            <div v-if="!bookings?.data?.length" class="text-center py-16 text-gray-400">
                <svg class="w-12 h-12 mx-auto mb-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                <p class="text-sm">{{ isRtl ? 'لا توجد حجوزات' : 'No bookings found' }}</p>
                <Link :href="lp('/bookings/create')" class="inline-block mt-3 text-sm text-[var(--brand-primary)] hover:text-[var(--brand-primary-hover)] font-medium">
                    {{ isRtl ? 'احجز موعدك الأول' : 'Book your first appointment' }}
                </Link>
            </div>
        </div>

        <!-- Pagination -->
        <div v-if="bookings?.links?.length > 3" class="flex justify-center gap-1 mt-6">
            <template v-for="link in bookings.links" :key="link.label">
                <Link
                    v-if="link.url"
                    :href="link.url"
                    class="px-3 py-2 rounded-lg text-sm transition-colors"
                    :class="link.active ? 'bg-[var(--brand-primary)] text-white' : 'bg-white text-gray-600 hover:bg-gray-100 border border-gray-200'"
                    v-html="link.label"
                />
                <span v-else class="px-3 py-2 text-sm text-gray-300" v-html="link.label" />
            </template>
        </div>
    </div>
</template>
