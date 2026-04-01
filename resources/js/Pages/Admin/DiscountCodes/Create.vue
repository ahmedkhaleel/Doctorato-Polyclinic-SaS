<script setup>
import { ref, computed } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { useCurrency } from '@/Composables/useCurrency.js';

const { currencyCode } = useCurrency();

const page = usePage();
const locale = computed(() => page.props.locale || 'ar');
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const props = defineProps({
    services: Array,
    packageBundles: Array,
});

const form = useForm({
    code: '',
    discount_type: 'percentage',
    discount_value: '',
    max_uses: '',
    start_date: '',
    end_date: '',
    is_active: true,
    applicable_services: [],
    applicable_packages: [],
    min_order_amount: '',
    max_discount_amount: '',
    per_patient_limit: '',
    first_booking_only: false,
    show_on_website: false,
    popup_title_en: '',
    popup_title_ar: '',
    popup_description_en: '',
    popup_description_ar: '',
    popup_image: null,
    notes: '',
});

const imagePreview = ref(null);

function onImageChange(e) {
    const file = e.target.files[0];
    if (file) {
        form.popup_image = file;
        const reader = new FileReader();
        reader.onload = (ev) => { imagePreview.value = ev.target.result; };
        reader.readAsDataURL(file);
    }
}

function removeImage() {
    form.popup_image = null;
    imagePreview.value = null;
    const input = document.getElementById('popup_image_input');
    if (input) input.value = '';
}

function toggleService(id) {
    const idx = form.applicable_services.indexOf(id);
    if (idx > -1) {
        form.applicable_services.splice(idx, 1);
    } else {
        form.applicable_services.push(id);
    }
}

function togglePackage(id) {
    const idx = form.applicable_packages.indexOf(id);
    if (idx > -1) {
        form.applicable_packages.splice(idx, 1);
    } else {
        form.applicable_packages.push(id);
    }
}

const applicabilityCount = computed(() => {
    return form.applicable_services.length + form.applicable_packages.length;
});

function submit() {
    form.post('/admin/discount-codes', {
        forceFormData: true,
    });
}
</script>

<template>
    <AdminLayout :title="$t('a_add_discount_code')">
        <div class="space-y-6">
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-bold text-gray-800">{{ $t('a_add_discount_code') }}</h1>
            </div>

            <form @submit.prevent="submit" class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Main Fields -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Code Details -->
                    <div class="bg-white rounded-lg shadow-sm p-6 space-y-4">
                        <h2 class="text-lg font-semibold text-gray-700 border-b pb-2">{{ $t('a_code_details') }}</h2>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('a_code') }} <span class="text-red-500">*</span></label>
                            <input v-model="form.code" type="text" class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm uppercase focus:ring-2 focus:ring-yellow-200 focus:border-transparent" :placeholder="$t('a_code_placeholder')" />
                            <p v-if="form.errors.code" class="mt-1 text-sm text-red-600">{{ form.errors.code }}</p>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('a_discount_type') }} <span class="text-red-500">*</span></label>
                                <select v-model="form.discount_type" class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-yellow-200 focus:border-transparent">
                                    <option value="percentage">{{ $t('a_percentage') }} (%)</option>
                                    <option value="fixed">{{ $t('a_fixed_amount') }} ({{ currencyCode }})</option>
                                </select>
                                <p v-if="form.errors.discount_type" class="mt-1 text-sm text-red-600">{{ form.errors.discount_type }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('a_discount_value') }} <span class="text-red-500">*</span></label>
                                <input v-model="form.discount_value" type="number" step="0.01" min="0" class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-yellow-200 focus:border-transparent" :placeholder="form.discount_type === 'percentage' ? 'e.g. 25' : 'e.g. 100'" />
                                <p v-if="form.errors.discount_value" class="mt-1 text-sm text-red-600">{{ form.errors.discount_value }}</p>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('a_max_uses') }}</label>
                            <input v-model="form.max_uses" type="number" min="1" class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-yellow-200 focus:border-transparent" :placeholder="$t('a_leave_empty_unlimited')" />
                            <p v-if="form.errors.max_uses" class="mt-1 text-sm text-red-600">{{ form.errors.max_uses }}</p>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('a_start_date') }}</label>
                                <input v-model="form.start_date" type="date" class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-yellow-200 focus:border-transparent" />
                                <p v-if="form.errors.start_date" class="mt-1 text-sm text-red-600">{{ form.errors.start_date }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('a_end_date') }}</label>
                                <input v-model="form.end_date" type="date" class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-yellow-200 focus:border-transparent" />
                                <p v-if="form.errors.end_date" class="mt-1 text-sm text-red-600">{{ form.errors.end_date }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Applicability -->
                    <div class="bg-white rounded-lg shadow-sm p-6 space-y-4">
                        <div class="flex items-center justify-between border-b pb-2">
                            <h2 class="text-lg font-semibold text-gray-700">{{ $t('a_applicability') }}</h2>
                            <span v-if="applicabilityCount > 0" class="text-xs font-medium px-2 py-1 rounded-full bg-amber-50 text-amber-700">
                                {{ applicabilityCount }} {{ $t('a_selected') }}
                            </span>
                        </div>
                        <p class="text-xs text-gray-500">{{ $t('a_applicability_hint') }}</p>

                        <!-- Services -->
                        <div v-if="services && services.length > 0">
                            <label class="block text-sm font-medium text-gray-700 mb-2">{{ $t('a_services') }}</label>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 max-h-48 overflow-y-auto border border-gray-200 rounded-lg p-3">
                                <label v-for="svc in services" :key="svc.id" class="flex items-center space-x-2 p-1.5 rounded hover:bg-gray-50 cursor-pointer">
                                    <input
                                        type="checkbox"
                                        :checked="form.applicable_services.includes(svc.id)"
                                        @change="toggleService(svc.id)"
                                        class="rounded border-gray-300 text-amber-600 focus:ring-amber-200"
                                    />
                                    <span class="text-sm text-gray-700 truncate">{{ svc.name_en }}</span>
                                </label>
                            </div>
                            <p v-if="form.errors.applicable_services" class="mt-1 text-sm text-red-600">{{ form.errors.applicable_services }}</p>
                        </div>

                        <!-- Package Bundles -->
                        <div v-if="packageBundles && packageBundles.length > 0">
                            <label class="block text-sm font-medium text-gray-700 mb-2">{{ $t('a_package_bundles') }}</label>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 max-h-48 overflow-y-auto border border-gray-200 rounded-lg p-3">
                                <label v-for="pkg in packageBundles" :key="pkg.id" class="flex items-center space-x-2 p-1.5 rounded hover:bg-gray-50 cursor-pointer">
                                    <input
                                        type="checkbox"
                                        :checked="form.applicable_packages.includes(pkg.id)"
                                        @change="togglePackage(pkg.id)"
                                        class="rounded border-gray-300 text-amber-600 focus:ring-amber-200"
                                    />
                                    <span class="text-sm text-gray-700 truncate">{{ pkg.name_en }}</span>
                                    <span class="text-xs text-gray-400 ml-auto">{{ pkg.total_price }} {{ currencyCode }}</span>
                                </label>
                            </div>
                            <p v-if="form.errors.applicable_packages" class="mt-1 text-sm text-red-600">{{ form.errors.applicable_packages }}</p>
                        </div>
                    </div>

                    <!-- Conditions -->
                    <div class="bg-white rounded-lg shadow-sm p-6 space-y-4">
                        <h2 class="text-lg font-semibold text-gray-700 border-b pb-2">{{ $t('a_conditions') }}</h2>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('a_minimum_order_amount') }} ({{ currencyCode }})</label>
                                <input v-model="form.min_order_amount" type="number" step="0.01" min="0" class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-yellow-200 focus:border-transparent" placeholder="0.00" />
                                <p class="mt-1 text-xs text-gray-400">{{ $t('a_min_order_hint') }}</p>
                                <p v-if="form.errors.min_order_amount" class="mt-1 text-sm text-red-600">{{ form.errors.min_order_amount }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('a_max_discount_cap') }} ({{ currencyCode }})</label>
                                <input v-model="form.max_discount_amount" type="number" step="0.01" min="0" class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-yellow-200 focus:border-transparent" :placeholder="$t('a_no_cap')" />
                                <p class="mt-1 text-xs text-gray-400">{{ $t('a_max_discount_hint') }}</p>
                                <p v-if="form.errors.max_discount_amount" class="mt-1 text-sm text-red-600">{{ form.errors.max_discount_amount }}</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('a_per_patient_limit') }}</label>
                                <input v-model="form.per_patient_limit" type="number" min="1" class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-yellow-200 focus:border-transparent" :placeholder="$t('a_unlimited')" />
                                <p class="mt-1 text-xs text-gray-400">{{ $t('a_per_patient_hint') }}</p>
                                <p v-if="form.errors.per_patient_limit" class="mt-1 text-sm text-red-600">{{ form.errors.per_patient_limit }}</p>
                            </div>
                            <div class="flex items-end pb-2">
                                <label class="flex items-center space-x-3 cursor-pointer">
                                    <input type="checkbox" v-model="form.first_booking_only" class="rounded border-gray-300 text-amber-600 focus:ring-amber-200" />
                                    <div>
                                        <span class="text-sm font-medium text-gray-700">{{ $t('a_first_booking_only') }}</span>
                                        <p class="text-xs text-gray-400">{{ $t('a_first_booking_hint') }}</p>
                                    </div>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Website Popup -->
                    <div class="bg-white rounded-lg shadow-sm p-6 space-y-4">
                        <div class="flex items-center justify-between border-b pb-2">
                            <h2 class="text-lg font-semibold text-gray-700">{{ $t('a_website_popup') }}</h2>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" v-model="form.show_on_website" class="sr-only peer" />
                                <div class="w-9 h-5 bg-gray-200 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-amber-200 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-amber-500"></div>
                                <span class="ml-2 text-sm font-medium" :class="form.show_on_website ? 'text-amber-700' : 'text-gray-400'">
                                    {{ form.show_on_website ? $t('a_enabled') : $t('a_disabled') }}
                                </span>
                            </label>
                        </div>

                        <div v-if="form.show_on_website" class="space-y-4">
                            <p class="text-xs text-gray-500">{{ $t('a_popup_config_hint') }}</p>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('a_popup_title_en') }}</label>
                                    <input v-model="form.popup_title_en" type="text" class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-yellow-200 focus:border-transparent" placeholder="e.g. Special Offer!" />
                                    <p v-if="form.errors.popup_title_en" class="mt-1 text-sm text-red-600">{{ form.errors.popup_title_en }}</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('a_popup_title_ar') }}</label>
                                    <input v-model="form.popup_title_ar" type="text" dir="rtl" class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-yellow-200 focus:border-transparent" placeholder="e.g. عرض خاص!" />
                                    <p v-if="form.errors.popup_title_ar" class="mt-1 text-sm text-red-600">{{ form.errors.popup_title_ar }}</p>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('a_popup_description_en') }}</label>
                                    <textarea v-model="form.popup_description_en" rows="3" class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-yellow-200 focus:border-transparent" placeholder="Describe the offer..."></textarea>
                                    <p v-if="form.errors.popup_description_en" class="mt-1 text-sm text-red-600">{{ form.errors.popup_description_en }}</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('a_popup_description_ar') }}</label>
                                    <textarea v-model="form.popup_description_ar" rows="3" dir="rtl" class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-yellow-200 focus:border-transparent" placeholder="وصف العرض..."></textarea>
                                    <p v-if="form.errors.popup_description_ar" class="mt-1 text-sm text-red-600">{{ form.errors.popup_description_ar }}</p>
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('a_popup_image') }}</label>
                                <div class="flex items-start space-x-4">
                                    <div v-if="imagePreview" class="relative w-32 h-20 rounded-lg overflow-hidden border border-gray-200 flex-shrink-0">
                                        <img :src="imagePreview" alt="Preview" class="w-full h-full object-cover" />
                                        <button type="button" @click="removeImage" class="absolute top-1 right-1 w-5 h-5 bg-red-500 text-white rounded-full flex items-center justify-center text-xs hover:bg-red-600">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                                        </button>
                                    </div>
                                    <div class="flex-1">
                                        <input id="popup_image_input" type="file" accept="image/*" @change="onImageChange" class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-amber-50 file:text-amber-700 hover:file:bg-amber-100" />
                                        <p class="mt-1 text-xs text-gray-400">{{ $t('a_popup_image_hint') }}</p>
                                    </div>
                                </div>
                                <p v-if="form.errors.popup_image" class="mt-1 text-sm text-red-600">{{ form.errors.popup_image }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Notes -->
                    <div class="bg-white rounded-lg shadow-sm p-6 space-y-4">
                        <h2 class="text-lg font-semibold text-gray-700 border-b pb-2">{{ $t('a_notes') }}</h2>
                        <div>
                            <textarea v-model="form.notes" rows="3" class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-yellow-200 focus:border-transparent" :placeholder="$t('a_optional_notes')"></textarea>
                        </div>
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="space-y-6">
                    <div class="bg-white rounded-lg shadow-sm p-6 space-y-4">
                        <h2 class="text-lg font-semibold text-gray-700 border-b pb-2">{{ $t('a_status') }}</h2>
                        <div>
                            <label class="flex items-center space-x-3">
                                <input type="checkbox" v-model="form.is_active" class="rounded border-gray-300 text-yellow-600 focus:ring-yellow-200" />
                                <span class="text-sm text-gray-700">{{ $t('a_active') }}</span>
                            </label>
                        </div>
                    </div>

                    <!-- Summary Card -->
                    <div class="bg-white rounded-lg shadow-sm p-6 space-y-3">
                        <h2 class="text-lg font-semibold text-gray-700 border-b pb-2">{{ $t('a_summary') }}</h2>
                        <div class="space-y-2 text-sm">
                            <div class="flex justify-between">
                                <span class="text-gray-500">{{ $t('a_discount') }}:</span>
                                <span class="font-medium text-gray-800">
                                    {{ form.discount_value ? (form.discount_type === 'percentage' ? form.discount_value + '%' : form.discount_value + ' ' + currencyCode) : '-' }}
                                </span>
                            </div>
                            <div v-if="form.max_discount_amount" class="flex justify-between">
                                <span class="text-gray-500">{{ $t('a_max_cap') }}:</span>
                                <span class="font-medium text-gray-800">{{ form.max_discount_amount }} {{ currencyCode }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-500">{{ $t('a_max_uses') }}:</span>
                                <span class="font-medium text-gray-800">{{ form.max_uses || $t('a_unlimited') }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-500">{{ $t('a_applies_to') }}:</span>
                                <span class="font-medium text-gray-800">{{ applicabilityCount > 0 ? applicabilityCount + ' ' + $t('a_items') : $t('a_all') }}</span>
                            </div>
                            <div v-if="form.first_booking_only" class="flex justify-between">
                                <span class="text-gray-500">{{ $t('a_restriction') }}:</span>
                                <span class="font-medium text-amber-700">{{ $t('a_first_booking_only') }}</span>
                            </div>
                            <div v-if="form.show_on_website" class="flex justify-between">
                                <span class="text-gray-500">{{ $t('a_website_popup') }}:</span>
                                <span class="font-medium text-green-600">{{ $t('a_enabled') }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end">
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="w-full px-6 py-3 rounded-lg text-white font-medium text-sm transition disabled:opacity-50"
                            style="background-color: #C4A265;"
                        >
                            {{ form.processing ? $t('a_saving') : $t('a_create_discount_code') }}
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </AdminLayout>
</template>
