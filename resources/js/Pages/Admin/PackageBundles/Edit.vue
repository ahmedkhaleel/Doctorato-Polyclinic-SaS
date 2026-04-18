<script setup>
import { ref, computed, watch } from 'vue';
import { useForm, Link, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { useCurrency } from '@/Composables/useCurrency.js';

const { formatCurrency, currencyCode } = useCurrency();

const page = usePage();
const locale = computed(() => page.props.locale || 'ar');
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const props = defineProps({
    bundle: Object,
    services: Array,
});

const form = useForm({
    name_ar: props.bundle.name_ar,
    name_en: props.bundle.name_en,
    description_ar: props.bundle.description_ar || '',
    description_en: props.bundle.description_en || '',
    total_price: props.bundle.total_price,
    is_active: props.bundle.is_active,
    image: null,
    display_order: props.bundle.display_order || 0,
    services: props.bundle.services.map(s => ({
        service_id: s.service_id,
        sessions_count: s.sessions_count,
        discount_percentage: Number(s.discount_percentage),
        bundle_price: Number(s.bundle_price),
    })),
});

const imagePreview = ref(props.bundle.image_url);

function handleImageChange(e) {
    const file = e.target.files[0];
    if (file) {
        form.image = file;
        imagePreview.value = URL.createObjectURL(file);
    }
}

function addService() {
    form.services.push({
        service_id: '',
        sessions_count: 1,
        discount_percentage: 0,
        bundle_price: 0,
    });
}

function removeService(index) {
    form.services.splice(index, 1);
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

function availableServices(currentIndex) {
    const usedIds = form.services.map((s, i) => i !== currentIndex ? s.service_id : null).filter(Boolean);
    return props.services.filter(s => !usedIds.includes(s.id));
}

// Auto-sync total_price with servicesTotal
watch(servicesTotal, (val) => {
    form.total_price = val;
});

function submit() {
    form.post(`/admin/package-bundles/${props.bundle.id}/update`, {
        forceFormData: true,
    });
}
</script>

<template>
    <AdminLayout :title="$t('a_edit_bundle')">
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <Link href="/admin/package-bundles" class="inline-flex items-center gap-1 text-sm text-gray-500 hover:text-gray-700 transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
                        Back to Bundles
                    </Link>
                    <h1 class="text-xl md:text-2xl font-bold text-gray-800 mt-1">{{ $t('a_edit_bundle') }}</h1>
                </div>
                <div class="flex items-center gap-2">
                    <span v-if="form.is_active" class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700 border border-emerald-200">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                        Active
                    </span>
                    <span v-else class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-medium bg-gray-50 text-gray-500 border border-gray-200">
                        <span class="w-1.5 h-1.5 rounded-full bg-gray-400"></span>
                        Inactive
                    </span>
                </div>
            </div>

            <form @submit.prevent="submit" class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- ===== Main Content (2 columns) ===== -->
                <div class="lg:col-span-2 space-y-6">

                    <!-- Names -->
                    <div class="bg-white rounded-lg shadow-sm p-4 md:p-6 space-y-5">
                        <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wider">{{ $t('a_bundle_information') }}</h3>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('a_name_en') }} *</label>
                                <input v-model="form.name_en" type="text" placeholder="e.g. Bride Glow Package" class="doctorato-input w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#C4A265]/30 focus:border-transparent transition" />
                                <p v-if="form.errors.name_en" class="mt-1 text-sm text-red-600">{{ form.errors.name_en }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('a_name_ar') }} *</label>
                                <input v-model="form.name_ar" type="text" dir="rtl" placeholder="مثال: باقة عروس متالقة" class="doctorato-input w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#C4A265]/30 focus:border-transparent transition" />
                                <p v-if="form.errors.name_ar" class="mt-1 text-sm text-red-600">{{ form.errors.name_ar }}</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('a_description_en') }}</label>
                                <textarea v-model="form.description_en" rows="4" :placeholder="$t('a_bundle_desc_placeholder')" class="doctorato-input w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#C4A265]/30 focus:border-transparent transition"></textarea>
                                <p v-if="form.errors.description_en" class="mt-1 text-sm text-red-600">{{ form.errors.description_en }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('a_description_ar') }}</label>
                                <textarea v-model="form.description_ar" rows="4" dir="rtl" placeholder="اكتب وصف الباقة..." class="doctorato-input w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#C4A265]/30 focus:border-transparent transition"></textarea>
                                <p v-if="form.errors.description_ar" class="mt-1 text-sm text-red-600">{{ form.errors.description_ar }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Included Services -->
                    <div class="bg-white rounded-lg shadow-sm p-6">
                        <div class="flex items-center justify-between mb-5">
                            <div>
                                <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wider">{{ $t('a_included_services') }}</h3>
                                <p class="text-xs text-gray-400 mt-1">Add services and configure sessions, discounts, and pricing for each</p>
                            </div>
                            <button
                                type="button"
                                @click="addService"
                                class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg text-white text-sm font-medium transition hover:opacity-90 active:scale-[0.97]"
                                style="background-color: #C4A265;"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                                Add Service
                            </button>
                        </div>

                        <!-- Empty state -->
                        <div v-if="form.services.length === 0" class="text-center py-12 border-2 border-dashed border-gray-200 rounded-lg">
                            <svg class="mx-auto w-10 h-10 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" /></svg>
                            <p class="mt-3 text-sm text-gray-400">No services added yet</p>
                            <p class="text-xs text-gray-400 mt-1">Click "Add Service" to start building this bundle</p>
                        </div>

                        <!-- Service cards -->
                        <div class="space-y-3">
                            <div
                                v-for="(item, index) in form.services"
                                :key="index"
                                class="border border-gray-200 rounded-lg p-4 relative hover:border-gray-300 transition group"
                            >
                                <!-- Service number badge -->
                                <div class="absolute -top-2.5 left-3 px-2 py-0.5 text-[10px] font-bold uppercase tracking-widest rounded-full text-white" style="background-color: #C4A265;">
                                    Service {{ index + 1 }}
                                </div>

                                <!-- Remove button -->
                                <button
                                    type="button"
                                    @click="removeService(index)"
                                    class="absolute top-2 right-2 p-1.5 rounded-lg text-gray-300 hover:text-red-500 hover:bg-red-50 transition opacity-0 group-hover:opacity-100"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                </button>

                                <div class="grid grid-cols-1 md:grid-cols-12 gap-3 mt-2">
                                    <!-- Service select -->
                                    <div class="md:col-span-5">
                                        <label class="block text-xs font-medium text-gray-500 mb-1">{{ $t('a_service') }} *</label>
                                        <select
                                            v-model="item.service_id"
                                            @change="onServiceChange(index)"
                                            class="doctorato-input w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#C4A265]/30 focus:border-transparent transition"
                                        >
                                            <option value="">Select service...</option>
                                            <option v-for="svc in availableServices(index)" :key="svc.id" :value="svc.id">
                                                {{ svc.name_en }} ({{ formatCurrency(svc.price) }})
                                            </option>
                                        </select>
                                    </div>

                                    <!-- Sessions -->
                                    <div class="md:col-span-2">
                                        <label class="block text-xs font-medium text-gray-500 mb-1">{{ $t('a_sessions') }}</label>
                                        <input
                                            v-model.number="item.sessions_count"
                                            type="number"
                                            min="1"
                                            @change="recalcBundlePrice(index)"
                                            class="doctorato-input w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#C4A265]/30 focus:border-transparent transition"
                                        />
                                    </div>

                                    <!-- Discount -->
                                    <div class="md:col-span-2">
                                        <label class="block text-xs font-medium text-gray-500 mb-1">{{ $t('a_discount_pct') }}</label>
                                        <input
                                            v-model.number="item.discount_percentage"
                                            type="number"
                                            min="0"
                                            max="100"
                                            step="0.01"
                                            @input="recalcBundlePrice(index)"
                                            class="doctorato-input w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#C4A265]/30 focus:border-transparent transition"
                                        />
                                    </div>

                                    <!-- Bundle price -->
                                    <div class="md:col-span-3">
                                        <label class="block text-xs font-medium text-gray-500 mb-1">Bundle Price ({{ currencyCode }})</label>
                                        <input
                                            v-model.number="item.bundle_price"
                                            type="number"
                                            step="0.01"
                                            min="0"
                                            class="doctorato-input w-full px-3 py-2 border border-gray-300 rounded-lg text-sm font-semibold focus:ring-2 focus:ring-[#C4A265]/30 focus:border-transparent transition"
                                            style="color: #C4A265;"
                                        />
                                    </div>
                                </div>

                                <!-- Per-service summary row -->
                                <div v-if="getServiceById(item.service_id)" class="mt-2.5 pt-2.5 border-t border-gray-100 flex items-center justify-between text-xs text-gray-400">
                                    <span>
                                        Unit price: {{ formatCurrency(getServiceById(item.service_id)?.price) }}
                                        &times; {{ item.sessions_count }} session{{ item.sessions_count !== 1 ? 's' : '' }}
                                        = {{ formatCurrency(getServiceById(item.service_id)?.price * item.sessions_count) }}
                                    </span>
                                    <span v-if="item.discount_percentage > 0" class="inline-flex items-center gap-1 text-emerald-600 font-medium">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A2 2 0 013 12V7a4 4 0 014-4z" /></svg>
                                        {{ item.discount_percentage }}% off
                                    </span>
                                </div>
                            </div>
                        </div>

                        <p v-if="form.errors.services" class="mt-2 text-sm text-red-600">{{ form.errors.services }}</p>
                    </div>
                </div>

                <!-- ===== Sidebar ===== -->
                <div class="space-y-6">

                    <!-- Pricing Summary Card -->
                    <div class="bg-white rounded-lg shadow-sm p-4 md:p-6 space-y-4">
                        <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wider">{{ $t('a_pricing_summary') }}</h3>

                        <div class="space-y-3">
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-gray-500">Services</span>
                                <span class="font-medium text-gray-700">{{ form.services.length }}</span>
                            </div>
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-gray-500">Total Sessions</span>
                                <span class="font-medium text-gray-700">{{ totalSessions }}</span>
                            </div>

                            <div class="border-t border-gray-100 pt-3 space-y-2">
                                <div class="flex items-center justify-between text-sm">
                                    <span class="text-gray-500">Original Total</span>
                                    <span class="text-gray-400 line-through">{{ formatCurrency(originalPrice) }}</span>
                                </div>
                                <div class="flex items-center justify-between text-sm">
                                    <span class="text-gray-500">Services Sub-total</span>
                                    <span class="font-medium text-gray-700">{{ formatCurrency(servicesTotal) }}</span>
                                </div>
                            </div>

                            <div class="border-t border-gray-100 pt-3">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Bundle Price ({{ currencyCode }}) *</label>
                                    <input
                                        v-model="form.total_price"
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        class="doctorato-input w-full px-4 py-2.5 border-2 rounded-lg text-lg font-bold text-center focus:ring-2 focus:ring-[#C4A265]/30 focus:border-transparent transition"
                                        style="border-color: #C4A265; color: #C4A265;"
                                    />
                                    <p v-if="form.errors.total_price" class="mt-1 text-sm text-red-600">{{ form.errors.total_price }}</p>
                                </div>
                            </div>

                            <!-- Savings badge -->
                            <div v-if="savingsAmount > 0" class="p-3 rounded-lg bg-emerald-50 border border-emerald-200">
                                <div class="flex items-center justify-between">
                                    <span class="text-xs font-medium text-emerald-700">Customer Savings</span>
                                    <span class="text-sm font-bold text-emerald-700">{{ savingsPercentage }}%</span>
                                </div>
                                <p class="text-lg font-bold text-emerald-700 mt-0.5">{{ formatCurrency(savingsAmount) }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Settings Card -->
                    <div class="bg-white rounded-lg shadow-sm p-4 md:p-6 space-y-5">
                        <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wider">{{ $t('a_settings') }}</h3>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('a_display_order') }}</label>
                            <input v-model="form.display_order" type="number" min="0" class="doctorato-input w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#C4A265]/30 focus:border-transparent transition" />
                            <p class="text-xs text-gray-400 mt-1">Lower numbers appear first</p>
                        </div>

                        <!-- Status toggle -->
                        <div class="pt-3 border-t border-gray-100">
                            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">Status</p>
                            <div class="flex items-center">
                                <input v-model="form.is_active" type="checkbox" id="is_active" class="h-4 w-4 rounded border-gray-300 text-[#C4A265] focus:ring-[#C4A265]" />
                                <label for="is_active" class="ml-2 text-sm text-gray-700">{{ $t('a_active') }}</label>
                            </div>
                            <p class="text-[10px] text-gray-400 ml-6 mt-0.5">Visible to patients on the website</p>
                        </div>
                    </div>

                    <!-- Featured Image Card -->
                    <div class="bg-white rounded-lg shadow-sm p-4 md:p-6 space-y-4">
                        <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wider">{{ $t('a_featured_image') }}</h3>

                        <!-- Image preview -->
                        <div v-if="imagePreview" class="relative group">
                            <img :src="imagePreview" alt="Bundle image" class="w-full h-40 object-cover rounded-lg" />
                            <div class="absolute inset-0 bg-black/30 rounded-lg opacity-0 group-hover:opacity-100 transition flex items-center justify-center">
                                <span class="text-white text-xs font-medium">Upload a new image to replace</span>
                            </div>
                        </div>
                        <div v-else class="w-full h-32 border-2 border-dashed border-gray-200 rounded-lg flex items-center justify-center">
                            <div class="text-center">
                                <svg class="mx-auto w-8 h-8 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                                <p class="text-xs text-gray-400 mt-1">No image</p>
                            </div>
                        </div>

                        <input
                            type="file"
                            accept="image/*"
                            @change="handleImageChange"
                            class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-gray-100 file:text-gray-700 hover:file:bg-gray-200"
                        />
                        <p class="text-xs text-gray-400">Leave empty to keep current image</p>
                        <p v-if="form.errors.image" class="mt-1 text-sm text-red-600">{{ form.errors.image }}</p>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex space-x-3 rtl:space-x-reverse">
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="flex-1 py-2.5 px-4 rounded-lg text-white font-medium text-sm transition disabled:opacity-50 hover:opacity-90 active:scale-[0.97]"
                            style="background-color: #C4A265;"
                        >
                            {{ form.processing ? 'Saving...' : 'Save Changes' }}
                        </button>
                        <Link href="/admin/package-bundles" class="px-4 py-2.5 rounded-lg bg-gray-200 text-gray-700 text-sm font-medium hover:bg-gray-300 transition">
                            Cancel
                        </Link>
                    </div>
                </div>
            </form>
        </div>
    </AdminLayout>
</template>
