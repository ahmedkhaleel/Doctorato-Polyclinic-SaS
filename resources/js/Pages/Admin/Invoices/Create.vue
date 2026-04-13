<script setup>
import { ref, computed, watch, onMounted, nextTick } from 'vue';
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

// Animation state
const mounted = ref(false);
onMounted(() => {
    setTimeout(() => { mounted.value = true; }, 50);
});

// Only show enabled medical modules (derma, dental)
const medicalSlugs = ['derma', 'dental', 'pediatric'];
const selectedModule = ref('');
const activeModules = computed(() => {
    return Object.entries(modules.value)
        .filter(([slug, m]) => medicalSlugs.includes(slug) && m.enabled === true)
        .map(([slug, m]) => ({
            slug,
            name: isRtl.value ? m.name_ar : m.name_en,
            icon: m.icon || '',
            color: m.color || '#C4A265',
        }));
});

// Auto-select first module if only one
onMounted(() => {
    if (activeModules.value.length === 1) {
        selectedModule.value = activeModules.value[0].slug;
    }
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

// Current step tracking for visual progress
const currentStep = computed(() => {
    if (!form.patient_id) return 1;
    if (form.items.every(i => !i.service_id)) return 2;
    return 3;
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

const patientOptions = computed(() => (props.patients || []).map(p => ({
    value: p.id,
    label: p.full_name + (p.file_number ? ` (${p.file_number})` : '') + ' - ' + p.phone,
})));
const visitOptions = computed(() => filteredVisits.value.map(v => ({ value: v.id, label: new Date(v.visit_date).toLocaleDateString('en-GB') + ' - ' + (v.service?.name_en || v.visit_type) })));

// Item animation tracking
const itemAnimating = ref(new Set());

function addItem() {
    const newIndex = form.items.length;
    form.items.push({ service_id: '', description_en: '', description_ar: '', quantity: 1, unit_price: 0, discount: 0, total: 0 });
    itemAnimating.value.add(newIndex);
    nextTick(() => {
        setTimeout(() => { itemAnimating.value.delete(newIndex); }, 500);
    });
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
        <div class="invoice-create-page" :class="{ 'is-mounted': mounted }">
            <!-- Header -->
            <div class="invoice-header">
                <div class="invoice-header-content">
                    <div class="invoice-header-left">
                        <div class="invoice-icon-wrapper">
                            <svg class="invoice-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </div>
                        <div>
                            <h1 class="invoice-title">{{ $t('a_create_invoice') }}</h1>
                            <p class="invoice-subtitle">{{ isRtl ? 'إنشاء فاتورة جديدة للمريض' : 'Create a new patient invoice' }}</p>
                        </div>
                    </div>
                    <Link href="/admin/invoices" class="invoice-back-btn">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="isRtl ? 'M9 5l7 7-7 7' : 'M15 19l-7-7 7-7'" />
                        </svg>
                        {{ $t('a_back_to_invoices') }}
                    </Link>
                </div>

                <!-- Progress Steps -->
                <div class="progress-steps">
                    <div class="progress-step" :class="{ active: currentStep >= 1, completed: currentStep > 1 }">
                        <div class="step-circle">
                            <svg v-if="currentStep > 1" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                            <span v-else>1</span>
                        </div>
                        <span class="step-label">{{ isRtl ? 'بيانات المريض' : 'Patient Info' }}</span>
                    </div>
                    <div class="step-connector" :class="{ active: currentStep > 1 }"></div>
                    <div class="progress-step" :class="{ active: currentStep >= 2, completed: currentStep > 2 }">
                        <div class="step-circle">
                            <svg v-if="currentStep > 2" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                            <span v-else>2</span>
                        </div>
                        <span class="step-label">{{ isRtl ? 'الخدمات' : 'Services' }}</span>
                    </div>
                    <div class="step-connector" :class="{ active: currentStep > 2 }"></div>
                    <div class="progress-step" :class="{ active: currentStep >= 3 }">
                        <div class="step-circle"><span>3</span></div>
                        <span class="step-label">{{ isRtl ? 'المراجعة والإنشاء' : 'Review & Create' }}</span>
                    </div>
                </div>
            </div>

            <form @submit.prevent="submit" class="invoice-form">
                <!-- Section 1: Patient & Visit -->
                <div class="form-section section-delay-1">
                    <div class="section-header">
                        <div class="section-number">1</div>
                        <div>
                            <h2 class="section-title">{{ $t('a_invoice_details') }}</h2>
                            <p class="section-desc">{{ isRtl ? 'اختر المريض والقسم وتاريخ الفاتورة' : 'Select patient, department and invoice date' }}</p>
                        </div>
                    </div>

                    <!-- Module Selection -->
                    <div v-if="activeModules.length > 0" class="module-selector">
                        <label class="field-label">{{ isRtl ? 'التخصص الطبي' : 'Medical Specialty' }}</label>
                        <div class="module-chips">
                            <button
                                v-for="mod in activeModules"
                                :key="mod.slug"
                                type="button"
                                @click="selectedModule = selectedModule === mod.slug ? '' : mod.slug"
                                class="module-chip"
                                :class="{ selected: selectedModule === mod.slug }"
                                :style="selectedModule === mod.slug ? { '--chip-color': mod.color } : {}"
                            >
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" :d="mod.icon" />
                                </svg>
                                <span>{{ mod.name }}</span>
                                <div v-if="selectedModule === mod.slug" class="chip-check">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                </div>
                            </button>
                        </div>
                    </div>

                    <div class="fields-grid fields-grid-3">
                        <div class="field-group">
                            <label class="field-label">{{ $t('a_patient') }} <span class="required">*</span></label>
                            <SearchableSelect
                                v-model="form.patient_id"
                                :options="patientOptions"
                                :placeholder="$t('a_select_patient')"
                                :searchPlaceholder="$t('a_search_patients')"
                                :error="form.errors.patient_id"
                            />
                            <!-- Patient Quick Info -->
                            <Transition name="slide-fade">
                                <div v-if="selectedPatient" class="patient-badge">
                                    <svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                    <span>{{ selectedPatient.full_name }}</span>
                                    <span v-if="selectedPatient.file_number" class="patient-file-num">#{{ selectedPatient.file_number }}</span>
                                </div>
                            </Transition>
                        </div>

                        <div class="field-group">
                            <label class="field-label">{{ $t('a_visit_optional') }}</label>
                            <SearchableSelect
                                v-model="form.visit_id"
                                :options="visitOptions"
                                :placeholder="$t('a_no_visit')"
                                :searchPlaceholder="$t('a_search_visits')"
                                :disabled="!form.patient_id"
                            />
                        </div>

                        <div class="field-group">
                            <label class="field-label">{{ $t('a_invoice_date') }} <span class="required">*</span></label>
                            <div class="date-input-wrapper">
                                <svg class="date-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                <input
                                    v-model="form.invoice_date"
                                    type="date"
                                    class="date-input"
                                />
                            </div>
                            <p v-if="form.errors.invoice_date" class="field-error">{{ form.errors.invoice_date }}</p>
                        </div>
                    </div>
                </div>

                <!-- Section 2: Line Items -->
                <div class="form-section section-delay-2">
                    <div class="section-header">
                        <div class="section-number">2</div>
                        <div class="flex-1">
                            <h2 class="section-title">{{ $t('a_items') }}</h2>
                            <p class="section-desc">{{ isRtl ? 'أضف الخدمات والمنتجات للفاتورة' : 'Add services and products to the invoice' }}</p>
                        </div>
                        <button
                            type="button"
                            @click="addItem"
                            class="add-item-btn"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            {{ $t('a_add_item') }}
                        </button>
                    </div>

                    <!-- Items Cards (Mobile-Friendly) -->
                    <div class="items-container">
                        <TransitionGroup name="item-list">
                            <div
                                v-for="(item, index) in form.items"
                                :key="'item-' + index"
                                class="item-card"
                                :class="{ 'item-animating': itemAnimating.has(index) }"
                            >
                                <div class="item-card-header">
                                    <span class="item-number">#{{ index + 1 }}</span>
                                    <button
                                        v-if="form.items.length > 1"
                                        type="button"
                                        @click="removeItem(index)"
                                        class="item-remove-btn"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </div>

                                <!-- Service Selector -->
                                <div class="item-service-row">
                                    <label class="field-label-sm">{{ isRtl ? 'الخدمة' : 'Service' }}</label>
                                    <select
                                        :value="item.service_id"
                                        @change="onServiceSelect(item, $event.target.value)"
                                        class="item-select"
                                    >
                                        <option value="">{{ isRtl ? 'اختر خدمة...' : 'Select service...' }}</option>
                                        <option v-for="s in serviceOptions" :key="s.value" :value="s.value">{{ s.label }}</option>
                                    </select>
                                </div>

                                <!-- Descriptions Row -->
                                <div class="item-fields-row">
                                    <div class="item-field">
                                        <label class="field-label-sm">{{ $t('a_description_en') }}</label>
                                        <input
                                            v-model="item.description_en"
                                            type="text"
                                            :placeholder="$t('a_service_product')"
                                            class="item-input"
                                        />
                                        <p v-if="form.errors[`items.${index}.description_en`]" class="field-error">{{ form.errors[`items.${index}.description_en`] }}</p>
                                    </div>
                                    <div class="item-field">
                                        <label class="field-label-sm">{{ $t('a_description_ar') }}</label>
                                        <input
                                            v-model="item.description_ar"
                                            type="text"
                                            dir="rtl"
                                            placeholder="الوصف بالعربي"
                                            class="item-input"
                                        />
                                    </div>
                                </div>

                                <!-- Numbers Row -->
                                <div class="item-numbers-row">
                                    <div class="item-num-field">
                                        <label class="field-label-sm">{{ $t('a_qty') }}</label>
                                        <input v-model.number="item.quantity" type="number" min="1" class="item-input item-input-num" />
                                    </div>
                                    <div class="item-num-field">
                                        <label class="field-label-sm">{{ $t('a_unit_price') }}</label>
                                        <input v-model.number="item.unit_price" type="number" min="0" step="0.01" class="item-input item-input-num" />
                                    </div>
                                    <div class="item-num-field">
                                        <label class="field-label-sm">{{ $t('a_discount') }}</label>
                                        <input v-model.number="item.discount" type="number" min="0" step="0.01" class="item-input item-input-num" />
                                    </div>
                                    <div class="item-total-field">
                                        <label class="field-label-sm">{{ $t('a_total') }}</label>
                                        <div class="item-total-value">{{ formatCurrency(item.total || 0) }}</div>
                                    </div>
                                </div>
                            </div>
                        </TransitionGroup>
                    </div>
                    <p v-if="form.errors.items" class="field-error mt-2">{{ form.errors.items }}</p>
                </div>

                <!-- Section 3: Discount, Notes & Summary -->
                <div class="bottom-grid section-delay-3">
                    <!-- Left: Discount & Notes -->
                    <div class="form-section">
                        <div class="section-header">
                            <div class="section-number">3</div>
                            <div>
                                <h2 class="section-title">{{ $t('a_discount_code') }} &amp; {{ $t('a_notes') }}</h2>
                            </div>
                        </div>

                        <!-- Discount Code -->
                        <div class="discount-section">
                            <div v-if="!form.discount_code" class="discount-input-group">
                                <div class="discount-input-wrapper">
                                    <svg class="discount-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
                                    <input
                                        v-model="discountCodeInput"
                                        type="text"
                                        :placeholder="$t('a_enter_discount_code')"
                                        class="discount-input"
                                    />
                                </div>
                                <button
                                    type="button"
                                    @click="applyDiscountCode"
                                    :disabled="applyingDiscount || !discountCodeInput"
                                    class="discount-apply-btn"
                                >
                                    <svg v-if="applyingDiscount" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                                    {{ applyingDiscount ? $t('a_applying') : $t('a_apply') }}
                                </button>
                            </div>
                            <Transition name="slide-fade">
                                <div v-if="form.discount_code" class="discount-applied">
                                    <div class="discount-applied-info">
                                        <svg class="w-5 h-5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        <span class="discount-code-text">{{ form.discount_code }}</span>
                                        <span class="discount-amount-text">-{{ formatCurrency(form.discount_amount) }}</span>
                                    </div>
                                    <button type="button" @click="removeDiscount" class="discount-remove-btn">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                    </button>
                                </div>
                            </Transition>
                            <p v-if="discountCodeError" class="field-error">{{ discountCodeError }}</p>
                            <p v-if="discountCodeSuccess" class="field-success">{{ discountCodeSuccess }}</p>
                        </div>

                        <!-- Notes -->
                        <div class="notes-section">
                            <label class="field-label">{{ $t('a_notes') }}</label>
                            <textarea
                                v-model="form.notes"
                                rows="4"
                                class="notes-textarea"
                                :placeholder="$t('a_optional_notes')"
                            ></textarea>
                        </div>

                        <!-- Insurance Section -->
                        <Transition name="slide-fade">
                            <div v-if="activeInsurance" class="insurance-card">
                                <div class="insurance-header">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                                    <h3>{{ isRtl ? 'تأمين صحي' : 'Insurance Coverage' }}</h3>
                                </div>
                                <div class="insurance-details">
                                    <div class="insurance-detail">
                                        <span class="insurance-detail-label">{{ isRtl ? 'الشركة' : 'Company' }}</span>
                                        <span class="insurance-detail-value">{{ isRtl ? insuranceCompany?.name_ar : insuranceCompany?.name_en }}</span>
                                    </div>
                                    <div class="insurance-detail">
                                        <span class="insurance-detail-label">{{ isRtl ? 'الخطة' : 'Plan' }}</span>
                                        <span class="insurance-detail-value">{{ isRtl ? insurancePlan?.name_ar : insurancePlan?.name_en }}</span>
                                    </div>
                                    <div class="insurance-detail">
                                        <span class="insurance-detail-label">{{ isRtl ? 'نسبة التغطية' : 'Coverage' }}</span>
                                        <span class="insurance-detail-value insurance-pct">{{ insurancePlan?.coverage_percentage || 0 }}%</span>
                                    </div>
                                    <div v-if="insurancePlan?.copay_amount" class="insurance-detail">
                                        <span class="insurance-detail-label">{{ isRtl ? 'الدفع المشترك' : 'Copay' }}</span>
                                        <span class="insurance-detail-value">{{ formatCurrency(insurancePlan.copay_amount) }}</span>
                                    </div>
                                </div>
                                <div class="insurance-covered-input">
                                    <label>{{ isRtl ? 'المبلغ المغطى' : 'Covered Amount' }}</label>
                                    <input v-model.number="form.insurance_covered" type="number" min="0" :max="total" step="0.01" />
                                </div>
                            </div>
                        </Transition>
                    </div>

                    <!-- Right: Summary -->
                    <div class="summary-section section-delay-4">
                        <div class="summary-card">
                            <div class="summary-header">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                                <h2>{{ $t('a_summary') }}</h2>
                            </div>

                            <!-- Installment Option -->
                            <div class="installment-toggle">
                                <label class="installment-label">
                                    <div class="toggle-switch" :class="{ active: form.is_installment }">
                                        <input v-model="form.is_installment" type="checkbox" class="sr-only" />
                                        <div class="toggle-track">
                                            <div class="toggle-thumb"></div>
                                        </div>
                                    </div>
                                    <span>{{ isRtl ? 'الدفع بالتقسيط' : 'Installment Payment' }}</span>
                                </label>
                                <Transition name="slide-fade">
                                    <div v-if="form.is_installment" class="installment-config">
                                        <select v-model.number="form.installment_count" class="installment-select">
                                            <option v-for="n in [2,3,4,6,8,10,12]" :key="n" :value="n">{{ n }} {{ isRtl ? 'أقساط' : 'installments' }}</option>
                                        </select>
                                        <div class="installment-amount">
                                            <span class="installment-per">{{ isRtl ? 'القسط' : 'Per installment' }}</span>
                                            <span class="installment-value">{{ formatCurrency(installmentAmount) }}</span>
                                        </div>
                                    </div>
                                </Transition>
                            </div>

                            <!-- Summary Lines -->
                            <div class="summary-lines">
                                <div class="summary-line">
                                    <span class="summary-label">{{ $t('a_subtotal') }}</span>
                                    <span class="summary-value">{{ formatCurrency(subtotal) }}</span>
                                </div>

                                <Transition name="slide-fade">
                                    <div v-if="form.discount_amount > 0" class="summary-line discount-line">
                                        <span class="summary-label">{{ $t('a_discount') }}</span>
                                        <span class="summary-value text-red-500">-{{ formatCurrency(form.discount_amount) }}</span>
                                    </div>
                                </Transition>

                                <div class="summary-divider"></div>

                                <div class="summary-line total-line">
                                    <span class="summary-label">{{ $t('a_total') }}</span>
                                    <span class="summary-value">{{ formatCurrency(total) }}</span>
                                </div>

                                <Transition name="slide-fade">
                                    <div v-if="form.insurance_covered > 0" class="summary-line insurance-line">
                                        <span class="summary-label">
                                            <svg class="w-4 h-4 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                                            {{ isRtl ? 'تغطية التأمين' : 'Insurance' }}
                                        </span>
                                        <span class="summary-value text-blue-600">-{{ formatCurrency(form.insurance_covered) }}</span>
                                    </div>
                                </Transition>

                                <div class="summary-divider-thick"></div>

                                <div class="summary-line net-line">
                                    <span class="summary-label">{{ isRtl ? 'المبلغ على المريض' : 'Patient Net' }}</span>
                                    <span class="summary-value">{{ formatCurrency(patientNet) }}</span>
                                </div>
                            </div>

                            <!-- Submit Button -->
                            <button
                                type="submit"
                                :disabled="form.processing || !form.patient_id"
                                class="submit-btn"
                                :class="{ 'is-processing': form.processing }"
                            >
                                <svg v-if="form.processing" class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                                </svg>
                                <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                {{ form.processing ? $t('a_creating') : $t('a_create_invoice') }}
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </AdminLayout>
</template>

<style scoped>
/* ── Base & Animation ── */
.invoice-create-page {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 1rem;
}

.invoice-create-page > * {
    opacity: 0;
    transform: translateY(20px);
    transition: all 0.6s cubic-bezier(0.22, 1, 0.36, 1);
}

.invoice-create-page.is-mounted > * {
    opacity: 1;
    transform: translateY(0);
}

.section-delay-1 { transition-delay: 0.1s; }
.section-delay-2 { transition-delay: 0.2s; }
.section-delay-3 { transition-delay: 0.3s; }
.section-delay-4 { transition-delay: 0.4s; }

/* ── Header ── */
.invoice-header {
    margin-bottom: 2rem;
}

.invoice-header-content {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.invoice-header-left {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.invoice-icon-wrapper {
    width: 48px;
    height: 48px;
    border-radius: 14px;
    background: linear-gradient(135deg, #C4A265 0%, #D4B87A 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 4px 12px rgba(196, 162, 101, 0.3);
}

.invoice-icon {
    width: 24px;
    height: 24px;
    color: white;
}

.invoice-title {
    font-size: 1.5rem;
    font-weight: 700;
    color: #1f2937;
    line-height: 1.2;
}

.invoice-subtitle {
    font-size: 0.875rem;
    color: #9ca3af;
    margin-top: 2px;
}

.invoice-back-btn {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 0.875rem;
    color: #6b7280;
    padding: 8px 16px;
    border-radius: 10px;
    border: 1px solid #e5e7eb;
    background: white;
    transition: all 0.2s;
    text-decoration: none;
}

.invoice-back-btn:hover {
    color: #C4A265;
    border-color: #C4A265;
    background: #fffbf5;
}

/* ── Progress Steps ── */
.progress-steps {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0;
    padding: 16px 24px;
    background: white;
    border-radius: 16px;
    border: 1px solid #f3f4f6;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
}

.progress-step {
    display: flex;
    align-items: center;
    gap: 8px;
}

.step-circle {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.8rem;
    font-weight: 700;
    border: 2px solid #e5e7eb;
    color: #9ca3af;
    background: white;
    transition: all 0.4s cubic-bezier(0.22, 1, 0.36, 1);
}

.progress-step.active .step-circle {
    border-color: #C4A265;
    color: #C4A265;
    background: #fffbf5;
}

.progress-step.completed .step-circle {
    border-color: #10b981;
    color: white;
    background: #10b981;
}

.step-label {
    font-size: 0.8125rem;
    font-weight: 500;
    color: #9ca3af;
    transition: color 0.3s;
}

.progress-step.active .step-label {
    color: #374151;
}

.step-connector {
    width: 48px;
    height: 2px;
    background: #e5e7eb;
    margin: 0 12px;
    border-radius: 1px;
    transition: background 0.4s;
}

.step-connector.active {
    background: #10b981;
}

/* ── Form Sections ── */
.form-section {
    background: white;
    border-radius: 20px;
    padding: 28px;
    border: 1px solid #f3f4f6;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
    margin-bottom: 1.5rem;
    transition: box-shadow 0.3s;
}

.form-section:hover {
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.06);
}

.section-header {
    display: flex;
    align-items: flex-start;
    gap: 14px;
    margin-bottom: 24px;
}

.section-number {
    width: 32px;
    height: 32px;
    border-radius: 10px;
    background: linear-gradient(135deg, #C4A265, #D4B87A);
    color: white;
    font-weight: 700;
    font-size: 0.875rem;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.section-title {
    font-size: 1.1rem;
    font-weight: 700;
    color: #1f2937;
}

.section-desc {
    font-size: 0.8125rem;
    color: #9ca3af;
    margin-top: 2px;
}

.flex-1 { flex: 1; }

/* ── Module Selector ── */
.module-selector {
    margin-bottom: 24px;
}

.module-chips {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
    margin-top: 8px;
}

.module-chip {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 10px 20px;
    border-radius: 12px;
    border: 2px solid #e5e7eb;
    background: white;
    font-size: 0.875rem;
    font-weight: 600;
    color: #6b7280;
    cursor: pointer;
    transition: all 0.3s cubic-bezier(0.22, 1, 0.36, 1);
    position: relative;
    overflow: hidden;
}

.module-chip::before {
    content: '';
    position: absolute;
    inset: 0;
    background: var(--chip-color, transparent);
    opacity: 0;
    transition: opacity 0.3s;
}

.module-chip:hover {
    border-color: #d1d5db;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.06);
}

.module-chip.selected {
    border-color: var(--chip-color);
    color: white;
    box-shadow: 0 4px 16px color-mix(in srgb, var(--chip-color) 30%, transparent);
}

.module-chip.selected::before {
    opacity: 1;
}

.module-chip.selected > * {
    position: relative;
    z-index: 1;
}

.chip-check {
    width: 18px;
    height: 18px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.3);
    display: flex;
    align-items: center;
    justify-content: center;
}

/* ── Fields ── */
.fields-grid {
    display: grid;
    gap: 20px;
}

.fields-grid-3 {
    grid-template-columns: repeat(3, 1fr);
}

@media (max-width: 768px) {
    .fields-grid-3 {
        grid-template-columns: 1fr;
    }
}

.field-group {
    display: flex;
    flex-direction: column;
}

.field-label {
    display: block;
    font-size: 0.8125rem;
    font-weight: 600;
    color: #374151;
    margin-bottom: 6px;
}

.field-label-sm {
    display: block;
    font-size: 0.75rem;
    font-weight: 500;
    color: #6b7280;
    margin-bottom: 4px;
}

.required {
    color: #ef4444;
}

.field-error {
    font-size: 0.75rem;
    color: #ef4444;
    margin-top: 4px;
}

.field-success {
    font-size: 0.75rem;
    color: #10b981;
    margin-top: 4px;
}

/* Date Input */
.date-input-wrapper {
    position: relative;
}

.date-icon {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    width: 18px;
    height: 18px;
    color: #9ca3af;
    pointer-events: none;
}

[dir="rtl"] .date-icon,
.date-input-wrapper .date-icon {
    inset-inline-start: 12px;
}

.date-input {
    width: 100%;
    padding: 10px 12px;
    padding-inline-start: 40px;
    border: 1.5px solid #e5e7eb;
    border-radius: 12px;
    font-size: 0.875rem;
    color: #374151;
    background: white;
    transition: all 0.2s;
}

.date-input:focus {
    outline: none;
    border-color: #C4A265;
    box-shadow: 0 0 0 3px rgba(196, 162, 101, 0.1);
}

/* Patient Badge */
.patient-badge {
    display: flex;
    align-items: center;
    gap: 6px;
    margin-top: 8px;
    padding: 6px 12px;
    background: #f0fdf4;
    border: 1px solid #bbf7d0;
    border-radius: 8px;
    font-size: 0.8125rem;
    font-weight: 500;
    color: #166534;
}

.patient-file-num {
    color: #16a34a;
    font-weight: 400;
    font-size: 0.75rem;
}

/* ── Items ── */
.items-container {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.item-card {
    background: #fafbfc;
    border: 1.5px solid #f0f1f3;
    border-radius: 16px;
    padding: 20px;
    transition: all 0.3s cubic-bezier(0.22, 1, 0.36, 1);
}

.item-card:hover {
    border-color: #e5e7eb;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
}

.item-animating {
    animation: itemPop 0.5s cubic-bezier(0.22, 1, 0.36, 1);
}

@keyframes itemPop {
    0% { opacity: 0; transform: translateY(-10px) scale(0.98); }
    100% { opacity: 1; transform: translateY(0) scale(1); }
}

.item-card-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 14px;
}

.item-number {
    font-size: 0.75rem;
    font-weight: 700;
    color: #C4A265;
    background: rgba(196, 162, 101, 0.1);
    padding: 3px 10px;
    border-radius: 6px;
}

.item-remove-btn {
    color: #d1d5db;
    padding: 4px;
    border-radius: 8px;
    transition: all 0.2s;
    border: none;
    background: none;
    cursor: pointer;
}

.item-remove-btn:hover {
    color: #ef4444;
    background: #fef2f2;
}

.item-service-row {
    margin-bottom: 12px;
}

.item-select {
    width: 100%;
    padding: 10px 12px;
    border: 1.5px solid #e5e7eb;
    border-radius: 12px;
    font-size: 0.875rem;
    color: #374151;
    background: white;
    transition: all 0.2s;
    cursor: pointer;
    appearance: auto;
}

.item-select:focus {
    outline: none;
    border-color: #C4A265;
    box-shadow: 0 0 0 3px rgba(196, 162, 101, 0.1);
}

.item-fields-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
    margin-bottom: 12px;
}

.item-field {
    display: flex;
    flex-direction: column;
}

.item-input {
    width: 100%;
    padding: 9px 12px;
    border: 1.5px solid #e5e7eb;
    border-radius: 10px;
    font-size: 0.875rem;
    color: #374151;
    background: white;
    transition: all 0.2s;
}

.item-input:focus {
    outline: none;
    border-color: #C4A265;
    box-shadow: 0 0 0 3px rgba(196, 162, 101, 0.1);
}

.item-input-num {
    text-align: center;
    font-weight: 600;
}

.item-numbers-row {
    display: grid;
    grid-template-columns: 1fr 1.2fr 1fr 1.3fr;
    gap: 12px;
    align-items: end;
}

.item-num-field,
.item-total-field {
    display: flex;
    flex-direction: column;
}

.item-total-value {
    padding: 9px 12px;
    background: linear-gradient(135deg, #fffbf5, #fff8ed);
    border: 1.5px solid #f0e6d2;
    border-radius: 10px;
    font-size: 0.875rem;
    font-weight: 700;
    color: #C4A265;
    text-align: center;
}

@media (max-width: 640px) {
    .item-fields-row {
        grid-template-columns: 1fr;
    }
    .item-numbers-row {
        grid-template-columns: 1fr 1fr;
    }
}

/* ── Add Item Button ── */
.add-item-btn {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 8px 18px;
    border-radius: 10px;
    border: 2px dashed #C4A265;
    background: transparent;
    color: #C4A265;
    font-size: 0.8125rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s;
}

.add-item-btn:hover {
    background: #fffbf5;
    border-color: #b8934f;
    transform: translateY(-1px);
}

/* ── Bottom Grid ── */
.bottom-grid {
    display: grid;
    grid-template-columns: 1fr 420px;
    gap: 1.5rem;
    align-items: start;
}

@media (max-width: 1024px) {
    .bottom-grid {
        grid-template-columns: 1fr;
    }
}

/* ── Discount Section ── */
.discount-section {
    margin-bottom: 20px;
}

.discount-input-group {
    display: flex;
    gap: 10px;
}

.discount-input-wrapper {
    flex: 1;
    position: relative;
}

.discount-icon {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    inset-inline-start: 12px;
    width: 18px;
    height: 18px;
    color: #9ca3af;
    pointer-events: none;
}

.discount-input {
    width: 100%;
    padding: 10px 12px;
    padding-inline-start: 40px;
    border: 1.5px solid #e5e7eb;
    border-radius: 12px;
    font-size: 0.875rem;
    transition: all 0.2s;
}

.discount-input:focus {
    outline: none;
    border-color: #C4A265;
    box-shadow: 0 0 0 3px rgba(196, 162, 101, 0.1);
}

.discount-apply-btn {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 10px 20px;
    border-radius: 12px;
    background: linear-gradient(135deg, #C4A265, #D4B87A);
    color: white;
    font-size: 0.875rem;
    font-weight: 600;
    border: none;
    cursor: pointer;
    transition: all 0.2s;
    white-space: nowrap;
}

.discount-apply-btn:hover:not(:disabled) {
    box-shadow: 0 4px 12px rgba(196, 162, 101, 0.3);
    transform: translateY(-1px);
}

.discount-apply-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.discount-applied {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 12px 16px;
    background: #f0fdf4;
    border: 1px solid #bbf7d0;
    border-radius: 12px;
}

.discount-applied-info {
    display: flex;
    align-items: center;
    gap: 8px;
}

.discount-code-text {
    font-weight: 700;
    color: #166534;
    font-size: 0.875rem;
}

.discount-amount-text {
    font-weight: 600;
    color: #16a34a;
    font-size: 0.875rem;
}

.discount-remove-btn {
    color: #d1d5db;
    padding: 4px;
    border-radius: 8px;
    border: none;
    background: none;
    cursor: pointer;
    transition: all 0.2s;
}

.discount-remove-btn:hover {
    color: #ef4444;
    background: #fef2f2;
}

/* ── Notes ── */
.notes-section {
    margin-bottom: 20px;
}

.notes-textarea {
    width: 100%;
    padding: 12px 14px;
    border: 1.5px solid #e5e7eb;
    border-radius: 12px;
    font-size: 0.875rem;
    color: #374151;
    resize: vertical;
    transition: all 0.2s;
    min-height: 90px;
}

.notes-textarea:focus {
    outline: none;
    border-color: #C4A265;
    box-shadow: 0 0 0 3px rgba(196, 162, 101, 0.1);
}

/* ── Insurance Card ── */
.insurance-card {
    background: linear-gradient(135deg, #eff6ff, #f0f9ff);
    border: 1.5px solid #bfdbfe;
    border-radius: 16px;
    padding: 20px;
}

.insurance-header {
    display: flex;
    align-items: center;
    gap: 8px;
    color: #1e40af;
    font-weight: 700;
    font-size: 0.9375rem;
    margin-bottom: 16px;
}

.insurance-header h3 {
    margin: 0;
}

.insurance-details {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 10px;
    margin-bottom: 16px;
}

.insurance-detail {
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.insurance-detail-label {
    font-size: 0.6875rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: #60a5fa;
    font-weight: 500;
}

.insurance-detail-value {
    font-size: 0.8125rem;
    font-weight: 600;
    color: #1e3a5f;
}

.insurance-pct {
    color: #2563eb;
}

.insurance-covered-input {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding-top: 12px;
    border-top: 1px solid #bfdbfe;
    gap: 12px;
}

.insurance-covered-input label {
    font-size: 0.8125rem;
    font-weight: 600;
    color: #1e40af;
}

.insurance-covered-input input {
    width: 140px;
    padding: 8px 12px;
    border: 1.5px solid #93c5fd;
    border-radius: 10px;
    text-align: center;
    font-size: 0.875rem;
    font-weight: 600;
    color: #1e40af;
    background: white;
}

.insurance-covered-input input:focus {
    outline: none;
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

/* ── Summary Card ── */
.summary-section {
    position: sticky;
    top: 100px;
}

.summary-card {
    background: white;
    border-radius: 20px;
    padding: 28px;
    border: 1px solid #f3f4f6;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.06);
}

.summary-header {
    display: flex;
    align-items: center;
    gap: 8px;
    color: #1f2937;
    font-size: 1.1rem;
    font-weight: 700;
    margin-bottom: 20px;
    padding-bottom: 16px;
    border-bottom: 2px solid #f3f4f6;
}

.summary-header h2 {
    margin: 0;
}

/* ── Installment ── */
.installment-toggle {
    margin-bottom: 20px;
    padding: 16px;
    background: #fafbfc;
    border-radius: 14px;
    border: 1px solid #f0f1f3;
}

.installment-label {
    display: flex;
    align-items: center;
    gap: 12px;
    cursor: pointer;
    font-size: 0.875rem;
    font-weight: 600;
    color: #374151;
}

.toggle-switch {
    position: relative;
    flex-shrink: 0;
}

.toggle-track {
    width: 44px;
    height: 24px;
    background: #d1d5db;
    border-radius: 12px;
    transition: background 0.3s;
    position: relative;
}

.toggle-switch.active .toggle-track {
    background: #C4A265;
}

.toggle-thumb {
    width: 20px;
    height: 20px;
    background: white;
    border-radius: 50%;
    position: absolute;
    top: 2px;
    inset-inline-start: 2px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.15);
    transition: inset-inline-start 0.3s cubic-bezier(0.22, 1, 0.36, 1);
}

.toggle-switch.active .toggle-thumb {
    inset-inline-start: 22px;
}

.installment-config {
    margin-top: 14px;
    padding-top: 14px;
    border-top: 1px solid #e5e7eb;
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.installment-select {
    width: 100%;
    padding: 9px 12px;
    border: 1.5px solid #e5e7eb;
    border-radius: 10px;
    font-size: 0.875rem;
    font-weight: 500;
    color: #374151;
    background: white;
    cursor: pointer;
}

.installment-amount {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 10px 14px;
    background: linear-gradient(135deg, #fffbf5, #fff8ed);
    border-radius: 10px;
}

.installment-per {
    font-size: 0.8125rem;
    color: #9ca3af;
}

.installment-value {
    font-size: 1rem;
    font-weight: 700;
    color: #C4A265;
}

/* ── Summary Lines ── */
.summary-lines {
    display: flex;
    flex-direction: column;
    gap: 12px;
    margin-bottom: 24px;
}

.summary-line {
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.summary-label {
    font-size: 0.875rem;
    color: #6b7280;
    display: flex;
    align-items: center;
    gap: 6px;
}

.summary-value {
    font-size: 0.9375rem;
    font-weight: 600;
    color: #374151;
}

.summary-divider {
    height: 1px;
    background: #f3f4f6;
}

.summary-divider-thick {
    height: 2px;
    background: linear-gradient(to right, #C4A265, #D4B87A);
    border-radius: 1px;
    margin: 4px 0;
}

.total-line .summary-label {
    font-weight: 600;
    color: #374151;
}

.total-line .summary-value {
    font-size: 1rem;
}

.insurance-line .summary-label {
    color: #2563eb;
}

.net-line {
    padding-top: 4px;
}

.net-line .summary-label {
    font-size: 1rem;
    font-weight: 700;
    color: #1f2937;
}

.net-line .summary-value {
    font-size: 1.25rem;
    font-weight: 800;
    color: #C4A265;
}

/* ── Submit Button ── */
.submit-btn {
    width: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    padding: 14px 24px;
    border-radius: 14px;
    background: linear-gradient(135deg, #C4A265, #b8934f);
    color: white;
    font-size: 1rem;
    font-weight: 700;
    border: none;
    cursor: pointer;
    transition: all 0.3s cubic-bezier(0.22, 1, 0.36, 1);
    box-shadow: 0 4px 16px rgba(196, 162, 101, 0.3);
}

.submit-btn:hover:not(:disabled) {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(196, 162, 101, 0.4);
}

.submit-btn:active:not(:disabled) {
    transform: translateY(0);
}

.submit-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.submit-btn.is-processing {
    background: #9ca3af;
    box-shadow: none;
}

/* ── Transitions ── */
.slide-fade-enter-active {
    transition: all 0.3s cubic-bezier(0.22, 1, 0.36, 1);
}

.slide-fade-leave-active {
    transition: all 0.2s ease;
}

.slide-fade-enter-from {
    opacity: 0;
    transform: translateY(-8px);
}

.slide-fade-leave-to {
    opacity: 0;
    transform: translateY(-4px);
}

.item-list-enter-active {
    transition: all 0.4s cubic-bezier(0.22, 1, 0.36, 1);
}

.item-list-leave-active {
    transition: all 0.3s ease;
}

.item-list-enter-from {
    opacity: 0;
    transform: translateY(-10px) scale(0.98);
}

.item-list-leave-to {
    opacity: 0;
    transform: translateX(20px) scale(0.95);
}

.item-list-move {
    transition: transform 0.3s ease;
}

/* ── Utilities ── */
.sr-only {
    position: absolute;
    width: 1px;
    height: 1px;
    padding: 0;
    margin: -1px;
    overflow: hidden;
    clip: rect(0, 0, 0, 0);
    white-space: nowrap;
    border: 0;
}

.mt-2 { margin-top: 0.5rem; }
.animate-spin {
    animation: spin 1s linear infinite;
}

@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

/* ── Responsive ── */
@media (max-width: 640px) {
    .invoice-header-content {
        flex-direction: column;
        align-items: flex-start;
    }

    .progress-steps {
        flex-wrap: wrap;
        gap: 8px;
        justify-content: flex-start;
    }

    .step-connector {
        width: 24px;
        margin: 0 4px;
    }

    .step-label {
        display: none;
    }

    .form-section {
        padding: 20px 16px;
    }

    .bottom-grid {
        grid-template-columns: 1fr;
    }

    .summary-section {
        position: static;
    }
}
</style>
