<script setup>
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import FrontendLayout from '@/Layouts/FrontendLayout.vue';

defineProps({
    outcome: String,
    booking: Object,
});

const page = usePage();
const locale = computed(() => page.props.locale || 'ar');
const isRtl  = computed(() => (page.props.dir || 'rtl') === 'rtl');
</script>

<template>
    <FrontendLayout :title="isRtl ? 'حالة الحجز' : 'Booking status'">
        <section class="min-h-[60vh] flex items-center justify-center py-16 bg-gradient-to-br from-slate-50 to-white px-4">
            <div class="max-w-md w-full bg-white rounded-3xl shadow-xl border border-gray-100 overflow-hidden">
                <!-- Color band per outcome -->
                <div :class="{
                        'bg-emerald-500': outcome === 'confirmed',
                        'bg-blue-500':    outcome === 'already_confirmed' || outcome === 'already_cancelled',
                        'bg-red-500':     outcome === 'cancelled',
                        'bg-amber-500':   outcome === 'not_confirmable' || outcome === 'not_cancellable',
                     }" class="h-1.5"></div>

                <div class="p-8 text-center">
                    <div class="text-6xl mb-4">
                        {{ {
                            confirmed: '✅', cancelled: '',
                            already_confirmed: 'ℹ', already_cancelled: 'ℹ',
                            not_confirmable: '', not_cancellable: '',
                        }[outcome] || '✉' }}
                    </div>

                    <h1 class="text-2xl font-extrabold text-gray-800 mb-2">
                        {{ isRtl
                            ? {
                                confirmed: 'تم تأكيد حجزك',
                                cancelled: 'تم إلغاء حجزك',
                                already_confirmed: 'حجزك مؤكد بالفعل',
                                already_cancelled: 'الحجز ملغى بالفعل',
                                not_confirmable: 'لا يمكن تأكيد هذا الحجز',
                                not_cancellable: 'لا يمكن إلغاء هذا الحجز',
                            }[outcome]
                            : {
                                confirmed: 'Your booking is confirmed',
                                cancelled: 'Your booking is cancelled',
                                already_confirmed: 'Already confirmed',
                                already_cancelled: 'Already cancelled',
                                not_confirmable: 'Cannot confirm',
                                not_cancellable: 'Cannot cancel',
                            }[outcome] }}
                    </h1>

                    <p class="text-sm text-gray-500 leading-relaxed mb-6">
                        {{ isRtl
                            ? {
                                confirmed: 'شكراً لتأكيد حضورك. سنراك في الموعد المحدد.',
                                cancelled: 'تم استلام طلب الإلغاء. يمكنك الحجز مرة أخرى في أي وقت.',
                                already_confirmed: 'حجزك مسجّل كمؤكد منذ زيارة سابقة لهذا الرابط.',
                                already_cancelled: 'هذا الحجز سبق إلغاؤه.',
                                not_confirmable: 'الحجز في حالة لا تسمح بالتأكيد عبر الرابط. تواصل معنا للمساعدة.',
                                not_cancellable: 'الحجوزات المكتملة لا يمكن إلغاؤها. تواصل معنا لو احتجت مساعدة.',
                            }[outcome]
                            : {
                                confirmed: 'Thank you for confirming. See you at the scheduled time.',
                                cancelled: 'Cancellation received. You can book again any time.',
                                already_confirmed: 'This booking was already marked as confirmed.',
                                already_cancelled: 'This booking was already cancelled.',
                                not_confirmable: 'This booking is in a state that doesn\'t allow email confirmation. Please contact us.',
                                not_cancellable: 'Completed bookings cannot be cancelled. Please contact us.',
                            }[outcome] }}
                    </p>

                    <!-- Booking summary -->
                    <div v-if="booking" class="bg-gradient-to-r from-[#FAF7F0] to-white border border-[#C4A265]/30 rounded-xl p-4 mb-6 text-sm">
                        <p class="text-[10px] font-bold text-[#8B7043] tracking-wider uppercase mb-1.5">
                            {{ isRtl ? 'تفاصيل الحجز' : 'Booking details' }}
                        </p>
                        <p class="font-mono text-xs text-gray-500 mb-1">{{ booking.booking_number }}</p>
                        <p class="text-base font-bold text-[#1B365D] tabular-nums">
                            {{ booking.preferred_date }}<span v-if="booking.preferred_time"> · {{ booking.preferred_time }}</span>
                        </p>
                    </div>

                    <div class="flex flex-col sm:flex-row gap-3">
                        <Link :href="`/${locale}`"
                              class="flex-1 px-5 py-3 rounded-xl bg-[#1B365D] hover:bg-[#22406F] text-white text-sm font-bold transition">
                            {{ isRtl ? ' الصفحة الرئيسية' : ' Home' }}
                        </Link>
                        <Link :href="`/${locale}/booking`"
                              class="flex-1 px-5 py-3 rounded-xl bg-white border border-gray-200 text-gray-700 hover:bg-gray-50 text-sm font-semibold transition">
                            {{ isRtl ? ' حجز جديد' : ' New booking' }}
                        </Link>
                    </div>
                </div>
            </div>
        </section>
    </FrontendLayout>
</template>
