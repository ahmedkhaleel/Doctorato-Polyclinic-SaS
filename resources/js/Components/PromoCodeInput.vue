<script setup>
import { ref, watch, onMounted, computed } from 'vue';
import { useLocale } from '@/Composables/useLocale';
import { useCurrency } from '@/Composables/useCurrency';
import axios from 'axios';

const props = defineProps({
    modelValue: { type: String, default: '' },
    bookingType: { type: String, default: 'service' },
    serviceId: { type: [Number, String], default: null },
    packageBundleId: { type: [Number, String], default: null },
    amount: { type: Number, default: 0 },
});

const emit = defineEmits(['update:modelValue', 'validated', 'cleared']);

const { locale, isRtl } = useLocale();
const { formatCurrency, currencyCode } = useCurrency();

const isExpanded = ref(false);
const codeInput = ref('');
const isLoading = ref(false);
const validationResult = ref(null);
const isApplied = ref(false);

const hasError = computed(() => validationResult.value && !validationResult.value.valid);
const hasSuccess = computed(() => validationResult.value && validationResult.value.valid);

const errorMessage = computed(() => {
    if (!validationResult.value || validationResult.value.valid) return '';
    const messages = {
        invalid_code: locale.value === 'ar' ? 'كود الخصم غير صالح' : 'Invalid promo code',
        code_inactive: locale.value === 'ar' ? 'كود الخصم غير مفعل' : 'This code is not active',
        code_not_started: locale.value === 'ar' ? 'كود الخصم لم يبدا بعد' : 'This code is not yet active',
        code_expired: locale.value === 'ar' ? 'كود الخصم منتهي الصلاحية' : 'This code has expired',
        max_uses_reached: locale.value === 'ar' ? 'تم استخدام كود الخصم بالكامل' : 'This code has reached its maximum uses',
        not_applicable_service: locale.value === 'ar' ? 'كود الخصم لا ينطبق على هذه الخدمة' : 'This code does not apply to this service',
        not_applicable_package: locale.value === 'ar' ? 'كود الخصم لا ينطبق على هذه الباقة' : 'This code does not apply to this package',
        min_amount_not_met: locale.value === 'ar' ? 'الحد الادنى للمبلغ غير متحقق' : 'Minimum order amount not met',
        patient_limit_exceeded: locale.value === 'ar' ? 'لقد وصلت للحد الاقصى لاستخدام هذا الكود' : 'You have reached the usage limit for this code',
        first_booking_only: locale.value === 'ar' ? 'هذا الكود للحجز الاول فقط' : 'This code is for first booking only',
    };
    return messages[validationResult.value.message] || (locale.value === 'ar' ? 'كود غير صالح' : 'Invalid code');
});

const discountPreviewText = computed(() => {
    if (!validationResult.value?.valid) return '';
    const r = validationResult.value;

    if (r.discount_preview) {
        return locale.value === 'ar'
            ? `خصم ${formatCurrency(r.discount_preview)}`
            : `${formatCurrency(r.discount_preview)} discount`;
    }

    if (r.discount_type === 'percentage') {
        let text = locale.value === 'ar'
            ? `خصم ${Number(r.discount_value)}%`
            : `${Number(r.discount_value)}% discount`;
        if (r.max_discount_amount) {
            text += locale.value === 'ar'
                ? ` (بحد اقصى ${formatCurrency(r.max_discount_amount)})`
                : ` (max ${formatCurrency(r.max_discount_amount)})`;
        }
        return text;
    }

    return locale.value === 'ar'
        ? `خصم ${formatCurrency(r.discount_value)}`
        : `${formatCurrency(r.discount_value)} discount`;
});

async function applyCode() {
    const code = codeInput.value.trim();
    if (!code || isLoading.value) return;

    isLoading.value = true;
    validationResult.value = null;

    try {
        const { data } = await axios.post('/api/promo-code/validate', {
            code,
            booking_type: props.bookingType,
            service_id: props.serviceId || null,
            package_bundle_id: props.packageBundleId || null,
            amount: props.amount || 0,
        });

        validationResult.value = data;

        if (data.valid) {
            isApplied.value = true;
            emit('update:modelValue', code);
            emit('validated', data);
        }
    } catch (error) {
        validationResult.value = {
            valid: false,
            message: 'invalid_code',
        };
    } finally {
        isLoading.value = false;
    }
}

function removeCode() {
    codeInput.value = '';
    validationResult.value = null;
    isApplied.value = false;
    emit('update:modelValue', '');
    emit('cleared');
}

// Watch for external modelValue changes
watch(() => props.modelValue, (newVal) => {
    if (newVal && newVal !== codeInput.value) {
        codeInput.value = newVal;
    }
});

// Check if a code was stored from the popup
onMounted(() => {
    const storedCode = localStorage.getItem('promo_code_applied');
    if (storedCode) {
        codeInput.value = storedCode;
        isExpanded.value = true;
        localStorage.removeItem('promo_code_applied');
        // Auto-validate
        applyCode();
    } else if (props.modelValue) {
        codeInput.value = props.modelValue;
        isExpanded.value = true;
    }
});
</script>

<template>
    <div class="promoCodeInput">
        <!-- Collapsible header -->
        <button
            type="button"
            @click="isExpanded = !isExpanded"
            class="flex items-center gap-2 text-sm font-medium transition-colors duration-200"
            :class="isApplied ? 'text-emerald-600' : 'text-[#C4A265] hover:text-[#A68B52]'"
        >
            <!-- Gift icon -->
            <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V5.5A2.5 2.5 0 109.5 8H12zm-7 4h14M5 12a2 2 0 110-4h14a2 2 0 110 4M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7" />
            </svg>
            <span>{{ locale === 'ar' ? 'هل لديك كود خصم؟' : 'Have a promo code?' }}</span>
            <!-- Chevron -->
            <svg
                class="w-4 h-4 transition-transform duration-200"
                :class="{ 'rotate-180': isExpanded }"
                fill="none" stroke="currentColor" viewBox="0 0 24 24"
            >
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
            </svg>
        </button>

        <!-- Expandable content -->
        <Transition
            enter-active-class="transition-all duration-300 ease-out"
            enter-from-class="opacity-0 max-h-0"
            enter-to-class="opacity-100 max-h-40"
            leave-active-class="transition-all duration-200 ease-in"
            leave-from-class="opacity-100 max-h-40"
            leave-to-class="opacity-0 max-h-0"
        >
            <div v-if="isExpanded" class="mt-3 overflow-hidden">
                <!-- Applied state -->
                <div v-if="isApplied && hasSuccess" class="flex items-center gap-3 p-3 bg-emerald-50 border border-emerald-200 rounded-xl">
                    <div class="flex-shrink-0 w-8 h-8 rounded-lg bg-emerald-100 flex items-center justify-center">
                        <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                        </svg>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2">
                            <span class="text-sm font-bold text-emerald-800 font-mono tracking-wider">{{ codeInput }}</span>
                        </div>
                        <p class="text-xs text-emerald-600 mt-0.5">{{ discountPreviewText }}</p>
                    </div>
                    <button
                        type="button"
                        @click="removeCode"
                        class="flex-shrink-0 text-xs font-medium text-red-500 hover:text-red-700 px-2 py-1 rounded hover:bg-red-50 transition-colors"
                    >
                        {{ locale === 'ar' ? 'ازالة' : 'Remove' }}
                    </button>
                </div>

                <!-- Input state -->
                <div v-else>
                    <div class="flex gap-2" :class="{ 'flex-row-reverse': isRtl }">
                        <div class="flex-1 relative">
                            <input
                                v-model="codeInput"
                                type="text"
                                :placeholder="locale === 'ar' ? 'ادخل كود الخصم' : 'Enter promo code'"
                                class="doctorato-input w-full px-4 py-2.5 border rounded-xl text-sm font-medium uppercase tracking-wider transition-all duration-200 focus:outline-none focus:ring-2"
                                :class="[ hasError ? 'border-red-300 focus:ring-[#C4A265]/30 focus:border-red-400 bg-red-50/50' : 'border-gray-200 focus:ring-[#C4A265]/20 focus:border-[#C4A265] bg-gray-50', isRtl ? 'text-right' : 'text-left' ]"
                                :dir="isRtl ? 'rtl' : 'ltr'"
                                @keyup.enter="applyCode"
                                :disabled="isLoading"
                            />
                            <!-- Loading spinner inside input -->
                            <div v-if="isLoading" class="absolute top-1/2 -translate-y-1/2" :class="isRtl ? 'left-3' : 'right-3'">
                                <svg class="w-5 h-5 text-[#C4A265] animate-spin" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
                                </svg>
                            </div>
                        </div>
                        <button
                            type="button"
                            @click="applyCode"
                            :disabled="!codeInput.trim() || isLoading"
                            class="px-5 py-2.5 rounded-xl text-sm font-semibold text-white transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed bg-[#C4A265] hover:bg-[#A68B52] shadow-sm hover:shadow"
                        >
                            {{ locale === 'ar' ? 'تطبيق' : 'Apply' }}
                        </button>
                    </div>

                    <!-- Error message -->
                    <Transition
                        enter-active-class="transition-all duration-200 ease-out"
                        enter-from-class="opacity-0 -translate-y-1"
                        enter-to-class="opacity-100 translate-y-0"
                        leave-active-class="transition-all duration-150 ease-in"
                        leave-from-class="opacity-100"
                        leave-to-class="opacity-0"
                    >
                        <p v-if="hasError" class="mt-2 text-xs text-red-500 flex items-center gap-1.5" :class="isRtl ? 'text-right' : 'text-left'">
                            <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            {{ errorMessage }}
                        </p>
                    </Transition>
                </div>
            </div>
        </Transition>
    </div>
</template>
