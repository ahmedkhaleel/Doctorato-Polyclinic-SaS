<script setup>
import { ref, computed, watch, onMounted, onUnmounted, nextTick } from 'vue'
import { router, usePage } from '@inertiajs/vue3'

const page = usePage()
const locale = computed(() => page.props.locale || 'ar')
const isRtl = computed(() => locale.value === 'ar')

const isOpen = ref(false)
const query = ref('')
const results = ref([])
const loading = ref(false)
const selectedIndex = ref(0)
const searchInput = ref(null)

let searchTimeout = null

const typeConfig = {
    patient: { icon: 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z', color: 'text-[#1B365D] bg-slate-50', label_ar: 'مريض', label_en: 'Patient' },
    invoice: { icon: 'M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z', color: 'text-emerald-600 bg-emerald-50', label_ar: 'فاتورة', label_en: 'Invoice' },
    visit: { icon: 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z', color: 'text-[#1B365D] bg-slate-50', label_ar: 'زيارة', label_en: 'Visit' },
    supply: { icon: 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4', color: 'text-[#C4A265] bg-amber-50', label_ar: 'مخزون', label_en: 'Supply' },
}

// Quick actions when no search query
const quickActions = [
    { title_ar: 'مريض جديد', title_en: 'New Patient', url: '/admin/patients/create', icon: 'M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z', color: 'text-[#1B365D]' },
    { title_ar: 'فاتورة جديدة', title_en: 'New Invoice', url: '/admin/invoices/create', icon: 'M12 4v16m8-8H4', color: 'text-emerald-600' },
    { title_ar: 'حجز جديد', title_en: 'New Booking', url: '/admin/bookings/create', icon: 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z', color: 'text-[#1B365D]' },
    { title_ar: 'أمر شراء جديد', title_en: 'New Purchase Order', url: '/admin/purchase-orders/create', icon: 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2', color: 'text-[#1B365D]' },
]

// Grouped results by type
const groupedResults = computed(() => {
    const groups = {}
    results.value.forEach(r => {
        if (!groups[r.type]) groups[r.type] = []
        groups[r.type].push(r)
    })
    return groups
})

const flatResults = computed(() => {
    if (query.value.length < 2) return quickActions.map((a, i) => ({ ...a, _index: i, _isAction: true }))
    return results.value.map((r, i) => ({ ...r, _index: i }))
})

function toggle() {
    isOpen.value = !isOpen.value
    if (isOpen.value) {
        query.value = ''
        results.value = []
        selectedIndex.value = 0
        nextTick(() => searchInput.value?.focus())
    }
}

function close() {
    isOpen.value = false
}

function search() {
    clearTimeout(searchTimeout)
    if (query.value.length < 2) {
        results.value = []
        selectedIndex.value = 0
        return
    }
    loading.value = true
    searchTimeout = setTimeout(async () => {
        try {
            const res = await fetch(`/admin/api/global-search?q=${encodeURIComponent(query.value)}`, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            })
            if (res.ok) {
                const data = await res.json()
                results.value = data.results || []
                selectedIndex.value = 0
            }
        } catch (e) {
            console.error('Search failed:', e)
        } finally {
            loading.value = false
        }
    }, 250)
}

watch(query, search)

function navigate(item) {
    const url = item.url || item._url
    if (url) {
        router.visit(url)
        close()
    }
}

function onKeydown(e) {
    if (e.key === 'ArrowDown') {
        e.preventDefault()
        selectedIndex.value = Math.min(selectedIndex.value + 1, flatResults.value.length - 1)
    } else if (e.key === 'ArrowUp') {
        e.preventDefault()
        selectedIndex.value = Math.max(selectedIndex.value - 1, 0)
    } else if (e.key === 'Enter') {
        e.preventDefault()
        const selected = flatResults.value[selectedIndex.value]
        if (selected) navigate(selected)
    } else if (e.key === 'Escape') {
        close()
    }
}

function onGlobalKeydown(e) {
    if ((e.metaKey || e.ctrlKey) && e.key === 'k') {
        e.preventDefault()
        toggle()
    }
}

onMounted(() => { document.addEventListener('keydown', onGlobalKeydown) })
onUnmounted(() => { document.removeEventListener('keydown', onGlobalKeydown) })

defineExpose({ toggle })
</script>

<template>
    <Teleport to="body">
        <Transition enter-active-class="transition duration-200" enter-from-class="opacity-0" enter-to-class="opacity-100" leave-active-class="transition duration-150" leave-from-class="opacity-100" leave-to-class="opacity-0">
            <div v-if="isOpen" class="fixed inset-0 z-[9999] flex items-start justify-center pt-[15vh] p-4">
                <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" @click="close" />

                <Transition enter-active-class="transition duration-200" enter-from-class="opacity-0 scale-95 translate-y-4" enter-to-class="opacity-100 scale-100 translate-y-0" leave-active-class="transition duration-150" leave-from-class="opacity-100 scale-100" leave-to-class="opacity-0 scale-95 translate-y-4" appear>
                    <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-xl overflow-hidden border border-gray-200">
                        <!-- Search Input -->
                        <div class="flex items-center gap-3 px-5 py-4 border-b border-gray-100">
                            <svg class="w-5 h-5 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                            <input ref="searchInput" v-model="query" @keydown="onKeydown" type="text"
                                :placeholder="isRtl ? 'ابحث في المرضى، الفواتير، المخزون...' : 'Search patients, invoices, supplies...'"
                                class="doctorato-input flex-1 text-sm border-0 outline-none focus:ring-0 placeholder-gray-400 bg-transparent" />
                            <div v-if="loading" class="w-4 h-4 border-2 border-[#1B365D] border-t-transparent rounded-full animate-spin"></div>
                            <kbd class="hidden sm:flex items-center gap-0.5 text-[10px] text-gray-400 bg-gray-100 px-1.5 py-0.5 rounded font-mono">ESC</kbd>
                        </div>

                        <!-- Results -->
                        <div class="max-h-80 overflow-y-auto py-2">
                            <!-- Quick Actions (when no query) -->
                            <div v-if="query.length < 2">
                                <p class="px-5 py-2 text-[10px] font-semibold text-gray-400 uppercase">{{ isRtl ? 'إجراءات سريعة' : 'Quick Actions' }}</p>
                                <button v-for="(action, idx) in quickActions" :key="idx"
                                    @click="navigate(action)" @mouseenter="selectedIndex = idx"
                                    class="w-full flex items-center gap-3 px-5 py-2.5 text-start transition-colors"
                                    :class="selectedIndex === idx ? 'bg-slate-50' : 'hover:bg-gray-50'">
                                    <div class="w-8 h-8 rounded-lg bg-gray-50 flex items-center justify-center shrink-0" :class="action.color">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="action.icon"/></svg>
                                    </div>
                                    <span class="text-sm text-gray-700">{{ isRtl ? action.title_ar : action.title_en }}</span>
                                </button>
                            </div>

                            <!-- Search Results -->
                            <div v-else-if="results.length > 0">
                                <template v-for="(items, type) in groupedResults" :key="type">
                                    <p class="px-5 py-2 text-[10px] font-semibold text-gray-400 uppercase">
                                        {{ isRtl ? typeConfig[type]?.label_ar : typeConfig[type]?.label_en }}
                                    </p>
                                    <button v-for="item in items" :key="item.id + item.type"
                                        @click="navigate(item)"
                                        @mouseenter="selectedIndex = flatResults.findIndex(r => r.id === item.id && r.type === item.type)"
                                        class="w-full flex items-center gap-3 px-5 py-2.5 text-start transition-colors"
                                        :class="flatResults[selectedIndex]?.id === item.id && flatResults[selectedIndex]?.type === item.type ? 'bg-slate-50' : 'hover:bg-gray-50'">
                                        <div class="w-8 h-8 rounded-lg flex items-center justify-center shrink-0" :class="typeConfig[type]?.color">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="typeConfig[type]?.icon"/></svg>
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <p class="text-sm font-medium text-gray-800 truncate">{{ item.title }}</p>
                                            <p class="text-xs text-gray-400 truncate">{{ item.subtitle }}</p>
                                        </div>
                                        <svg class="w-4 h-4 text-gray-300 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                    </button>
                                </template>
                            </div>

                            <!-- No results -->
                            <div v-else-if="query.length >= 2 && !loading" class="px-5 py-8 text-center">
                                <svg class="w-10 h-10 text-gray-200 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                                <p class="text-sm text-gray-400">{{ isRtl ? 'لا توجد نتائج' : 'No results found' }}</p>
                            </div>
                        </div>

                        <!-- Footer -->
                        <div class="px-5 py-2.5 bg-gray-50 border-t border-gray-100 flex items-center justify-between">
                            <div class="flex items-center gap-3 text-[10px] text-gray-400">
                                <span class="flex items-center gap-1"><kbd class="bg-white border px-1 py-0.5 rounded font-mono text-[9px]">&uarr;&darr;</kbd> {{ isRtl ? 'تنقل' : 'Navigate' }}</span>
                                <span class="flex items-center gap-1"><kbd class="bg-white border px-1 py-0.5 rounded font-mono text-[9px]">&crarr;</kbd> {{ isRtl ? 'فتح' : 'Open' }}</span>
                                <span class="flex items-center gap-1"><kbd class="bg-white border px-1 py-0.5 rounded font-mono text-[9px]">esc</kbd> {{ isRtl ? 'إغلاق' : 'Close' }}</span>
                            </div>
                            <span class="text-[10px] text-gray-400" v-if="results.length > 0">{{ results.length }} {{ isRtl ? 'نتيجة' : 'results' }}</span>
                        </div>
                    </div>
                </Transition>
            </div>
        </Transition>
    </Teleport>
</template>
