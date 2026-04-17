<script setup>
import { computed, ref, onMounted } from 'vue';
import { useForm, Link , usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { useCurrency } from '@/Composables/useCurrency.js';

const { formatCurrency, currencyCode } = useCurrency();

const props = defineProps({ marketers: Array, leads: Array });

const page = usePage();
const locale = computed(() => page.props.locale || 'ar');
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const mounted = ref(false);
onMounted(() => setTimeout(() => mounted.value = true, 50));

const form = useForm({
    user_id: '', lead_id: '', booking_id: '',
    commission_type: 'fixed', rate: '', base_amount: '', notes: '',
});

const calculatedCommission = computed(() => {
    if (!form.rate || !form.base_amount) return 0;
    if (form.commission_type === 'percentage') {
        return ((form.rate / 100) * form.base_amount).toFixed(2);
    }
    return form.rate;
});

const selectedLead = computed(() => {
    return props.leads?.find(l => l.id == form.lead_id);
});

function submit() { form.post('/admin/commissions'); }
</script>

<template>
    <AdminLayout :title="$t('a_add_commission')">
        <div class="space-y-6">
            <!-- Header -->
            <div :class="mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'"
                class="transition-all duration-500 ease-out flex items-center gap-4">
                <Link href="/admin/commissions"
                    class="p-2.5 rounded-xl text-gray-400 hover:text-[#C4A265] hover:bg-[#C4A265]/5 transition-all duration-200 border border-transparent hover:border-[#C4A265]/20">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
                </Link>
                <div>
                    <h1 class="text-xl md:text-2xl font-bold text-gray-900 tracking-tight">{{ $t('a_add_commission') }}</h1>
                    <p class="text-xs text-gray-400 mt-0.5">{{ $t('a_record_commission_description') }}</p>
                </div>
            </div>

            <form @submit.prevent="submit" class="space-y-6">
                <!-- Commission Details Card - Full Width -->
                <div :class="mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'"
                    class="transition-all duration-500 delay-100 ease-out bg-white rounded-2xl shadow-sm border border-gray-100 relative overflow-hidden">
                    <div class="absolute top-0 ltr:left-0 rtl:right-0 ltr:right-0 rtl:left-0 h-1" style="background: linear-gradient(90deg, #C4A265, #D4B87A, #C4A265);"></div>
                    <div class="p-8">
                        <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-6">{{ $t('a_commission_details') }}</h3>

                        <div class="space-y-6">
                            <!-- Marketer & Lead Row -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">{{ $t('a_marketer') }}<span class="text-red-400">*</span></label>
                                    <select v-model="form.user_id"
                                        class="w-full px-4 py-3 text-sm bg-gray-50/80 border border-gray-200/80 rounded-xl focus:ring-2 focus:ring-[#C4A265]/30 focus:border-[#C4A265] focus:bg-white transition-all duration-200 appearance-none cursor-pointer">
                                        <option value="">Select marketer...</option>
                                        <option v-for="m in marketers" :key="m.id" :value="m.id">{{ m.name }}</option>
                                    </select>
                                    <p v-if="form.errors.user_id" class="text-xs text-red-500 mt-1.5 font-medium">{{ form.errors.user_id }}</p>
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">{{ $t('a_lead') }}<span class="text-red-400">*</span></label>
                                    <select v-model="form.lead_id"
                                        class="w-full px-4 py-3 text-sm bg-gray-50/80 border border-gray-200/80 rounded-xl focus:ring-2 focus:ring-[#C4A265]/30 focus:border-[#C4A265] focus:bg-white transition-all duration-200 appearance-none cursor-pointer">
                                        <option value="">Select converted lead...</option>
                                        <option v-for="l in leads" :key="l.id" :value="l.id">{{ l.full_name }}</option>
                                    </select>
                                    <p v-if="form.errors.lead_id" class="text-xs text-red-500 mt-1.5 font-medium">{{ form.errors.lead_id }}</p>
                                </div>
                            </div>

                            <!-- Commission Type Toggle Cards -->
                            <div>
                                <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">{{ $t('a_commission_type') }}<span class="text-red-400">*</span></label>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <button type="button" @click="form.commission_type = 'fixed'"
                                        :class="form.commission_type === 'fixed'
                                            ? 'border-[#C4A265] ring-2 ring-[#C4A265]/20 bg-[#C4A265]/[0.03]'
                                            : 'border-gray-200/80 bg-gray-50/50 hover:bg-gray-50 hover:border-gray-300'"
                                        class="px-5 py-4 border rounded-xl transition-all duration-200 ltr:text-left rtl:text-right group">
                                        <div class="flex items-center gap-3">
                                            <div :class="form.commission_type === 'fixed' ? 'bg-[#C4A265]/10 text-[#C4A265] scale-110' : 'bg-gray-100 text-gray-400 group-hover:bg-gray-200'"
                                                class="w-11 h-11 rounded-xl flex items-center justify-center transition-all duration-300">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1" /></svg>
                                            </div>
                                            <div>
                                                <p class="text-sm font-bold" :class="form.commission_type === 'fixed' ? 'text-[#C4A265]' : 'text-gray-600'">{{ $t('a_fixed_amount') }}</p>
                                                <p class="text-[10px] text-gray-400 mt-0.5">{{ $t('a_specific_commission_hint') }}</p>
                                            </div>
                                        </div>
                                    </button>
                                    <button type="button" @click="form.commission_type = 'percentage'"
                                        :class="form.commission_type === 'percentage'
                                            ? 'border-[#C4A265] ring-2 ring-[#C4A265]/20 bg-[#C4A265]/[0.03]'
                                            : 'border-gray-200/80 bg-gray-50/50 hover:bg-gray-50 hover:border-gray-300'"
                                        class="px-5 py-4 border rounded-xl transition-all duration-200 ltr:text-left rtl:text-right group">
                                        <div class="flex items-center gap-3">
                                            <div :class="form.commission_type === 'percentage' ? 'bg-[#C4A265]/10 text-[#C4A265] scale-110' : 'bg-gray-100 text-gray-400 group-hover:bg-gray-200'"
                                                class="w-11 h-11 rounded-xl flex items-center justify-center transition-all duration-300">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2" /></svg>
                                            </div>
                                            <div>
                                                <p class="text-sm font-bold" :class="form.commission_type === 'percentage' ? 'text-[#C4A265]' : 'text-gray-600'">{{ $t('a_percentage') }}</p>
                                                <p class="text-[10px] text-gray-400 mt-0.5">{{ $t('a_calculate_from_base') }}</p>
                                            </div>
                                        </div>
                                    </button>
                                </div>
                            </div>

                            <!-- Rate & Base Amount -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">
                                        {{ form.commission_type === 'percentage' ? 'Rate (%)' : `Rate (${currencyCode})` }} <span class="text-red-400">*</span>
                                    </label>
                                    <div class="relative">
                                        <input v-model="form.rate" type="number" step="0.01" min="0"
                                            class="w-full px-4 py-3 text-sm bg-gray-50/80 border border-gray-200/80 rounded-xl focus:ring-2 focus:ring-[#C4A265]/30 focus:border-[#C4A265] focus:bg-white transition-all duration-200 ltr:pr-14 rtl:pl-14" />
                                        <span class="absolute ltr:right-4 rtl:left-4 top-1/2 -translate-y-1/2 text-xs font-bold text-gray-300">{{ form.commission_type === 'percentage' ? '%' : currencyCode }}</span>
                                    </div>
                                    <p v-if="form.errors.rate" class="text-xs text-red-500 mt-1.5 font-medium">{{ form.errors.rate }}</p>
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Base Amount ({{ currencyCode }}) <span class="text-red-400">*</span></label>
                                    <input v-model="form.base_amount" type="number" step="0.01" min="0"
                                        class="w-full px-4 py-3 text-sm bg-gray-50/80 border border-gray-200/80 rounded-xl focus:ring-2 focus:ring-[#C4A265]/30 focus:border-[#C4A265] focus:bg-white transition-all duration-200" />
                                    <p v-if="form.errors.base_amount" class="text-xs text-red-500 mt-1.5 font-medium">{{ form.errors.base_amount }}</p>
                                </div>
                            </div>

                            <!-- Notes -->
                            <div>
                                <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">{{ $t('a_notes') }}</label>
                                <textarea v-model="form.notes" rows="3"
                                    class="w-full px-4 py-3 text-sm bg-gray-50/80 border border-gray-200/80 rounded-xl resize-none focus:ring-2 focus:ring-[#C4A265]/30 focus:border-[#C4A265] focus:bg-white transition-all duration-200 placeholder-gray-300"
                                    :placeholder="$t('a_optional_notes')"></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Summary & Actions Row - Full Width -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Calculated Commission Card -->
                    <div :class="mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'"
                        class="transition-all duration-500 delay-150 ease-out relative overflow-hidden rounded-2xl p-8"
                        style="background: linear-gradient(135deg, #C4A265, #D4B87A);">
                        <div class="absolute top-0 ltr:right-0 rtl:left-0 w-32 h-32 rounded-full bg-white/10 -translate-y-8 translate-x-8"></div>
                        <div class="absolute bottom-0 ltr:left-0 rtl:right-0 w-24 h-24 rounded-full bg-white/5 translate-y-6 -translate-x-6"></div>
                        <div class="absolute top-1/2 ltr:right-8 rtl:left-8 w-16 h-16 rounded-full bg-white/5 -translate-y-1/2"></div>
                        <div class="relative">
                            <p class="text-sm text-white/70 font-semibold uppercase tracking-wider">{{ $t('a_calculated_commission') }}</p>
                            <p class="text-2xl md:text-4xl font-bold text-white mt-3 tracking-tight font-mono">{{ formatCurrency(calculatedCommission) }}</p>
                            <div class="mt-5 pt-4 border-t border-white/20 space-y-2">
                                <div class="flex items-center justify-between text-sm">
                                    <span class="text-white/50">{{ $t('a_type') }}</span>
                                    <span class="font-semibold text-white capitalize">{{ form.commission_type }}</span>
                                </div>
                                <div v-if="form.rate" class="flex items-center justify-between text-sm">
                                    <span class="text-white/50">{{ $t('a_rate') }}</span>
                                    <span class="font-semibold text-white font-mono">{{ form.commission_type === 'percentage' ? form.rate + '%' : formatCurrency(form.rate) }}</span>
                                </div>
                                <div v-if="form.base_amount" class="flex items-center justify-between text-sm">
                                    <span class="text-white/50">{{ $t('a_base') }}</span>
                                    <span class="font-semibold text-white font-mono">{{ formatCurrency(form.base_amount) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Selected Lead Info -->
                    <div :class="mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'"
                        class="transition-all duration-500 delay-200 ease-out bg-white rounded-2xl shadow-sm border border-gray-100 p-4 md:p-8 relative overflow-hidden">
                        <div class="absolute top-0 ltr:left-0 rtl:right-0 ltr:right-0 rtl:left-0 h-1" style="background: linear-gradient(90deg, #C4A265, #D4B87A, #C4A265);"></div>
                        <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-4">{{ $t('a_selected_lead') }}</h3>
                        <div v-if="selectedLead" class="flex items-center gap-4">
                            <div class="w-12 h-12 rounded-xl flex items-center justify-center text-white text-sm font-bold shadow-md flex-shrink-0"
                                style="background: linear-gradient(135deg, #C4A265, #D4B87A);">
                                {{ selectedLead.full_name?.charAt(0)?.toUpperCase() || '?' }}
                            </div>
                            <div>
                                <p class="font-bold text-gray-900">{{ selectedLead.full_name }}</p>
                                <p class="text-xs text-gray-400 mt-0.5">{{ selectedLead.phone || selectedLead.email || 'No contact info' }}</p>
                            </div>
                        </div>
                        <div v-else class="flex items-center justify-center py-6">
                            <div class="text-center">
                                <div class="w-12 h-12 mx-auto mb-3 rounded-xl bg-gray-50 flex items-center justify-center">
                                    <svg class="w-6 h-6 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                                </div>
                                <p class="text-xs text-gray-400 font-medium">{{ $t('a_select_lead_details') }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div :class="mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'"
                        class="transition-all duration-500 delay-[250ms] ease-out bg-white rounded-2xl shadow-sm border border-gray-100 p-4 md:p-8 flex flex-col justify-between">
                        <div>
                            <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">{{ $t('a_actions') }}</h3>
                            <p class="text-xs text-gray-400 mb-6">Review the details and submit when ready.</p>
                        </div>
                        <div class="space-y-3">
                            <button type="submit" :disabled="form.processing"
                                class="w-full px-4 md:px-6 py-3.5 rounded-xl text-white text-sm font-semibold transition-all duration-300 shadow-lg shadow-[#C4A265]/25 hover:shadow-xl hover:shadow-[#C4A265]/35 hover:-translate-y-0.5 active:translate-y-0 disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:translate-y-0 disabled:hover:shadow-lg flex items-center justify-center gap-2"
                                style="background: linear-gradient(135deg, #C4A265, #D4B87A);">
                                <svg v-if="form.processing" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                                <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>{{ $t('a_create_commission') }}</button>
                            <Link href="/admin/commissions"
                                class="block w-full px-4 md:px-6 py-3 text-center rounded-xl text-sm font-semibold text-gray-500 hover:text-gray-700 hover:bg-gray-50 border border-gray-200 transition-all duration-200">{{ $t('a_cancel') }}</Link>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </AdminLayout>
</template>
