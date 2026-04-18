<script setup>
import { ref, computed } from 'vue';
import { useForm, Link, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { useCurrency } from '@/Composables/useCurrency.js';

const { currencyCode } = useCurrency();

const page = usePage();
const locale = computed(() => page.props.locale || 'ar');
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const props = defineProps({
    supply: Object,
    categories: Array,
});

const form = useForm({
    _method: 'PUT',
    name_en: props.supply.name_en || '',
    name_ar: props.supply.name_ar || '',
    module: props.supply.module || 'shared',
    sku: props.supply.sku || '',
    barcode: props.supply.barcode || '',
    supply_category_id: props.supply.supply_category_id || '',
    unit: props.supply.unit || '',
    min_quantity: props.supply.min_quantity ?? 0,
    purchase_price: props.supply.purchase_price || '',
    supplier: props.supply.supplier || '',
    description: props.supply.description || '',
    expiry_date: props.supply.expiry_date ? props.supply.expiry_date.substring(0, 10) : '',
    batch_number: props.supply.batch_number || '',
    image: null,
});

const imagePreview = ref(props.supply.image ? `/storage/${props.supply.image}` : null);

function handleImageChange(e) {
    const file = e.target.files[0];
    if (file) {
        form.image = file;
        const reader = new FileReader();
        reader.onload = (ev) => { imagePreview.value = ev.target.result; };
        reader.readAsDataURL(file);
    }
}

function removeImage() {
    form.image = null;
    imagePreview.value = null;
}

function submit() {
    form.post(`/admin/supplies/${props.supply.id}/update`, {
        forceFormData: true,
    });
}

const unitOptions = ['pcs', 'ml', 'mg', 'g', 'box', 'pack', 'bottle', 'tube', 'vial', 'ampule', 'cartridge', 'tip', 'pair', 'set'];
</script>

<template>
    <AdminLayout :title="$t('a_edit_product')">
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between animate-fade-in-up">
                <div>
                    <div class="flex items-center gap-2 text-sm text-gray-500 mb-1">
                        <Link href="/admin/inventory" class="hover:text-[#1B365D] transition-colors">{{ $t('a_inventory') }}</Link>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                        <Link href="/admin/supplies" class="hover:text-[#1B365D] transition-colors">{{ $t('a_products') }}</Link>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                        <span class="text-gray-400">{{ $t('a_edit') }}</span>
                    </div>
                    <h1 class="text-xl md:text-2xl font-bold text-gray-800">Edit: {{ supply.name_en }}</h1>
                </div>
                <span class="text-sm font-mono px-3 py-1.5 rounded-full bg-[#1B365D]/10 text-[#1B365D] font-medium">{{ supply.sku }}</span>
            </div>

            <form @submit.prevent="submit" class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Main Content -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Basic Information -->
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden animate-fade-in-up" style="animation-delay: 0.1s;">
                        <div class="px-4 md:px-6 py-4 border-b border-gray-100 bg-gradient-to-r from-gray-50 to-white">
                            <h2 class="text-base font-semibold text-gray-800 flex items-center gap-2">
                                <div class="w-8 h-8 rounded-lg bg-[#1B365D]/10 flex items-center justify-center">
                                    <svg class="w-4 h-4 text-[#1B365D]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                </div>
                                {{ $t('a_basic_information') }}
                            </h2>
                        </div>
                        <div class="p-4 md:p-6 space-y-5">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1.5">{{ $t('a_name_english') }} <span class="text-red-500">*</span></label>
                                    <input v-model="form.name_en" type="text" class="doctorato-input w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-[#1B365D]/20 focus:border-[#1B365D] transition-all duration-200" />
                                    <p v-if="form.errors.name_en" class="mt-1.5 text-sm text-red-500">{{ form.errors.name_en }}</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1.5">{{ $t('a_name_arabic') }} <span class="text-red-500">*</span></label>
                                    <input v-model="form.name_ar" type="text" dir="rtl" class="doctorato-input w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-[#1B365D]/20 focus:border-[#1B365D] transition-all duration-200" />
                                    <p v-if="form.errors.name_ar" class="mt-1.5 text-sm text-red-500">{{ form.errors.name_ar }}</p>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1.5">{{ $t('a_sku') }}</label>
                                    <input v-model="form.sku" type="text" class="doctorato-input w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm font-mono focus:ring-2 focus:ring-[#1B365D]/20 focus:border-[#1B365D] transition-all duration-200" />
                                    <p v-if="form.errors.sku" class="mt-1.5 text-sm text-red-500">{{ form.errors.sku }}</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1.5">{{ $t('a_barcode') }}</label>
                                    <input v-model="form.barcode" type="text" class="doctorato-input w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm font-mono focus:ring-2 focus:ring-[#1B365D]/20 focus:border-[#1B365D] transition-all duration-200" />
                                    <p v-if="form.errors.barcode" class="mt-1.5 text-sm text-red-500">{{ form.errors.barcode }}</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1.5">{{ $t('a_category') }}</label>
                                    <select v-model="form.supply_category_id" class="doctorato-input w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-[#1B365D]/20 focus:border-[#1B365D] transition-all duration-200">
                                        <option value="">{{ $t('a_select_category') }}</option>
                                        <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name_en }}</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1.5">{{ isRtl ? 'القسم الطبي' : 'Medical Module' }}</label>
                                    <select v-model="form.module" class="doctorato-input w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-[#1B365D]/20 focus:border-[#1B365D] transition-all duration-200">
                                        <option value="shared">{{ isRtl ? 'مشترك' : 'Shared' }}</option>
                                        <option value="derma">{{ isRtl ? 'الجلدية' : 'Dermatology' }}</option>
                                        <option value="dental">{{ isRtl ? 'الأسنان' : 'Dental' }}</option>
                                    </select>
                                    <p v-if="form.errors.module" class="mt-1.5 text-sm text-red-500">{{ form.errors.module }}</p>
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">{{ $t('a_description') }}</label>
                                <textarea v-model="form.description" rows="3" class="doctorato-input w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-[#1B365D]/20 focus:border-[#1B365D] transition-all duration-200 resize-none"></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Stock Information -->
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden animate-fade-in-up" style="animation-delay: 0.2s;">
                        <div class="px-4 md:px-6 py-4 border-b border-gray-100 bg-gradient-to-r from-gray-50 to-white">
                            <h2 class="text-base font-semibold text-gray-800 flex items-center gap-2">
                                <div class="w-8 h-8 rounded-lg bg-emerald-500/10 flex items-center justify-center">
                                    <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0v10l-8 4-8-4V7m16 0l-8 4m0 10V11m0 0L4 7" /></svg>
                                </div>
                                {{ $t('a_stock_measurement') }}
                            </h2>
                        </div>
                        <div class="p-4 md:p-6 space-y-5">
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1.5">{{ $t('a_current_quantity') }}</label>
                                    <div class="px-4 py-2.5 bg-gray-50 rounded-xl text-sm font-semibold text-gray-700 border border-gray-200">
                                        {{ supply.quantity }} {{ supply.unit || '' }}
                                    </div>
                                    <p class="text-xs text-gray-400 mt-1">{{ $t('a_use_transactions_change_stock') }}</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1.5">{{ $t('a_min_quantity_alert') }}</label>
                                    <input v-model="form.min_quantity" type="number" min="0" step="0.01" class="doctorato-input w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-[#1B365D]/20 focus:border-[#1B365D] transition-all duration-200" />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1.5">{{ $t('a_unit') }}</label>
                                    <select v-model="form.unit" class="doctorato-input w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-[#1B365D]/20 focus:border-[#1B365D] transition-all duration-200">
                                        <option value="">{{ $t('a_select_unit') }}</option>
                                        <option v-for="u in unitOptions" :key="u" :value="u">{{ u }}</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Image Upload -->
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden animate-fade-in-up" style="animation-delay: 0.3s;">
                        <div class="px-4 md:px-6 py-4 border-b border-gray-100 bg-gradient-to-r from-gray-50 to-white">
                            <h2 class="text-base font-semibold text-gray-800 flex items-center gap-2">
                                <div class="w-8 h-8 rounded-lg bg-[#1B365D]/10 flex items-center justify-center">
                                    <svg class="w-4 h-4 text-[#1B365D]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                                </div>
                                {{ $t('a_product_image') }}
                            </h2>
                        </div>
                        <div class="p-6">
                            <div v-if="imagePreview" class="relative inline-block mb-4">
                                <img :src="imagePreview" class="w-32 h-32 rounded-xl object-cover border-2 border-gray-100 shadow-sm" />
                                <button type="button" @click="removeImage" class="absolute -top-2 -right-2 w-6 h-6 bg-red-500 text-white rounded-full flex items-center justify-center text-xs hover:bg-red-600 transition-colors shadow-md">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                                </button>
                            </div>
                            <label class="block cursor-pointer">
                                <div class="border-2 border-dashed border-gray-200 rounded-xl p-4 md:p-6 text-center hover:border-slate-400/50 hover:bg-slate-50/50 transition-all duration-300">
                                    <svg class="w-8 h-8 mx-auto text-gray-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" /></svg>
                                    <p class="text-sm text-gray-500">{{ $t('a_click_upload') }}</p>
                                    <p class="text-xs text-gray-400 mt-1">{{ $t('a_png_jpg_2mb') }}</p>
                                </div>
                                <input type="file" accept="image/*" @change="handleImageChange" class="hidden" />
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="space-y-6">
                    <!-- Purchase Information -->
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden animate-fade-in-up" style="animation-delay: 0.15s;">
                        <div class="px-4 md:px-6 py-4 border-b border-gray-100 bg-gradient-to-r from-gray-50 to-white">
                            <h2 class="text-base font-semibold text-gray-800 flex items-center gap-2">
                                <div class="w-8 h-8 rounded-lg bg-[#1B365D]/10 flex items-center justify-center">
                                    <svg class="w-4 h-4 text-[#1B365D]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
                                </div>
                                {{ $t('a_purchasing') }}
                            </h2>
                        </div>
                        <div class="p-4 md:p-6 space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">{{ $t('a_purchase_price') }} ({{ currencyCode }})</label>
                                <input v-model="form.purchase_price" type="number" step="0.01" min="0" placeholder="0.00" class="doctorato-input w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-[#1B365D]/20 focus:border-[#1B365D] transition-all duration-200" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">{{ $t('a_supplier') }}</label>
                                <input v-model="form.supplier" type="text" class="doctorato-input w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-[#1B365D]/20 focus:border-[#1B365D] transition-all duration-200" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">{{ $t('a_batch_number') }}</label>
                                <input v-model="form.batch_number" type="text" class="doctorato-input w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm font-mono focus:ring-2 focus:ring-[#1B365D]/20 focus:border-[#1B365D] transition-all duration-200" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">{{ $t('a_expiry_date') }}</label>
                                <input v-model="form.expiry_date" type="date" class="doctorato-input w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-[#1B365D]/20 focus:border-[#1B365D] transition-all duration-200" />
                            </div>
                        </div>
                    </div>

                    <!-- Submit -->
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="w-full px-4 md:px-6 py-3.5 rounded-xl text-white font-semibold text-sm transition-all duration-300 disabled:opacity-50 shadow-lg shadow-[#1B365D]/20 hover:shadow-xl hover:shadow-[#1B365D]/30 hover:-translate-y-0.5 active:translate-y-0"
                        style="background: linear-gradient(135deg, #6366F1, #818CF8);"
                    >
                        <span v-if="form.processing" class="flex items-center justify-center gap-2">
                            <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                            {{ $t('a_saving') }}
                        </span>
                        <span v-else class="flex items-center justify-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                            {{ $t('a_update_product') }}
                        </span>
                    </button>
                </div>
            </form>
        </div>
    </AdminLayout>
</template>
