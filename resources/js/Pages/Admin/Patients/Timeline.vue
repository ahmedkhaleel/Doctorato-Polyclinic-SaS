<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { ref, computed, onMounted } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'

defineOptions({ layout: AdminLayout })

const props = defineProps({ patient: Object, timeline: Array })

const page = usePage()
const locale = computed(() => page.props.locale || 'ar')
const isRtl = computed(() => locale.value === 'ar')

const typeFilter = ref('')
const searchQuery = ref('')
const headerLoaded = ref(false)
const itemsLoaded = ref(false)

onMounted(() => {
    setTimeout(() => headerLoaded.value = true, 50)
    setTimeout(() => itemsLoaded.value = true, 200)
})

const typeConfig = {
    visit: { icon: 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z', color: 'from-blue-500 to-blue-600', bg: 'bg-blue-500', ring: 'ring-blue-100', badge: 'bg-blue-50 text-blue-700 border-blue-200', label_en: 'Visit', label_ar: 'زيارة' },
    invoice: { icon: 'M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z', color: 'from-emerald-500 to-emerald-600', bg: 'bg-emerald-500', ring: 'ring-emerald-100', badge: 'bg-emerald-50 text-emerald-700 border-emerald-200', label_en: 'Invoice', label_ar: 'فاتورة' },
    prescription: { icon: 'M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z', color: 'from-rose-500 to-rose-600', bg: 'bg-rose-500', ring: 'ring-rose-100', badge: 'bg-rose-50 text-rose-700 border-rose-200', label_en: 'Prescription', label_ar: 'وصفة' },
    vital: { icon: 'M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z', color: 'from-red-500 to-red-600', bg: 'bg-red-500', ring: 'ring-red-100', badge: 'bg-red-50 text-red-700 border-red-200', label_en: 'Vitals', label_ar: 'علامات حيوية' },
    document: { icon: 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z', color: 'from-purple-500 to-purple-600', bg: 'bg-purple-500', ring: 'ring-purple-100', badge: 'bg-purple-50 text-purple-700 border-purple-200', label_en: 'Document', label_ar: 'مستند' },
    referral: { icon: 'M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4', color: 'from-indigo-500 to-indigo-600', bg: 'bg-indigo-500', ring: 'ring-indigo-100', badge: 'bg-indigo-50 text-indigo-700 border-indigo-200', label_en: 'Referral', label_ar: 'تحويل' },
}

const statusColors = {
    completed: 'bg-emerald-50 text-emerald-700 border-emerald-200',
    paid: 'bg-emerald-50 text-emerald-700 border-emerald-200',
    active: 'bg-blue-50 text-blue-700 border-blue-200',
    in_progress: 'bg-blue-50 text-blue-700 border-blue-200',
    waiting: 'bg-amber-50 text-amber-700 border-amber-200',
    pending: 'bg-amber-50 text-amber-700 border-amber-200',
    unpaid: 'bg-red-50 text-red-700 border-red-200',
    cancelled: 'bg-gray-100 text-gray-500 border-gray-200',
    declined: 'bg-red-50 text-red-700 border-red-200',
    expired: 'bg-red-50 text-red-700 border-red-200',
    recorded: 'bg-cyan-50 text-cyan-700 border-cyan-200',
    issued: 'bg-blue-50 text-blue-700 border-blue-200',
    partial: 'bg-orange-50 text-orange-700 border-orange-200',
}

const filteredTimeline = computed(() => {
    let items = props.timeline || []
    if (typeFilter.value) items = items.filter(e => e.type === typeFilter.value)
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
    ;(props.timeline || []).forEach(e => { counts[e.type] = (counts[e.type] || 0) + 1 })
    return counts
})

function formatDate(d) {
    if (!d) return '-'
    return new Date(d).toLocaleDateString(isRtl.value ? 'ar-EG' : 'en-GB', { day: 'numeric', month: 'short', year: 'numeric' })
}

function formatDay(d) {
    if (!d) return '-'
    return new Date(d).getDate()
}

function formatMonth(d) {
    if (!d) return ''
    return new Date(d).toLocaleDateString('en', { month: 'short' })
}
</script>

<template>
    <div class="max-w-5xl mx-auto p-6">
        <!-- ═══════ Hero Header ═══════ -->
        <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-gray-900 via-gray-800 to-gray-900 shadow-xl mb-8 transition-all duration-700"
            :class="headerLoaded ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'">
            <div class="absolute inset-0 opacity-10">
                <div class="absolute -top-16 -end-16 w-48 h-48 rounded-full" style="background: radial-gradient(circle, #C4A265, transparent 70%);"></div>
            </div>
            <div class="relative p-6 sm:p-8">
                <div class="flex items-center gap-4">
                    <Link :href="`/admin/patients/${patient.id}`"
                        class="w-10 h-10 rounded-xl bg-white/10 hover:bg-white/20 flex items-center justify-center text-white/70 hover:text-white transition-all duration-200">
                        <svg class="w-5 h-5" :class="isRtl ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                    </Link>
                    <div class="flex-1">
                        <h1 class="text-2xl sm:text-3xl font-bold text-white tracking-tight">{{ isRtl ? 'السجل الزمني الطبي' : 'Medical Timeline' }}</h1>
                        <div class="flex items-center gap-3 mt-1.5 text-sm text-white/50">
                            <span class="font-semibold text-white/70">{{ patient.full_name }}</span>
                            <span v-if="patient.file_number" class="font-mono text-amber-400/60">#{{ patient.file_number }}</span>
                        </div>
                    </div>
                    <div class="text-end">
                        <div class="inline-flex items-center gap-2 bg-white/10 backdrop-blur-sm rounded-xl px-5 py-3 border border-white/10">
                            <svg class="w-5 h-5 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            <div>
                                <p class="text-2xl font-bold text-white leading-none">{{ (timeline || []).length }}</p>
                                <p class="text-[10px] text-white/40 font-medium">{{ isRtl ? 'حدث' : 'Events' }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ═══════ Filters Bar ═══════ -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 mb-6 transition-all duration-500"
            :class="headerLoaded ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'">
            <!-- Search -->
            <div class="relative mb-3">
                <svg class="w-4 h-4 text-gray-400 absolute top-3 start-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <input v-model="searchQuery" type="text" :placeholder="isRtl ? 'بحث في السجل الزمني...' : 'Search timeline...'"
                    class="w-full ps-11 pe-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-amber-200 focus:border-transparent transition-all" />
            </div>
            <!-- Type Chips -->
            <div class="flex flex-wrap gap-2">
                <button @click="typeFilter = ''"
                    class="px-3.5 py-1.5 text-xs font-semibold rounded-xl transition-all duration-200 border"
                    :class="!typeFilter ? 'bg-gray-900 text-white border-gray-900 shadow-sm' : 'bg-white text-gray-600 border-gray-200 hover:border-gray-300 hover:bg-gray-50'">
                    {{ isRtl ? 'الكل' : 'All' }}
                    <span class="ms-1 opacity-60">{{ (timeline || []).length }}</span>
                </button>
                <button v-for="(cfg, type) in typeConfig" :key="type" v-show="typeCounts[type]"
                    @click="typeFilter = typeFilter === type ? '' : type"
                    class="px-3.5 py-1.5 text-xs font-semibold rounded-xl transition-all duration-200 inline-flex items-center gap-1.5 border"
                    :class="typeFilter === type ? cfg.bg + ' text-white border-transparent shadow-sm' : 'bg-white text-gray-600 border-gray-200 hover:border-gray-300 hover:bg-gray-50'">
                    <span class="w-1.5 h-1.5 rounded-full" :class="typeFilter === type ? 'bg-white/60' : cfg.bg"></span>
                    {{ isRtl ? cfg.label_ar : cfg.label_en }}
                    <span class="opacity-60">{{ typeCounts[type] || 0 }}</span>
                </button>
            </div>
        </div>

        <!-- ═══════ Timeline Content ═══════ -->
        <div v-if="groupedTimeline.length > 0" class="space-y-10 transition-all duration-700"
            :class="itemsLoaded ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-6'">
            <div v-for="(group, gIdx) in groupedTimeline" :key="group.label">
                <!-- Month Header -->
                <div class="flex items-center gap-3 mb-5">
                    <div class="h-px flex-1 bg-gradient-to-e from-gray-200 to-transparent"></div>
                    <h2 class="text-sm font-bold text-gray-700 bg-white px-4 py-1.5 rounded-full border border-gray-100 shadow-sm">
                        {{ group.label }}
                    </h2>
                    <div class="h-px flex-1 bg-gradient-to-s from-gray-200 to-transparent"></div>
                </div>

                <!-- Events -->
                <div class="relative ms-6">
                    <!-- Vertical line -->
                    <div class="absolute start-0 top-0 bottom-0 w-0.5 rounded-full bg-gradient-to-b from-gray-200 via-gray-200 to-transparent"></div>

                    <div v-for="(event, eIdx) in group.items" :key="event.type + '-' + event.id"
                        class="relative flex gap-5 mb-5 last:mb-0 group">
                        <!-- Dot + Connector -->
                        <div class="absolute -start-[5px] top-5 z-10">
                            <div class="w-3 h-3 rounded-full border-2 border-white shadow-sm transition-transform duration-200 group-hover:scale-125"
                                :class="typeConfig[event.type]?.bg || 'bg-gray-400'"></div>
                        </div>

                        <!-- Content Card -->
                        <component :is="event.url ? 'a' : 'div'" :href="event.url || undefined"
                            class="ms-5 flex-1 bg-white rounded-2xl border border-gray-100 overflow-hidden hover:border-gray-200 hover:shadow-lg transition-all duration-300 group/card">
                            <div class="flex items-start gap-4 p-5">
                                <!-- Icon -->
                                <div class="flex-shrink-0 w-11 h-11 rounded-xl bg-gradient-to-br flex items-center justify-center shadow-sm"
                                    :class="typeConfig[event.type]?.color || 'from-gray-400 to-gray-500'">
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="typeConfig[event.type]?.icon || 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z'"/></svg>
                                </div>

                                <!-- Text -->
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2 mb-1 flex-wrap">
                                        <h3 class="text-sm font-bold text-gray-800">{{ isRtl ? event.title_ar : event.title_en }}</h3>
                                        <span v-if="event.status" class="inline-flex px-2 py-0.5 rounded-full text-[10px] font-semibold border capitalize"
                                            :class="statusColors[event.status] || 'bg-gray-100 text-gray-600 border-gray-200'">
                                            {{ event.status }}
                                        </span>
                                    </div>
                                    <p v-if="event.subtitle" class="text-xs text-gray-500 mb-0.5">{{ event.subtitle }}</p>
                                    <p v-if="event.details" class="text-xs text-gray-400 line-clamp-2 leading-relaxed">{{ event.details }}</p>
                                </div>

                                <!-- Date -->
                                <div class="flex-shrink-0 text-end">
                                    <div class="w-12 h-12 rounded-xl bg-gray-50 border border-gray-100 flex flex-col items-center justify-center">
                                        <span class="text-sm font-bold text-gray-800 leading-none">{{ formatDay(event.date) }}</span>
                                        <span class="text-[9px] font-semibold text-gray-400 uppercase">{{ formatMonth(event.date) }}</span>
                                    </div>
                                    <p v-if="event.time" class="text-[10px] text-gray-400 mt-1.5 font-medium">{{ event.time }}</p>
                                </div>
                            </div>

                            <!-- View Link -->
                            <div v-if="event.url" class="px-5 py-2.5 bg-gray-50/50 border-t border-gray-100 flex items-center gap-1 text-[11px] font-semibold opacity-0 group-hover/card:opacity-100 transition-opacity duration-200" style="color: #C4A265;">
                                <span>{{ isRtl ? 'عرض التفاصيل' : 'View details' }}</span>
                                <svg class="w-3 h-3 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                            </div>
                        </component>
                    </div>
                </div>
            </div>
        </div>

        <!-- ═══════ Empty State ═══════ -->
        <div v-else class="text-center py-20 transition-all duration-500" :class="itemsLoaded ? 'opacity-100' : 'opacity-0'">
            <div class="w-24 h-24 mx-auto mb-6 rounded-3xl bg-gray-50 flex items-center justify-center">
                <svg class="w-12 h-12 text-gray-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <h3 class="text-lg font-bold text-gray-400 mb-1">{{ isRtl ? 'لا توجد أحداث' : 'No events found' }}</h3>
            <p class="text-sm text-gray-300">{{ isRtl ? 'جرب تغيير معايير البحث' : 'Try adjusting your filters' }}</p>
        </div>
    </div>
</template>
