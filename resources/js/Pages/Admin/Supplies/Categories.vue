<script setup>
import { ref, computed } from 'vue';
import { useForm, router, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { usePermissions } from '@/Composables/usePermissions.js';
import ConfirmModal from '@/Components/Admin/ConfirmModal.vue';

const { can } = usePermissions();

const page = usePage();
const locale = computed(() => page.props.locale || 'ar');
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const props = defineProps({
    categories: Array,
});

const showModal = ref(false);
const editingCategory = ref(null);
const showDeleteConfirm = ref(false);
const deletingId = ref(null);

const form = useForm({
    name_en: '',
    name_ar: '',
    icon: '',
    color: '#6366F1',
    description: '',
    display_order: 0,
    is_active: true,
});

const colorPresets = [
    '#6366F1', '#8B5CF6', '#F59E0B', '#EC4899',
    '#10B981', '#3B82F6', '#EF4444', '#14B8A6',
    '#6B7280', '#F97316', '#84CC16', '#0EA5E9',
];

function openCreate() {
    editingCategory.value = null;
    form.reset();
    form.color = '#6366F1';
    form.is_active = true;
    showModal.value = true;
}

function openEdit(cat) {
    editingCategory.value = cat;
    form.name_en = cat.name_en;
    form.name_ar = cat.name_ar;
    form.icon = cat.icon || '';
    form.color = cat.color || '#6366F1';
    form.description = cat.description || '';
    form.display_order = cat.display_order || 0;
    form.is_active = cat.is_active;
    showModal.value = true;
}

function submit() {
    if (editingCategory.value) {
        form.post(`/admin/supply-categories/${editingCategory.value.id}/update`, {
            onSuccess: () => { showModal.value = false; },
        });
    } else {
        form.post('/admin/supply-categories', {
            onSuccess: () => { showModal.value = false; form.reset(); },
        });
    }
}

function confirmDelete(id) {
    deletingId.value = id;
    showDeleteConfirm.value = true;
}

function deleteCategory() {
    router.post(`/admin/supply-categories/${deletingId.value}/delete`, {
        onSuccess: () => { showDeleteConfirm.value = false; deletingId.value = null; },
    });
}
</script>

<template>
    <AdminLayout :title="$t('a_supply_categories')">
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between animate-fade-in-up">
                <div>
                    <div class="flex items-center gap-2 text-sm text-gray-500 mb-1">
                        <a href="/admin/inventory" class="hover:text-[#1B365D] transition-colors">{{ $t('a_inventory') }}</a>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                        <span class="text-gray-400">{{ $t('a_categories') }}</span>
                    </div>
                    <h1 class="text-xl md:text-2xl font-bold text-gray-800">{{ $t('a_supply_categories') }}</h1>
                </div>
                <button
                    v-if="can('supplies.create')"
                    @click="openCreate"
                    class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-white text-sm font-semibold transition-all duration-300 shadow-lg shadow-[#1B365D]/20 hover:shadow-xl hover:shadow-[#1B365D]/30 hover:-translate-y-0.5"
                    style="background: linear-gradient(135deg, #6366F1, #818CF8);"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                    {{ $t('a_add_category') }}
                </button>
            </div>

            <!-- Categories Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-5 stagger-inventory">
                <div
                    v-for="cat in categories"
                    :key="cat.id"
                    class="opacity-0 animate-inventory-card bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden inventory-card-hover group"
                >
                    <!-- Color accent -->
                    <div class="h-1.5" :style="{ background: `linear-gradient(90deg, ${cat.color || '#6366F1'}, ${cat.color || '#6366F1'}88)` }"></div>

                    <div class="p-5">
                        <div class="flex items-start justify-between mb-4">
                            <div class="w-12 h-12 rounded-xl flex items-center justify-center transition-transform duration-300 group-hover:scale-110" :style="{ backgroundColor: (cat.color || '#6366F1') + '15' }">
                                <svg class="w-6 h-6" :style="{ color: cat.color || '#6366F1' }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0v10l-8 4-8-4V7m16 0l-8 4m0 10V11m0 0L4 7" />
                                </svg>
                            </div>
                            <span class="px-2.5 py-1 rounded-full text-xs font-semibold" :class="cat.is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-gray-100 text-gray-500'">
                                {{ cat.is_active ? $t('a_active') : $t('a_inactive') }}
                            </span>
                        </div>

                        <h3 class="font-semibold text-gray-800 text-sm mb-0.5">{{ cat.name_en }}</h3>
                        <p class="text-xs text-gray-400 mb-3" dir="rtl">{{ cat.name_ar }}</p>

                        <p v-if="cat.description" class="text-xs text-gray-500 mb-4 line-clamp-2">{{ cat.description }}</p>

                        <div class="flex items-center justify-between pt-3 border-t border-gray-100">
                            <div class="flex items-center gap-1.5">
                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0v10l-8 4-8-4V7m16 0l-8 4m0 10V11m0 0L4 7" /></svg>
                                <span class="text-sm font-semibold text-gray-700">{{ cat.supplies_count || 0 }}</span>
                                <span class="text-xs text-gray-400">{{ $t('a_products') }}</span>
                            </div>
                            <div class="flex items-center gap-1">
                                <button v-if="can('supplies.update')" @click="openEdit(cat)" class="w-8 h-8 rounded-lg flex items-center justify-center text-gray-400 hover:text-[#1B365D] hover:bg-slate-50 transition-all duration-200">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                                </button>
                                <button v-if="can('supplies.delete')" @click="confirmDelete(cat.id)" class="w-8 h-8 rounded-lg flex items-center justify-center text-gray-400 hover:text-red-600 hover:bg-red-50 transition-all duration-200">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Empty State -->
                <div v-if="!categories || categories.length === 0" class="col-span-full text-center py-16">
                    <div class="w-16 h-16 rounded-2xl bg-gray-100 flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5" /></svg>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-700">{{ $t('a_no_categories_yet') }}</h3>
                    <p class="text-sm text-gray-500 mt-1">{{ $t('a_create_first_category') }}</p>
                </div>
            </div>
        </div>

        <!-- Create/Edit Modal -->
        <Teleport to="body">
            <Transition
                enter-active-class="transition ease-out duration-200"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="transition ease-in duration-150"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-4" @click.self="showModal = false">
                    <div class="fixed inset-0 bg-black/40 backdrop-blur-sm"></div>
                    <Transition
                        enter-active-class="transition ease-out duration-200"
                        enter-from-class="opacity-0 scale-95"
                        enter-to-class="opacity-100 scale-100"
                        leave-active-class="transition ease-in duration-150"
                        leave-from-class="opacity-100 scale-100"
                        leave-to-class="opacity-0 scale-95"
                    >
                        <div v-if="showModal" class="relative bg-white rounded-2xl shadow-xl max-w-lg w-full z-10 overflow-hidden">
                            <!-- Modal Header -->
                            <div class="px-4 md:px-6 py-4 border-b border-gray-100 bg-gradient-to-r from-gray-50 to-white flex items-center justify-between">
                                <h3 class="text-lg font-semibold text-gray-800">{{ editingCategory ? $t('a_edit_category') : $t('a_new_category') }}</h3>
                                <button @click="showModal = false" class="w-8 h-8 rounded-lg flex items-center justify-center text-gray-400 hover:text-gray-600 hover:bg-gray-100 transition-all">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                                </button>
                            </div>

                            <!-- Modal Body -->
                            <form @submit.prevent="submit" class="p-4 md:p-6 space-y-5">
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1.5">{{ $t('a_name_english') }} <span class="text-red-500">*</span></label>
                                        <input v-model="form.name_en" type="text" placeholder="e.g. Injectables" class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-[#1B365D]/20 focus:border-[#1B365D] transition-all" />
                                        <p v-if="form.errors.name_en" class="mt-1 text-sm text-red-500">{{ form.errors.name_en }}</p>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1.5">{{ $t('a_name_arabic') }} <span class="text-red-500">*</span></label>
                                        <input v-model="form.name_ar" type="text" dir="rtl" placeholder="مثال: حقن تجميلية" class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-[#1B365D]/20 focus:border-[#1B365D] transition-all" />
                                        <p v-if="form.errors.name_ar" class="mt-1 text-sm text-red-500">{{ form.errors.name_ar }}</p>
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1.5">{{ $t('a_description') }}</label>
                                    <textarea v-model="form.description" rows="2" placeholder="Brief description of this category..." class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-[#1B365D]/20 focus:border-[#1B365D] transition-all resize-none"></textarea>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">{{ $t('a_color') }}</label>
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <button
                                            v-for="c in colorPresets"
                                            :key="c"
                                            type="button"
                                            @click="form.color = c"
                                            class="w-8 h-8 rounded-lg transition-all duration-200 border-2"
                                            :class="form.color === c ? 'scale-110 border-gray-800 ring-2 ring-offset-1 ring-gray-300' : 'border-transparent hover:scale-105'"
                                            :style="{ backgroundColor: c }"
                                        ></button>
                                        <input v-model="form.color" type="color" class="w-8 h-8 rounded-lg cursor-pointer border-0 p-0" />
                                    </div>
                                </div>

                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1.5">{{ $t('a_display_order') }}</label>
                                        <input v-model="form.display_order" type="number" min="0" class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-[#1B365D]/20 focus:border-[#1B365D] transition-all" />
                                    </div>
                                    <div class="flex items-end pb-1">
                                        <label class="flex items-center gap-2 cursor-pointer">
                                            <input v-model="form.is_active" type="checkbox" class="rounded border-gray-300 text-[#1B365D] focus:ring-[#1B365D]/20 w-5 h-5" />
                                            <span class="text-sm font-medium text-gray-700">{{ $t('a_active') }}</span>
                                        </label>
                                    </div>
                                </div>

                                <!-- Preview -->
                                <div class="pt-3 border-t border-gray-100">
                                    <p class="text-xs text-gray-400 mb-2">{{ $t('a_preview') }}</p>
                                    <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full text-xs font-semibold text-white" :style="{ backgroundColor: form.color }">
                                        {{ form.name_en || 'Category Name' }}
                                    </div>
                                </div>

                                <!-- Actions -->
                                <div class="flex justify-end gap-3 pt-2">
                                    <button type="button" @click="showModal = false" class="px-5 py-2.5 rounded-xl border border-gray-200 text-sm font-medium text-gray-700 hover:bg-gray-50 transition-all">{{ $t('a_cancel') }}</button>
                                    <button
                                        type="submit"
                                        :disabled="form.processing"
                                        class="px-5 py-2.5 rounded-xl text-white text-sm font-semibold transition-all disabled:opacity-50"
                                        style="background: linear-gradient(135deg, #6366F1, #818CF8);"
                                    >
                                        {{ form.processing ? $t('a_saving') : (editingCategory ? $t('a_update') : $t('a_create')) }}
                                    </button>
                                </div>
                            </form>
                        </div>
                    </Transition>
                </div>
            </Transition>
        </Teleport>

        <!-- Delete Confirm -->
        <ConfirmModal
            :show="showDeleteConfirm"
            :title="$t('a_delete_category')"
            :message="$t('a_delete_category_confirm')"
            :confirmText="$t('a_delete')"
            confirmColor="red"
            @confirm="deleteCategory"
            @cancel="showDeleteConfirm = false"
        />
    </AdminLayout>
</template>
