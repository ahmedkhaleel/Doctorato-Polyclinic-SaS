<script setup>
import { ref, computed, watch, nextTick } from 'vue';
import { useForm, Link, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import SearchableSelect from '@/Components/Admin/SearchableSelect.vue';
import { useCurrency } from '@/Composables/useCurrency.js';

const { formatCurrency, currencyCode } = useCurrency();

const page = usePage();
const locale = computed(() => page.props.locale || 'ar');
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const props = defineProps({
    services: Array,
});

const form = useForm({
    name_ar: '',
    name_en: '',
    description_ar: '',
    description_en: '',
    total_price: '',
    is_active: true,
    image: null,
    display_order: 0,
    services: [],
});

const imagePreview = ref(null);
const isDragging = ref(false);
const expandedServices = ref({});
const justAdded = ref(null);

function handleImageChange(e) {
    const file = e.target.files[0];
    if (file) {
        form.image = file;
        imagePreview.value = URL.createObjectURL(file);
    }
}

function handleDrop(e) {
    isDragging.value = false;
    const file = e.dataTransfer.files[0];
    if (file && file.type.startsWith('image/')) {
        form.image = file;
        imagePreview.value = URL.createObjectURL(file);
    }
}

function removeImage() {
    form.image = null;
    imagePreview.value = null;
}

function addService() {
    const newIndex = form.services.length;
    form.services.push({
        service_id: '',
        sessions_count: 1,
        discount_percentage: 0,
        bundle_price: 0,
    });
    expandedServices.value[newIndex] = true;
    justAdded.value = newIndex;
    nextTick(() => {
        setTimeout(() => { justAdded.value = null; }, 600);
    });
}

function removeService(index) {
    form.services.splice(index, 1);
    // Reindex expanded
    const newExpanded = {};
    Object.keys(expandedServices.value).forEach(k => {
        const n = Number(k);
        if (n < index) newExpanded[n] = expandedServices.value[n];
        else if (n > index) newExpanded[n - 1] = expandedServices.value[n];
    });
    expandedServices.value = newExpanded;
}

function toggleService(index) {
    expandedServices.value[index] = !expandedServices.value[index];
}

function getServiceById(id) {
    return props.services.find(s => s.id == id);
}

function recalcBundlePrice(index) {
    const item = form.services[index];
    const service = getServiceById(item.service_id);
    if (service) {
        const perSession = Number(service.price) * (1 - Number(item.discount_percentage) / 100);
        item.bundle_price = Math.round(perSession * Number(item.sessions_count) * 100) / 100;
    }
}

function onServiceChange(index) {
    const item = form.services[index];
    const service = getServiceById(item.service_id);
    if (service) {
        item.bundle_price = Number(service.price) * item.sessions_count;
        item.discount_percentage = 0;
    }
}

const originalPrice = computed(() => {
    return form.services.reduce((sum, item) => {
        const service = getServiceById(item.service_id);
        return sum + (service ? Number(service.price) * Number(item.sessions_count) : 0);
    }, 0);
});

const servicesTotal = computed(() => {
    return form.services.reduce((sum, item) => sum + Number(item.bundle_price || 0), 0);
});

const savingsAmount = computed(() => {
    return Math.max(0, originalPrice.value - Number(form.total_price || 0));
});

const savingsPercentage = computed(() => {
    if (originalPrice.value <= 0) return 0;
    return Math.round((savingsAmount.value / originalPrice.value) * 100);
});

const totalSessions = computed(() => {
    return form.services.reduce((sum, item) => sum + Number(item.sessions_count || 0), 0);
});

const completedServices = computed(() => {
    return form.services.filter(s => s.service_id).length;
});

const formProgress = computed(() => {
    let steps = 0;
    let total = 4;
    if (form.name_en || form.name_ar) steps++;
    if (form.description_en || form.description_ar) steps++;
    if (form.services.length > 0 && completedServices.value > 0) steps++;
    if (Number(form.total_price) > 0) steps++;
    return Math.round((steps / total) * 100);
});

function availableServices(currentIndex) {
    const usedIds = form.services.map((s, i) => i !== currentIndex ? s.service_id : null).filter(Boolean);
    return props.services.filter(s => !usedIds.includes(s.id));
}

function serviceOptions(currentIndex) {
    return availableServices(currentIndex).map(svc => ({
        value: svc.id,
        label: svc.name_en,
        subtitle: formatCurrency(svc.price),
        searchText: `${svc.name_en} ${svc.name_ar || ''} ${svc.price}`,
    }));
}


// Auto-sync total_price with servicesTotal
watch(servicesTotal, (val) => {
    form.total_price = val;
});

function submit() {
    form.post('/admin/package-bundles', {
        forceFormData: true,
    });
}
</script>

<template>
    <AdminLayout :title="$t('a_create_bundle')">
        <div class="cb-page space-y-6">

            <!-- ============ Header ============ -->
            <div class="cb-header flex items-center justify-between">
                <div>
                    <Link href="/admin/package-bundles" class="group inline-flex items-center gap-1.5 text-sm text-gray-400 hover:text-[#C4A265] transition-colors duration-300">
                        <svg class="w-4 h-4 transition-transform duration-300 group-hover:-translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
                        Back to Bundles
                    </Link>
                    <h1 class="text-xl md:text-2xl font-bold text-gray-800 mt-1 flex items-center gap-3">
                        <span class="cb-icon-wrap inline-flex items-center justify-center w-10 h-10 rounded-xl text-white" style="background: linear-gradient(135deg, #C4A265, #D4B87A);">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" /></svg>
                        </span>
                        Create Package Bundle
                    </h1>
                </div>

                <!-- Progress indicator -->
                <div class="hidden md:flex items-center gap-3 bg-white rounded-xl shadow-sm px-5 py-3 border border-gray-100">
                    <div class="relative w-12 h-12">
                        <svg class="w-12 h-12 -rotate-90" viewBox="0 0 36 36">
                            <path d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" fill="none" stroke="#f3f4f6" stroke-width="3" />
                            <path d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"
                                fill="none" stroke="#C4A265" stroke-width="3" stroke-linecap="round"
                                :stroke-dasharray="`${formProgress}, 100`"
                                class="cb-progress-ring"
                            />
                        </svg>
                        <span class="absolute inset-0 flex items-center justify-center text-xs font-bold text-gray-600">{{ formProgress }}%</span>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-gray-500">Form Progress</p>
                        <p class="text-sm font-semibold text-gray-700">{{ completedServices }} service{{ completedServices !== 1 ? 's' : '' }} added</p>
                    </div>
                </div>
            </div>

            <!-- ============ Form ============ -->
            <form @submit.prevent="submit" class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                <!-- ===== Main Content (2/3) ===== -->
                <div class="lg:col-span-2 space-y-6">

                    <!-- ---- Bundle Information Card ---- -->
                    <div class="cb-card bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                        <div class="cb-card-header px-4 md:px-6 py-4 border-b border-gray-50" style="background: linear-gradient(135deg, rgba(196,162,101,0.04), rgba(196,162,101,0.01));">
                            <div class="flex items-center gap-2.5">
                                <span class="flex items-center justify-center w-7 h-7 rounded-lg text-white text-xs font-bold" style="background: linear-gradient(135deg, #C4A265, #D4B87A);">1</span>
                                <div>
                                    <h3 class="text-sm font-semibold text-gray-800">{{ $t('a_bundle_information') }}</h3>
                                    <p class="text-[11px] text-gray-400 mt-0.5">Enter the bundle name and description in both languages</p>
                                </div>
                            </div>
                        </div>
                        <div class="p-4 md:p-6 space-y-5">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                <div class="cb-field">
                                    <label class="cb-label">
                                        <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129" /></svg>
                                        Name (English) <span class="text-red-400">*</span>
                                    </label>
                                    <input v-model="form.name_en" type="text" placeholder="e.g. Bride Glow Package" class="doctorato-input cb-input" />
                                    <p v-if="form.errors.name_en" class="cb-error">{{ form.errors.name_en }}</p>
                                </div>
                                <div class="cb-field">
                                    <label class="cb-label">
                                        <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129" /></svg>
                                        Name (Arabic) <span class="text-red-400">*</span>
                                    </label>
                                    <input v-model="form.name_ar" type="text" dir="rtl" placeholder="مثال: باقة عروس متالقة" class="doctorato-input cb-input" />
                                    <p v-if="form.errors.name_ar" class="cb-error">{{ form.errors.name_ar }}</p>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                <div class="cb-field">
                                    <label class="cb-label">
                                        <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7" /></svg>
                                        Description (English)
                                    </label>
                                    <textarea v-model="form.description_en" rows="4" :placeholder="$t('a_bundle_desc_placeholder')" class="doctorato-input cb-input cb-textarea"></textarea>
                                    <p v-if="form.errors.description_en" class="cb-error">{{ form.errors.description_en }}</p>
                                </div>
                                <div class="cb-field">
                                    <label class="cb-label">
                                        <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7" /></svg>
                                        Description (Arabic)
                                    </label>
                                    <textarea v-model="form.description_ar" rows="4" dir="rtl" placeholder="اكتب وصف الباقة..." class="doctorato-input cb-input cb-textarea"></textarea>
                                    <p v-if="form.errors.description_ar" class="cb-error">{{ form.errors.description_ar }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ---- Included Services Card ---- -->
                    <div class="cb-card bg-white rounded-xl shadow-sm border border-gray-100">
                        <div class="cb-card-header px-4 md:px-6 py-4 border-b border-gray-50 flex items-center justify-between rounded-t-xl" style="background: linear-gradient(135deg, rgba(196,162,101,0.04), rgba(196,162,101,0.01));">
                            <div class="flex items-center gap-2.5">
                                <span class="flex items-center justify-center w-7 h-7 rounded-lg text-white text-xs font-bold" style="background: linear-gradient(135deg, #C4A265, #D4B87A);">2</span>
                                <div>
                                    <h3 class="text-sm font-semibold text-gray-800">{{ $t('a_included_services') }}</h3>
                                    <p class="text-[11px] text-gray-400 mt-0.5">Add services and configure sessions, discounts, and pricing</p>
                                </div>
                            </div>
                            <button
                                type="button"
                                @click="addService"
                                class="cb-add-btn group inline-flex items-center gap-2 px-4 py-2 rounded-lg text-white text-sm font-medium transition-all duration-300 hover:shadow-lg hover:shadow-[#C4A265]/20 active:scale-[0.97]"
                                style="background: linear-gradient(135deg, #C4A265, #D4B87A);"
                            >
                                <svg class="w-4 h-4 transition-transform duration-300 group-hover:rotate-90" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                                Add Service
                            </button>
                        </div>

                        <div class="p-6">
                            <!-- Empty state -->
                            <div v-if="form.services.length === 0" class="cb-empty-state text-center py-16 border-2 border-dashed border-gray-200 rounded-xl transition-colors duration-300 hover:border-[#C4A265]/30">
                                <div class="cb-empty-icon mx-auto w-16 h-16 rounded-2xl flex items-center justify-center mb-4" style="background: linear-gradient(135deg, rgba(196,162,101,0.08), rgba(196,162,101,0.03));">
                                    <svg class="w-8 h-8 text-[#C4A265]/40" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" /></svg>
                                </div>
                                <p class="text-sm font-medium text-gray-500">No services added yet</p>
                                <p class="text-xs text-gray-400 mt-1 mb-4">Click "Add Service" to start building this bundle</p>
                                <button
                                    type="button"
                                    @click="addService"
                                    class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg text-sm font-medium border-2 border-dashed border-[#C4A265]/30 text-[#C4A265] hover:bg-[#C4A265]/5 transition-all duration-300"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                                    Add First Service
                                </button>
                            </div>

                            <!-- Service cards -->
                            <TransitionGroup name="cb-service" tag="div" class="space-y-4">
                                <div
                                    v-for="(item, index) in form.services"
                                    :key="'svc-' + index"
                                    class="cb-service-card rounded-xl border-2 transition-all duration-500"
                                    :class="[
                                        justAdded === index ? 'cb-pulse border-[#C4A265]/40 shadow-lg shadow-[#C4A265]/10' : 'border-gray-100 hover:border-gray-200',
                                        getServiceById(item.service_id) ? 'bg-white' : 'bg-gray-50/50'
                                    ]"
                                >
                                    <!-- Service Header (always visible) -->
                                    <div
                                        class="flex items-center justify-between px-4 py-3 cursor-pointer select-none"
                                        @click="toggleService(index)"
                                    >
                                        <div class="flex items-center gap-3 min-w-0">
                                            <span class="cb-service-num flex-shrink-0 flex items-center justify-center w-8 h-8 rounded-lg text-white text-xs font-bold transition-all duration-300"
                                                :style="getServiceById(item.service_id)
                                                    ? 'background: linear-gradient(135deg, #C4A265, #D4B87A);'
                                                    : 'background: linear-gradient(135deg, #d1d5db, #9ca3af);'"
                                            >
                                                {{ index + 1 }}
                                            </span>
                                            <div class="min-w-0">
                                                <p class="text-sm font-medium text-gray-700 truncate">
                                                    {{ getServiceById(item.service_id)?.name_en || 'Select a service...' }}
                                                </p>
                                                <p v-if="getServiceById(item.service_id)" class="text-xs text-gray-400 mt-0.5">
                                                    {{ item.sessions_count }} session{{ item.sessions_count !== 1 ? 's' : '' }}
                                                    <span v-if="item.discount_percentage > 0" class="inline-flex items-center ml-1.5 text-emerald-600">
                                                        &middot; {{ item.discount_percentage }}% off
                                                    </span>
                                                </p>
                                            </div>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <span v-if="getServiceById(item.service_id)" class="text-sm font-semibold" style="color: #C4A265;">
                                                {{ formatCurrency(item.bundle_price) }}
                                            </span>
                                            <button
                                                type="button"
                                                @click.stop="removeService(index)"
                                                class="p-1.5 rounded-lg text-gray-300 hover:text-red-500 hover:bg-red-50 transition-all duration-200"
                                            >
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                            </button>
                                            <svg class="w-4 h-4 text-gray-400 transition-transform duration-300" :class="expandedServices[index] ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                                        </div>
                                    </div>

                                    <!-- Service Body (expandable) -->
                                    <Transition name="cb-expand">
                                        <div v-if="expandedServices[index]" class="px-4 pb-4 border-t border-gray-100">
                                            <div class="grid grid-cols-1 md:grid-cols-12 gap-3 pt-4">
                                                <!-- Service select -->
                                                <div class="md:col-span-5">
                                                    <label class="block text-xs font-medium text-gray-500 mb-1.5">{{ $t('a_service') }} *</label>
                                                    <SearchableSelect
                                                        :modelValue="item.service_id"
                                                        @update:modelValue="val => { item.service_id = val; onServiceChange(index); }"
                                                        :options="serviceOptions(index)"
                                                        placeholder="Select service..."
                                                        searchPlaceholder="Search services..."
                                                    />
                                                </div>

                                                <!-- Sessions -->
                                                <div class="md:col-span-2">
                                                    <label class="block text-xs font-medium text-gray-500 mb-1.5">{{ $t('a_sessions') }}</label>
                                                    <input
                                                        v-model.number="item.sessions_count"
                                                        type="number"
                                                        min="1"
                                                        @change="recalcBundlePrice(index)"
                                                        class="doctorato-input cb-input text-sm"
                                                    />
                                                </div>

                                                <!-- Discount -->
                                                <div class="md:col-span-2">
                                                    <label class="block text-xs font-medium text-gray-500 mb-1.5">{{ $t('a_discount_pct') }}</label>
                                                    <input
                                                        v-model.number="item.discount_percentage"
                                                        type="number"
                                                        min="0"
                                                        max="100"
                                                        step="0.01"
                                                        @input="recalcBundlePrice(index)"
                                                        class="doctorato-input cb-input text-sm"
                                                    />
                                                </div>

                                                <!-- Bundle price -->
                                                <div class="md:col-span-3">
                                                    <label class="block text-xs font-medium text-gray-500 mb-1.5">{{ $t('a_bundle_price') }}</label>
                                                    <input
                                                        v-model.number="item.bundle_price"
                                                        type="number"
                                                        step="0.01"
                                                        min="0"
                                                        class="doctorato-input cb-input text-sm font-semibold"
                                                        style="color: #C4A265;"
                                                    />
                                                </div>
                                            </div>

                                            <!-- Per-service breakdown -->
                                            <div v-if="getServiceById(item.service_id)" class="mt-3 p-3 rounded-lg transition-all duration-300" style="background: linear-gradient(135deg, rgba(196,162,101,0.04), rgba(196,162,101,0.01));">
                                                <div class="flex items-center justify-between text-xs">
                                                    <span class="text-gray-500">
                                                        Unit: {{ formatCurrency(getServiceById(item.service_id)?.price) }}
                                                        x {{ item.sessions_count }} session{{ item.sessions_count !== 1 ? 's' : '' }}
                                                        = <span class="font-medium text-gray-600">{{ formatCurrency(getServiceById(item.service_id)?.price * item.sessions_count) }}</span>
                                                    </span>
                                                    <span v-if="item.discount_percentage > 0" class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-700 font-semibold">
                                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A2 2 0 013 12V7a4 4 0 014-4z" /></svg>
                                                        -{{ item.discount_percentage }}%
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </Transition>
                                </div>
                            </TransitionGroup>
                        </div>
                        <p v-if="form.errors.services" class="px-4 md:px-6 pb-4 text-sm text-red-600">{{ form.errors.services }}</p>
                    </div>
                </div>

                <!-- ===== Sidebar (1/3) ===== -->
                <div class="space-y-6">

                    <!-- ---- Pricing Summary Card ---- -->
                    <div class="cb-card cb-pricing-card bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                        <div class="px-4 md:px-6 py-4 text-white" style="background: linear-gradient(135deg, #1E1E1E, #2d2d2d);">
                            <div class="flex items-center justify-between">
                                <h3 class="text-sm font-semibold uppercase tracking-wider opacity-80">{{ $t('a_pricing_summary') }}</h3>
                                <svg class="w-5 h-5 opacity-40" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" /></svg>
                            </div>
                        </div>
                        <div class="p-4 md:p-6 space-y-4">
                            <!-- Stats Row -->
                            <div class="grid grid-cols-2 gap-3">
                                <div class="cb-stat-box text-center p-3 rounded-lg border border-gray-100 transition-all duration-300 hover:border-[#C4A265]/20 hover:shadow-sm">
                                    <p class="text-xl md:text-2xl font-bold text-gray-800 cb-counter">{{ form.services.length }}</p>
                                    <p class="text-[10px] uppercase tracking-wider text-gray-400 font-medium mt-0.5">Services</p>
                                </div>
                                <div class="cb-stat-box text-center p-3 rounded-lg border border-gray-100 transition-all duration-300 hover:border-[#C4A265]/20 hover:shadow-sm">
                                    <p class="text-xl md:text-2xl font-bold text-gray-800 cb-counter">{{ totalSessions }}</p>
                                    <p class="text-[10px] uppercase tracking-wider text-gray-400 font-medium mt-0.5">Sessions</p>
                                </div>
                            </div>

                            <div class="space-y-2.5 pt-2">
                                <div class="flex items-center justify-between text-sm">
                                    <span class="text-gray-500">Original Total</span>
                                    <span class="text-gray-400 line-through">{{ formatCurrency(originalPrice) }}</span>
                                </div>
                                <div class="flex items-center justify-between text-sm">
                                    <span class="text-gray-500">Services Sub-total</span>
                                    <span class="font-medium text-gray-700">{{ formatCurrency(servicesTotal) }}</span>
                                </div>
                            </div>

                            <div class="border-t border-gray-100 pt-4">
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Bundle Price ({{ currencyCode }}) *</label>
                                <input
                                    v-model="form.total_price"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    class="doctorato-input cb-price-input w-full px-4 py-3 border-2 rounded-xl text-xl font-bold text-center transition-all duration-300"
                                    style="border-color: #C4A265; color: #C4A265;"
                                />
                                <p v-if="form.errors.total_price" class="mt-1.5 text-sm text-red-600">{{ form.errors.total_price }}</p>
                            </div>

                            <!-- Savings badge -->
                            <Transition name="cb-fade">
                                <div v-if="savingsAmount > 0" class="cb-savings p-4 rounded-xl border border-emerald-200 overflow-hidden relative" style="background: linear-gradient(135deg, #ecfdf5, #f0fdf4);">
                                    <div class="absolute top-0 right-0 w-16 h-16 rounded-full bg-emerald-400/5 -translate-y-1/2 translate-x-1/2"></div>
                                    <div class="flex items-center justify-between relative">
                                        <div class="flex items-center gap-2">
                                            <span class="flex items-center justify-center w-8 h-8 rounded-lg bg-emerald-100">
                                                <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" /></svg>
                                            </span>
                                            <div>
                                                <p class="text-[10px] uppercase tracking-wider text-emerald-600 font-semibold">Customer Saves</p>
                                                <p class="text-lg font-bold text-emerald-700">{{ formatCurrency(savingsAmount) }}</p>
                                            </div>
                                        </div>
                                        <span class="cb-savings-badge inline-flex items-center justify-center w-12 h-12 rounded-full bg-emerald-100 text-emerald-700 font-bold text-sm">
                                            {{ savingsPercentage }}%
                                        </span>
                                    </div>
                                </div>
                            </Transition>
                        </div>
                    </div>

                    <!-- ---- Settings Card ---- -->
                    <div class="cb-card bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                        <div class="cb-card-header px-4 md:px-6 py-4 border-b border-gray-50" style="background: linear-gradient(135deg, rgba(196,162,101,0.04), rgba(196,162,101,0.01));">
                            <div class="flex items-center gap-2.5">
                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                                <h3 class="text-sm font-semibold text-gray-800">{{ $t('a_settings') }}</h3>
                            </div>
                        </div>
                        <div class="p-4 md:p-6 space-y-5">
                            <div class="cb-field">
                                <label class="cb-label">
                                    <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" /></svg>
                                    Display Order
                                </label>
                                <input v-model="form.display_order" type="number" min="0" class="doctorato-input cb-input" />
                                <p class="text-[11px] text-gray-400 mt-1">Lower numbers appear first</p>
                            </div>

                            <!-- Active Toggle -->
                            <div class="pt-3 border-t border-gray-100">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-2">
                                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                                        <div>
                                            <p class="text-sm font-medium text-gray-700">Active</p>
                                            <p class="text-[10px] text-gray-400">Visible to patients on the website</p>
                                        </div>
                                    </div>
                                    <label class="cb-toggle relative inline-flex items-center cursor-pointer">
                                        <input v-model="form.is_active" type="checkbox" class="sr-only peer" />
                                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-[#C4A265] after:shadow-sm"></div>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ---- Featured Image Card ---- -->
                    <div class="cb-card bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                        <div class="cb-card-header px-4 md:px-6 py-4 border-b border-gray-50" style="background: linear-gradient(135deg, rgba(196,162,101,0.04), rgba(196,162,101,0.01));">
                            <div class="flex items-center gap-2.5">
                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                                <h3 class="text-sm font-semibold text-gray-800">{{ $t('a_featured_image') }}</h3>
                            </div>
                        </div>
                        <div class="p-4 md:p-6 space-y-4">
                            <!-- Image preview -->
                            <div v-if="imagePreview" class="relative group rounded-xl overflow-hidden">
                                <img :src="imagePreview" alt="Bundle image" class="w-full h-44 object-cover transition-transform duration-500 group-hover:scale-105" />
                                <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-all duration-300 flex items-end justify-center pb-4">
                                    <button type="button" @click="removeImage" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-white/90 backdrop-blur-sm rounded-lg text-xs font-medium text-red-600 hover:bg-white transition-all duration-200">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                        Remove
                                    </button>
                                </div>
                            </div>

                            <!-- Upload dropzone -->
                            <div
                                v-else
                                class="cb-dropzone relative w-full h-40 border-2 border-dashed rounded-xl flex items-center justify-center transition-all duration-300 cursor-pointer"
                                :class="isDragging ? 'border-[#C4A265] bg-[#C4A265]/5 scale-[1.02]' : 'border-gray-200 hover:border-[#C4A265]/40 hover:bg-gray-50'"
                                @dragover.prevent="isDragging = true"
                                @dragleave="isDragging = false"
                                @drop.prevent="handleDrop"
                                @click="$refs.fileInput.click()"
                            >
                                <div class="text-center">
                                    <div class="mx-auto w-12 h-12 rounded-xl flex items-center justify-center mb-2 transition-colors duration-300" :class="isDragging ? 'bg-[#C4A265]/10' : 'bg-gray-100'">
                                        <svg class="w-6 h-6 transition-colors duration-300" :class="isDragging ? 'text-[#C4A265]' : 'text-gray-400'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" /></svg>
                                    </div>
                                    <p class="text-sm font-medium text-gray-500">Drop image here or <span class="text-[#C4A265]">browse</span></p>
                                    <p class="text-xs text-gray-400 mt-0.5">PNG, JPG, WEBP up to 5MB</p>
                                </div>
                            </div>

                            <input
                                ref="fileInput"
                                type="file"
                                accept="image/*"
                                @change="handleImageChange"
                                class="hidden"
                            />
                            <p v-if="form.errors.image" class="text-sm text-red-600">{{ form.errors.image }}</p>
                        </div>
                    </div>

                    <!-- ---- Action Buttons ---- -->
                    <div class="space-y-3">
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="cb-submit-btn w-full py-3 px-4 rounded-xl text-white font-semibold text-sm transition-all duration-300 disabled:opacity-50 hover:shadow-lg hover:shadow-[#C4A265]/25 active:scale-[0.98] flex items-center justify-center gap-2"
                            style="background: linear-gradient(135deg, #C4A265, #D4B87A);"
                        >
                            <svg v-if="!form.processing" class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                            <svg v-else class="w-4.5 h-4.5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" /><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" /></svg>
                            {{ form.processing ? 'Creating Bundle...' : 'Create Bundle' }}
                        </button>
                        <Link href="/admin/package-bundles" class="w-full py-2.5 px-4 rounded-xl bg-gray-100 text-gray-600 text-sm font-medium hover:bg-gray-200 transition-all duration-200 flex items-center justify-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                            Cancel
                        </Link>
                    </div>
                </div>
            </form>
        </div>
    </AdminLayout>
</template>

<style scoped>
/* ===== Base Animations ===== */
.cb-page {
    animation: cbFadeUp 0.5s ease-out;
}

@keyframes cbFadeUp {
    from { opacity: 0; transform: translateY(12px); }
    to { opacity: 1; transform: translateY(0); }
}

/* ===== Cards entrance ===== */
.cb-card {
    animation: cbCardIn 0.5s ease-out both;
}

.cb-card:nth-child(1) { animation-delay: 0.05s; }
.cb-card:nth-child(2) { animation-delay: 0.1s; }
.cb-card:nth-child(3) { animation-delay: 0.15s; }
.cb-card:nth-child(4) { animation-delay: 0.2s; }

@keyframes cbCardIn {
    from { opacity: 0; transform: translateY(8px); }
    to { opacity: 1; transform: translateY(0); }
}

/* ===== Progress Ring ===== */
.cb-progress-ring {
    transition: stroke-dasharray 0.6s cubic-bezier(0.4, 0, 0.2, 1);
}

/* ===== Input Styling ===== */
.cb-label {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 0.8125rem;
    font-weight: 500;
    color: #374151;
    margin-bottom: 6px;
}

.cb-input {
    width: 100%;
    padding: 0.625rem 0.875rem;
    border: 1.5px solid #e5e7eb;
    border-radius: 0.625rem;
    font-size: 0.875rem;
    background: #fff;
    transition: all 0.25s ease;
    outline: none;
}

.cb-input:focus {
    border-color: #C4A265;
    box-shadow: 0 0 0 3px rgba(196, 162, 101, 0.1);
}

.cb-input:hover:not(:focus) {
    border-color: #d1d5db;
}

.cb-textarea {
    resize: vertical;
    min-height: 80px;
}

.cb-error {
    margin-top: 4px;
    font-size: 0.8125rem;
    color: #dc2626;
}

/* ===== Price Input ===== */
.cb-price-input:focus {
    box-shadow: 0 0 0 4px rgba(196, 162, 101, 0.12);
    transform: scale(1.02);
}

/* ===== Service Card Transitions ===== */
.cb-service-enter-active {
    animation: cbServiceIn 0.4s ease-out;
}

.cb-service-leave-active {
    animation: cbServiceOut 0.3s ease-in forwards;
}

@keyframes cbServiceIn {
    from {
        opacity: 0;
        transform: translateX(-12px) scale(0.97);
    }
    to {
        opacity: 1;
        transform: translateX(0) scale(1);
    }
}

@keyframes cbServiceOut {
    to {
        opacity: 0;
        transform: translateX(12px) scale(0.97);
        max-height: 0;
        margin: 0;
        padding: 0;
    }
}

/* ===== Expand transition ===== */
.cb-expand-enter-active {
    animation: cbExpand 0.35s ease-out;
}

.cb-expand-leave-active {
    animation: cbCollapse 0.25s ease-in forwards;
}

@keyframes cbExpand {
    from {
        opacity: 0;
        max-height: 0;
        transform: translateY(-8px);
    }
    to {
        opacity: 1;
        max-height: 300px;
        transform: translateY(0);
    }
}

@keyframes cbCollapse {
    from {
        opacity: 1;
        max-height: 300px;
    }
    to {
        opacity: 0;
        max-height: 0;
        overflow: hidden;
    }
}

/* ===== Fade transition ===== */
.cb-fade-enter-active {
    animation: cbFadeIn 0.4s ease-out;
}

.cb-fade-leave-active {
    animation: cbFadeOut 0.2s ease-in forwards;
}

@keyframes cbFadeIn {
    from { opacity: 0; transform: scale(0.95); }
    to { opacity: 1; transform: scale(1); }
}

@keyframes cbFadeOut {
    to { opacity: 0; transform: scale(0.95); }
}

/* ===== Pulse for newly added ===== */
.cb-pulse {
    animation: cbPulseGlow 0.6s ease-out;
}

@keyframes cbPulseGlow {
    0% { box-shadow: 0 0 0 0 rgba(196, 162, 101, 0.4); }
    70% { box-shadow: 0 0 0 10px rgba(196, 162, 101, 0); }
    100% { box-shadow: 0 0 0 0 rgba(196, 162, 101, 0); }
}

/* ===== Empty state icon float ===== */
.cb-empty-icon {
    animation: cbFloat 3s ease-in-out infinite;
}

@keyframes cbFloat {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-6px); }
}

/* ===== Savings badge bounce ===== */
.cb-savings-badge {
    animation: cbBounceIn 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}

@keyframes cbBounceIn {
    0% { transform: scale(0); }
    60% { transform: scale(1.15); }
    100% { transform: scale(1); }
}

/* ===== Counter animation ===== */
.cb-counter {
    transition: all 0.3s ease;
}

/* ===== Stat boxes ===== */
.cb-stat-box:hover .cb-counter {
    color: #C4A265;
}

/* ===== Submit button shimmer ===== */
.cb-submit-btn {
    position: relative;
    overflow: hidden;
}

.cb-submit-btn::after {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.15), transparent);
    transition: left 0.5s ease;
}

.cb-submit-btn:hover::after {
    left: 100%;
}

/* ===== Header icon ===== */
.cb-icon-wrap {
    animation: cbIconPop 0.6s cubic-bezier(0.175, 0.885, 0.32, 1.275) 0.2s both;
}

@keyframes cbIconPop {
    from { transform: scale(0) rotate(-15deg); }
    to { transform: scale(1) rotate(0); }
}

/* ===== Dropzone hover ===== */
.cb-dropzone:hover svg {
    animation: cbUpload 0.6s ease-in-out infinite alternate;
}

@keyframes cbUpload {
    from { transform: translateY(0); }
    to { transform: translateY(-3px); }
}

/* ===== Service number badge ===== */
.cb-service-num {
    box-shadow: 0 2px 6px rgba(196, 162, 101, 0.25);
}

/* ===== Add button plus rotation ===== */
.cb-add-btn:active svg {
    transform: rotate(90deg);
}
</style>
