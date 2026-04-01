<script setup>
import { computed } from 'vue';
import { usePage, Link } from '@inertiajs/vue3';
import PatientLayout from '@/Layouts/PatientLayout.vue';
import { usePatientLocale } from '@/Composables/usePatientLocale';

const { lp } = usePatientLocale();
defineOptions({ layout: PatientLayout });

const props = defineProps({ labOrders: Object });

const page = usePage();
const locale = computed(() => page.props.locale || 'ar');
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

function $localized(obj, field) {
    if (!obj) return '';
    return obj[field + '_' + (locale.value === 'ar' ? 'ar' : 'en')] || obj[field + '_en'] || obj[field] || '';
}

const statusConfig = {
    ordered: { ar: 'تم الطلب', en: 'Ordered', color: 'bg-blue-100 text-blue-700', icon: 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z' },
    in_production: { ar: 'قيد التصنيع', en: 'In Production', color: 'bg-yellow-100 text-yellow-700', icon: 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z' },
    ready: { ar: 'جاهز للتركيب', en: 'Ready', color: 'bg-green-100 text-green-700', icon: 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z' },
    delivered: { ar: 'تم التسليم', en: 'Delivered', color: 'bg-green-100 text-green-700', icon: 'M5 13l4 4L19 7' },
    completed: { ar: 'مكتمل', en: 'Completed', color: 'bg-gray-100 text-gray-600', icon: 'M5 13l4 4L19 7' },
    cancelled: { ar: 'ملغي', en: 'Cancelled', color: 'bg-red-100 text-red-600', icon: 'M6 18L18 6M6 6l12 12' },
};

function getStatus(status) { return statusConfig[status] || { ar: status, en: status, color: 'bg-gray-100 text-gray-500', icon: '' }; }

const itemLabels = {
    crown: { ar: 'تاج', en: 'Crown' },
    bridge: { ar: 'جسر', en: 'Bridge' },
    denture: { ar: 'طقم أسنان', en: 'Denture' },
    veneer: { ar: 'فينير', en: 'Veneer' },
    retainer: { ar: 'مثبت', en: 'Retainer' },
    aligner: { ar: 'تقويم شفاف', en: 'Aligner' },
    night_guard: { ar: 'حارس ليلي', en: 'Night Guard' },
    implant_abutment: { ar: 'دعامة زرعة', en: 'Implant Abutment' },
    inlay_onlay: { ar: 'حشوة تجميلية', en: 'Inlay/Onlay' },
};

function itemLabel(type) {
    const l = itemLabels[type];
    return l ? (isRtl.value ? l.ar : l.en) : type;
}
</script>

<template>
    <div>
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-800">{{ isRtl ? 'طلبات المعمل' : 'Lab Orders' }}</h1>
            <p class="text-sm text-gray-500 mt-1">{{ isRtl ? 'تتبع حالة الأعمال المخبرية (التيجان، الجسور، الأطقم...)' : 'Track the status of your dental lab work (crowns, bridges, dentures...)' }}</p>
        </div>

        <!-- Orders List -->
        <div v-if="labOrders.data?.length" class="space-y-4">
            <div v-for="order in labOrders.data" :key="order.id"
                class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 hover:shadow-md transition-shadow">
                <div class="flex items-start justify-between mb-3">
                    <div>
                        <h3 class="text-sm font-semibold text-gray-800">{{ itemLabel(order.item_type) }}</h3>
                        <p class="text-xs text-gray-500 mt-0.5">{{ $localized(order.doctor, 'name') }}</p>
                    </div>
                    <span :class="['px-2.5 py-1 rounded-full text-xs font-medium', getStatus(order.status).color]">
                        {{ isRtl ? getStatus(order.status).ar : getStatus(order.status).en }}
                    </span>
                </div>

                <!-- Progress Bar -->
                <div class="mb-3">
                    <div class="flex justify-between text-[10px] text-gray-400 mb-1">
                        <span>{{ isRtl ? 'طلب' : 'Ordered' }}</span>
                        <span>{{ isRtl ? 'تصنيع' : 'Production' }}</span>
                        <span>{{ isRtl ? 'جاهز' : 'Ready' }}</span>
                        <span>{{ isRtl ? 'تسليم' : 'Delivered' }}</span>
                    </div>
                    <div class="w-full bg-gray-100 rounded-full h-2">
                        <div class="h-2 rounded-full transition-all duration-500"
                            :class="{
                                'bg-blue-400 w-1/4': order.status === 'ordered',
                                'bg-yellow-400 w-2/4': order.status === 'in_production',
                                'bg-green-400 w-3/4': order.status === 'ready',
                                'bg-green-500 w-full': ['delivered', 'completed'].includes(order.status),
                                'bg-red-300 w-full': order.status === 'cancelled',
                            }">
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-4 text-xs text-gray-500">
                    <span v-if="order.order_date">{{ isRtl ? 'تاريخ الطلب:' : 'Ordered:' }} {{ order.order_date }}</span>
                    <span v-if="order.expected_date">{{ isRtl ? 'متوقع:' : 'Expected:' }} {{ order.expected_date }}</span>
                    <span v-if="order.delivered_date" class="text-green-600">{{ isRtl ? 'تسليم:' : 'Delivered:' }} {{ order.delivered_date }}</span>
                    <span v-if="order.tooth_number" class="text-gray-400">{{ isRtl ? 'سن' : 'Tooth' }} #{{ order.tooth_number }}</span>
                </div>

                <p v-if="order.notes" class="mt-2 text-xs text-gray-400 italic">{{ order.notes }}</p>
            </div>
        </div>

        <!-- Empty State -->
        <div v-else class="bg-white rounded-2xl shadow-sm border border-gray-100 p-12 text-center">
            <svg class="w-12 h-12 text-gray-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" /></svg>
            <p class="text-gray-400 text-sm">{{ isRtl ? 'لا توجد طلبات معمل' : 'No lab orders yet' }}</p>
        </div>

        <!-- Pagination -->
        <div v-if="labOrders.last_page > 1" class="flex justify-center mt-6 gap-1">
            <Link v-for="link in labOrders.links" :key="link.label"
                :href="link.url || '#'"
                :class="['px-3 py-1.5 text-xs rounded-lg border', link.active ? 'bg-cyan-600 text-white border-cyan-600' : 'bg-white text-gray-600 border-gray-200 hover:bg-gray-50']"
                v-html="link.label" preserve-state />
        </div>
    </div>
</template>
