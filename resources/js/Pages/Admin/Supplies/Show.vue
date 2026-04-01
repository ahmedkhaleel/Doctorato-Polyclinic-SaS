<script setup>
import { ref, computed, onMounted } from 'vue';
import { Link, router, useForm, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { usePermissions } from '@/Composables/usePermissions.js';
import { useCurrency } from '@/Composables/useCurrency.js';

const { can } = usePermissions();
const { formatCurrency } = useCurrency();

const page = usePage();
const locale = computed(() => page.props.locale || 'ar');
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const props = defineProps({
    supply: Object,
    transactions: Object,
    stockPercentage: Number,
});

// ── Transaction Form ───────────────────────────────────────────────
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

// ── Delete ─────────────────────────────────────────────────────────
function deleteSupply() {
    if (window.confirm('Are you sure you want to delete this supply item? This action cannot be undone.')) {
        router.delete(`/admin/supplies/${props.supply.id}`);
    }
}

// ── Helpers ────────────────────────────────────────────────────────
function formatDate(d) {
    if (!d) return '-';
    return new Date(d).toLocaleDateString('en-GB', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}

function formatShortDate(d) {
    if (!d) return '-';
    return new Date(d).toLocaleDateString('en-GB', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
    });
}

function isLowStock() {
    return props.supply.quantity <= props.supply.min_quantity;
}

// ── Expiry helpers ─────────────────────────────────────────────────
const daysUntilExpiry = computed(() => {
    if (!props.supply.expiry_date) return null;
    const now = new Date();
    const expiry = new Date(props.supply.expiry_date);
    return Math.ceil((expiry - now) / (1000 * 60 * 60 * 24));
});

const isExpired = computed(() => daysUntilExpiry.value !== null && daysUntilExpiry.value <= 0);
const isExpiringSoon = computed(() => daysUntilExpiry.value !== null && daysUntilExpiry.value > 0 && daysUntilExpiry.value <= 30);

// ── Transaction type config ────────────────────────────────────────
const typeLabels = {
    purchase: 'Purchase',
    usage: 'Usage',
    adjustment: 'Adjustment',
    return: 'Return',
    manual_deduction: 'Manual Deduction',
};

const typeColors = {
    purchase: 'bg-green-100 text-green-800',
    usage: 'bg-blue-100 text-blue-800',
    adjustment: 'bg-yellow-100 text-yellow-800',
    return: 'bg-purple-100 text-purple-800',
    manual_deduction: 'bg-red-100 text-red-800',
};

const typeDotColors = {
    purchase: 'bg-green-500',
    usage: 'bg-blue-500',
    adjustment: 'bg-yellow-500',
    return: 'bg-purple-500',
    manual_deduction: 'bg-red-500',
};

// ── Stock gauge ────────────────────────────────────────────────────
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

// ── Animations ─────────────────────────────────────────────────────
const mounted = ref(false);

onMounted(() => {
    requestAnimationFrame(() => {
        mounted.value = true;
    });
});
</script>

<template>
    <AdminLayout :title="supply.name_en">
        <div class="space-y-6">
            <!-- ══════════════════════════════════════════════════════
                 HEADER
                 ══════════════════════════════════════════════════════ -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div class="flex items-center gap-4 flex-wrap">
                    <Link
                        href="/admin/supplies"
                        class="inline-flex items-center text-sm text-gray-500 hover:text-gray-700 transition-colors"
                    >
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                        {{ $t('a_back_to_products') }}
                    </Link>

                    <div class="hidden sm:block w-px h-6 bg-gray-300"></div>

                    <h1 class="text-2xl font-bold text-gray-800">{{ supply.name_en }}</h1>

                    <span
                        class="font-mono text-xs px-3 py-1 rounded-full bg-yellow-50"
                        style="color: #C4A265;"
                    >
                        {{ supply.sku }}
                    </span>

                    <span
                        :class="supply.is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-500'"
                        class="px-2.5 py-0.5 text-xs font-semibold rounded-full"
                    >
                        {{ supply.is_active ? $t('a_active') : $t('a_inactive') }}
                    </span>
                </div>

                <div class="flex items-center gap-2">
                    <Link
                        v-if="can('supplies.update')"
                        :href="`/admin/supplies/${supply.id}/edit`"
                        class="inline-flex items-center px-4 py-2 rounded-lg bg-blue-600 text-white text-sm font-medium transition hover:bg-blue-700"
                    >
                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                        </svg>
                        {{ $t('a_edit') }}
                    </Link>
                    <button
                        v-if="can('supplies.delete')"
                        @click="deleteSupply"
                        class="inline-flex items-center px-4 py-2 rounded-lg bg-red-600 text-white text-sm font-medium transition hover:bg-red-700"
                    >
                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                        {{ $t('a_delete') }}
                    </button>
                </div>
            </div>

            <!-- ══════════════════════════════════════════════════════
                 MAIN CONTENT - 2 Column Grid
                 ══════════════════════════════════════════════════════ -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                <!-- ── LEFT COLUMN (2/3) ──────────────────────────── -->
                <div class="lg:col-span-2 space-y-6">

                    <!-- Product Info Card -->
                    <div
                        class="bg-white rounded-2xl shadow-sm p-6 transition-all duration-700"
                        :class="mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'"
                    >
                        <h2 class="text-lg font-semibold text-gray-700 mb-5 flex items-center gap-2">
                            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            {{ $t('a_product_information') }}
                        </h2>

                        <!-- Product Image -->
                        <div v-if="supply.image" class="mb-6">
                            <img
                                :src="supply.image"
                                :alt="supply.name_en"
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
                                <p class="text-sm text-gray-900 font-mono" style="color: #C4A265;">{{ supply.sku || '-' }}</p>
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
                                        backgroundColor: (supply.supply_category.color || '#C4A265') + '1A',
                                        color: supply.supply_category.color || '#C4A265',
                                    }"
                                >
                                    <span v-if="supply.supply_category.icon" v-html="supply.supply_category.icon"></span>
                                    {{ supply.supply_category.name_en }}
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
                                    <span v-if="isExpired" class="ml-1 text-xs px-1.5 py-0.5 bg-red-100 text-red-700 rounded">{{ $t('a_expired') }}</span>
                                    <span v-else-if="isExpiringSoon" class="ml-1 text-xs px-1.5 py-0.5 bg-amber-100 text-amber-700 rounded">{{ daysUntilExpiry }} days left</span>
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
                        class="bg-white rounded-2xl shadow-sm overflow-hidden transition-all duration-700 delay-150"
                        :class="mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'"
                    >
                        <!-- Header -->
                        <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                            <h2 class="text-lg font-semibold text-gray-700 flex items-center gap-2">
                                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                {{ $t('a_transaction_history') }}
                            </h2>
                            <button
                                v-if="can('supplies.update')"
                                @click="showTransactionForm = !showTransactionForm"
                                class="inline-flex items-center px-3 py-1.5 rounded-lg text-sm font-medium transition"
                                :class="showTransactionForm
                                    ? 'bg-gray-100 text-gray-600 hover:bg-gray-200'
                                    : 'text-white hover:opacity-90'"
                                :style="!showTransactionForm ? 'background-color: #C4A265;' : ''"
                            >
                                <svg v-if="!showTransactionForm" class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                </svg>
                                {{ showTransactionForm ? $t('a_cancel') : $t('a_add_transaction') }}
                            </button>
                        </div>

                        <!-- Expandable Transaction Form -->
                        <Transition
                            enter-active-class="transition-all duration-300 ease-out"
                            enter-from-class="max-h-0 opacity-0"
                            enter-to-class="max-h-96 opacity-100"
                            leave-active-class="transition-all duration-200 ease-in"
                            leave-from-class="max-h-96 opacity-100"
                            leave-to-class="max-h-0 opacity-0"
                        >
                            <div v-if="showTransactionForm" class="overflow-hidden">
                                <form @submit.prevent="submitTransaction" class="p-6 bg-gray-50 border-b border-gray-100">
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('a_type') }} <span class="text-red-500">*</span></label>
                                            <select
                                                v-model="form.transaction_type"
                                                class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-yellow-200 focus:border-transparent"
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
                                                class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-yellow-200 focus:border-transparent"
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
                                                class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-yellow-200 focus:border-transparent"
                                                placeholder="0.00"
                                            />
                                            <p v-if="form.errors.unit_cost" class="mt-1 text-xs text-red-600">{{ form.errors.unit_cost }}</p>
                                        </div>

                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('a_notes') }}</label>
                                            <input
                                                v-model="form.notes"
                                                type="text"
                                                class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-yellow-200 focus:border-transparent"
                                                :placeholder="$t('a_optional_notes')"
                                            />
                                        </div>
                                    </div>

                                    <div class="mt-4 flex justify-end">
                                        <button
                                            type="submit"
                                            :disabled="form.processing"
                                            class="inline-flex items-center px-6 py-2 rounded-lg text-white text-sm font-medium transition disabled:opacity-50 hover:opacity-90"
                                            style="background-color: #C4A265;"
                                        >
                                            <svg v-if="form.processing" class="w-4 h-4 mr-2 animate-spin" fill="none" viewBox="0 0 24 24">
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
                                <div class="absolute left-3 top-2 bottom-2 w-px bg-gray-200"></div>

                                <div class="space-y-6">
                                    <div
                                        v-for="(txn, index) in transactions.data"
                                        :key="txn.id"
                                        class="relative pl-10 transition-all duration-500"
                                        :class="mounted ? 'opacity-100 translate-x-0' : 'opacity-0 -translate-x-4'"
                                        :style="{ transitionDelay: `${300 + index * 80}ms` }"
                                    >
                                        <!-- Dot -->
                                        <div
                                            class="absolute left-1 top-1.5 w-5 h-5 rounded-full border-2 border-white shadow-sm"
                                            :class="typeDotColors[txn.transaction_type] || 'bg-gray-400'"
                                        ></div>

                                        <div class="bg-gray-50 rounded-xl p-4 hover:bg-gray-100 transition-colors">
                                            <div class="flex items-start justify-between gap-3 flex-wrap">
                                                <div class="flex items-center gap-2 flex-wrap">
                                                    <span
                                                        :class="typeColors[txn.transaction_type] || 'bg-gray-100 text-gray-800'"
                                                        class="px-2 py-0.5 text-xs font-semibold rounded-full"
                                                    >
                                                        {{ typeLabels[txn.transaction_type] || txn.transaction_type }}
                                                    </span>

                                                    <span
                                                        class="text-sm font-bold"
                                                        :class="txn.quantity > 0 ? 'text-green-600' : 'text-red-600'"
                                                    >
                                                        {{ txn.quantity > 0 ? '+' : '' }}{{ txn.quantity }}
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
                            <div v-else class="text-center py-10">
                                <svg class="w-12 h-12 mx-auto text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                </svg>
                                <p class="text-sm text-gray-400">No transactions recorded yet.</p>
                            </div>
                        </div>

                        <!-- Pagination -->
                        <div v-if="transactions.links && transactions.links.length > 3" class="px-6 py-3 border-t border-gray-100 flex items-center justify-between">
                            <p class="text-sm text-gray-500">Showing {{ transactions.from }} to {{ transactions.to }} of {{ transactions.total }} results</p>
                            <nav class="flex space-x-1 rtl:space-x-reverse">
                                <template v-for="link in transactions.links" :key="link.label">
                                    <Link
                                        v-if="link.url"
                                        :href="link.url"
                                        v-html="link.label"
                                        class="px-3 py-1 text-sm rounded border transition"
                                        :class="link.active ? 'text-white border-transparent' : 'text-gray-600 border-gray-300 hover:bg-gray-50'"
                                        :style="link.active ? 'background-color: #C4A265;' : ''"
                                        preserve-state
                                    />
                                    <span v-else v-html="link.label" class="px-3 py-1 text-sm text-gray-400" />
                                </template>
                            </nav>
                        </div>
                    </div>
                </div>

                <!-- ── RIGHT COLUMN (1/3) ─────────────────────────── -->
                <div class="space-y-6">

                    <!-- Stock Level Card -->
                    <div
                        class="bg-white rounded-2xl shadow-sm p-6 transition-all duration-700 delay-100"
                        :class="mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'"
                    >
                        <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-4">Stock Level</h3>

                        <!-- SVG Circular Gauge -->
                        <div class="flex justify-center mb-5">
                            <div class="relative w-36 h-36">
                                <svg class="w-36 h-36 -rotate-90" viewBox="0 0 120 120">
                                    <!-- Background circle -->
                                    <circle
                                        cx="60"
                                        cy="60"
                                        r="54"
                                        fill="none"
                                        stroke="#f3f4f6"
                                        stroke-width="10"
                                    />
                                    <!-- Progress circle -->
                                    <circle
                                        cx="60"
                                        cy="60"
                                        r="54"
                                        fill="none"
                                        :stroke="gaugeColor"
                                        stroke-width="10"
                                        stroke-linecap="round"
                                        :stroke-dasharray="gaugeDashArray"
                                        class="transition-all duration-1000 ease-out"
                                        :style="{ strokeDasharray: mounted ? gaugeDashArray : `0 ${gaugeCircumference}` }"
                                    />
                                </svg>
                                <!-- Center text -->
                                <div class="absolute inset-0 flex flex-col items-center justify-center">
                                    <span class="text-2xl font-bold" :style="{ color: gaugeColor }">{{ stockPercentage }}%</span>
                                    <span class="text-xs text-gray-400">Stock</span>
                                </div>
                            </div>
                        </div>

                        <!-- Stock Details -->
                        <div class="space-y-3">
                            <div class="flex items-center justify-between py-2 border-t border-gray-100">
                                <span class="text-sm text-gray-500">Current Stock</span>
                                <span
                                    class="text-xl font-bold"
                                    :class="isLowStock() ? 'text-red-600' : 'text-gray-900'"
                                >
                                    {{ supply.quantity }} <span class="text-sm font-normal text-gray-400">{{ supply.unit || '' }}</span>
                                </span>
                            </div>
                            <div class="flex items-center justify-between py-2 border-t border-gray-100">
                                <span class="text-sm text-gray-500">Min Quantity</span>
                                <span class="text-sm font-semibold text-gray-700">{{ supply.min_quantity }}</span>
                            </div>
                            <div class="flex items-center justify-between py-2 border-t border-gray-100">
                                <span class="text-sm text-gray-500">Status</span>
                                <span
                                    :class="supply.is_low_stock ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800'"
                                    class="px-2.5 py-0.5 text-xs font-semibold rounded-full"
                                >
                                    {{ supply.is_low_stock ? 'Low Stock' : 'In Stock' }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Purchase Info Card -->
                    <div
                        class="bg-white rounded-2xl shadow-sm p-6 transition-all duration-700 delay-200"
                        :class="mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'"
                    >
                        <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-4">Purchase Info</h3>

                        <div class="space-y-4">
                            <div>
                                <label class="text-xs text-gray-400">Purchase Price</label>
                                <p class="text-lg font-bold" style="color: #C4A265;">{{ formatCurrency(supply.purchase_price) }}</p>
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
                                    <p
                                        class="text-sm font-medium"
                                        :class="isExpired ? 'text-red-600' : isExpiringSoon ? 'text-amber-600' : 'text-gray-800'"
                                    >
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
                        class="bg-white rounded-2xl shadow-sm p-6 transition-all duration-700 delay-300"
                        :class="mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'"
                    >
                        <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-4 flex items-center gap-2">
                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                            </svg>
                            Linked Services
                        </h3>

                        <div class="space-y-3">
                            <div
                                v-for="(ss, index) in supply.service_supplies"
                                :key="index"
                                class="flex items-center justify-between py-2.5 px-3 rounded-lg hover:bg-gray-50 transition-colors"
                                :class="index > 0 ? 'border-t border-gray-100' : ''"
                            >
                                <div class="flex items-center gap-2">
                                    <div class="w-2 h-2 rounded-full flex-shrink-0" style="background-color: #C4A265;"></div>
                                    <span class="text-sm text-gray-800">{{ ss.service?.name_en || 'Unknown Service' }}</span>
                                </div>
                                <span class="text-xs font-semibold px-2 py-0.5 rounded-full bg-gray-100 text-gray-600">
                                    {{ ss.quantity_per_session }} / session
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<style scoped>
/* Smooth entrance for the gauge stroke animation */
@keyframes gaugeAppear {
    from { stroke-dasharray: 0 339.292; }
}
</style>
