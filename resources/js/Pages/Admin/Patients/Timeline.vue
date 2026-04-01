<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { ref, computed } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'

defineOptions({ layout: AdminLayout })

const props = defineProps({ patient: Object, timeline: Array })

const page = usePage()
const locale = computed(() => page.props.locale || 'ar')
const isRtl = computed(() => locale.value === 'ar')

const typeFilter = ref('')
const searchQuery = ref('')

const typeConfig = {
    visit: { icon: 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z', color: 'bg-blue-500', ring: 'ring-blue-100', label_en: 'Visit', label_ar: 'زيارة' },
    invoice: { icon: 'M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z', color: 'bg-emerald-500', ring: 'ring-emerald-100', label_en: 'Invoice', label_ar: 'فاتورة' },
    prescription: { icon: 'M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z', color: 'bg-rose-500', ring: 'ring-rose-100', label_en: 'Prescription', label_ar: 'وصفة' },
    vital: { icon: 'M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z', color: 'bg-red-500', ring: 'ring-red-100', label_en: 'Vitals', label_ar: 'علامات حيوية' },
    document: { icon: 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z', color: 'bg-purple-500', ring: 'ring-purple-100', label_en: 'Document', label_ar: 'مستند' },
    referral: { icon: 'M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4', color: 'bg-indigo-500', ring: 'ring-indigo-100', label_en: 'Referral', label_ar: 'تحويل' },
}

const statusColors = {
    completed: 'bg-green-100 text-green-700',
    paid: 'bg-green-100 text-green-700',
    active: 'bg-blue-100 text-blue-700',
    in_progress: 'bg-blue-100 text-blue-700',
    waiting: 'bg-yellow-100 text-yellow-700',
    pending: 'bg-yellow-100 text-yellow-700',
    unpaid: 'bg-red-100 text-red-700',
    cancelled: 'bg-gray-100 text-gray-500',
    declined: 'bg-red-100 text-red-700',
    expired: 'bg-red-100 text-red-700',
    recorded: 'bg-cyan-100 text-cyan-700',
    issued: 'bg-blue-100 text-blue-700',
    partial: 'bg-orange-100 text-orange-700',
}

const filteredTimeline = computed(() => {
    let items = props.timeline || []
    if (typeFilter.value) {
        items = items.filter(e => e.type === typeFilter.value)
    }
    if (searchQuery.value) {
        const q = searchQuery.value.toLowerCase()
        items = items.filter(e =>
            (e.title_en || '').toLowerCase().includes(q) ||
            (e.title_ar || '').toLowerCase().includes(q) ||
            (e.subtitle || '').toLowerCase().includes(q) ||
            (e.details || '').toLowerCase().includes(q)
        )
    }
    return items
})

// Group by month
const groupedTimeline = computed(() => {
    const groups = {}
    filteredTimeline.value.forEach(event => {
        const d = new Date(event.date)
        const key = `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}`
        const label = d.toLocaleDateString(isRtl.value ? 'ar-EG' : 'en-US', { year: 'numeric', month: 'long' })
        if (!groups[key]) groups[key] = { label, items: [] }
        groups[key].items.push(event)
    })
    return Object.values(groups)
})

const typeCounts = computed(() => {
    const counts = {}
    ;(props.timeline || []).forEach(e => {
        counts[e.type] = (counts[e.type] || 0) + 1
    })
    return counts
})

function formatDate(d) {
    if (!d) return '-'
    return new Date(d).toLocaleDateString(isRtl.value ? 'ar-EG' : 'en-GB', { day: 'numeric', month: 'short', year: 'numeric' })
}
</script>

<template>
    <div class="p-6 max-w-4xl mx-auto">
        <!-- Header -->
        <div class="flex items-center gap-3 mb-6">
            <Link :href="`/admin/patients/${patient.id}`" class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-xl transition">
                <svg class="w-5 h-5" :class="isRtl ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            </Link>
            <div class="flex-1">
                <h1 class="text-2xl font-bold text-gray-800">{{ isRtl ? 'السجل الزمني الطبي' : 'Medical Timeline' }}</h1>
                <p class="text-gray-500 text-sm mt-0.5">
                    {{ patient.full_name }}
                    <span v-if="patient.file_number" class="font-mono text-gray-400 ms-2">#{{ patient.file_number }}</span>
                </p>
            </div>
            <div class="text-end">
                <p class="text-2xl font-bold text-gray-800">{{ (timeline || []).length }}</p>
                <p class="text-xs text-gray-400">{{ isRtl ? 'حدث طبي' : 'Events' }}</p>
            </div>
        </div>

        <!-- Type Filter Chips -->
        <div class="flex flex-wrap gap-2 mb-4">
            <button @click="typeFilter = ''" class="px-3 py-1.5 text-xs font-medium rounded-full transition" :class="!typeFilter ? 'bg-gray-800 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'">
                {{ isRtl ? 'الكل' : 'All' }} ({{ (timeline || []).length }})
            </button>
            <button v-for="(cfg, type) in typeConfig" :key="type" v-show="typeCounts[type]" @click="typeFilter = typeFilter === type ? '' : type"
                class="px-3 py-1.5 text-xs font-medium rounded-full transition inline-flex items-center gap-1.5"
                :class="typeFilter === type ? cfg.color + ' text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'">
                <span class="w-1.5 h-1.5 rounded-full" :class="typeFilter === type ? 'bg-white/60' : cfg.color"></span>
                {{ isRtl ? cfg.label_ar : cfg.label_en }} ({{ typeCounts[type] || 0 }})
            </button>
        </div>

        <!-- Search -->
        <div class="relative mb-6">
            <input v-model="searchQuery" type="text" :placeholder="isRtl ? 'بحث في السجل...' : 'Search timeline...'" class="w-full px-4 py-2.5 ps-10 border border-gray-200 rounded-xl text-sm focus:ring-cyan-500 focus:border-cyan-500" />
            <svg class="w-4 h-4 text-gray-400 absolute top-3 start-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
        </div>

        <!-- Timeline -->
        <div v-if="groupedTimeline.length > 0" class="space-y-8">
            <div v-for="group in groupedTimeline" :key="group.label">
                <!-- Month Header -->
                <div class="sticky top-0 z-10 bg-gray-50/90 backdrop-blur-sm -mx-2 px-2 py-2 rounded-lg mb-4">
                    <h2 class="text-sm font-bold text-gray-700">{{ group.label }}</h2>
                </div>

                <!-- Events -->
                <div class="relative">
                    <!-- Vertical line -->
                    <div class="absolute start-5 top-0 bottom-0 w-0.5 bg-gray-100"></div>

                    <div v-for="event in group.items" :key="event.type + '-' + event.id" class="relative flex gap-4 mb-4 last:mb-0">
                        <!-- Icon -->
                        <div class="relative z-10 w-10 h-10 rounded-xl flex items-center justify-center shrink-0 ring-4"
                            :class="[typeConfig[event.type]?.color || 'bg-gray-500', typeConfig[event.type]?.ring || 'ring-gray-100']">
                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="typeConfig[event.type]?.icon || 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z'"/></svg>
                        </div>

                        <!-- Content Card -->
                        <component :is="event.url ? 'a' : 'div'" :href="event.url || undefined"
                            class="flex-1 bg-white rounded-xl border border-gray-100 p-4 hover:border-gray-200 hover:shadow-sm transition group">
                            <div class="flex items-start justify-between gap-2">
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-center gap-2 mb-1">
                                        <h3 class="text-sm font-semibold text-gray-800 truncate">
                                            {{ isRtl ? event.title_ar : event.title_en }}
                                        </h3>
                                        <span v-if="event.status" class="px-2 py-0.5 rounded-full text-[10px] font-medium shrink-0" :class="statusColors[event.status] || 'bg-gray-100 text-gray-600'">
                                            {{ event.status }}
                                        </span>
                                    </div>
                                    <p v-if="event.subtitle" class="text-xs text-gray-500 mb-1">{{ event.subtitle }}</p>
                                    <p v-if="event.details" class="text-xs text-gray-400 line-clamp-2">{{ event.details }}</p>
                                </div>
                                <div class="text-end shrink-0">
                                    <p class="text-xs text-gray-500">{{ formatDate(event.date) }}</p>
                                    <p class="text-[10px] text-gray-400">{{ event.time }}</p>
                                </div>
                            </div>
                            <div v-if="event.url" class="flex items-center gap-1 mt-2 text-[10px] text-cyan-600 opacity-0 group-hover:opacity-100 transition">
                                <span>{{ isRtl ? 'عرض التفاصيل' : 'View details' }}</span>
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                            </div>
                        </component>
                    </div>
                </div>
            </div>
        </div>

        <!-- Empty State -->
        <div v-else class="text-center py-16">
            <svg class="w-16 h-16 text-gray-200 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <p class="text-gray-400">{{ isRtl ? 'لا توجد أحداث' : 'No events found' }}</p>
        </div>
    </div>
</template>
