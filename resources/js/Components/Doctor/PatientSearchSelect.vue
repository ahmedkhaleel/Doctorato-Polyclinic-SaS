<script setup>
import { ref, watch, computed, onMounted, onUnmounted } from 'vue';
import { usePage } from '@inertiajs/vue3';

const page = usePage();
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const props = defineProps({
    modelValue: { type: [Number, String], default: '' },
    // If an initial patient object is provided (for edit forms), show it immediately
    initialPatient: { type: Object, default: null },
    searchUrl: { type: String, default: '/doctor/api/patients' },
    placeholder: { type: String, default: '' },
    required: { type: Boolean, default: false },
    accentColor: { type: String, default: '#C4A265' },
});

const accent = computed(() => props.accentColor);

const emit = defineEmits(['update:modelValue', 'patient-selected']);

const query = ref('');
const results = ref([]);
const isOpen = ref(false);
const isLoading = ref(false);
const selectedPatient = ref(props.initialPatient || null);
const wrapperRef = ref(null);
let debounceTimer = null;
let abortController = null;

const placeholderText = computed(() => props.placeholder || (isRtl.value ? 'ابحث بالاسم أو الهاتف أو رقم الملف...' : 'Search by name, phone, or file number...'));

function search(q) {
    if (q.length < 2) {
        results.value = [];
        isOpen.value = false;
        return;
    }

    isLoading.value = true;
    if (abortController) abortController.abort();
    abortController = new AbortController();

    fetch(`${props.searchUrl}?q=${encodeURIComponent(q)}`, {
        signal: abortController.signal,
        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        credentials: 'same-origin',
    })
        .then(res => res.json())
        .then(data => {
            results.value = data;
            isOpen.value = true;
            isLoading.value = false;
        })
        .catch(err => {
            if (err.name !== 'AbortError') isLoading.value = false;
        });
}

watch(query, (val) => {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => search(val), 300);
});

function selectPatient(patient) {
    selectedPatient.value = patient;
    emit('update:modelValue', patient.id);
    emit('patient-selected', patient);
    isOpen.value = false;
    query.value = '';
    results.value = [];
}

function clearSelection() {
    selectedPatient.value = null;
    emit('update:modelValue', '');
    emit('patient-selected', null);
    query.value = '';
}

function handleClickOutside(e) {
    if (wrapperRef.value && !wrapperRef.value.contains(e.target)) {
        isOpen.value = false;
    }
}

onMounted(() => document.addEventListener('click', handleClickOutside));
onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside);
    clearTimeout(debounceTimer);
    if (abortController) abortController.abort();
});
</script>

<template>
    <div ref="wrapperRef" class="relative">
        <!-- Selected patient chip -->
        <div v-if="selectedPatient" class="flex items-center gap-2 px-3.5 py-2.5 border rounded-xl"
            :style="{ borderColor: accent + '66', backgroundColor: accent + '0D' }">
            <div class="flex-1 min-w-0">
                <span class="text-sm font-semibold text-gray-800">{{ selectedPatient.full_name }}</span>
                <span v-if="selectedPatient.file_number" class="text-xs font-mono ms-2" :style="{ color: accent }">#{{ selectedPatient.file_number }}</span>
                <span v-if="selectedPatient.phone" class="text-xs text-gray-400 ms-2">{{ selectedPatient.phone }}</span>
            </div>
            <button type="button" @click="clearSelection" class="p-1 rounded-full hover:bg-red-100 text-gray-400 hover:text-red-500 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
            </button>
        </div>

        <!-- Search input -->
        <div v-else class="relative">
            <input
                v-model="query"
                type="text"
                :placeholder="placeholderText"
                :required="required && !selectedPatient"
                class="w-full px-3.5 py-2.5 ps-10 border border-gray-200 rounded-xl text-sm focus:ring-2 bg-white"
                :style="{ '--tw-ring-color': accent + '4D', '--tw-ring-offset-color': accent }"
                @focus="query.length >= 2 && (isOpen = true)"
            />
            <svg class="w-4 h-4 text-gray-400 absolute start-3.5 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
            <svg v-if="isLoading" class="w-4 h-4 absolute end-3.5 top-1/2 -translate-y-1/2 animate-spin" :style="{ color: accent }" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" /><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" /></svg>
        </div>

        <!-- Dropdown results -->
        <Transition enter-active-class="transition ease-out duration-150" enter-from-class="opacity-0 translate-y-1" enter-to-class="opacity-100 translate-y-0" leave-active-class="transition ease-in duration-100" leave-from-class="opacity-100" leave-to-class="opacity-0 translate-y-1">
            <ul v-if="isOpen && results.length > 0" class="absolute z-50 w-full mt-1 bg-white border border-gray-200 rounded-xl shadow-lg max-h-60 overflow-y-auto">
                <li v-for="p in results" :key="p.id" @click="selectPatient(p)" class="flex items-center gap-3 px-4 py-2.5 cursor-pointer transition group"
                    :class="'hover:bg-gray-50'">
                    <div class="w-8 h-8 rounded-full flex items-center justify-center flex-shrink-0"
                        :style="{ background: `linear-gradient(135deg, ${accent}33, ${accent}0D)` }">
                        <span class="text-xs font-bold" :style="{ color: accent }">{{ p.full_name?.charAt(0) }}</span>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="text-sm font-semibold text-gray-800 truncate transition-colors" :style="{ '--hover-color': accent }">{{ p.full_name }}</div>
                        <div class="text-xs text-gray-400">
                            <span v-if="p.file_number" class="font-mono">#{{ p.file_number }}</span>
                            <span v-if="p.phone" class="ms-2">{{ p.phone }}</span>
                        </div>
                    </div>
                    <span v-if="p.medical_notes" class="w-2 h-2 rounded-full bg-red-400 flex-shrink-0" :title="isRtl ? 'يوجد ملاحظات طبية' : 'Has medical notes'"></span>
                </li>
            </ul>
        </Transition>

        <!-- No results message -->
        <div v-if="isOpen && query.length >= 2 && results.length === 0 && !isLoading" class="absolute z-50 w-full mt-1 bg-white border border-gray-200 rounded-xl shadow-lg px-4 py-3 text-sm text-gray-400 text-center">
            {{ isRtl ? 'لا توجد نتائج' : 'No results found' }}
        </div>
    </div>
</template>
