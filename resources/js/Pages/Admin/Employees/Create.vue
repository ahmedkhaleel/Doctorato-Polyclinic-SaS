<script setup>
import { ref, computed, onMounted } from 'vue';
import { useForm, Link, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { useCurrency } from '@/Composables/useCurrency.js';

const { currencyCode } = useCurrency();

const page = usePage();
const locale = computed(() => page.props.locale || 'ar');
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

defineProps({
    departments: Array,
    availableUsers: Array,
});

const form = useForm({
    user_id: '',
    department_id: '',
    job_title_ar: '',
    job_title_en: '',
    hire_date: '',
    contract_type: 'full_time',
    contract_end_date: '',
    basic_salary: '',
    housing_allowance: '',
    transport_allowance: '',
    other_allowances: '',
    national_id: '',
    phone: '',
    emergency_contact_name: '',
    emergency_contact_phone: '',
    address: '',
    bank_name: '',
    bank_account_number: '',
    insurance_number: '',
});

const currentStep = ref(0);
const mounted = ref(false);

onMounted(() => {
    setTimeout(() => { mounted.value = true; }, 50);
});

const stepKeys = [
    { id: 'basic',    tKey: 'a_basic_info',       icon: 'user',   shortTKey: 'a_basic' },
    { id: 'salary',   tKey: 'a_salary',           icon: 'wallet', shortTKey: 'a_salary' },
    { id: 'personal', tKey: 'a_personal',         icon: 'id',     shortTKey: 'a_personal' },
    { id: 'bank',     tKey: 'a_bank_insurance',   icon: 'bank',   shortTKey: 'a_bank' },
];

const totalSalary = computed(() => {
    const basic = parseFloat(form.basic_salary) || 0;
    const housing = parseFloat(form.housing_allowance) || 0;
    const transport = parseFloat(form.transport_allowance) || 0;
    const other = parseFloat(form.other_allowances) || 0;
    return basic + housing + transport + other;
});

function nextStep() {
    if (currentStep.value < stepKeys.length - 1) currentStep.value++;
}
function prevStep() {
    if (currentStep.value > 0) currentStep.value--;
}
function goToStep(idx) {
    currentStep.value = idx;
}

function submit() {
    form.post('/admin/employees');
}
</script>

<template>
    <AdminLayout :title="$t('a_create_employee')">
        <div class="emp-create" :class="{ 'is-mounted': mounted }">
            <!-- Page Header -->
            <div class="emp-header">
                <div class="emp-header__left">
                    <Link href="/admin/employees" class="emp-header__back">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
                    </Link>
                    <div>
                        <h1 class="emp-header__title">{{ $t('a_new_employee') }}</h1>
                        <p class="emp-header__sub">{{ $t('a_new_employee_subtitle') }}</p>
                    </div>
                </div>
                <div class="emp-header__badge">
                    <span class="emp-header__step-label">{{ $t('a_step') }} {{ currentStep + 1 }} {{ $t('a_of') }} {{ stepKeys.length }}</span>
                </div>
            </div>

            <!-- Stepper Progress -->
            <div class="stepper">
                <div class="stepper__track">
                    <div class="stepper__fill" :style="{ width: ((currentStep) / (stepKeys.length - 1)) * 100 + '%' }"></div>
                </div>
                <button
                    v-for="(step, idx) in stepKeys"
                    :key="step.id"
                    type="button"
                    @click="goToStep(idx)"
                    class="stepper__node"
                    :class="{
                        'is-active': idx === currentStep,
                        'is-done': idx < currentStep,
                    }"
                >
                    <span class="stepper__dot">
                        <svg v-if="idx < currentStep" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" /></svg>
                        <span v-else class="stepper__num">{{ idx + 1 }}</span>
                    </span>
                    <span class="stepper__label hidden sm:block">{{ $t(step.tKey) }}</span>
                    <span class="stepper__label sm:hidden">{{ $t(step.shortTKey) }}</span>
                </button>
            </div>

            <form @submit.prevent="submit">
                <div class="emp-card">
                    <!-- Step 1: Basic Information -->
                    <Transition name="step-slide" mode="out-in">
                    <div v-if="currentStep === 0" key="basic" class="emp-panel">
                        <div class="emp-panel__header">
                            <div class="emp-panel__icon" style="background: linear-gradient(135deg, #C4A265, #D4B87A);">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                            </div>
                            <div>
                                <h2 class="emp-panel__title">{{ $t('a_basic_information') }}</h2>
                                <p class="emp-panel__desc">{{ $t('a_basic_info_desc') }}</p>
                            </div>
                        </div>

                        <div class="emp-panel__body">
                            <div class="emp-field-group">
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                                    <div class="sm:col-span-2">
                                        <label class="emp-label">{{ $t('a_user') }} <span class="text-red-400">*</span></label>
                                        <div class="emp-select-wrap">
                                            <select v-model="form.user_id" class="doctorato-input emp-input">
                                                <option value="" disabled>{{ $t('a_select_user') }}</option>
                                                <option v-for="user in availableUsers" :key="user.id" :value="user.id">
                                                    {{ user.name }} ({{ user.email }})
                                                </option>
                                            </select>
                                            <div class="emp-select-arrow">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                                            </div>
                                        </div>
                                        <p v-if="form.errors.user_id" class="emp-error">{{ form.errors.user_id }}</p>
                                    </div>

                                    <div class="sm:col-span-2">
                                        <label class="emp-label">{{ $t('a_department') }}</label>
                                        <div class="emp-select-wrap">
                                            <select v-model="form.department_id" class="doctorato-input emp-input">
                                                <option value="" disabled>{{ $t('a_select_department') }}</option>
                                                <option v-for="dept in departments" :key="dept.id" :value="dept.id">
                                                    {{ dept.name_en }} - {{ dept.name_ar }}
                                                </option>
                                            </select>
                                            <div class="emp-select-arrow">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                                            </div>
                                        </div>
                                        <p v-if="form.errors.department_id" class="emp-error">{{ form.errors.department_id }}</p>
                                    </div>

                                    <div>
                                        <label class="emp-label">{{ $t('a_job_title_en') }}</label>
                                        <input v-model="form.job_title_en" type="text" :placeholder="$t('a_job_title_en_placeholder')" class="doctorato-input emp-input" />
                                        <p v-if="form.errors.job_title_en" class="emp-error">{{ form.errors.job_title_en }}</p>
                                    </div>
                                    <div>
                                        <label class="emp-label">{{ $t('a_job_title_ar') }}</label>
                                        <input v-model="form.job_title_ar" type="text" dir="rtl" :placeholder="$t('a_job_title_ar_placeholder')" class="doctorato-input emp-input" />
                                        <p v-if="form.errors.job_title_ar" class="emp-error">{{ form.errors.job_title_ar }}</p>
                                    </div>

                                    <div>
                                        <label class="emp-label">{{ $t('a_hire_date') }}</label>
                                        <input v-model="form.hire_date" type="date" class="doctorato-input emp-input" />
                                        <p v-if="form.errors.hire_date" class="emp-error">{{ form.errors.hire_date }}</p>
                                    </div>
                                    <div>
                                        <label class="emp-label">{{ $t('a_contract_type') }}</label>
                                        <div class="emp-select-wrap">
                                            <select v-model="form.contract_type" class="doctorato-input emp-input">
                                                <option value="full_time">{{ $t('a_full_time') }}</option>
                                                <option value="part_time">{{ $t('a_part_time') }}</option>
                                                <option value="contract">{{ $t('a_contract') }}</option>
                                            </select>
                                            <div class="emp-select-arrow">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                                            </div>
                                        </div>
                                        <p v-if="form.errors.contract_type" class="emp-error">{{ form.errors.contract_type }}</p>
                                    </div>

                                    <div v-if="form.contract_type !== 'full_time'" class="sm:col-span-2">
                                        <label class="emp-label">{{ $t('a_contract_end_date') }}</label>
                                        <input v-model="form.contract_end_date" type="date" class="doctorato-input emp-input" />
                                        <p v-if="form.errors.contract_end_date" class="emp-error">{{ form.errors.contract_end_date }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    </Transition>

                    <!-- Step 2: Salary & Allowances -->
                    <Transition name="step-slide" mode="out-in">
                    <div v-if="currentStep === 1" key="salary" class="emp-panel">
                        <div class="emp-panel__header">
                            <div class="emp-panel__icon" style="background: linear-gradient(135deg, #059669, #10b981);">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            </div>
                            <div>
                                <h2 class="emp-panel__title">{{ $t('a_salary_allowances') }}</h2>
                                <p class="emp-panel__desc">{{ $t('a_salary_allowances_desc') }}</p>
                            </div>
                        </div>

                        <div class="emp-panel__body">
                            <!-- Total Salary Preview -->
                            <div class="salary-preview">
                                <div class="salary-preview__label">{{ $t('a_total_package') }}</div>
                                <div class="salary-preview__amount">{{ totalSalary.toLocaleString('en-US', { minimumFractionDigits: 2 }) }}</div>
                                <div class="salary-preview__currency">{{ currencyCode }} / {{ $t('a_month') }}</div>
                            </div>

                            <div class="emp-field-group">
                                <div class="grid grid-cols-1 gap-5">
                                    <div>
                                        <label class="emp-label">{{ $t('a_basic_salary') }} <span class="text-red-400">*</span></label>
                                        <div class="emp-input-with-addon">
                                            <span class="emp-addon">{{ currencyCode }}</span>
                                            <input v-model="form.basic_salary" type="number" step="0.01" min="0" placeholder="0.00" class="doctorato-input emp-input emp-input--addon" />
                                        </div>
                                        <p v-if="form.errors.basic_salary" class="emp-error">{{ form.errors.basic_salary }}</p>
                                    </div>
                                </div>

                                <div class="emp-divider">
                                    <span>{{ $t('a_allowances') }}</span>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
                                    <div>
                                        <label class="emp-label">{{ $t('a_housing') }}</label>
                                        <div class="emp-input-with-addon">
                                            <span class="emp-addon">{{ currencyCode }}</span>
                                            <input v-model="form.housing_allowance" type="number" step="0.01" min="0" placeholder="0.00" class="doctorato-input emp-input emp-input--addon" />
                                        </div>
                                        <p v-if="form.errors.housing_allowance" class="emp-error">{{ form.errors.housing_allowance }}</p>
                                    </div>
                                    <div>
                                        <label class="emp-label">{{ $t('a_transport') }}</label>
                                        <div class="emp-input-with-addon">
                                            <span class="emp-addon">{{ currencyCode }}</span>
                                            <input v-model="form.transport_allowance" type="number" step="0.01" min="0" placeholder="0.00" class="doctorato-input emp-input emp-input--addon" />
                                        </div>
                                        <p v-if="form.errors.transport_allowance" class="emp-error">{{ form.errors.transport_allowance }}</p>
                                    </div>
                                    <div>
                                        <label class="emp-label">{{ $t('a_other') }}</label>
                                        <div class="emp-input-with-addon">
                                            <span class="emp-addon">{{ currencyCode }}</span>
                                            <input v-model="form.other_allowances" type="number" step="0.01" min="0" placeholder="0.00" class="doctorato-input emp-input emp-input--addon" />
                                        </div>
                                        <p v-if="form.errors.other_allowances" class="emp-error">{{ form.errors.other_allowances }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    </Transition>

                    <!-- Step 3: Personal Information -->
                    <Transition name="step-slide" mode="out-in">
                    <div v-if="currentStep === 2" key="personal" class="emp-panel">
                        <div class="emp-panel__header">
                            <div class="emp-panel__icon" style="background: linear-gradient(135deg, #7c3aed, #8b5cf6);">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2" /></svg>
                            </div>
                            <div>
                                <h2 class="emp-panel__title">{{ $t('a_personal_information') }}</h2>
                                <p class="emp-panel__desc">{{ $t('a_personal_info_desc') }}</p>
                            </div>
                        </div>

                        <div class="emp-panel__body">
                            <div class="emp-field-group">
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                                    <div>
                                        <label class="emp-label">{{ $t('a_national_id') }}</label>
                                        <div class="emp-input-icon-wrap">
                                            <svg class="emp-input-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 9h3.75M15 12h3.75M15 15h3.75M4.5 19.5h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5zm6-10.125a1.875 1.875 0 11-3.75 0 1.875 1.875 0 013.75 0zm1.294 6.336a6.721 6.721 0 01-3.17.789 6.721 6.721 0 01-3.168-.789 3.376 3.376 0 016.338 0z" /></svg>
                                            <input v-model="form.national_id" type="text" :placeholder="$t('a_national_id_placeholder')" class="doctorato-input emp-input emp-input--icon" />
                                        </div>
                                        <p v-if="form.errors.national_id" class="emp-error">{{ form.errors.national_id }}</p>
                                    </div>
                                    <div>
                                        <label class="emp-label">{{ $t('a_phone') }}</label>
                                        <div class="emp-input-icon-wrap">
                                            <svg class="emp-input-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 01-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 00-1.091-.852H4.5A2.25 2.25 0 002.25 4.5v2.25z" /></svg>
                                            <input v-model="form.phone" type="text" :placeholder="$t('a_phone_placeholder')" class="doctorato-input emp-input emp-input--icon" />
                                        </div>
                                        <p v-if="form.errors.phone" class="emp-error">{{ form.errors.phone }}</p>
                                    </div>
                                </div>

                                <div class="emp-divider">
                                    <span>{{ $t('a_emergency_contact') }}</span>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                                    <div>
                                        <label class="emp-label">{{ $t('a_contact_name') }}</label>
                                        <input v-model="form.emergency_contact_name" type="text" :placeholder="$t('a_emergency_contact_name_placeholder')" class="doctorato-input emp-input" />
                                        <p v-if="form.errors.emergency_contact_name" class="emp-error">{{ form.errors.emergency_contact_name }}</p>
                                    </div>
                                    <div>
                                        <label class="emp-label">{{ $t('a_contact_phone') }}</label>
                                        <input v-model="form.emergency_contact_phone" type="text" :placeholder="$t('a_emergency_contact_phone_placeholder')" class="doctorato-input emp-input" />
                                        <p v-if="form.errors.emergency_contact_phone" class="emp-error">{{ form.errors.emergency_contact_phone }}</p>
                                    </div>
                                    <div class="sm:col-span-2">
                                        <label class="emp-label">{{ $t('a_address') }}</label>
                                        <textarea v-model="form.address" rows="3" :placeholder="$t('a_address_placeholder')" class="doctorato-input emp-input emp-textarea"></textarea>
                                        <p v-if="form.errors.address" class="emp-error">{{ form.errors.address }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    </Transition>

                    <!-- Step 4: Bank & Insurance -->
                    <Transition name="step-slide" mode="out-in">
                    <div v-if="currentStep === 3" key="bank" class="emp-panel">
                        <div class="emp-panel__header">
                            <div class="emp-panel__icon" style="background: linear-gradient(135deg, #0369a1, #0ea5e9);">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 21v-8.25M15.75 21v-8.25M8.25 21v-8.25M3 9l9-6 9 6m-1.5 12V10.332A48.36 48.36 0 0012 9.75c-2.551 0-5.056.2-7.5.582V21M3 21h18M12 6.75h.008v.008H12V6.75z" /></svg>
                            </div>
                            <div>
                                <h2 class="emp-panel__title">{{ $t('a_bank_insurance') }}</h2>
                                <p class="emp-panel__desc">{{ $t('a_bank_insurance_desc') }}</p>
                            </div>
                        </div>

                        <div class="emp-panel__body">
                            <div class="emp-field-group">
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                                    <div>
                                        <label class="emp-label">{{ $t('a_bank_name') }}</label>
                                        <div class="emp-input-icon-wrap">
                                            <svg class="emp-input-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 21v-8.25M15.75 21v-8.25M8.25 21v-8.25M3 9l9-6 9 6m-1.5 12V10.332A48.36 48.36 0 0012 9.75c-2.551 0-5.056.2-7.5.582V21M3 21h18M12 6.75h.008v.008H12V6.75z" /></svg>
                                            <input v-model="form.bank_name" type="text" :placeholder="$t('a_bank_name_placeholder')" class="doctorato-input emp-input emp-input--icon" />
                                        </div>
                                        <p v-if="form.errors.bank_name" class="emp-error">{{ form.errors.bank_name }}</p>
                                    </div>
                                    <div>
                                        <label class="emp-label">{{ $t('a_account_number') }}</label>
                                        <div class="emp-input-icon-wrap">
                                            <svg class="emp-input-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5z" /></svg>
                                            <input v-model="form.bank_account_number" type="text" :placeholder="$t('a_account_number_placeholder')" class="doctorato-input emp-input emp-input--icon" />
                                        </div>
                                        <p v-if="form.errors.bank_account_number" class="emp-error">{{ form.errors.bank_account_number }}</p>
                                    </div>
                                    <div class="sm:col-span-2">
                                        <label class="emp-label">{{ $t('a_insurance_number') }}</label>
                                        <div class="emp-input-icon-wrap">
                                            <svg class="emp-input-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" /></svg>
                                            <input v-model="form.insurance_number" type="text" :placeholder="$t('a_insurance_number_placeholder')" class="doctorato-input emp-input emp-input--icon" />
                                        </div>
                                        <p v-if="form.errors.insurance_number" class="emp-error">{{ form.errors.insurance_number }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    </Transition>
                </div>

                <!-- Footer Actions -->
                <div class="emp-footer">
                    <div class="emp-footer__left">
                        <button
                            v-if="currentStep > 0"
                            type="button"
                            @click="prevStep"
                            class="emp-btn emp-btn--ghost"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
                            <span>{{ $t('a_previous') }}</span>
                        </button>
                        <Link v-else href="/admin/employees" class="emp-btn emp-btn--ghost">
                            <span>{{ $t('a_cancel') }}</span>
                        </Link>
                    </div>
                    <div class="emp-footer__right">
                        <button
                            v-if="currentStep < stepKeys.length - 1"
                            type="button"
                            @click="nextStep"
                            class="emp-btn emp-btn--primary"
                        >
                            <span>{{ $t('a_next_step') }}</span>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                        </button>
                        <button
                            v-else
                            type="submit"
                            :disabled="form.processing"
                            class="emp-btn emp-btn--submit"
                        >
                            <svg v-if="!form.processing" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                            <svg v-else class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                            <span>{{ form.processing ? $t('a_creating') : $t('a_create_employee') }}</span>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </AdminLayout>
</template>

<style scoped>
/* ═══════════════════════════════════════════════
   CREATE EMPLOYEE — Multi-Step Wizard
   ═══════════════════════════════════════════════ */

.emp-create {
    max-width: 860px;
    margin: 0 auto;
    opacity: 0;
    transform: translateY(10px);
    transition: opacity 0.5s cubic-bezier(0.16, 1, 0.3, 1), transform 0.5s cubic-bezier(0.16, 1, 0.3, 1);
}
.emp-create.is-mounted {
    opacity: 1;
    transform: translateY(0);
}

/* ── Header ────────────────────── */
.emp-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 1.75rem;
}
.emp-header__left {
    display: flex;
    align-items: center;
    gap: 0.875rem;
}
.emp-header__back {
    width: 38px;
    height: 38px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #fff;
    border: 1px solid #e2e8f0;
    color: #64748b;
    transition: all 0.2s ease;
    flex-shrink: 0;
}
.emp-header__back:hover {
    border-color: #C4A265;
    color: #C4A265;
    box-shadow: 0 2px 8px rgba(196,162,101,0.15);
}
.emp-header__title {
    font-family: 'Poppins', sans-serif;
    font-size: 1.5rem;
    font-weight: 700;
    color: #1e293b;
    letter-spacing: -0.02em;
}
.emp-header__sub {
    font-size: 0.8125rem;
    color: #94a3b8;
}
.emp-header__badge {
    background: linear-gradient(135deg, #1E1E1E, #2a2520);
    padding: 0.375rem 0.875rem;
    border-radius: 20px;
}
.emp-header__step-label {
    font-size: 0.6875rem;
    font-weight: 600;
    color: #C4A265;
    letter-spacing: 0.03em;
}

/* ── Stepper ───────────────────── */
.stepper {
    position: relative;
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    margin-bottom: 1.75rem;
    padding: 0 0.5rem;
}
.stepper__track {
    position: absolute;
    top: 17px;
    left: 40px;
    right: 40px;
    height: 3px;
    background: #e2e8f0;
    border-radius: 3px;
    z-index: 0;
}
.stepper__fill {
    height: 100%;
    background: linear-gradient(90deg, #C4A265, #D4B87A);
    border-radius: 3px;
    transition: width 0.5s cubic-bezier(0.16, 1, 0.3, 1);
}
.stepper__node {
    position: relative;
    z-index: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.5rem;
    background: none;
    border: none;
    cursor: pointer;
    padding: 0;
}
.stepper__dot {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #fff;
    border: 2.5px solid #e2e8f0;
    color: #94a3b8;
    font-size: 0.8125rem;
    font-weight: 700;
    transition: all 0.35s cubic-bezier(0.16, 1, 0.3, 1);
}
.stepper__node.is-active .stepper__dot {
    border-color: #C4A265;
    background: #C4A265;
    color: #fff;
    box-shadow: 0 0 0 4px rgba(196,162,101,0.15), 0 2px 8px rgba(196,162,101,0.3);
    transform: scale(1.1);
}
.stepper__node.is-done .stepper__dot {
    border-color: #C4A265;
    background: #C4A265;
    color: #fff;
}
.stepper__num {
    font-family: 'Poppins', sans-serif;
}
.stepper__label {
    font-size: 0.6875rem;
    font-weight: 600;
    color: #94a3b8;
    transition: color 0.3s ease;
    white-space: nowrap;
}
.stepper__node.is-active .stepper__label {
    color: #C4A265;
}
.stepper__node.is-done .stepper__label {
    color: #64748b;
}

/* ── Card & Panel ───────────────── */
.emp-card {
    min-height: 380px;
}
.emp-panel {
    background: #fff;
    border-radius: 16px;
    border: 1px solid rgba(0,0,0,0.06);
    box-shadow: 0 1px 3px rgba(0,0,0,0.04), 0 8px 24px rgba(0,0,0,0.02);
    overflow: hidden;
}

/* Step Transitions */
.step-slide-enter-active {
    transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
}
.step-slide-leave-active {
    transition: all 0.2s cubic-bezier(0.4, 0, 1, 1);
}
.step-slide-enter-from {
    opacity: 0;
    transform: translateX(30px);
}
.step-slide-leave-to {
    opacity: 0;
    transform: translateX(-20px);
}

/* Panel Header */
.emp-panel__header {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1.25rem 1.75rem;
    border-bottom: 1px solid rgba(0,0,0,0.06);
    background: linear-gradient(180deg, #faf9f7 0%, #fff 100%);
}
.emp-panel__icon {
    width: 42px;
    height: 42px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    flex-shrink: 0;
    box-shadow: 0 2px 6px rgba(0,0,0,0.15);
}
.emp-panel__title {
    font-family: 'Poppins', sans-serif;
    font-size: 1.0625rem;
    font-weight: 600;
    color: #1e293b;
    letter-spacing: -0.01em;
}
.emp-panel__desc {
    font-size: 0.75rem;
    color: #94a3b8;
    margin-top: 1px;
}
.emp-panel__body {
    padding: 1.75rem;
}

/* ── Fields ──────────────────── */
.emp-field-group {
    display: flex;
    flex-direction: column;
    gap: 1.25rem;
}
.emp-label {
    display: block;
    font-size: 0.75rem;
    font-weight: 600;
    color: #475569;
    margin-bottom: 0.375rem;
    letter-spacing: 0.01em;
}
.emp-input {
    width: 100%;
    padding: 0.5625rem 0.875rem;
    border: 1.5px solid #e2e8f0;
    border-radius: 10px;
    font-size: 0.8125rem;
    color: #1e293b;
    background: #fff;
    transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
    outline: none;
    font-family: 'Poppins', sans-serif;
    -webkit-appearance: none;
}
.emp-input:hover {
    border-color: #cbd5e1;
}
.emp-input:focus {
    border-color: #C4A265;
    box-shadow: 0 0 0 3px rgba(196,162,101,0.1), 0 1px 2px rgba(0,0,0,0.04);
}
.emp-input::placeholder {
    color: #cbd5e1;
}
.emp-textarea {
    resize: vertical;
    min-height: 44px;
}
.emp-error {
    font-size: 0.75rem;
    color: #ef4444;
    margin-top: 0.25rem;
    font-weight: 500;
}

/* Select wrapper */
.emp-select-wrap {
    position: relative;
}
.emp-select-wrap select {
    appearance: none;
    padding-right: 2.5rem;
}
.emp-select-arrow {
    position: absolute;
    right: 0.75rem;
    top: 50%;
    transform: translateY(-50%);
    color: #94a3b8;
    pointer-events: none;
}

/* Input with currency addon */
.emp-input-with-addon {
    position: relative;
    display: flex;
}
.emp-addon {
    position: absolute;
    left: 0;
    top: 0;
    bottom: 0;
    display: flex;
    align-items: center;
    padding: 0 0.875rem;
    font-size: 0.75rem;
    font-weight: 700;
    color: #C4A265;
    background: #faf9f7;
    border: 1.5px solid #e2e8f0;
    border-right: none;
    border-radius: 10px 0 0 10px;
    letter-spacing: 0.02em;
}
.emp-input--addon {
    padding-left: 4rem;
    border-radius: 0 10px 10px 0 !important;
}

/* Input with icon */
.emp-input-icon-wrap {
    position: relative;
}
.emp-input-icon {
    position: absolute;
    left: 0.75rem;
    top: 50%;
    transform: translateY(-50%);
    width: 18px;
    height: 18px;
    color: #94a3b8;
    pointer-events: none;
    z-index: 1;
}
.emp-input--icon {
    padding-left: 2.5rem;
}

/* Divider */
.emp-divider {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin: 0.25rem 0;
}
.emp-divider span {
    font-size: 0.6875rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.1em;
    color: #C4A265;
    white-space: nowrap;
}
.emp-divider::after {
    content: '';
    flex: 1;
    height: 1px;
    background: linear-gradient(90deg, rgba(196,162,101,0.25), transparent);
}

/* ── Salary Preview ──────────────── */
.salary-preview {
    text-align: center;
    padding: 1.5rem;
    margin-bottom: 1.75rem;
    border-radius: 14px;
    background: linear-gradient(135deg, #1E1E1E 0%, #2a2520 50%, #1E1E1E 100%);
    position: relative;
    overflow: hidden;
}
.salary-preview::before {
    content: '';
    position: absolute;
    inset: 0;
    background: radial-gradient(circle at 50% 50%, rgba(196,162,101,0.08) 0%, transparent 70%);
    pointer-events: none;
}
.salary-preview__label {
    font-size: 0.625rem;
    text-transform: uppercase;
    letter-spacing: 0.15em;
    color: rgba(196,162,101,0.6);
    font-weight: 600;
    margin-bottom: 0.375rem;
}
.salary-preview__amount {
    font-family: 'Poppins', sans-serif;
    font-size: 2.25rem;
    font-weight: 700;
    color: #C4A265;
    letter-spacing: -0.02em;
    line-height: 1.1;
    transition: all 0.3s ease;
}
.salary-preview__currency {
    font-size: 0.75rem;
    color: rgba(255,255,255,0.35);
    margin-top: 0.25rem;
}

/* ── Footer Actions ──────────────── */
.emp-footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-top: 1.5rem;
    padding: 1rem 1.25rem;
    background: #fff;
    border-radius: 14px;
    border: 1px solid rgba(0,0,0,0.06);
    box-shadow: 0 1px 3px rgba(0,0,0,0.04), 0 4px 12px rgba(0,0,0,0.02);
}
.emp-footer__left,
.emp-footer__right {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

/* Buttons */
.emp-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.625rem 1.25rem;
    border-radius: 10px;
    font-size: 0.8125rem;
    font-weight: 600;
    font-family: 'Poppins', sans-serif;
    cursor: pointer;
    transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
    border: none;
    text-decoration: none;
    white-space: nowrap;
}
.emp-btn--ghost {
    background: #f8fafc;
    color: #64748b;
    border: 1px solid #e2e8f0;
}
.emp-btn--ghost:hover {
    background: #f1f5f9;
    border-color: #cbd5e1;
    color: #334155;
}
.emp-btn--primary {
    background: linear-gradient(135deg, #1E1E1E, #2a2520);
    color: #C4A265;
    box-shadow: 0 2px 8px rgba(0,0,0,0.15);
}
.emp-btn--primary:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 14px rgba(0,0,0,0.2);
}
.emp-btn--submit {
    background: linear-gradient(135deg, #C4A265 0%, #b8953a 100%);
    color: #fff;
    box-shadow: 0 2px 8px rgba(196,162,101,0.3);
}
.emp-btn--submit:hover:not(:disabled) {
    transform: translateY(-1px);
    box-shadow: 0 4px 14px rgba(196,162,101,0.4);
}
.emp-btn--submit:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

/* ── Responsive ──────────────── */
@media (max-width: 640px) {
    .emp-header { flex-direction: column; align-items: flex-start; gap: 0.75rem; }
    .emp-panel__body { padding: 1.25rem; }
    .emp-panel__header { padding: 1rem 1.25rem; }
    .emp-footer { padding: 0.875rem 1rem; }
    .salary-preview__amount { font-size: 1.75rem; }
}
</style>
