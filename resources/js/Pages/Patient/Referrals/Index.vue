<script setup>
import { computed, ref } from 'vue';
import { usePage, Link } from '@inertiajs/vue3';
import PatientLayout from '@/Layouts/PatientLayout.vue';
import { usePatientLocale } from '@/Composables/usePatientLocale';
import { useCurrency } from '@/Composables/useCurrency';

const { lp } = usePatientLocale();
const { formatCurrency } = useCurrency();

defineOptions({ layout: PatientLayout });

const props = defineProps({
    code: String,
    share_url: String,
    count: Number,
    total_discount: Number,
    currency: String,
    referrals: Object, // paginator
});

const page = usePage();
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const copied = ref(false);
function copyLink() {
    if (!props.share_url) return;
    navigator.clipboard.writeText(props.share_url).then(() => {
        copied.value = true;
        setTimeout(() => { copied.value = false; }, 2000);
    });
}
function shareWhatsApp() {
    const text = encodeURIComponent(
        (isRtl.value ? 'انضم إليّ في عيادة Doctorato واحصل على خصم باستخدام كودي: '
                     : 'Join me at Doctorato and get a discount with my code: ')
        + props.share_url
    );
    window.open(`https://wa.me/?text=${text}`, '_blank');
}
function fmtDate(d) {
    if (!d) return '';
    try {
        return new Date(d).toLocaleDateString(isRtl.value ? 'ar-EG' : 'en-US',
            { year: 'numeric', month: 'short', day: 'numeric' });
    } catch { return d; }
}
</script>

<template>
    <div>
        <!-- Hero -->
        <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-[#1B365D] to-[#22406F] shadow-xl mb-6 p-6 md:p-8 text-white">
            <div class="absolute -top-16 -end-16 h-56 w-56 rounded-full bg-[#C4A265]/20 blur-3xl"></div>
            <div class="relative">
                <div class="flex items-center gap-2 mb-2">
                    <span class="h-[3px] w-6 bg-[#C4A265] rounded-full"></span>
                    <span class="text-[10px] font-bold text-[#C4A265] tracking-[0.25em] uppercase">
                        {{ isRtl ? 'برنامج الإحالة' : 'Referral Program' }}
                    </span>
                </div>
                <h1 class="text-3xl md:text-4xl font-extrabold mb-2">
                    {{ isRtl ? 'ادعُ صديقاً، اربحوا معاً' : 'Refer a friend, both win' }}
                </h1>
                <p class="text-sm text-white/70 max-w-2xl">
                    {{ isRtl
                        ? 'شارك كودك مع أصدقائك. عندما يحجزون أول موعد، تحصل أنت وهم على خصم على الحجز التالي.'
                        : 'Share your code with friends. When they book their first appointment, you both get a discount on your next booking.' }}
                </p>
            </div>
        </div>

        <!-- Stat row -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6">
            <div class="bg-white rounded-2xl shadow-sm border border-emerald-100 p-5 flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl bg-emerald-50 flex items-center justify-center flex-shrink-0">
                    <svg class="w-6 h-6 text-emerald-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-2xl font-extrabold text-gray-800 tabular-nums">{{ count }}</p>
                    <p class="text-[11px] text-gray-500 uppercase tracking-wider">
                        {{ isRtl ? 'أصدقاء أحلتهم' : 'Friends referred' }}
                    </p>
                </div>
            </div>
            <div class="bg-white rounded-2xl shadow-sm border border-[#C4A265]/30 p-5 flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-[#C4A265] to-[#8B7043] flex items-center justify-center flex-shrink-0">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-2xl font-extrabold text-[#8B7043] tabular-nums">{{ formatCurrency(total_discount) }}</p>
                    <p class="text-[11px] text-gray-500 uppercase tracking-wider">
                        {{ isRtl ? 'إجمالي الخصومات الممنوحة' : 'Total reward earned' }}
                    </p>
                </div>
            </div>
        </div>

        <!-- Code card -->
        <div v-if="code" class="bg-white rounded-2xl shadow-sm border border-[#C4A265]/20 p-6 mb-6">
            <p class="text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-2">
                {{ isRtl ? 'كودك الشخصي' : 'Your personal code' }}
            </p>
            <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
                <code class="flex-1 px-4 py-3 bg-gradient-to-r from-[#FAF7F0] to-white border-2 border-dashed border-[#C4A265]/40 rounded-xl text-[#1B365D] font-mono font-extrabold text-xl tracking-wider text-center">
                    {{ code }}
                </code>
                <button @click="copyLink"
                        class="inline-flex items-center justify-center gap-2 px-4 py-3 rounded-xl bg-[#1B365D] hover:bg-[#22406F] text-white text-sm font-semibold transition">
                    <svg v-if="!copied" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                    <svg v-else class="w-4 h-4 text-emerald-300" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                    {{ copied ? (isRtl ? 'تم النسخ' : 'Copied') : (isRtl ? 'نسخ الرابط' : 'Copy link') }}
                </button>
                <button @click="shareWhatsApp"
                        class="inline-flex items-center justify-center gap-2 px-4 py-3 rounded-xl bg-emerald-500 hover:bg-emerald-600 text-white text-sm font-bold transition">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51l-.57-.01c-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/>
                    </svg>
                    {{ isRtl ? 'مشاركة عبر واتساب' : 'Share via WhatsApp' }}
                </button>
            </div>
            <p class="text-[11px] text-gray-400 mt-3 break-all">{{ share_url }}</p>
        </div>

        <!-- Referrals list -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="p-5 border-b border-gray-100">
                <h2 class="text-base font-bold text-gray-800">{{ isRtl ? 'أصدقاؤك المُحالين' : 'Your referrals' }}</h2>
            </div>
            <div v-if="referrals.data.length" class="divide-y divide-gray-50">
                <div v-for="r in referrals.data" :key="r.id" class="flex items-center justify-between gap-3 p-4 hover:bg-gray-50 transition">
                    <div class="flex items-center gap-3 min-w-0 flex-1">
                        <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-emerald-100 to-emerald-50 flex items-center justify-center flex-shrink-0">
                            <span class="text-emerald-600 font-bold">{{ (r.referred_name || '?').charAt(0).toUpperCase() }}</span>
                        </div>
                        <div class="min-w-0">
                            <p class="text-sm font-semibold text-gray-800 truncate">{{ r.referred_name }}</p>
                            <p class="text-[11px] text-gray-400 mt-0.5">
                                {{ isRtl ? 'انضم في' : 'Joined' }} {{ fmtDate(r.created_at) }}
                                <span v-if="r.redeemed_at" class="ms-2 inline-flex items-center gap-1 px-1.5 py-0.5 rounded-full bg-emerald-50 text-emerald-700 text-[10px] font-semibold">
                                    {{ isRtl ? 'تم الاستبدال' : 'redeemed' }}
                                </span>
                            </p>
                        </div>
                    </div>
                    <span v-if="r.discount_amount > 0" class="text-sm font-bold text-[#8B7043] tabular-nums whitespace-nowrap">
                        {{ formatCurrency(r.discount_amount) }}
                    </span>
                </div>
            </div>
            <div v-else class="p-12 text-center text-gray-400 text-sm">
                {{ isRtl ? 'لم تحل أي صديق بعد. شارك كودك وابدأ!' : 'No referrals yet. Share your code to get started!' }}
            </div>

            <!-- Pagination -->
            <div v-if="referrals.last_page > 1" class="p-4 border-t border-gray-100 flex items-center justify-center flex-wrap gap-2">
                <Link v-for="link in referrals.links" :key="link.label"
                      :href="link.url || '#'"
                      v-html="link.label"
                      :class="[
                        'px-3 py-1.5 rounded-lg text-xs font-medium border',
                        link.active ? 'bg-[#1B365D] text-white border-[#1B365D]'
                                    : link.url ? 'bg-white text-gray-600 border-gray-200 hover:bg-gray-50'
                                               : 'bg-gray-50 text-gray-300 border-gray-100 cursor-not-allowed'
                      ]" />
            </div>
        </div>
    </div>
</template>
