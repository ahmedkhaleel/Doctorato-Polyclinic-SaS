<script setup>
import { ref, computed, watch } from 'vue';
import { useForm, Link , usePage } from '@inertiajs/vue3';
import SecretaryLayout from '@/Layouts/SecretaryLayout.vue';
import SearchableSelect from '@/Components/Admin/SearchableSelect.vue';
import { useCurrency } from '@/Composables/useCurrency.js';

defineOptions({ layout: SecretaryLayout });

const page = usePage();
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const { formatCurrency, currencyCode } = useCurrency();

const props = defineProps({
    patients: Array,
    services: Array,
    discountCodes: Array,
});

const form = useForm({
    patient_id: '',
    visit_id: '',
    discount_code_id: '',
    discount_amount: 0,
    tax_amount: 0,
    notes: '',
    items: [
        { description_en: '', description_ar: '', quantity: 1, unit_price: 0, discount: 0, total: 0 },
    ],
});

const discountCodeInput = ref('');
const discountCodeError = ref('');
const discountCodeSuccess = ref('');

const patientOptions = computed(() => (props.patients || []).map(p => ({ value: p.id, label: p.full_name + ' - ' + (p.phone || p.file_number || '') })));

function calcItemTotal(item) {
    const subtotal = (item.quantity || 0) * (item.unit_price || 0);
    item.total = Math.max(0, subtotal - (item.discount || 0));
}

watch(() => form.items, (items) => {
    items.forEach(calcItemTotal);
}, { deep: true });

const subtotal = computed(() => form.items.reduce((sum, item) => sum + (item.total || 0), 0));
const total = computed(() => Math.max(0, subtotal.value - (form.discount_amount || 0) + (form.tax_amount || 0)));

function addItem() {
    form.items.push({ description_en: '', description_ar: '', quantity: 1, unit_price: 0, discount: 0, total: 0 });
}

function removeItem(index) {
    if (form.items.length > 1) form.items.splice(index, 1);
}

function prefillFromService(index) {
    const item = form.items[index];
    if (!item.description_en) return;
    const service = (props.services || []).find(s => s.name_en === item.description_en || s.name_ar === item.description_en);
    if (service) {
        item.description_ar = service.name_ar || '';
        item.unit_price = parseFloat(service.price_after_discount) || parseFloat(service.price) || 0;
        calcItemTotal(item);
    }
}

async function applyDiscountCode() {
    if (!discountCodeInput.value) return;
    discountCodeError.value = '';
    discountCodeSuccess.value = '';

    const code = (props.discountCodes || []).find(dc => dc.code === discountCodeInput.value && dc.is_active);
    if (code) {
        form.discount_code_id = code.id;
        if (code.type === 'percentage') {
            form.discount_amount = Math.round(subtotal.value * code.value / 100);
        } else {
            form.discount_amount = code.value;
        }
        discountCodeSuccess.value = `Discount applied: ${code.value}${code.type === 'percentage' ? '%' : ' ' + currencyCode.value}`;
    } else {
        discountCodeError.value = 'Invalid or expired discount code.';
    }
}

function removeDiscount() {
    form.discount_code_id = '';
    form.discount_amount = 0;
    discountCodeInput.value = '';
    discountCodeSuccess.value = '';
    discountCodeError.value = '';
}

function submit() {
    form.post('/secretary/invoices');
}
</script>

<template>
    <div>
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">{{ isRtl ? 'إنشاء فاتورة' : 'Create Invoice' }}</h1>
                <p class="text-sm text-gray-500 mt-1">Create a new patient invoice</p>
            </div>
            <Link href="/secretary/invoices" class="text-sm text-gray-500 hover:text-gray-700">← Back to Invoices</Link>
        </div>

        <form @submit.prevent="submit" class="space-y-6">
            <!-- Patient Selection -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100/80 p-6">
                <h2 class="text-sm font-bold text-gray-800 mb-4 border-b border-gray-100 pb-2">{{ isRtl ? 'تفاصيل الفاتورة' : 'Invoice Details' }}</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Patient <span class="text-red-500">*</span></label>
                        <SearchableSelect v-model="form.patient_id" :options="patientOptions" placeholder="Select Patient" searchPlaceholder="Search patients..." :error="form.errors.patient_id" />
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">{{ isRtl ? 'ملاحظات' : 'Notes' }}</label>
                        <input v-model="form.notes" type="text" placeholder="Optional notes" class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-teal-500/30 focus:border-teal-500" />
                    </div>
                </div>
            </div>

            <!-- Line Items -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100/80 p-6">
                <div class="flex items-center justify-between border-b border-gray-100 pb-2 mb-4">
                    <h2 class="text-sm font-bold text-gray-800">Items</h2>
                    <button type="button" @click="addItem" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-teal-500 text-white rounded-lg text-xs font-semibold hover:bg-teal-600 transition">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                        Add Item
                    </button>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="text-xs text-gray-500 uppercase">
                                <th class="ltr:text-left rtl:ltr:text-right rtl:text-left py-2 px-2">Description (EN)</th>
                                <th class="ltr:text-left rtl:ltr:text-right rtl:text-left py-2 px-2">Description (AR)</th>
                                <th class="ltr:text-left rtl:ltr:text-right rtl:text-left py-2 px-2 w-20">Qty</th>
                                <th class="ltr:text-left rtl:ltr:text-right rtl:text-left py-2 px-2 w-28">Unit Price</th>
                                <th class="ltr:text-left rtl:ltr:text-right rtl:text-left py-2 px-2 w-28">{{ isRtl ? 'الخصم' : 'Discount' }}</th>
                                <th class="ltr:text-right rtl:text-left py-2 px-2 w-28">{{ isRtl ? 'الإجمالي' : 'Total' }}</th>
                                <th class="py-2 px-2 w-10"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(item, index) in form.items" :key="index" class="border-t border-gray-100">
                                <td class="py-2 px-2">
                                    <input v-model="item.description_en" @blur="prefillFromService(index)" type="text" placeholder="Service / Product" class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-teal-500/30 focus:border-teal-500" />
                                    <p v-if="form.errors[`items.${index}.description_ar`]" class="mt-1 text-xs text-red-600">{{ form.errors[`items.${index}.description_ar`] }}</p>
                                </td>
                                <td class="py-2 px-2">
                                    <input v-model="item.description_ar" type="text" dir="rtl" placeholder="الوصف بالعربي" class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-teal-500/30 focus:border-teal-500" />
                                </td>
                                <td class="py-2 px-2">
                                    <input v-model.number="item.quantity" type="number" min="1" class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-teal-500/30 focus:border-teal-500" />
                                </td>
                                <td class="py-2 px-2">
                                    <input v-model.number="item.unit_price" type="number" min="0" step="0.01" class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-teal-500/30 focus:border-teal-500" />
                                </td>
                                <td class="py-2 px-2">
                                    <input v-model.number="item.discount" type="number" min="0" step="0.01" class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-teal-500/30 focus:border-teal-500" />
                                </td>
                                <td class="py-2 px-2 ltr:text-right rtl:text-left text-sm font-bold text-gray-800">
                                    {{ formatCurrency(item.total) }}
                                </td>
                                <td class="py-2 px-2 text-center">
                                    <button v-if="form.items.length > 1" type="button" @click="removeItem(index)" class="text-red-400 hover:text-red-600 transition">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <p v-if="form.errors.items" class="text-sm text-red-600 mt-2">{{ form.errors.items }}</p>
            </div>

            <!-- Discount + Summary -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Discount Code -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100/80 p-6">
                    <h2 class="text-sm font-bold text-gray-800 mb-4 border-b border-gray-100 pb-2">Discount Code</h2>
                    <div v-if="!form.discount_code_id" class="flex gap-2">
                        <input v-model="discountCodeInput" type="text" placeholder="Enter discount code" class="flex-1 px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-teal-500/30 focus:border-teal-500" />
                        <button type="button" @click="applyDiscountCode" :disabled="!discountCodeInput" class="px-4 py-2.5 bg-teal-500 text-white rounded-xl text-sm font-semibold hover:bg-teal-600 transition disabled:opacity-50">{{ isRtl ? 'تطبيق' : 'Apply' }}</button>
                    </div>
                    <div v-else class="flex items-center justify-between bg-emerald-50 p-3 rounded-xl">
                        <div>
                            <span class="text-sm font-medium text-emerald-800">{{ discountCodeInput }}</span>
                            <span class="text-sm text-emerald-600 ltr:ml-2 rtl:mr-2">-{{ formatCurrency(form.discount_amount) }}</span>
                        </div>
                        <button type="button" @click="removeDiscount" class="text-red-500 hover:text-red-700 text-sm font-medium">{{ isRtl ? 'إزالة' : 'Remove' }}</button>
                    </div>
                    <p v-if="discountCodeError" class="text-sm text-red-600 mt-2">{{ discountCodeError }}</p>
                    <p v-if="discountCodeSuccess" class="text-sm text-emerald-600 mt-2">{{ discountCodeSuccess }}</p>
                </div>

                <!-- Summary -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100/80 p-6">
                    <h2 class="text-sm font-bold text-gray-800 mb-4 border-b border-gray-100 pb-2">Summary</h2>
                    <dl class="space-y-3">
                        <div class="flex justify-between text-sm">
                            <dt class="text-gray-500">Subtotal</dt>
                            <dd class="text-gray-900 font-medium">{{ formatCurrency(subtotal) }}</dd>
                        </div>
                        <div v-if="form.discount_amount > 0" class="flex justify-between text-sm">
                            <dt class="text-gray-500">{{ isRtl ? 'الخصم' : 'Discount' }}</dt>
                            <dd class="text-red-600 font-medium">-{{ formatCurrency(form.discount_amount) }}</dd>
                        </div>
                        <div class="flex justify-between text-lg font-bold border-t border-gray-100 pt-3">
                            <dt class="text-gray-800">{{ isRtl ? 'الإجمالي' : 'Total' }}</dt>
                            <dd class="text-teal-600">{{ formatCurrency(total) }}</dd>
                        </div>
                    </dl>

                    <button type="submit" :disabled="form.processing" class="w-full mt-6 px-6 py-3 bg-gradient-to-r from-teal-500 to-cyan-500 text-white rounded-xl font-semibold text-sm hover:from-teal-600 hover:to-cyan-600 transition-all shadow-lg shadow-teal-500/20 disabled:opacity-50">
                        {{ form.processing ? 'Creating...' : 'Create Invoice' }}
                    </button>
                </div>
            </div>
        </form>
    </div>
</template>
