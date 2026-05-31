<script setup>
import { ref, computed } from 'vue';
import { usePage, router } from '@inertiajs/vue3';

const page = usePage();
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');
const t = (ar, en) => (isRtl.value ? ar : en);

const branch = computed(() => page.props.branch);
const open = ref(false);

// Hide entirely when there's nothing to switch (single branch, no all-view).
const visible = computed(() => branch.value && (branch.value.list.length > 1 || branch.value.can_all));
const currentLabel = computed(() => {
    if (!branch.value) return '';
    if (branch.value.is_all) return t('كل الفروع', 'All branches');
    const b = branch.value.list.find((x) => x.id === branch.value.current);
    return b ? b.name : t('الفرع', 'Branch');
});

function choose(value) {
    open.value = false;
    router.post('/admin/switch-branch', { branch: String(value) }, { preserveScroll: false });
}
</script>

<template>
    <div v-if="visible" class="relative">
        <button @click="open = !open" class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg text-sm font-semibold border transition text-[#1B365D] border-[#1B365D]/25 hover:bg-[#1B365D]/5">
            <svg class="w-4 h-4 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0H5m14 0h2M5 21H3m4-14h.01M11 7h.01M7 11h.01M11 11h.01M7 15h.01M11 15h.01" /></svg>
            <span class="max-w-[120px] truncate">{{ currentLabel }}</span>
            <svg class="w-3 h-3 opacity-70" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
        </button>

        <transition enter-active-class="transition duration-150" enter-from-class="opacity-0 -translate-y-1" leave-active-class="transition duration-100" leave-to-class="opacity-0">
            <div v-if="open" class="absolute z-50 mt-2 w-56 rounded-xl bg-white shadow-2xl border border-gray-100 overflow-hidden py-1" :class="isRtl ? 'start-0' : 'end-0'">
                <button v-if="branch.can_all" @click="choose('all')"
                        class="w-full text-start px-4 py-2 text-sm hover:bg-gray-50 flex items-center justify-between"
                        :class="branch.is_all ? 'text-[#1B365D] font-bold' : 'text-gray-700'">
                    {{ t('كل الفروع', 'All branches') }}
                    <span v-if="branch.is_all">✓</span>
                </button>
                <div v-if="branch.can_all" class="h-px bg-gray-100 my-1"></div>
                <button v-for="b in branch.list" :key="b.id" @click="choose(b.id)"
                        class="w-full text-start px-4 py-2 text-sm hover:bg-gray-50 flex items-center justify-between"
                        :class="(!branch.is_all && branch.current === b.id) ? 'text-[#1B365D] font-bold' : 'text-gray-700'">
                    {{ b.name }}
                    <span v-if="!branch.is_all && branch.current === b.id">✓</span>
                </button>
            </div>
        </transition>
        <div v-if="open" class="fixed inset-0 z-40" @click="open = false"></div>
    </div>
</template>
