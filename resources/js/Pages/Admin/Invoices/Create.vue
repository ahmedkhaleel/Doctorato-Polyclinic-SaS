<script setup>
import { ref, computed, watch } from 'vue';
import { useForm, Link, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import SearchableSelect from '@/Components/Admin/SearchableSelect.vue';
import { useCurrency } from '@/Composables/useCurrency.js';

const { formatCurrency, currencyCode } = useCurrency();

const page = usePage();
const locale = computed(() => page.props.locale || 'ar');
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');
const modules = computed(() => page.props.modules || {});

const props = defineProps({
    patients: Array,
    visits: Array,
    services: Array,
});

// Module filter
const selectedModule = ref('');
const activeModules = computed(() => {
    return Object.entries(modules.value)
        .filter(([, m]) => m.is_enabled !== false)
        .map(([slug, m]) => ({ slug, name: isRtl.value ? m.name_ar : m.name_en }));
});

const form = useForm({
    patient_id: '',
    visit_id: '',
    invoice_date: new Date().toISOString().slice(0, 10),
    discount_code: '',
    discount_amount: 0,
    notes: '',
    patient_insurance_id: null,
    insurance_covered: 0,
    is_installment: false,
    installment_count: 3,
    items: [
        { service_id: '', description_en: '', description_ar: '', quantity: 1, unit_price: 0, discount: 0, total: 0 },
    ],
});

// Service options for dropdown (filtered by module)
const serviceOptions = computed(() => {
    let list = props.services || [];
    if (selectedModule.value) {
        list = list.filter(s => s.module === selectedModule.value);
    }
    return list.map(s => ({
        value: s.id,
        label: isRtl.value ? s.name_ar : s.name_en,
        name_en: s.name_en,
        name_ar: s.name_ar,
        price: s.price_after_discount || s.price,
    }));
});

// When a service is selected, auto-fill description and price
function onServiceSelect(item, serviceId) {
    item.service_id = serviceId;
    const service = (props.services || []).find(s => s.id == serviceId);
    if (service) {
        item.description_en = service.name_en || '';
        item.description_ar = service.name_ar || '';
        item.unit_price = service.price_after_discount || service.price || 0;
    }
}

// Calculate item total
function calcItemTotal(item) {
    const subtotal = (item.quantity || 0) * (item.unit_price || 0);
    item.total = Math.max(0, subtotal - (item.discount || 0));
}

// Watch items for auto-calc
watch(() => form.items, (items) => {
    items.forEach(calcItemTotal);
}, { deep: true });

const subtotal = computed(() => {
    return form.items.reduce((sum, item) => sum + (item.total || 0), 0);
});

const total = computed(() => {
    return Math.max(0, subtotal.value - (form.discount_amount || 0));
});

// Insurance integration
const selectedPatient = computed(() => props.patients?.find(p => p.id == form.patient_id));
const activeInsurance = computed(() => {
    const ins = selectedPatient.value?.active_insurance?.[0];
    return ins || null;
});
const insurancePlan = computed(() => activeInsurance.value?.plan);
const insuranceCompany = computed(() => activeInsurance.value?.company);

// Auto-calculate insurance coverage when patient or total changes
watch([() => form.patient_id, total], () => {
    if (activeInsurance.value && total.value > 0) {
        form.patient_insurance_id = activeInsurance.value.id;
        const plan = insurancePlan.value;
        if (plan) {
            const coveragePercent = plan.coverage_percentage || 0;
            const maxCoverage = plan.max_coverage_amount || Infinity;
            const copay = plan.copay_amount || 0;
            let covered = (total.value * coveragePercent / 100);
            covered = Math.min(covered, maxCoverage);
            // Check remaining balance if tracked
            if (activeInsurance.value.max_amount && activeInsurance.value.used_amount != null) {
                const remaining = activeInsurance.value.max_amount - activeInsurance.value.used_amount;
                covered = Math.min(covered, Math.max(0, remaining));
            }
            form.insurance_covered = Math.round(covered * 100) / 100;
        }
    } else {
        form.patient_insurance_id = null;
        form.insurance_covered = 0;
    }
});

const patientNet = computed(() => Math.max(0, total.value - form.insurance_covered));
const installmentAmount = computed(() => form.is_installment && form.installment_count > 0 ? Math.round(patientNet.value / form.installment_count * 100) / 100 : 0);

const discountCodeInput = ref('');
const discountCodeError = ref('');
const discountCodeSuccess = ref('');
const applyingDiscount = ref(false);

// Filter visits by selected patient
const filteredVisits = computed(() => {
    if (!form.patient_id) return [];
    return (props.visits || []).filter(v => v.patient_id == form.patient_id);
});

const patientOptions = computed(() => (props.patients || []).map(p => ({ value: p.id, label: p.full_name + ' - ' + p.phone })));
const visitOptions = computed(() => filteredVisits.value.map(v => ({ value: v.id, label: new Date(v.visit_date).toLocaleDateString('en-GB') + ' - ' + (v.service?.name_en || v.visit_type) })));

function addItem() {
    form.items.push({ service_id: '', description_en: '', description_ar: '', quantity: 1, unit_price: 0, discount: 0, total: 0 });
}

function removeItem(index) {
    if (form.items.length > 1) {
        form.items.splice(index, 1);
    }
}

async function applyDiscountCode() {
    if (!discountCodeInput.value) return;

    discountCodeError.value = '';
    discountCodeSuccess.value = '';
    applyingDiscount.value = true;

    try {
        const response = await fetch('/admin/api/discount-codes/validate', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                'Accept': 'application/json',
            },
            body: JSON.stringify({
                code: discountCodeInput.value,
                subtotal: subtotal.value,
            }),
        });

        const data = await response.json();

        if (response.ok && data.valid) {
            form.discount_code = discountCodeInput.value;
            form.discount_amount = data.discount_amount || 0;
            discountCodeSuccess.value = data.message || 'Discount applied!';
        } else {
            discountCodeError.value = data.message || 'Invalid discount code.';
        }
    } catch (e) {
        discountCodeError.value = 'Failed to validate discount code.';
    } finally {
        applyingDiscount.value = false;
    }
}

function removeDiscount() {
    form.discount_code = '';
    form.discount_amount = 0;
    discountCodeInput.value = '';
    discountCodeSuccess.value = '';
    discountCodeError.value = '';
}

function submit() {
    form.transform(data => ({
        ...data,
        patient_insurance_id: data.patient_insurance_id || null,
        insurance_covered: data.insurance_covered || 0,
        is_installment: data.is_installment || false,
        installment_count: data.is_installment ? data.installment_count : null,
    })).post('/admin/invoices');
}
</script>

<template>
    <AdminLayout :title="$t('a_create_invoice')">
        <div class="space-y-6">
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-bold text-gray-800">{{ $t('a_create_invoice') }}</h1>
                <Link href="/admin/invoices" class="text-sm text-gray-500 hover:text-gray-700">{{ $t('a_back_to_invoices') }}</Link>
            </div>

            <form @submit.prevent="submit" class="space-y-6">
                <!-- Patient & Visit Selection -->
                <div class="bg-white rounded-lg shadow-sm p-6 space-y-4">
                    <h2 class="text-lg font-semibold text-gray-700 border-b pb-2">{{ $t('a_invoice_details') }}</h2>

                    <!-- Module Selection (Department) -->
                    <div v-if="activeModules.length > 1" class="flex items-center gap-3 mb-4">
                        <span class="text-sm font-medium text-gray-700">{{ isRtl ? 'القسم:' : 'Department:' }}</span>
                        <button
                            v-for="mod in activeModules"
                            :key="mod.slug"
                            type="button"
                            @click="selectedModule = selectedModule === mod.slug ? '' : mod.slug"
                            class="px-4 py-2 rounded-lg text-sm font-medium border-2 transition"
                            :class="selectedModule === mod.slug
                                ? 'border-[#C4A265] bg-[#C4A265] text-white'
                                : 'border-gray-200 bg-white text-gray-600 hover:border-[#C4A265]'"
                        >
                            {{ mod.name }}
                        </button>
                        <button
                            v-if="selectedModule"
                            type="button"
                            @click="selectedModule = ''"
                            class="text-xs text-gray-400 hover:text-gray-600 underline"
                        >
                            {{ isRtl ? 'عرض الكل' : 'Show All' }}
                        </button>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('a_patient') }} <span class="text-red-500">*</span></label>
                            <SearchableSelect v-model="form.patient_id" :options="patientOptions" :placeholder="$t('a_select_patient')" :searchPlaceholder="$t('a_search_patients')" :error="form.errors.patient_id" />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('a_visit_optional') }}</label>
                            <SearchableSelect v-model="form.visit_id" :options="visitOptions" :placeholder="$t('a_no_visit')" :searchPlaceholder="$t('a_search_visits')" :disabled="!form.patient_id" />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('a_invoice_date') }} <span class="text-red-500">*</span></label>
                            <input
                                v-model="form.invoice_date"
                                type="date"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-yellow-200 focus:border-transparent"
                            />
                            <p v-if="form.errors.invoice_date" class="mt-1 text-sm text-red-600">{{ form.errors.invoice_date }}</p>
                        </div>
                    </div>
                </div>

                <!-- Line Items -->
                <div class="bg-white rounded-lg shadow-sm p-6 space-y-4">
                    <div class="flex items-center justify-between border-b pb-2">
                        <h2 class="text-lg font-semibold text-gray-700">{{ $t('a_items') }}</h2>
                        <button
                            type="button"
                            @click="addItem"
                            class="inline-flex items-center px-3 py-1 rounded text-sm font-medium text-white transition"
                            style="background-color: #C4A265;"
                        >
                            <svg class="w-4 h-4 ltr:mr-1 rtl:ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            {{ $t('a_add_item') }}
                        </button>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full">
                            <thead>
                                <tr class="text-xs text-gray-500 uppercase">
                                    <th class="text-start py-2 px-2 w-48">{{ isRtl ? 'العنصر' : 'Item' }}</th>
                                    <th class="text-start py-2 px-2">{{ $t('a_description_en') }}</th>
                                    <th class="text-start py-2 px-2">{{ $t('a_description_ar') }}</th>
                                    <th class="text-start py-2 px-2 w-20">{{ $t('a_qty') }}</th>
                                    <th class="text-start py-2 px-2 w-28">{{ $t('a_unit_price') }}</th>
                                    <th class="text-start py-2 px-2 w-28">{{ $t('a_discount') }}</th>
                                    <th class="text-end py-2 px-2 w-28">{{ $t('a_total') }}</th>
                                    <th class="py-2 px-2 w-12"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(item, index) in form.items" :key="index" class="border-t border-gray-100">
                                    <td class="py-2 px-2">
                                        <select
                                            :value="item.service_id"
                                            @change="onServiceSelect(item, $event.target.value)"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-yellow-200 focus:border-transparent"
                                        >
                                            <option value="">{{ isRtl ? 'اختر خدمة...' : 'Select service...' }}</option>
                                            <option v-for="s in serviceOptions" :key="s.value" :value="s.value">{{ s.label }}</option>
                                        </select>
                                    </td>
                                    <td class="py-2 px-2">
                                        <input
                                            v-model="item.description_en"
                                            type="text"
                                            :placeholder="$t('a_service_product')"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-yellow-200 focus:border-transparent"
                                        />
                                        <p v-if="form.errors[`items.${index}.description_en`]" class="mt-1 text-xs text-red-600">{{ form.errors[`items.${index}.description_en`] }}</p>
                                    </td>
                                    <td class="py-2 px-2">
                                        <input
                                            v-model="item.description_ar"
                                            type="text"
                                            dir="rtl"
                                            placeholder="الوصف بالعربي"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-yellow-200 focus:border-transparent"
                                        />
                                    </td>
                                    <td class="py-2 px-2">
                                        <input
                                            v-model.number="item.quantity"
                                            type="number"
                                            min="1"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-yellow-200 focus:border-transparent"
                                        />
                                    </td>
                                    <td class="py-2 px-2">
                                        <input
                                            v-model.number="item.unit_price"
                                            type="number"
                                            min="0"
                                            step="0.01"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-yellow-200 focus:border-transparent"
                                        />
                                    </td>
                                    <td class="py-2 px-2">
                                        <input
                                            v-model.number="item.discount"
                                            type="number"
                                            min="0"
                                            step="0.01"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-yellow-200 focus:border-transparent"
                                        />
                                    </td>
                                    <td class="py-2 px-2 text-end text-sm font-medium text-gray-900">
                                        {{ formatCurrency(item.total || 0) }}
                                    </td>
                                    <td class="py-2 px-2 text-center">
                                        <button
                                            v-if="form.items.length > 1"
                                            type="button"
                                            @click="removeItem(index)"
                                            class="text-red-400 hover:text-red-600 transition"
                                        >
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <p v-if="form.errors.items" class="text-sm text-red-600">{{ form.errors.items }}</p>
                </div>

                <!-- Discount Code & Totals -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <div class="bg-white rounded-lg shadow-sm p-6 space-y-4">
                        <h2 class="text-lg font-semibold text-gray-700 border-b pb-2">{{ $t('a_discount_code') }}</h2>

                        <div v-if="!form.discount_code" class="flex gap-2">
                            <input
                                v-model="discountCodeInput"
                                type="text"
                                :placeholder="$t('a_enter_discount_code')"
                                class="flex-1 px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-yellow-200 focus:border-transparent"
                            />
                            <button
                                type="button"
                                @click="applyDiscountCode"
                                :disabled="applyingDiscount || !discountCodeInput"
                                class="px-4 py-2 rounded-lg text-white text-sm font-medium transition disabled:opacity-50"
                                style="background-color: #C4A265;"
                            >
                                {{ applyingDiscount ? $t('a_applying') : $t('a_apply') }}
                            </button>
                        </div>
                        <div v-else class="flex items-center justify-between bg-green-50 p-3 rounded-lg">
                            <div>
                                <span class="text-sm font-medium text-green-800">{{ form.discount_code }}</span>
                                <span class="text-sm text-green-600 ltr:ml-2 rtl:mr-2">-{{ formatCurrency(form.discount_amount) }}</span>
                            </div>
                            <button type="button" @click="removeDiscount" class="text-red-500 hover:text-red-700 text-sm font-medium">{{ $t('a_remove') }}</button>
                        </div>

                        <p v-if="discountCodeError" class="text-sm text-red-600">{{ discountCodeError }}</p>
                        <p v-if="discountCodeSuccess" class="text-sm text-green-600">{{ discountCodeSuccess }}</p>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('a_notes') }}</label>
                            <textarea
                                v-model="form.notes"
                                rows="3"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-yellow-200 focus:border-transparent"
                                :placeholder="$t('a_optional_notes')"
                            ></textarea>
                        </div>
                    </div>

                    <div class="bg-white rounded-lg shadow-sm p-6 space-y-5">
                        <!-- Insurance Section -->
                        <div v-if="activeInsurance" class="bg-blue-50 rounded-xl p-4 border border-blue-100">
                            <div class="flex items-center gap-2 mb-3">
                                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                                <h3 class="font-semibold text-blue-800 text-sm">{{ isRtl ? 'تأمين صحي' : 'Insurance Coverage' }}</h3>
                            </div>
                            <div class="grid grid-cols-2 gap-2 text-xs">
                                <div>
                                    <span class="text-blue-600">{{ isRtl ? 'الشركة:' : 'Company:' }}</span>
                                    <span class="text-blue-800 font-medium ms-1">{{ isRtl ? insuranceCompany?.name_ar : insuranceCompany?.name_en }}</span>
                                </div>
                                <div>
                                    <span class="text-blue-600">{{ isRtl ? 'الخطة:' : 'Plan:' }}</span>
                                    <span class="text-blue-800 font-medium ms-1">{{ isRtl ? insurancePlan?.name_ar : insurancePlan?.name_en }}</span>
                                </div>
                                <div>
                                    <span class="text-blue-600">{{ isRtl ? 'نسبة التغطية:' : 'Coverage:' }}</span>
                                    <span class="text-blue-800 font-medium ms-1">{{ insurancePlan?.coverage_percentage || 0 }}%</span>
                                </div>
                                <div v-if="insurancePlan?.copay_amount">
                                    <span class="text-blue-600">{{ isRtl ? 'الدفع المشترك:' : 'Copay:' }}</span>
                                    <span class="text-blue-800 font-medium ms-1">{{ formatCurrency(insurancePlan.copay_amount) }}</span>
                                </div>
                            </div>
                            <div class="mt-3 pt-3 border-t border-blue-200">
                                <div class="flex items-center justify-between">
                                    <label class="text-xs font-medium text-blue-700">{{ isRtl ? 'المبلغ المغطى:' : 'Covered Amount:' }}</label>
                                    <input v-model.number="form.insurance_covered" type="number" min="0" :max="total" step="0.01" class="w-32 px-2 py-1 border border-blue-200 rounded-lg text-sm text-center focus:ring-blue-400 focus:border-blue-400 bg-white" />
                                </div>
                            </div>
                        </div>

                        <!-- Installment Option -->
                        <div class="border border-gray-100 rounded-xl p-4">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input v-model="form.is_installment" type="checkbox" class="rounded text-yellow-600 focus:ring-yellow-200" />
                                <span class="text-sm font-medium text-gray-700">{{ isRtl ? 'الدفع بالتقسيط' : 'Installment Payment' }}</span>
                            </label>
                            <div v-if="form.is_installment" class="mt-3 flex items-center gap-3">
                                <label class="text-xs text-gray-500">{{ isRtl ? 'عدد الأقساط:' : 'Number of installments:' }}</label>
                                <select v-model.number="form.installment_count" class="px-3 py-1.5 border border-gray-200 rounded-lg text-sm">
                                    <option v-for="n in [2,3,4,6,8,10,12]" :key="n" :value="n">{{ n }}</option>
                                </select>
                                <span class="text-xs text-gray-500">{{ isRtl ? 'القسط:' : 'Per installment:' }} <strong class="text-gray-800">{{ formatCurrency(installmentAmount) }}</strong></span>
                            </div>
                        </div>

                        <!-- Summary -->
                        <h2 class="text-lg font-semibold text-gray-700 border-b pb-2">{{ $t('a_summary') }}</h2>
                        <dl class="space-y-3">
                            <div class="flex justify-between text-sm">
                                <dt class="text-gray-500">{{ $t('a_subtotal') }}</dt>
                                <dd class="text-gray-900 font-medium">{{ formatCurrency(subtotal) }}</dd>
                            </div>
                            <div v-if="form.discount_amount > 0" class="flex justify-between text-sm">
                                <dt class="text-gray-500">{{ $t('a_discount') }}</dt>
                                <dd class="text-red-600 font-medium">-{{ formatCurrency(form.discount_amount) }}</dd>
                            </div>
                            <div class="flex justify-between text-sm font-medium border-t pt-2">
                                <dt class="text-gray-600">{{ $t('a_total') }}</dt>
                                <dd class="text-gray-900">{{ formatCurrency(total) }}</dd>
                            </div>
                            <div v-if="form.insurance_covered > 0" class="flex justify-between text-sm">
                                <dt class="text-blue-600">{{ isRtl ? 'تغطية التأمين' : 'Insurance Coverage' }}</dt>
                                <dd class="text-blue-600 font-medium">-{{ formatCurrency(form.insurance_covered) }}</dd>
                            </div>
                            <div class="flex justify-between text-lg font-bold border-t pt-3">
                                <dt class="text-gray-800">{{ isRtl ? 'المبلغ على المريض' : 'Patient Net' }}</dt>
                                <dd style="color: #C4A265;">{{ formatCurrency(patientNet) }}</dd>
                            </div>
                        </dl>

                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="w-full mt-4 px-6 py-3 rounded-lg text-white font-medium text-sm transition disabled:opacity-50"
                            style="background-color: #C4A265;"
                        >
                            {{ form.processing ? $t('a_creating') : $t('a_create_invoice') }}
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </AdminLayout>
</template>
