<script setup>
import { ref, watch, onMounted, computed } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import DoctorLayout from '@/Layouts/DoctorLayout.vue';
import { useLocale } from '@/Composables/useLocale.js';

defineOptions({ layout: DoctorLayout });

const { t } = useLocale();
const page = usePage();
const locale = computed(() => page.props.locale || 'ar');
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const props = defineProps({
    xrays: Object,
    filters: Object,
    xrayTypes: Array,
});

const mounted = ref(false);
const search = ref(props.filters?.search || '');
const typeFilter = ref(props.filters?.type || '');
const viewMode = ref('grid');
const selectedXray = ref(null);

let searchTimeout = null;

onMounted(() => {
    setTimeout(() => { mounted.value = true; }, 50);
});

function applyFilters() {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        router.get('/doctor/dental/xrays', {
            search: search.value || undefined,
            type: typeFilter.value || undefined,
        }, {
            preserveState: true,
            replace: true,
        });
    }, 400);
}

watch([search, typeFilter], applyFilters);

function formatDate(date) {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
}
</script>

<template>
    <div class="space-y-6">
        <!-- Hero Header -->
        <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-gray-900 via-gray-800 to-gray-900 p-6 sm:p-8"
            :class="mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'"
            style="transition: all 0.7s cubic-bezier(0.16, 1, 0.3, 1)"
        >
            <div class="absolute top-0 right-0 w-72 h-72 bg-[#C4A265]/10 rounded-full -translate-y-1/2 translate-x-1/3 blur-3xl"></div>
            <div class="absolute bottom-0 left-0 w-48 h-48 bg-purple-500/10 rounded-full translate-y-1/2 -translate-x-1/4 blur-2xl"></div>

            <div class="relative z-10">
                <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mb-5">
                    <div>
                        <p class="text-[#C4A265] text-xs font-semibold tracking-wider uppercase mb-1">{{ isRtl ? 'طب الأسنان' : 'Dental' }}</p>
                        <h1 class="text-2xl sm:text-3xl font-bold text-white">{{ isRtl ? 'صور الأشعة' : 'X-Rays' }}</h1>
                        <p class="text-gray-400 text-sm mt-1">{{ isRtl ? 'جميع صور الأشعة الخاصة بمرضاك' : 'All your patients x-ray images' }}</p>
                    </div>
                    <div class="flex items-center gap-3">
                        <!-- View Toggle -->
                        <div class="flex items-center bg-white/5 backdrop-blur-sm rounded-lg p-1 border border-white/10">
                            <button
                                @click="viewMode = 'table'"
                                :class="viewMode === 'table' ? 'bg-white/15 text-[#C4A265]' : 'text-gray-400'"
                                class="px-3 py-1.5 text-sm rounded-md transition"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16" />
                                </svg>
                            </button>
                            <button
                                @click="viewMode = 'grid'"
                                :class="viewMode === 'grid' ? 'bg-white/15 text-[#C4A265]' : 'text-gray-400'"
                                class="px-3 py-1.5 text-sm rounded-md transition"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                                </svg>
                            </button>
                        </div>
                        <div class="bg-white/5 backdrop-blur-sm rounded-xl px-4 py-3 border border-white/10 text-center">
                            <p class="text-2xl font-bold text-white">{{ xrays.total || 0 }}</p>
                            <p class="text-xs text-gray-400">{{ isRtl ? 'إجمالي الأشعة' : 'Total X-Rays' }}</p>
                        </div>
                    </div>
                </div>

                <!-- Filters -->
                <div class="flex flex-col sm:flex-row gap-3 max-w-2xl"
                    :class="mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'"
                    style="transition: all 0.6s cubic-bezier(0.16, 1, 0.3, 1); transition-delay: 0.15s"
                >
                    <div class="relative flex-1">
                        <svg class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                        <input
                            v-model="search"
                            type="text"
                            :placeholder="isRtl ? 'بحث بالمريض...' : 'Search by patient...'"
                            class="w-full pl-12 pr-4 py-3 bg-white/10 backdrop-blur-sm border border-white/15 rounded-xl text-sm text-white placeholder-gray-400 focus:ring-2 focus:ring-[#C4A265]/30 focus:border-[#C4A265]/50 focus:bg-white/15 transition-all"
                        />
                    </div>
                    <select
                        v-model="typeFilter"
                        class="px-4 py-3 bg-white/10 backdrop-blur-sm border border-white/15 rounded-xl text-sm text-white focus:ring-2 focus:ring-[#C4A265]/30 focus:border-[#C4A265]/50 transition-all"
                    >
                        <option value="" class="text-gray-900">{{ isRtl ? 'جميع الأنواع' : 'All Types' }}</option>
                        <option v-for="xType in xrayTypes" :key="xType.value || xType" :value="xType.value || xType" class="text-gray-900">
                            {{ isRtl ? {
                                periapical: 'حول الذروة', panoramic: 'بانوراما', bitewing: 'جناح العضة',
                                cephalometric: 'قياس الرأس', cbct: 'أشعة مقطعية مخروطية', occlusal: 'إطباقية'
                            }[xType.value || xType] || (xType.value || xType) : (xType.label || xType.value || xType) }}
                        </option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Table View -->
        <div v-if="viewMode === 'table'" class="bg-white rounded-2xl shadow-sm border border-gray-100/80 overflow-hidden"
            :class="mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'"
            style="transition: all 0.6s cubic-bezier(0.16, 1, 0.3, 1); transition-delay: 0.2s"
        >
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50/80">
                        <tr>
                            <th class="px-4 py-3 text-start text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ isRtl ? 'المريض' : 'Patient' }}</th>
                            <th class="px-4 py-3 text-start text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ isRtl ? 'النوع' : 'Type' }}</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ isRtl ? 'رقم السن' : 'Tooth #' }}</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ isRtl ? 'صورة مصغرة' : 'Thumbnail' }}</th>
                            <th class="px-4 py-3 text-start text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ isRtl ? 'التاريخ' : 'Date' }}</th>
                            <th class="px-4 py-3 text-end text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ isRtl ? 'إجراءات' : 'Actions' }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="xray in xrays.data" :key="xray.id" class="hover:bg-gray-50/60 transition-colors">
                            <td class="px-4 py-3 text-sm">
                                <div class="font-semibold text-gray-800">{{ xray.patient?.full_name || '-' }}</div>
                                <div class="text-xs text-gray-400 font-mono">{{ xray.patient?.file_number }}</div>
                            </td>
                            <td class="px-4 py-3 text-sm">
                                <span class="px-2 py-1 bg-[#C4A265]/10 text-[#C4A265] rounded text-xs font-medium">
                                    {{ isRtl ? {
                                        periapical: 'حول الذروة', panoramic: 'بانوراما', bitewing: 'جناح العضة',
                                        cephalometric: 'قياس الرأس', cbct: 'مقطعية مخروطية', occlusal: 'إطباقية'
                                    }[xray.type] || xray.type : xray.type }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-sm text-center font-mono font-bold">{{ xray.tooth_number || '-' }}</td>
                            <td class="px-4 py-3 text-center">
                                <img
                                    v-if="xray.image_url"
                                    :src="xray.image_url"
                                    alt=""
                                    class="w-12 h-12 object-cover rounded-lg border mx-auto cursor-pointer hover:opacity-80 transition"
                                    @click="selectedXray = xray"
                                />
                                <span v-else class="text-gray-400 text-xs">-</span>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-500">{{ formatDate(xray.taken_date || xray.created_at) }}</td>
                            <td class="px-4 py-3 text-end">
                                <Link v-if="xray.patient" :href="`/doctor/dental/xrays/patient/${xray.patient.id}`"
                                    class="text-[#C4A265] hover:text-[#B39255] text-xs font-medium transition-colors">
                                    {{ isRtl ? 'عرض الكل' : 'View All' }}
                                </Link>
                            </td>
                        </tr>
                        <tr v-if="!xrays.data || xrays.data.length === 0">
                            <td colspan="6" class="px-6 py-16 text-center">
                                <div class="w-16 h-16 mx-auto bg-gray-50 rounded-2xl flex items-center justify-center mb-4 border border-gray-100">
                                    <svg class="w-8 h-8 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                                </div>
                                <p class="text-sm font-medium text-gray-500">{{ isRtl ? 'لا توجد صور أشعة' : 'No x-rays found' }}</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div v-if="xrays.links && xrays.links.length > 3" class="flex items-center justify-center gap-1 px-6 py-4 border-t border-gray-100 bg-gray-50/50">
                <template v-for="link in xrays.links" :key="link.label">
                    <Link v-if="link.url" :href="link.url"
                        class="px-3 py-1.5 rounded-lg text-xs font-medium transition-colors"
                        :class="link.active ? 'bg-[#C4A265] text-white' : 'text-gray-500 hover:bg-gray-100'"
                        v-html="link.label" preserve-state
                    />
                    <span v-else class="px-3 py-1.5 text-xs text-gray-300" v-html="link.label" />
                </template>
            </div>
        </div>

        <!-- Grid View -->
        <div v-if="viewMode === 'grid'"
            :class="mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'"
            style="transition: all 0.6s cubic-bezier(0.16, 1, 0.3, 1); transition-delay: 0.2s"
        >
            <div v-if="!xrays.data || xrays.data.length === 0" class="bg-white rounded-2xl shadow-sm border border-gray-100/80 p-12 text-center">
                <div class="w-16 h-16 mx-auto bg-gray-50 rounded-2xl flex items-center justify-center mb-4 border border-gray-100">
                    <svg class="w-8 h-8 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                </div>
                <p class="text-sm font-medium text-gray-500">{{ isRtl ? 'لا توجد صور أشعة' : 'No x-rays found' }}</p>
            </div>
            <div v-else class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                <div v-for="xray in xrays.data" :key="xray.id"
                    class="bg-white rounded-2xl shadow-sm border border-gray-100/80 overflow-hidden hover:shadow-md transition group cursor-pointer"
                    @click="selectedXray = xray"
                >
                    <div class="aspect-square bg-gray-100 relative">
                        <img
                            v-if="xray.image_url"
                            :src="xray.image_url"
                            alt=""
                            class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                        />
                        <div v-else class="w-full h-full flex items-center justify-center text-gray-300">
                            <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <div class="absolute top-2 start-2">
                            <span class="px-2 py-1 bg-[#C4A265] text-white rounded text-xs font-medium shadow-sm">
                                {{ isRtl ? {
                                    periapical: 'حول الذروة', panoramic: 'بانوراما', bitewing: 'جناح العضة',
                                    cephalometric: 'قياس الرأس', cbct: 'مقطعية', occlusal: 'إطباقية'
                                }[xray.type] || xray.type : xray.type }}
                            </span>
                        </div>
                    </div>
                    <div class="p-3">
                        <div class="font-semibold text-sm text-gray-900">{{ xray.patient?.full_name || '-' }}</div>
                        <div class="text-xs text-gray-500 mt-1 flex items-center justify-between">
                            <span>{{ isRtl ? 'سن' : 'Tooth' }} {{ xray.tooth_number || '-' }}</span>
                            <span>{{ formatDate(xray.taken_date || xray.created_at) }}</span>
                        </div>
                        <Link v-if="xray.patient" :href="`/doctor/dental/xrays/patient/${xray.patient.id}`"
                            class="text-[#C4A265] hover:text-[#B39255] text-xs mt-2 inline-block font-medium" @click.stop>
                            {{ isRtl ? 'عرض صور المريض' : 'View Patient X-Rays' }}
                        </Link>
                    </div>
                </div>
            </div>

            <!-- Grid Pagination -->
            <div v-if="xrays.links && xrays.links.length > 3" class="mt-4 flex items-center justify-center gap-1">
                <template v-for="link in xrays.links" :key="link.label">
                    <Link v-if="link.url" :href="link.url"
                        class="px-3 py-1.5 rounded-lg text-xs font-medium transition-colors"
                        :class="link.active ? 'bg-[#C4A265] text-white' : 'text-gray-500 hover:bg-gray-100 bg-white border'"
                        v-html="link.label" preserve-state
                    />
                    <span v-else class="px-3 py-1.5 text-xs text-gray-300" v-html="link.label" />
                </template>
            </div>
        </div>

        <!-- Lightbox Modal -->
        <Teleport to="body">
            <div v-if="selectedXray" class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 p-4" @click.self="selectedXray = null">
                <div class="relative max-w-4xl w-full">
                    <button @click="selectedXray = null" class="absolute -top-10 end-0 text-white hover:text-gray-300 transition">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                    <img
                        v-if="selectedXray.image_url"
                        :src="selectedXray.image_url"
                        alt=""
                        class="w-full rounded-lg"
                    />
                    <div class="mt-4 bg-white rounded-xl p-4">
                        <div class="grid grid-cols-2 gap-4 text-sm">
                            <div>
                                <span class="text-gray-500">{{ isRtl ? 'المريض' : 'Patient' }}:</span>
                                <span class="ms-1 font-medium">{{ selectedXray.patient?.full_name || '-' }}</span>
                            </div>
                            <div>
                                <span class="text-gray-500">{{ isRtl ? 'النوع' : 'Type' }}:</span>
                                <span class="ms-1 font-medium">{{ selectedXray.type }}</span>
                            </div>
                            <div>
                                <span class="text-gray-500">{{ isRtl ? 'السن' : 'Tooth' }}:</span>
                                <span class="ms-1 font-medium">{{ selectedXray.tooth_number || '-' }}</span>
                            </div>
                            <div>
                                <span class="text-gray-500">{{ isRtl ? 'التاريخ' : 'Date' }}:</span>
                                <span class="ms-1 font-medium">{{ formatDate(selectedXray.taken_date || selectedXray.created_at) }}</span>
                            </div>
                            <div v-if="selectedXray.findings" class="col-span-2">
                                <span class="text-gray-500">{{ isRtl ? 'النتائج' : 'Findings' }}:</span>
                                <span class="ms-1">{{ selectedXray.findings }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </Teleport>
    </div>
</template>
