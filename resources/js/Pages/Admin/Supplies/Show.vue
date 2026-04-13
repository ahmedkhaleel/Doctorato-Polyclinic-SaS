<script setup>
import { ref, computed, onMounted } from 'vue';
import { Link, router, useForm, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { usePermissions } from '@/Composables/usePermissions.js';
import { useCurrency } from '@/Composables/useCurrency.js';

defineOptions({ layout: AdminLayout });

const { can } = usePermissions();
const { formatCurrency } = useCurrency();

const page = usePage();
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const ACCENT = '#6366F1';

const props = defineProps({
    supply: Object,
    transactions: Object,
    stockPercentage: Number,
});

// ── Display Name ──────────────────────────────────────────────────
function displayName(item) {
    if (!item) return '-';
    return isRtl.value ? (item.name_ar || item.name_en) : (item.name_en || item.name_ar);
}

// ── Transaction Form ──────────────────────────────────────────────
const showTransactionForm = ref(false);

const form = useForm({
    transaction_type: 'purchase',
    quantity: '',
    unit_cost: '',
    notes: '',
});

function submitTransaction() {
    form.post(`/admin/supplies/${props.supply.id}/transactions`, {
        onSuccess: () => {
            form.reset();
            showTransactionForm.value = false;
        },
    });
}

// ── Delete ────────────────────────────────────────────────────────
function deleteSupply() {
    if (window.confirm('Are you sure you want to delete this supply item? This action cannot be undone.')) {
        router.delete(`/admin/supplies/${props.supply.id}`);
    }
}

// ── Helpers ───────────────────────────────────────────────────────
function formatDate(d) {
    if (!d) return '-';
    return new Date(d).toLocaleDateString('en-GB', {
        month: 'short', day: 'numeric', year: 'numeric',
        hour: '2-digit', minute: '2-digit',
    });
}

function formatShortDate(d) {
    if (!d) return '-';
    return new Date(d).toLocaleDateString('en-GB', { month: 'short', day: 'numeric', year: 'numeric' });
}

function isLowStock() {
    return props.supply.quantity <= props.supply.min_quantity;
}

// ── Expiry helpers ────────────────────────────────────────────────
const daysUntilExpiry = computed(() => {
    if (!props.supply.expiry_date) return null;
    const now = new Date();
    const expiry = new Date(props.supply.expiry_date);
    return Math.ceil((expiry - now) / (1000 * 60 * 60 * 24));
});

const isExpired = computed(() => daysUntilExpiry.value !== null && daysUntilExpiry.value <= 0);
const isExpiringSoon = computed(() => daysUntilExpiry.value !== null && daysUntilExpiry.value > 0 && daysUntilExpiry.value <= 30);

// ── Transaction type config ───────────────────────────────────────
const typeConfig = {
    purchase:         { label: 'Purchase',        bg: 'bg-emerald-50', text: 'text-emerald-700', dot: 'bg-emerald-500', sign: '+' },
    usage:            { label: 'Usage',            bg: 'bg-blue-50',    text: 'text-blue-700',    dot: 'bg-blue-500',    sign: '-' },
    adjustment:       { label: 'Adjustment',       bg: 'bg-amber-50',   text: 'text-amber-700',   dot: 'bg-amber-500',   sign: '' },
    return:           { label: 'Return',           bg: 'bg-violet-50',  text: 'text-violet-700',  dot: 'bg-violet-500',  sign: '+' },
    manual_deduction: { label: 'Manual Deduction', bg: 'bg-red-50',     text: 'text-red-700',     dot: 'bg-red-500',     sign: '-' },
};

function txnConfig(type) {
    return typeConfig[type] || { label: type, bg: 'bg-gray-50', text: 'text-gray-700', dot: 'bg-gray-400', sign: '' };
}

// ── Stock gauge ───────────────────────────────────────────────────
const gaugeColor = computed(() => {
    if (props.stockPercentage > 50) return '#22c55e';
    if (props.stockPercentage > 25) return '#eab308';
    return '#ef4444';
});

const gaugeCircumference = 2 * Math.PI * 54;

const gaugeDashArray = computed(() => {
    const filled = (props.stockPercentage / 100) * gaugeCircumference;
    return `${filled} ${gaugeCircumference}`;
});

// ── Animations ────────────────────────────────────────────────────
const mounted = ref(false);
const cardsVisible = ref(false);
const timelineVisible = ref(false);

onMounted(() => {
    requestAnimationFrame(() => { mounted.value = true; });
    setTimeout(() => { cardsVisible.value = true; }, 200);
    setTimeout(() => { timelineVisible.value = true; }, 400);
});
</script>

<template>
    <div class="space-y-6">
        <!-- ══════════════════════════════════════════════════════
             HERO HEADER
             ══════════════════════════════════════════════════════ -->
        <div
            class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-indigo-900 via-indigo-800 to-purple-900 p-6 sm:p-8 transition-all duration-700"
            :style="{ opacity: mounted ? 1 : 0, transform: mounted ? 'translateY(0)' : 'translateY(12px)', transitionTimingFunction: 'cubic-bezier(0.16, 1, 0.3, 1)' }"
        >
            <!-- Decorative Elements -->
            <div class="absolute top-0 right-0 w-64 h-64 bg-white/5 rounded-full -translate-y-1/2 translate-x-1/2 blur-3xl"></div>
            <div class="absolute bottom-0 left-0 w-48 h-48 bg-purple-400/10 rounded-full translate-y-1/2 -translate-x-1/4 blur-2xl"></div>
            <div class="absolute inset-0 opacity-[0.03]" style="background-image: radial-gradient(circle, white 1px, transparent 1px); background-size: 24px 24px;"></div>

            <div class="relative z-10">
                <!-- Breadcrumb -->
                <Link
                    href="/admin/supplies"
                    class="inline-flex items-center gap-1.5 text-sm text-indigo-200/70 hover:text-white transition-colors mb-4"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    {{ $t('a_back_to_products') }}
                </Link>

                <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
                    <div class="flex-1 min-w-0">
                        <!-- Name & badges -->
                        <div class="flex items-center gap-3 flex-wrap mb-2">
                            <h1 class="text-2xl sm:text-3xl font-bold text-white truncate">
                                {{ displayName(supply) }}
                            </h1>
                            <span
                                :class="supply.is_active ? 'bg-emerald-400/20 text-emerald-300' : 'bg-white/10 text-white/50'"
                                class="px-2.5 py-0.5 text-xs font-semibold rounded-full"
                            >
                                {{ supply.is_active ? $t('a_active') : $t('a_inactive') }}
                            </span>
                            <span v-if="isExpired" class="px-2.5 py-0.5 text-xs font-semibold rounded-full bg-red-400/20 text-red-300">
                                {{ $t('a_expired') }}
                            </span>
                            <span v-else-if="isExpiringSoon" class="px-2.5 py-0.5 text-xs font-semibold rounded-full bg-amber-400/20 text-amber-300">
                                {{ daysUntilExpiry }} days left
                            </span>
                        </div>

                        <!-- Secondary info -->
                        <div class="flex items-center gap-4 flex-wrap text-sm text-indigo-200/70">
                            <span v-if="supply.sku" class="font-mono">SKU: {{ supply.sku }}</span>
                            <span v-if="supply.barcode" class="font-mono">{{ supply.barcode }}</span>
                            <span v-if="supply.supply_category" class="flex items-center gap-1">
                                <span class="w-2 h-2 rounded-full" :style="{ backgroundColor: supply.supply_category.color || ACCENT }"></span>
                                {{ displayName(supply.supply_category) }}
                            </span>
                            <span v-if="supply.unit">{{ supply.unit }}</span>
                            <span
                                v-if="supply.module && supply.module !== 'shared'"
                                class="px-2 py-0.5 rounded-md text-xs font-bold uppercase"
                                :class="supply.module === 'dental' ? 'bg-sky-400/20 text-sky-300' : 'bg-rose-400/20 text-rose-300'"
                            >
                                {{ supply.module }}
                            </span>
                            <span v-else-if="supply.module === 'shared'" class="px-2 py-0.5 rounded-md text-xs font-bold uppercase bg-white/10 text-white/50">
                                shared
                            </span>
                        </div>
                    </div>

                    <!-- Action buttons -->
                    <div class="flex items-center gap-2 flex-shrink-0">
                        <Link
                            v-if="can('supplies.update')"
                            :href="`/admin/supplies/${supply.id}/edit`"
                            class="inline-flex items-center px-4 py-2.5 rounded-xl bg-white/10 hover:bg-white/20 text-white text-sm font-medium backdrop-blur-sm transition-all"
                        >
                            <svg class="w-4 h-4 me-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                            </svg>
                            {{ $t('a_edit') }}
                        </Link>
                        <button
                            v-if="can('supplies.delete')"
                            @click="deleteSupply"
                            class="inline-flex items-center px-4 py-2.5 rounded-xl bg-red-500/20 hover:bg-red-500/30 text-red-200 text-sm font-medium backdrop-blur-sm transition-all"
                        >
                            <svg class="w-4 h-4 me-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                            {{ $t('a_delete') }}
                        </button>
                    </div>
                </div>

                <!-- Quick Stats Row -->
                <div class="mt-6 grid grid-cols-2 sm:grid-cols-4 gap-3">
                    <div class="bg-white/10 backdrop-blur-sm rounded-xl px-4 py-3">
                        <p class="text-xs text-indigo-200/60">Current Stock</p>
                        <p class="text-xl font-bold text-white">
                            {{ supply.quantity }}
                            <span class="text-sm font-normal text-indigo-200/50">{{ supply.unit || '' }}</span>
                        </p>
                    </div>
                    <div class="bg-white/10 backdrop-blur-sm rounded-xl px-4 py-3">
                        <p class="text-xs text-indigo-200/60">Min Quantity</p>
                        <p class="text-xl font-bold text-white">{{ supply.min_quantity || 0 }}</p>
                    </div>
                    <div class="bg-white/10 backdrop-blur-sm rounded-xl px-4 py-3">
                        <p class="text-xs text-indigo-200/60">Purchase Price</p>
                        <p class="text-xl font-bold text-white">{{ formatCurrency(supply.purchase_price) }}</p>
                    </div>
                    <div class="bg-white/10 backdrop-blur-sm rounded-xl px-4 py-3">
                        <p class="text-xs text-indigo-200/60">Total Value</p>
                        <p class="text-xl font-bold text-white">{{ formatCurrency((supply.purchase_price || 0) * supply.quantity) }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- ══════════════════════════════════════════════════════
             MAIN CONTENT - 2 Column Grid
             ══════════════════════════════════════════════════════ -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            <!-- ── LEFT COLUMN (2/3) ─────────────────────────── -->
            <div class="lg:col-span-2 space-y-6">

                <!-- Product Info Card -->
                <div
                    class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 transition-all duration-700"
                    :style="{ opacity: cardsVisible ? 1 : 0, transform: cardsVisible ? 'translateY(0)' : 'translateY(16px)', transitionTimingFunction: 'cubic-bezier(0.16, 1, 0.3, 1)' }"
                >
                    <h2 class="text-lg font-semibold text-gray-800 mb-5 flex items-center gap-2">
                        <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        {{ $t('a_product_information') }}
                    </h2>

                    <!-- Product Image -->
                    <div v-if="supply.image" class="mb-6">
                        <img
                            :src="supply.image"
                            :alt="displayName(supply)"
                            class="rounded-xl max-h-48 object-contain border border-gray-100"
                        />
                    </div>

                    <!-- Details Grid -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-8 gap-y-5">
                        <div>
                            <label class="block text-xs font-medium text-gray-400 uppercase tracking-wider mb-1">{{ $t('a_name_en') }}</label>
                            <p class="text-sm text-gray-900 font-medium">{{ supply.name_en || '-' }}</p>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-400 uppercase tracking-wider mb-1">{{ $t('a_name_ar') }}</label>
                            <p class="text-sm text-gray-900 font-medium" dir="rtl">{{ supply.name_ar || '-' }}</p>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-400 uppercase tracking-wider mb-1">{{ $t('a_sku') }}</label>
                            <p class="text-sm font-mono text-indigo-600">{{ supply.sku || '-' }}</p>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-400 uppercase tracking-wider mb-1">{{ $t('a_barcode') }}</label>
                            <p class="text-sm text-gray-900 font-mono">{{ supply.barcode || '-' }}</p>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-400 uppercase tracking-wider mb-1">{{ $t('a_category') }}</label>
                            <span
                                v-if="supply.supply_category"
                                class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-semibold rounded-full"
                                :style="{
                                    backgroundColor: (supply.supply_category.color || ACCENT) + '1A',
                                    color: supply.supply_category.color || ACCENT,
                                }"
                            >
                                <span v-if="supply.supply_category.icon" v-html="supply.supply_category.icon"></span>
                                {{ displayName(supply.supply_category) }}
                            </span>
                            <p v-else class="text-sm text-gray-500">{{ supply.category || '-' }}</p>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-400 uppercase tracking-wider mb-1">{{ $t('a_unit') }}</label>
                            <p class="text-sm text-gray-900">{{ supply.unit || '-' }}</p>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-400 uppercase tracking-wider mb-1">{{ $t('a_supplier') }}</label>
                            <p class="text-sm text-gray-900">{{ supply.supplier || '-' }}</p>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-400 uppercase tracking-wider mb-1">{{ $t('a_batch_number') }}</label>
                            <p class="text-sm text-gray-900 font-mono">{{ supply.batch_number || '-' }}</p>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-400 uppercase tracking-wider mb-1">{{ $t('a_expiry_date') }}</label>
                            <p
                                v-if="supply.expiry_date"
                                class="text-sm font-medium"
                                :class="isExpired ? 'text-red-600' : isExpiringSoon ? 'text-amber-600' : 'text-gray-900'"
                            >
                                {{ formatShortDate(supply.expiry_date) }}
                                <span v-if="isExpired" class="ms-1 text-xs px-1.5 py-0.5 bg-red-100 text-red-700 rounded">{{ $t('a_expired') }}</span>
                                <span v-else-if="isExpiringSoon" class="ms-1 text-xs px-1.5 py-0.5 bg-amber-100 text-amber-700 rounded">{{ daysUntilExpiry }} days left</span>
                            </p>
                            <p v-else class="text-sm text-gray-500">-</p>
                        </div>
                        <div class="sm:col-span-2" v-if="supply.description">
                            <label class="block text-xs font-medium text-gray-400 uppercase tracking-wider mb-1">{{ $t('a_description') }}</label>
                            <p class="text-sm text-gray-700 bg-gray-50 rounded-lg p-3 whitespace-pre-wrap">{{ supply.description }}</p>
                        </div>
                    </div>
                </div>

                <!-- Transaction Timeline Card -->
                <div
                    class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden transition-all duration-700"
                    :style="{ opacity: timelineVisible ? 1 : 0, transform: timelineVisible ? 'translateY(0)' : 'translateY(16px)', transitionTimingFunction: 'cubic-bezier(0.16, 1, 0.3, 1)', transitionDelay: '100ms' }"
                >
                    <!-- Header -->
                    <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                        <h2 class="text-lg font-semibold text-gray-800 flex items-center gap-2">
                            <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            {{ $t('a_transaction_history') }}
                        </h2>
                        <button
                            v-if="can('supplies.update')"
                            @click="showTransactionForm = !showTransactionForm"
                            class="inline-flex items-center px-3.5 py-2 rounded-xl text-sm font-medium transition-all"
                            :class="showTransactionForm
                                ? 'bg-gray-100 text-gray-600 hover:bg-gray-200'
                                : 'text-white hover:shadow-lg hover:shadow-indigo-500/25'"
                            :style="!showTransactionForm ? `background-color: ${ACCENT};` : ''"
                        >
                            <svg v-if="!showTransactionForm" class="w-4 h-4 me-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            <svg v-else class="w-4 h-4 me-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                            {{ showTransactionForm ? $t('a_cancel') : $t('a_add_transaction') }}
                        </button>
                    </div>

                    <!-- Expandable Transaction Form -->
                    <Transition
                        enter-active-class="transition-all duration-300 ease-out"
                        enter-from-class="max-h-0 opacity-0"
                        enter-to-class="max-h-[500px] opacity-100"
                        leave-active-class="transition-all duration-200 ease-in"
                        leave-from-class="max-h-[500px] opacity-100"
                        leave-to-class="max-h-0 opacity-0"
                    >
                        <div v-if="showTransactionForm" class="overflow-hidden">
                            <form @submit.prevent="submitTransaction" class="p-6 bg-indigo-50/50 border-b border-gray-100">
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('a_type') }} <span class="text-red-500">*</span></label>
                                        <select
                                            v-model="form.transaction_type"
                                            class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 bg-white transition-all"
                                        >
                                            <option value="purchase">{{ $t('a_purchase') }}</option>
                                            <option value="adjustment">{{ $t('a_adjustment') }}</option>
                                            <option value="return">{{ $t('a_return') }}</option>
                                            <option value="manual_deduction">{{ $t('a_manual_deduction') }}</option>
                                        </select>
                                        <p v-if="form.errors.transaction_type" class="mt-1 text-xs text-red-600">{{ form.errors.transaction_type }}</p>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('a_quantity') }} <span class="text-red-500">*</span></label>
                                        <input
                                            v-model="form.quantity"
                                            type="number"
                                            class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 bg-white transition-all"
                                            :placeholder="$t('a_enter_quantity')"
                                        />
                                        <p v-if="form.errors.quantity" class="mt-1 text-xs text-red-600">{{ form.errors.quantity }}</p>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('a_unit_cost') }}</label>
                                        <input
                                            v-model="form.unit_cost"
                                            type="number"
                                            step="0.01"
                                            min="0"
                                            class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 bg-white transition-all"
                                            placeholder="0.00"
                                        />
                                        <p v-if="form.errors.unit_cost" class="mt-1 text-xs text-red-600">{{ form.errors.unit_cost }}</p>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('a_notes') }}</label>
                                        <input
                                            v-model="form.notes"
                                            type="text"
                                            class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 bg-white transition-all"
                                            :placeholder="$t('a_optional_notes')"
                                        />
                                    </div>
                                </div>

                                <div class="mt-4 flex justify-end">
                                    <button
                                        type="submit"
                                        :disabled="form.processing"
                                        class="inline-flex items-center px-6 py-2.5 rounded-xl text-white text-sm font-medium transition-all disabled:opacity-50 hover:shadow-lg hover:shadow-indigo-500/25"
                                        :style="`background-color: ${ACCENT};`"
                                    >
                                        <svg v-if="form.processing" class="w-4 h-4 me-2 animate-spin" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                                        </svg>
                                        {{ form.processing ? $t('a_saving') : $t('a_add_transaction') }}
                                    </button>
                                </div>
                            </form>
                        </div>
                    </Transition>

                    <!-- Timeline -->
                    <div class="p-6">
                        <div v-if="transactions.data && transactions.data.length > 0" class="relative">
                            <!-- Timeline line -->
                            <div :class="isRtl ? 'right-3' : 'left-3'" class="absolute top-2 bottom-2 w-px bg-gradient-to-b from-indigo-200 via-gray-200 to-transparent"></div>

                            <div class="space-y-4">
                                <div
                                    v-for="(txn, index) in transactions.data"
                                    :key="txn.id"
                                    class="relative transition-all duration-500"
                                    :class="[
                                        timelineVisible ? 'opacity-100 translate-x-0' : (isRtl ? 'opacity-0 translate-x-4' : 'opacity-0 -translate-x-4'),
                                        isRtl ? 'pr-10' : 'pl-10',
                                    ]"
                                    :style="{ transitionDelay: `${400 + index * 60}ms`, transitionTimingFunction: 'cubic-bezier(0.16, 1, 0.3, 1)' }"
                                >
                                    <!-- Dot -->
                                    <div
                                        class="absolute top-3 w-5 h-5 rounded-full border-2 border-white shadow-sm ring-2 ring-gray-100"
                                        :class="[txnConfig(txn.transaction_type).dot, isRtl ? 'right-1' : 'left-1']"
                                    ></div>

                                    <div class="bg-gray-50 rounded-xl p-4 hover:bg-gray-100/80 transition-colors border border-transparent hover:border-gray-200">
                                        <div class="flex items-start justify-between gap-3 flex-wrap">
                                            <div class="flex items-center gap-2.5 flex-wrap">
                                                <span
                                                    :class="[txnConfig(txn.transaction_type).bg, txnConfig(txn.transaction_type).text]"
                                                    class="px-2.5 py-1 text-xs font-semibold rounded-lg"
                                                >
                                                    {{ txnConfig(txn.transaction_type).label }}
                                                </span>

                                                <span
                                                    class="text-sm font-bold"
                                                    :class="['purchase', 'return'].includes(txn.transaction_type) ? 'text-emerald-600' : 'text-red-600'"
                                                >
                                                    {{ ['purchase', 'return'].includes(txn.transaction_type) ? '+' : '-' }}{{ txn.quantity }}
                                                </span>

                                                <span v-if="txn.unit_cost" class="text-xs text-gray-400">
                                                    @ {{ formatCurrency(txn.unit_cost) }}
                                                </span>
                                            </div>

                                            <span class="text-xs text-gray-400 whitespace-nowrap">
                                                {{ formatDate(txn.created_at) }}
                                            </span>
                                        </div>

                                        <div class="mt-2 flex items-center gap-3 text-xs text-gray-500">
                                            <span v-if="txn.creator?.name" class="flex items-center gap-1">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                                </svg>
                                                {{ txn.creator.name }}
                                            </span>
                                            <span v-if="txn.notes" class="text-gray-400 truncate max-w-xs">
                                                {{ txn.notes }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Empty State -->
                        <div v-else class="text-center py-12">
                            <div class="w-16 h-16 mx-auto mb-4 rounded-2xl bg-indigo-50 flex items-center justify-center">
                                <svg class="w-8 h-8 text-indigo-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                </svg>
                            </div>
                            <p class="text-sm text-gray-400">No transactions recorded yet.</p>
                        </div>
                    </div>

                    <!-- Pagination -->
                    <div v-if="transactions.links && transactions.links.length > 3" class="px-6 py-3 border-t border-gray-100 flex items-center justify-between">
                        <p class="text-sm text-gray-500">Showing {{ transactions.from }} to {{ transactions.to }} of {{ transactions.total }}</p>
                        <nav class="flex gap-1">
                            <template v-for="link in transactions.links" :key="link.label">
                                <Link
                                    v-if="link.url"
                                    :href="link.url"
                                    v-html="link.label"
                                    class="px-3 py-1.5 text-sm rounded-lg border transition-all"
                                    :class="link.active ? 'text-white border-transparent shadow-sm' : 'text-gray-600 border-gray-200 hover:bg-gray-50'"
                                    :style="link.active ? `background-color: ${ACCENT};` : ''"
                                    preserve-state
                                />
                                <span v-else v-html="link.label" class="px-3 py-1.5 text-sm text-gray-400" />
                            </template>
                        </nav>
                    </div>
                </div>
            </div>

            <!-- ── RIGHT COLUMN (1/3) ────────────────────────── -->
            <div class="space-y-6">

                <!-- Stock Level Card -->
                <div
                    class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 transition-all duration-700"
                    :style="{ opacity: cardsVisible ? 1 : 0, transform: cardsVisible ? 'translateY(0)' : 'translateY(16px)', transitionTimingFunction: 'cubic-bezier(0.16, 1, 0.3, 1)', transitionDelay: '100ms' }"
                >
                    <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-4 flex items-center gap-2">
                        <svg class="w-4 h-4 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                        </svg>
                        Stock Level
                    </h3>

                    <!-- SVG Circular Gauge -->
                    <div class="flex justify-center mb-5">
                        <div class="relative w-36 h-36">
                            <svg class="w-36 h-36 -rotate-90" viewBox="0 0 120 120">
                                <circle cx="60" cy="60" r="54" fill="none" stroke="#f3f4f6" stroke-width="10" />
                                <circle
                                    cx="60" cy="60" r="54" fill="none"
                                    :stroke="gaugeColor"
                                    stroke-width="10"
                                    stroke-linecap="round"
                                    class="gauge-circle"
                                    :style="{ strokeDasharray: mounted ? gaugeDashArray : `0 ${gaugeCircumference}` }"
                                />
                            </svg>
                            <div class="absolute inset-0 flex flex-col items-center justify-center">
                                <span class="text-2xl font-bold" :style="{ color: gaugeColor }">{{ stockPercentage }}%</span>
                                <span class="text-xs text-gray-400">Stock</span>
                            </div>
                        </div>
                    </div>

                    <!-- Stock Details -->
                    <div class="space-y-3">
                        <div class="flex items-center justify-between py-2.5 border-t border-gray-100">
                            <span class="text-sm text-gray-500">Current Stock</span>
                            <span class="text-xl font-bold" :class="isLowStock() ? 'text-red-600' : 'text-gray-900'">
                                {{ supply.quantity }} <span class="text-sm font-normal text-gray-400">{{ supply.unit || '' }}</span>
                            </span>
                        </div>
                        <div class="flex items-center justify-between py-2.5 border-t border-gray-100">
                            <span class="text-sm text-gray-500">Min Quantity</span>
                            <span class="text-sm font-semibold text-gray-700">{{ supply.min_quantity }}</span>
                        </div>
                        <div class="flex items-center justify-between py-2.5 border-t border-gray-100">
                            <span class="text-sm text-gray-500">Status</span>
                            <span
                                :class="supply.is_low_stock ? 'bg-red-50 text-red-700' : 'bg-emerald-50 text-emerald-700'"
                                class="px-2.5 py-1 text-xs font-semibold rounded-lg"
                            >
                                {{ supply.is_low_stock ? 'Low Stock' : 'In Stock' }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Purchase Info Card -->
                <div
                    class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 transition-all duration-700"
                    :style="{ opacity: cardsVisible ? 1 : 0, transform: cardsVisible ? 'translateY(0)' : 'translateY(16px)', transitionTimingFunction: 'cubic-bezier(0.16, 1, 0.3, 1)', transitionDelay: '200ms' }"
                >
                    <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-4 flex items-center gap-2">
                        <svg class="w-4 h-4 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 100 4 2 2 0 000-4z" />
                        </svg>
                        Purchase Info
                    </h3>

                    <div class="space-y-4">
                        <div>
                            <label class="text-xs text-gray-400">Purchase Price</label>
                            <p class="text-lg font-bold text-indigo-600">{{ formatCurrency(supply.purchase_price) }}</p>
                        </div>
                        <div>
                            <label class="text-xs text-gray-400">{{ $t('a_supplier') }}</label>
                            <p class="text-sm font-medium text-gray-800">{{ supply.supplier || '-' }}</p>
                        </div>
                        <div>
                            <label class="text-xs text-gray-400">{{ $t('a_batch_number') }}</label>
                            <p class="text-sm font-mono text-gray-800">{{ supply.batch_number || '-' }}</p>
                        </div>
                        <div>
                            <label class="text-xs text-gray-400">{{ $t('a_expiry_date') }}</label>
                            <div v-if="supply.expiry_date">
                                <p class="text-sm font-medium" :class="isExpired ? 'text-red-600' : isExpiringSoon ? 'text-amber-600' : 'text-gray-800'">
                                    {{ formatShortDate(supply.expiry_date) }}
                                </p>
                                <p v-if="!isExpired && daysUntilExpiry !== null" class="text-xs mt-0.5" :class="isExpiringSoon ? 'text-amber-500' : 'text-gray-400'">
                                    {{ daysUntilExpiry }} days remaining
                                </p>
                                <p v-else-if="isExpired" class="text-xs text-red-500 mt-0.5 font-medium">
                                    Expired {{ Math.abs(daysUntilExpiry) }} days ago
                                </p>
                            </div>
                            <p v-else class="text-sm text-gray-500">-</p>
                        </div>
                    </div>
                </div>

                <!-- Linked Services Card -->
                <div
                    v-if="supply.service_supplies && supply.service_supplies.length > 0"
                    class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 transition-all duration-700"
                    :style="{ opacity: cardsVisible ? 1 : 0, transform: cardsVisible ? 'translateY(0)' : 'translateY(16px)', transitionTimingFunction: 'cubic-bezier(0.16, 1, 0.3, 1)', transitionDelay: '300ms' }"
                >
                    <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-4 flex items-center gap-2">
                        <svg class="w-4 h-4 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                        </svg>
                        Linked Services
                    </h3>

                    <div class="space-y-2">
                        <div
                            v-for="(ss, index) in supply.service_supplies"
                            :key="index"
                            class="flex items-center justify-between py-2.5 px-3 rounded-xl hover:bg-indigo-50/50 transition-colors"
                            :class="index > 0 ? 'border-t border-gray-100' : ''"
                        >
                            <div class="flex items-center gap-2">
                                <div class="w-2 h-2 rounded-full flex-shrink-0 bg-indigo-500"></div>
                                <span class="text-sm text-gray-800">{{ ss.service?.name_en || 'Unknown Service' }}</span>
                            </div>
                            <span class="text-xs font-semibold px-2.5 py-1 rounded-lg bg-indigo-50 text-indigo-600">
                                {{ ss.quantity_per_session }} / session
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.gauge-circle {
    transition: stroke-dasharray 1.2s cubic-bezier(0.16, 1, 0.3, 1);
}
</style>
