<script setup>
import { ref, watch, computed, onMounted } from 'vue';
import { Link, router, useForm, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { usePermissions } from '@/Composables/usePermissions.js';
import { useConfirm } from '@/Composables/useConfirm.js';

defineOptions({ layout: AdminLayout });

const { can } = usePermissions();
const { confirm } = useConfirm();
const page = usePage();
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const ACCENT = '#6366F1';

const props = defineProps({
    medications: Object,
    filters: Object,
});

/* ── Filters ──────────────────────────────────────────── */
const search = ref(props.filters?.search || '');
let searchTimeout = null;

watch(search, () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        router.get('/admin/medications', { search: search.value || undefined }, { preserveState: true, replace: true });
    }, 400);
});

/* ── Modal ────────────────────────────────────────────── */
const showModal = ref(false);
const editingMedication = ref(null);

const form = useForm({
    name: '',
    category: '',
    default_dosage: '',
    default_frequency: '',
    default_duration: '',
    is_active: true,
});

function openCreate() {
    editingMedication.value = null;
    form.reset();
    form.clearErrors();
    showModal.value = true;
}

function openEdit(med) {
    editingMedication.value = med;
    form.name = med.name || '';
    form.category = med.category || '';
    form.default_dosage = med.default_dosage || '';
    form.default_frequency = med.default_frequency || '';
    form.default_duration = med.default_duration || '';
    form.is_active = med.is_active ?? true;
    form.clearErrors();
    showModal.value = true;
}

function closeModal() {
    showModal.value = false;
    editingMedication.value = null;
    form.reset();
}

function submit() {
    if (editingMedication.value) {
        form.post(`/admin/medications/${editingMedication.value.id}/update`, { onSuccess: () => closeModal() });
    } else {
        form.post('/admin/medications', { onSuccess: () => closeModal() });
    }
}

function deleteMedication(med) {
    const msg = isRtl.value ? 'هل أنت متأكد من حذف هذا الدواء؟' : 'Are you sure you want to delete this medication?';
    confirm(msg, () => {
        router.post(`/admin/medications/${med.id}/delete`);
    });
}

/* ── Animation ────────────────────────────────────────── */
const mounted = ref(false);
onMounted(() => { requestAnimationFrame(() => { mounted.value = true; }); });
</script>

<template>
    <div class="space-y-6">
        <!-- Hero Header -->
        <div
            class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-[#1B365D] via-[#1B365D] to-[#1B365D] p-4 md:p-6 sm:p-8 transition-all duration-700"
            :style="{ opacity: mounted ? 1 : 0, transform: mounted ? 'translateY(0)' : 'translateY(12px)', transitionTimingFunction: 'cubic-bezier(0.16, 1, 0.3, 1)' }"
        >
            <div class="absolute top-0 right-0 w-64 h-64 bg-slate-400/10 rounded-full -translate-y-1/2 translate-x-1/2 blur-3xl"></div>
            <div class="absolute bottom-0 left-0 w-48 h-48 bg-slate-400/10 rounded-full translate-y-1/2 -translate-x-1/4 blur-2xl"></div>
            <div class="absolute inset-0 opacity-[0.03]" style="background-image: radial-gradient(circle, white 1px, transparent 1px); background-size: 24px 24px;"></div>

            <div class="relative z-10 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                <div>
                    <h1 class="text-xl md:text-2xl sm:text-3xl font-bold text-white">
                        {{ isRtl ? 'الأدوية' : 'Medications' }}
                    </h1>
                    <p class="text-sm text-slate-200/70 mt-1">
                        {{ isRtl ? 'إدارة قاعدة بيانات الأدوية والجرعات الافتراضية' : 'Manage medications database and default dosages' }}
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <div class="bg-white/10 backdrop-blur-sm rounded-xl px-4 py-3 text-center">
                        <p class="text-xl md:text-2xl font-bold text-white">{{ medications.total }}</p>
                        <p class="text-[10px] text-slate-200/60 uppercase tracking-wide">{{ isRtl ? 'الأدوية' : 'Medications' }}</p>
                    </div>
                    <button
                        v-if="can('medications.create')"
                        @click="openCreate"
                        class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-sm font-semibold text-[#1B365D] bg-white shadow-lg shadow-white/20 hover:bg-slate-50 transition-all duration-200"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        {{ isRtl ? 'إضافة دواء' : 'Add Medication' }}
                    </button>
                </div>
            </div>
        </div>

        <!-- Search -->
        <div class="flex items-center gap-3">
            <div class="relative flex-1 max-w-md">
                <svg class="absolute start-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
                <input
                    v-model="search"
                    type="text"
                    :placeholder="isRtl ? 'بحث بالاسم أو الفئة...' : 'Search by name or category...'"
                    class="doctorato-input w-full ps-10 pe-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-[#1B365D]/20 focus:border-[#1B365D] transition-colors"
                />
            </div>
        </div>

        <!-- Medications Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
            <div
                v-for="(med, index) in medications.data"
                :key="med.id"
                class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden transition-all duration-500 hover:shadow-md hover:border-slate-100 group"
                :style="{ opacity: mounted ? 1 : 0, transform: mounted ? 'translateY(0)' : 'translateY(16px)', transitionDelay: `${100 + index * 40}ms`, transitionTimingFunction: 'cubic-bezier(0.16, 1, 0.3, 1)' }"
            >
                <div class="p-5">
                    <!-- Header -->
                    <div class="flex items-start justify-between mb-3">
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2">
                                <div class="w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0" :style="`background-color: ${ACCENT}18; color: ${ACCENT}`">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4.5 12.75l6-6a4.243 4.243 0 016.01 6.01l-6 6a4.243 4.243 0 01-6.01-6.01zM12 9l-3 3" />
                                    </svg>
                                </div>
                                <div class="min-w-0">
                                    <h3 class="text-sm font-bold text-gray-900 truncate">{{ med.name }}</h3>
                                    <p v-if="med.category" class="text-[11px] text-gray-400 truncate">{{ med.category }}</p>
                                </div>
                            </div>
                        </div>
                        <span
                            class="px-1.5 py-0.5 rounded text-[9px] font-bold uppercase flex-shrink-0 ms-2"
                            :class="med.is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-700'"
                        >
                            {{ med.is_active ? (isRtl ? 'فعال' : 'Active') : (isRtl ? 'معطل' : 'Inactive') }}
                        </span>
                    </div>

                    <!-- Details -->
                    <div class="space-y-2 mb-4">
                        <div v-if="med.default_dosage" class="flex items-center gap-2">
                            <span class="text-[10px] font-medium text-gray-400 uppercase w-16 flex-shrink-0">{{ isRtl ? 'الجرعة' : 'Dosage' }}</span>
                            <span class="text-xs text-gray-700 font-medium truncate">{{ med.default_dosage }}</span>
                        </div>
                        <div v-if="med.default_frequency" class="flex items-center gap-2">
                            <span class="text-[10px] font-medium text-gray-400 uppercase w-16 flex-shrink-0">{{ isRtl ? 'التكرار' : 'Freq.' }}</span>
                            <span class="text-xs text-gray-700 font-medium truncate">{{ med.default_frequency }}</span>
                        </div>
                        <div v-if="med.default_duration" class="flex items-center gap-2">
                            <span class="text-[10px] font-medium text-gray-400 uppercase w-16 flex-shrink-0">{{ isRtl ? 'المدة' : 'Duration' }}</span>
                            <span class="text-xs text-gray-700 font-medium truncate">{{ med.default_duration }}</span>
                        </div>
                        <div v-if="!med.default_dosage && !med.default_frequency && !med.default_duration" class="text-xs text-gray-400 italic">
                            {{ isRtl ? 'لا توجد جرعات افتراضية' : 'No default dosage set' }}
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex items-center justify-end gap-2 pt-3 border-t border-gray-100 opacity-0 group-hover:opacity-100 transition-all duration-200">
                        <button
                            v-if="can('medications.update')"
                            @click="openEdit(med)"
                            class="px-2.5 py-1 rounded-lg text-[11px] font-medium bg-slate-50 text-[#1B365D] hover:bg-slate-100 transition-colors"
                        >
                            {{ isRtl ? 'تعديل' : 'Edit' }}
                        </button>
                        <button
                            v-if="can('medications.delete')"
                            @click="deleteMedication(med)"
                            class="px-2.5 py-1 rounded-lg text-[11px] font-medium bg-red-50 text-red-600 hover:bg-red-100 transition-colors"
                        >
                            {{ isRtl ? 'حذف' : 'Delete' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Empty State -->
        <div v-if="medications.data.length === 0" class="text-center py-16">
            <div class="w-16 h-16 mx-auto mb-4 rounded-2xl bg-slate-50 flex items-center justify-center">
                <svg class="w-8 h-8 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4.5 12.75l6-6a4.243 4.243 0 016.01 6.01l-6 6a4.243 4.243 0 01-6.01-6.01zM12 9l-3 3" />
                </svg>
            </div>
            <h3 class="text-lg font-semibold text-gray-700">{{ isRtl ? 'لا توجد أدوية' : 'No medications found' }}</h3>
            <p class="text-sm text-gray-400 mt-1">{{ isRtl ? 'جرّب تغيير معايير البحث' : 'Try adjusting your search criteria' }}</p>
        </div>

        <!-- Pagination -->
        <div v-if="medications.links && medications.last_page > 1" class="flex justify-center gap-1">
            <template v-for="link in medications.links" :key="link.label">
                <Link
                    v-if="link.url"
                    :href="link.url"
                    v-html="link.label"
                    class="px-3 py-1.5 text-sm rounded-lg border transition-all"
                    :class="link.active ? 'text-white border-transparent shadow-sm' : 'text-gray-600 border-gray-200 hover:bg-gray-50'"
                    :style="link.active ? `background-color: ${ACCENT};` : ''"
                    preserve-state
                />
                <span v-else v-html="link.label" class="px-3 py-1.5 text-sm text-gray-400" />
            </template>
        </div>

        <!-- Create/Edit Modal -->
        <Teleport to="body">
            <Transition
                enter-active-class="transition duration-200 ease-out"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="transition duration-150 ease-in"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div v-if="showModal" v-focus-trap="() => (showModal = false)" role="dialog" aria-modal="true" class="fixed inset-0 z-[9999] flex items-center justify-center p-4">
                    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="closeModal"></div>

                    <Transition
                        enter-active-class="transition duration-200 ease-out"
                        enter-from-class="opacity-0 scale-95 translate-y-2"
                        enter-to-class="opacity-100 scale-100 translate-y-0"
                        leave-active-class="transition duration-150 ease-in"
                        leave-from-class="opacity-100 scale-100"
                        leave-to-class="opacity-0 scale-95"
                    >
                        <div v-if="showModal" class="relative w-full max-w-lg bg-white rounded-2xl shadow-2xl overflow-hidden">
                            <!-- Modal Header -->
                            <div class="bg-gradient-to-r from-[#1B365D] to-[#1B365D] px-4 md:px-6 py-4">
                                <h3 class="text-lg font-bold text-white">
                                    {{ editingMedication
                                        ? (isRtl ? 'تعديل الدواء' : 'Edit Medication')
                                        : (isRtl ? 'إضافة دواء جديد' : 'Add Medication')
                                    }}
                                </h3>
                            </div>

                            <!-- Modal Body -->
                            <form @submit.prevent="submit" class="p-4 md:p-6 space-y-4">
                                <!-- Name -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">
                                        {{ isRtl ? 'اسم الدواء' : 'Medication Name' }} <span class="text-red-500">*</span>
                                    </label>
                                    <input
                                        v-model="form.name"
                                        type="text"
                                        class="doctorato-input w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-[#1B365D]/20 focus:border-[#1B365D]"
                                        :placeholder="isRtl ? 'مثال: أموكسيسيلين' : 'e.g., Amoxicillin'"
                                    />
                                    <p v-if="form.errors.name" class="mt-1 text-xs text-red-600">{{ form.errors.name }}</p>
                                </div>

                                <!-- Category -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ isRtl ? 'الفئة' : 'Category' }}</label>
                                    <input
                                        v-model="form.category"
                                        type="text"
                                        class="doctorato-input w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-[#1B365D]/20 focus:border-[#1B365D]"
                                        :placeholder="isRtl ? 'مثال: مضاد حيوي' : 'e.g., Antibiotic'"
                                    />
                                    <p v-if="form.errors.category" class="mt-1 text-xs text-red-600">{{ form.errors.category }}</p>
                                </div>

                                <!-- Dosage / Frequency / Duration -->
                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ isRtl ? 'الجرعة' : 'Dosage' }}</label>
                                        <input
                                            v-model="form.default_dosage"
                                            type="text"
                                            class="doctorato-input w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-[#1B365D]/20 focus:border-[#1B365D]"
                                            :placeholder="isRtl ? '500mg' : '500mg'"
                                        />
                                        <p v-if="form.errors.default_dosage" class="mt-1 text-xs text-red-600">{{ form.errors.default_dosage }}</p>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ isRtl ? 'التكرار' : 'Frequency' }}</label>
                                        <input
                                            v-model="form.default_frequency"
                                            type="text"
                                            class="doctorato-input w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-[#1B365D]/20 focus:border-[#1B365D]"
                                            :placeholder="isRtl ? '3 مرات/يوم' : '3x daily'"
                                        />
                                        <p v-if="form.errors.default_frequency" class="mt-1 text-xs text-red-600">{{ form.errors.default_frequency }}</p>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ isRtl ? 'المدة' : 'Duration' }}</label>
                                        <input
                                            v-model="form.default_duration"
                                            type="text"
                                            class="doctorato-input w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-[#1B365D]/20 focus:border-[#1B365D]"
                                            :placeholder="isRtl ? '7 أيام' : '7 days'"
                                        />
                                        <p v-if="form.errors.default_duration" class="mt-1 text-xs text-red-600">{{ form.errors.default_duration }}</p>
                                    </div>
                                </div>

                                <!-- Active Toggle -->
                                <div class="flex items-center gap-3">
                                    <button
                                        type="button"
                                        @click="form.is_active = !form.is_active"
                                        class="relative w-10 h-5 rounded-full transition-colors duration-200"
                                        :class="form.is_active ? 'bg-[#1B365D]' : 'bg-gray-300'"
                                    >
                                        <span
                                            class="absolute top-0.5 w-4 h-4 bg-white rounded-full shadow transition-transform duration-200"
                                            :class="form.is_active ? 'translate-x-5' : 'translate-x-0.5'"
                                        ></span>
                                    </button>
                                    <span class="text-sm font-medium text-gray-700">
                                        {{ form.is_active ? (isRtl ? 'فعال' : 'Active') : (isRtl ? 'معطل' : 'Inactive') }}
                                    </span>
                                </div>

                                <!-- Footer -->
                                <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-100">
                                    <button
                                        type="button"
                                        @click="closeModal"
                                        class="px-4 py-2 text-sm font-medium text-gray-600 hover:text-gray-800 transition-colors"
                                    >
                                        {{ isRtl ? 'إلغاء' : 'Cancel' }}
                                    </button>
                                    <button
                                        type="submit"
                                        :disabled="form.processing"
                                        class="px-5 py-2 rounded-xl text-sm font-semibold text-white shadow-sm transition-all duration-200 disabled:opacity-50"
                                        :style="`background-color: ${ACCENT}`"
                                    >
                                        <span v-if="form.processing" class="flex items-center gap-2">
                                            <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" /><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" /></svg>
                                            {{ isRtl ? 'جاري الحفظ...' : 'Saving...' }}
                                        </span>
                                        <span v-else>
                                            {{ editingMedication ? (isRtl ? 'تحديث' : 'Update') : (isRtl ? 'إنشاء' : 'Create') }}
                                        </span>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </Transition>
                </div>
            </Transition>
        </Teleport>
    </div>
</template>
