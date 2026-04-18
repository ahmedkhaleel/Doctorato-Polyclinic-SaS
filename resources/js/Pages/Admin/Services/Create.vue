<script setup>
import { computed, watch } from 'vue';
import { Link, useForm, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import RichTextEditor from '@/Components/Admin/RichTextEditor.vue';
import { useCurrency } from '@/Composables/useCurrency.js';

const { formatCurrency, currencyCode } = useCurrency();
import SearchableSelect from '@/Components/Admin/SearchableSelect.vue';

const props = defineProps({
    categories: Array,
    modules: Object,
});

const page = usePage();
const locale = computed(() => page.props.locale || 'ar');
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');


const form = useForm({
    module: 'derma',
    name_ar: '',
    name_en: '',
    category_id: '',
    short_desc_ar: '',
    short_desc_en: '',
    full_desc_ar: '',
    full_desc_en: '',
    featured_image: null,
    icon: '',
    benefits_ar: '',
    benefits_en: '',
    sessions_count: '',
    results_ar: '',
    results_en: '',
    status: 'active',
    show_on_home: false,
    show_on_website: true,
    bookable: true,
    seo_title_ar: '',
    seo_title_en: '',
    seo_desc_ar: '',
    seo_desc_en: '',
    seo_keywords: '',
    // Pricing & Clinic
    price: '',
    price_after_discount: '',
    supply_cost: '',
    medical_fee: '',
    default_sessions: '',
    session_duration_minutes: '',
    clinic_notes: '',
});

const categoryOptions = computed(() => (props.categories || []).map(c => ({ value: c.id, label: c.name_en })));

const computedPrice = computed(() => {
    const supply = parseFloat(form.supply_cost) || 0;
    const medical = parseFloat(form.medical_fee) || 0;
    return (supply + medical).toFixed(2);
});

watch([() => form.supply_cost, () => form.medical_fee], () => {
    if (form.medical_fee !== '' && form.medical_fee !== null) {
        form.price = computedPrice.value;
    }
});

function submit() {
    form.post('/admin/services', {
        forceFormData: true,
    });
}
</script>

<template>
    <AdminLayout :title="$t('a_create_service')">
        <div class="space-y-6">
            <div class="flex items-center justify-between">
                <h1 class="text-xl md:text-2xl font-bold text-gray-800">{{ $t('a_create_service') }}</h1>
                <Link href="/admin/services" class="text-sm text-gray-500 hover:text-gray-700">{{ $t('a_back_to_services') }}</Link>
            </div>

            <form @submit.prevent="submit" class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Main Content -->
                <div class="lg:col-span-2 space-y-6">
                    <div class="bg-white rounded-lg shadow-sm p-4 md:p-6 space-y-5">
                        <!-- Module Selector -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">{{ $t('a_module') }}</label>
                            <div class="flex gap-2">
                                <button
                                    v-for="(mod, slug) in modules"
                                    :key="slug"
                                    type="button"
                                    @click="form.module = slug"
                                    class="flex items-center gap-2 px-4 py-2.5 rounded-lg border-2 text-sm font-medium transition-all duration-200"
                                    :class="form.module === slug ? 'border-transparent text-white shadow-sm' : 'border-gray-200 text-gray-600 hover:border-gray-300'"
                                    :style="form.module === slug ? { backgroundColor: mod.color } : {}"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="mod.icon" /></svg>
                                    <span>{{ locale === 'ar' ? mod.name_ar : mod.name_en }}</span>
                                </button>
                            </div>
                            <p v-if="form.errors.module" class="mt-1 text-sm text-red-600">{{ form.errors.module }}</p>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('a_name_en') }}</label>
                                <input v-model="form.name_en" type="text" class="doctorato-input w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#C4A265]/30 focus:border-transparent" />
                                <p v-if="form.errors.name_en" class="mt-1 text-sm text-red-600">{{ form.errors.name_en }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('a_name_ar') }}</label>
                                <input v-model="form.name_ar" type="text" dir="rtl" class="doctorato-input w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#C4A265]/30 focus:border-transparent" />
                                <p v-if="form.errors.name_ar" class="mt-1 text-sm text-red-600">{{ form.errors.name_ar }}</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('a_short_desc_en') }}</label>
                                <textarea v-model="form.short_desc_en" rows="3" class="doctorato-input w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#C4A265]/30 focus:border-transparent"></textarea>
                                <p v-if="form.errors.short_desc_en" class="mt-1 text-sm text-red-600">{{ form.errors.short_desc_en }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('a_short_desc_ar') }}</label>
                                <textarea v-model="form.short_desc_ar" rows="3" dir="rtl" class="doctorato-input w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#C4A265]/30 focus:border-transparent"></textarea>
                                <p v-if="form.errors.short_desc_ar" class="mt-1 text-sm text-red-600">{{ form.errors.short_desc_ar }}</p>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('a_full_desc_en') }}</label>
                            <RichTextEditor v-model="form.full_desc_en" dir="ltr" placeholder="Write full description..." />
                            <p v-if="form.errors.full_desc_en" class="mt-1 text-sm text-red-600">{{ form.errors.full_desc_en }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('a_full_desc_ar') }}</label>
                            <RichTextEditor v-model="form.full_desc_ar" dir="rtl" placeholder="اكتب الوصف الكامل..." />
                            <p v-if="form.errors.full_desc_ar" class="mt-1 text-sm text-red-600">{{ form.errors.full_desc_ar }}</p>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('a_benefits_en') }}</label>
                                <textarea v-model="form.benefits_en" rows="4" placeholder="One benefit per line" class="doctorato-input w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#C4A265]/30 focus:border-transparent"></textarea>
                                <p v-if="form.errors.benefits_en" class="mt-1 text-sm text-red-600">{{ form.errors.benefits_en }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('a_benefits_ar') }}</label>
                                <textarea v-model="form.benefits_ar" rows="4" dir="rtl" placeholder="One benefit per line" class="doctorato-input w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#C4A265]/30 focus:border-transparent"></textarea>
                                <p v-if="form.errors.benefits_ar" class="mt-1 text-sm text-red-600">{{ form.errors.benefits_ar }}</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('a_results_en') }}</label>
                                <textarea v-model="form.results_en" rows="3" class="doctorato-input w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#C4A265]/30 focus:border-transparent"></textarea>
                                <p v-if="form.errors.results_en" class="mt-1 text-sm text-red-600">{{ form.errors.results_en }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('a_results_ar') }}</label>
                                <textarea v-model="form.results_ar" rows="3" dir="rtl" class="doctorato-input w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#C4A265]/30 focus:border-transparent"></textarea>
                                <p v-if="form.errors.results_ar" class="mt-1 text-sm text-red-600">{{ form.errors.results_ar }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- SEO Section -->
                    <div class="bg-white rounded-lg shadow-sm p-4 md:p-6 space-y-5">
                        <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wider">{{ $t('a_seo_settings') }}</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('a_seo_title_en') }}</label>
                                <input v-model="form.seo_title_en" type="text" class="doctorato-input w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#C4A265]/30 focus:border-transparent" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('a_seo_title_ar') }}</label>
                                <input v-model="form.seo_title_ar" type="text" dir="rtl" class="doctorato-input w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#C4A265]/30 focus:border-transparent" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('a_seo_desc_en') }}</label>
                                <textarea v-model="form.seo_desc_en" rows="2" class="doctorato-input w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#C4A265]/30 focus:border-transparent"></textarea>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('a_seo_desc_ar') }}</label>
                                <textarea v-model="form.seo_desc_ar" rows="2" dir="rtl" class="doctorato-input w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#C4A265]/30 focus:border-transparent"></textarea>
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('a_seo_keywords') }}</label>
                            <input v-model="form.seo_keywords" type="text" placeholder="comma, separated, keywords" class="doctorato-input w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#C4A265]/30 focus:border-transparent" />
                        </div>
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="space-y-6">
                    <div class="bg-white rounded-lg shadow-sm p-4 md:p-6 space-y-5">
                        <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wider">{{ $t('a_details') }}</h3>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('a_category') }}</label>
                            <SearchableSelect v-model="form.category_id" :options="categoryOptions" placeholder="Select Category" searchPlaceholder="Search categories..." :error="form.errors.category_id" />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('a_status') }}</label>
                            <select v-model="form.status" class="doctorato-input w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#C4A265]/30 focus:border-transparent">
                                <option value="active">{{ $t('a_active') }}</option>
                                <option value="inactive">{{ $t('a_inactive') }}</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('a_sessions_count') }}</label>
                            <input v-model="form.sessions_count" type="number" min="0" class="doctorato-input w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#C4A265]/30 focus:border-transparent" />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('a_icon_label') }}</label>
                            <input v-model="form.icon" type="text" class="doctorato-input w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#C4A265]/30 focus:border-transparent" />
                        </div>

                        <!-- Visibility Settings -->
                        <div class="pt-3 border-t border-gray-100">
                            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">{{ $t('a_visibility') }}</p>
                            <div class="space-y-3">
                                <div class="flex items-center">
                                    <input v-model="form.show_on_website" type="checkbox" id="show_on_website" class="h-4 w-4 rounded border-gray-300 text-[#C4A265] focus:ring-[#C4A265]" />
                                    <label for="show_on_website" class="ltr:ml-2 rtl:mr-2 text-sm text-gray-700">{{ $t('a_show_on_website') }}</label>
                                </div>
                                <p class="text-[10px] text-gray-400 ltr:ml-6 rtl:mr-6 -mt-1">{{ $t('a_display_on_public') }}</p>

                                <div class="flex items-center">
                                    <input v-model="form.show_on_home" type="checkbox" id="show_on_home" class="h-4 w-4 rounded border-gray-300 text-[#C4A265] focus:ring-[#C4A265]" />
                                    <label for="show_on_home" class="ltr:ml-2 rtl:mr-2 text-sm text-gray-700">{{ $t('a_show_on_homepage') }}</label>
                                </div>
                                <p class="text-[10px] text-gray-400 ltr:ml-6 rtl:mr-6 -mt-1">{{ $t('a_feature_on_homepage') }}</p>

                                <div class="flex items-center">
                                    <input v-model="form.bookable" type="checkbox" id="bookable" class="h-4 w-4 rounded border-gray-300 text-[#C4A265] focus:ring-[#C4A265]" />
                                    <label for="bookable" class="ltr:ml-2 rtl:mr-2 text-sm text-gray-700">{{ $t('a_available_for_booking') }}</label>
                                </div>
                                <p class="text-[10px] text-gray-400 ltr:ml-6 rtl:mr-6 -mt-1">{{ $t('a_show_in_booking_forms') }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-lg shadow-sm p-4 md:p-6 space-y-5">
                        <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wider">{{ $t('a_featured_image') }}</h3>
                        <input
                            type="file"
                            accept="image/*"
                            @input="form.featured_image = $event.target.files[0]"
                            class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-gray-100 file:text-gray-700 hover:file:bg-gray-200"
                        />
                        <p v-if="form.errors.featured_image" class="mt-1 text-sm text-red-600">{{ form.errors.featured_image }}</p>
                    </div>

                    <!-- Pricing & Clinic Settings -->
                    <div class="bg-white rounded-lg shadow-sm p-4 md:p-6 space-y-5">
                        <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wider">{{ $t('a_pricing_clinic') }}</h3>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('a_supply_cost') }} ({{ currencyCode }})</label>
                                <input v-model="form.supply_cost" type="number" min="0" step="0.01" placeholder="0.00" class="doctorato-input w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#C4A265]/30 focus:border-transparent" />
                                <p v-if="form.errors.supply_cost" class="mt-1 text-sm text-red-600">{{ form.errors.supply_cost }}</p>
                                <p class="text-xs text-gray-400 mt-1">{{ $t('a_cost_of_materials') }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('a_medical_fee') }} ({{ currencyCode }})</label>
                                <input v-model="form.medical_fee" type="number" min="0" step="0.01" placeholder="0.00" class="doctorato-input w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#C4A265]/30 focus:border-transparent" />
                                <p v-if="form.errors.medical_fee" class="mt-1 text-sm text-red-600">{{ form.errors.medical_fee }}</p>
                                <p class="text-xs text-gray-400 mt-1">{{ $t('a_commission_calculated') }}</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('a_total_price') }} ({{ currencyCode }})</label>
                                <input v-model="form.price" type="number" min="0" step="0.01" placeholder="0.00"  class="doctorato-input" :class="[form.medical_fee ? 'bg-gray-50 text-gray-500' : '', 'w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#C4A265]/30 focus:border-transparent']" :readonly="!!form.medical_fee" />
                                <p v-if="form.errors.price" class="mt-1 text-sm text-red-600">{{ form.errors.price }}</p>
                                <p v-if="form.medical_fee" class="text-xs text-gray-400 mt-1">{{ $t('a_auto_computed') }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('a_after_discount') }}</label>
                                <input v-model="form.price_after_discount" type="number" min="0" step="0.01" placeholder="0.00" class="doctorato-input w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#C4A265]/30 focus:border-transparent" />
                                <p v-if="form.errors.price_after_discount" class="mt-1 text-sm text-red-600">{{ form.errors.price_after_discount }}</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('a_default_sessions') }}</label>
                                <input v-model="form.default_sessions" type="number" min="1" placeholder="e.g. 6" class="doctorato-input w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#C4A265]/30 focus:border-transparent" />
                                <p v-if="form.errors.default_sessions" class="mt-1 text-sm text-red-600">{{ form.errors.default_sessions }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('a_duration_min') }}</label>
                                <input v-model="form.session_duration_minutes" type="number" min="1" placeholder="e.g. 30" class="doctorato-input w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#C4A265]/30 focus:border-transparent" />
                                <p v-if="form.errors.session_duration_minutes" class="mt-1 text-sm text-red-600">{{ form.errors.session_duration_minutes }}</p>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('a_clinic_notes') }}</label>
                            <textarea v-model="form.clinic_notes" rows="3" placeholder="Internal notes about this service..." class="doctorato-input w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#C4A265]/30 focus:border-transparent"></textarea>
                            <p v-if="form.errors.clinic_notes" class="mt-1 text-sm text-red-600">{{ form.errors.clinic_notes }}</p>
                        </div>

                        <!-- Pricing Summary -->
                        <div v-if="form.price || form.medical_fee" class="p-3 rounded-lg border" style="background-color: rgba(196, 162, 101, 0.06); border-color: rgba(196, 162, 101, 0.2);">
                            <p class="text-xs font-medium text-gray-600 mb-2">{{ $t('a_pricing_summary') }}</p>
                            <div class="space-y-1 text-xs text-gray-500">
                                <div v-if="form.supply_cost" class="flex justify-between">
                                    <span>{{ $t('a_supply_cost') }}</span>
                                    <span class="font-medium text-gray-500">{{ formatCurrency(form.supply_cost) }}</span>
                                </div>
                                <div v-if="form.medical_fee" class="flex justify-between">
                                    <span>{{ $t('a_medical_fee') }}</span>
                                    <span class="font-medium" style="color: #C4A265;">{{ formatCurrency(form.medical_fee) }}</span>
                                </div>
                                <div class="flex justify-between border-t border-gray-200 pt-1">
                                    <span class="font-medium">{{ $t('a_total_price') }}</span>
                                    <span class="font-medium text-gray-700">{{ formatCurrency(form.price || 0) }}</span>
                                </div>
                                <div v-if="form.price_after_discount" class="flex justify-between">
                                    <span>{{ $t('a_after_discount') }}</span>
                                    <span class="font-medium text-emerald-600">{{ formatCurrency(form.price_after_discount) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="flex space-x-3">
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="flex-1 py-2.5 px-4 rounded-lg text-white font-medium text-sm transition disabled:opacity-50"
                            style="background-color: #C4A265;"
                        >
                            {{ form.processing ? $t('a_saving') : $t('a_create_service') }}
                        </button>
                        <Link href="/admin/services" class="px-4 py-2.5 rounded-lg bg-gray-200 text-gray-700 text-sm font-medium hover:bg-gray-300 transition">
                            {{ $t('a_cancel') }}
                        </Link>
                    </div>
                </div>
            </form>
        </div>
    </AdminLayout>
</template>
