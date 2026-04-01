<script setup>
import { computed } from 'vue';
import { usePage, Link } from '@inertiajs/vue3';
import PatientLayout from '@/Layouts/PatientLayout.vue';
import { usePatientLocale } from '@/Composables/usePatientLocale';

const { lp } = usePatientLocale();
defineOptions({ layout: PatientLayout });

const props = defineProps({ followups: Object });

const page = usePage();
const locale = computed(() => page.props.locale || 'ar');
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

function $localized(obj, field) {
    if (!obj) return '';
    return obj[field + '_' + (locale.value === 'ar' ? 'ar' : 'en')] || obj[field + '_en'] || obj[field] || '';
}

const statusConfig = {
    pending: { ar: 'بانتظار الحجز', en: 'Pending', color: 'bg-amber-100 text-amber-700', dot: 'bg-amber-400' },
    sms_sent: { ar: 'تم إرسال SMS', en: 'SMS Sent', color: 'bg-blue-100 text-blue-700', dot: 'bg-blue-400' },
    booking_created: { ar: 'تم الحجز', en: 'Booked', color: 'bg-green-100 text-green-700', dot: 'bg-green-400' },
    completed: { ar: 'مكتمل', en: 'Completed', color: 'bg-gray-100 text-gray-600', dot: 'bg-gray-400' },
    cancelled: { ar: 'ملغي', en: 'Cancelled', color: 'bg-red-100 text-red-600', dot: 'bg-red-400' },
    skipped: { ar: 'تم تخطيه', en: 'Skipped', color: 'bg-gray-100 text-gray-500', dot: 'bg-gray-300' },
};

function getStatus(status) { return statusConfig[status] || { ar: status, en: status, color: 'bg-gray-100 text-gray-500', dot: 'bg-gray-300' }; }

function isUpcoming(f) {
    return ['pending', 'sms_sent'].includes(f.status);
}

function daysUntil(dateStr) {
    if (!dateStr) return null;
    const diff = Math.ceil((new Date(dateStr) - new Date()) / (1000 * 60 * 60 * 24));
    return diff;
}
</script>

<template>
    <div>
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-800">{{ isRtl ? 'مواعيد المتابعة' : 'Follow-up Appointments' }}</h1>
            <p class="text-sm text-gray-500 mt-1">{{ isRtl ? 'مواعيد المتابعة المجدولة بعد العلاجات' : 'Scheduled follow-up appointments after your treatments' }}</p>
        </div>

        <!-- Followups List -->
        <div v-if="followups.data?.length" class="space-y-3">
            <div v-for="f in followups.data" :key="f.id"
                :class="['bg-white rounded-2xl shadow-sm border p-5 transition-all', isUpcoming(f) ? 'border-amber-200 hover:shadow-md' : 'border-gray-100']">
                <div class="flex items-start justify-between">
                    <div class="flex items-start gap-3">
                        <div :class="['w-3 h-3 rounded-full mt-1.5 shrink-0', getStatus(f.status).dot]"></div>
                        <div>
                            <div class="flex items-center gap-2">
                                <p class="text-sm font-semibold text-gray-800">{{ f.scheduled_date }}</p>
                                <span v-if="isUpcoming(f) && daysUntil(f.scheduled_date) !== null" class="text-[10px] px-1.5 py-0.5 rounded-full"
                                    :class="daysUntil(f.scheduled_date) <= 3 ? 'bg-red-50 text-red-600' : 'bg-amber-50 text-amber-600'">
                                    {{ daysUntil(f.scheduled_date) <= 0
                                        ? (isRtl ? 'اليوم!' : 'Today!')
                                        : (isRtl ? `بعد ${daysUntil(f.scheduled_date)} يوم` : `in ${daysUntil(f.scheduled_date)} days`) }}
                                </span>
                            </div>
                            <p v-if="f.treatment" class="text-xs text-gray-500 mt-0.5">
                                {{ f.treatment.treatment_type }}
                                <span v-if="f.treatment.tooth_number" class="text-gray-400"> &middot; {{ isRtl ? 'سن' : 'Tooth' }} #{{ f.treatment.tooth_number }}</span>
                            </p>
                            <p v-if="f.doctor" class="text-xs text-gray-400 mt-0.5">{{ $localized(f.doctor, 'name') }}</p>
                            <p v-if="f.notes" class="text-xs text-gray-400 mt-1 italic">{{ f.notes }}</p>
                        </div>
                    </div>
                    <span :class="['px-2.5 py-1 rounded-full text-xs font-medium shrink-0', getStatus(f.status).color]">
                        {{ isRtl ? getStatus(f.status).ar : getStatus(f.status).en }}
                    </span>
                </div>

                <!-- Action for pending -->
                <div v-if="isUpcoming(f)" class="mt-3 pt-3 border-t border-gray-100 flex items-center gap-3">
                    <Link :href="lp('/bookings/create')" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium text-white bg-gradient-to-r from-[var(--brand-primary)] to-[var(--brand-secondary)] hover:from-[var(--brand-primary-hover)] hover:to-[var(--brand-primary)] transition-all shadow-sm">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                        {{ isRtl ? 'حجز الموعد' : 'Book Appointment' }}
                    </Link>
                    <span class="text-[10px] text-gray-400">{{ isRtl ? 'يُنصح بحجز موعد للمتابعة' : 'We recommend booking a follow-up' }}</span>
                </div>
            </div>
        </div>

        <!-- Empty State -->
        <div v-else class="bg-white rounded-2xl shadow-sm border border-gray-100 p-12 text-center">
            <svg class="w-12 h-12 text-gray-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
            <p class="text-gray-400 text-sm">{{ isRtl ? 'لا توجد مواعيد متابعة' : 'No follow-up appointments' }}</p>
        </div>

        <!-- Pagination -->
        <div v-if="followups.last_page > 1" class="flex justify-center mt-6 gap-1">
            <Link v-for="link in followups.links" :key="link.label"
                :href="link.url || '#'"
                :class="['px-3 py-1.5 text-xs rounded-lg border', link.active ? 'bg-cyan-600 text-white border-cyan-600' : 'bg-white text-gray-600 border-gray-200 hover:bg-gray-50']"
                v-html="link.label" preserve-state />
        </div>
    </div>
</template>
