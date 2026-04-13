<script setup>
import { ref, computed, onMounted, Transition } from 'vue';
import { router, useForm, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { usePermissions } from '@/Composables/usePermissions.js';

const { can } = usePermissions();

const page = usePage();
const locale = computed(() => page.props.locale || 'ar');
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const props = defineProps({
    categories: Array,
});

const showModal = ref(false);
const editingCategory = ref(null);
const headerLoaded = ref(false);
const cardsLoaded = ref(false);
const searchQuery = ref('');

onMounted(() => {
    setTimeout(() => headerLoaded.value = true, 50);
    setTimeout(() => cardsLoaded.value = true, 200);
});

const form = useForm({
    name_ar: '',
    name_en: '',
});

const filteredCategories = computed(() => {
    const cats = Array.isArray(props.categories) ? props.categories : (props.categories?.data || []);
    if (!searchQuery.value) return cats;
    const q = searchQuery.value.toLowerCase();
    return cats.filter(c =>
        (c.name_ar || '').toLowerCase().includes(q) ||
        (c.name_en || '').toLowerCase().includes(q)
    );
});

const totalExpenses = computed(() => {
    const cats = Array.isArray(props.categories) ? props.categories : (props.categories?.data || []);
    return cats.reduce((sum, c) => sum + (c.expenses_count || 0), 0);
});

const totalItems = computed(() => {
    const cats = Array.isArray(props.categories) ? props.categories : (props.categories?.data || []);
    return cats.reduce((sum, c) => sum + (c.expense_items_count || 0), 0);
});

const allCategories = computed(() => {
    return Array.isArray(props.categories) ? props.categories : (props.categories?.data || []);
});

function openCreate() {
    editingCategory.value = null;
    form.reset();
    form.clearErrors();
    showModal.value = true;
}

function openEdit(category) {
    editingCategory.value = category;
    form.name_ar = category.name_ar || '';
    form.name_en = category.name_en || '';
    form.clearErrors();
    showModal.value = true;
}

function closeModal() {
    showModal.value = false;
    editingCategory.value = null;
    form.reset();
}

function submit() {
    if (editingCategory.value) {
        form.post(`/admin/expense-categories/${editingCategory.value.id}/update`, {
            onSuccess: () => closeModal(),
        });
    } else {
        form.post('/admin/expense-categories', {
            onSuccess: () => closeModal(),
        });
    }
}

function deleteCategory(id) {
    if (window.confirm(locale.value === 'ar' ? 'هل أنت متأكد من حذف هذا التصنيف؟' : 'Are you sure you want to delete this category?')) {
        router.post(`/admin/expense-categories/${id}/delete`);
    }
}

function getCategoryColor(index) {
    const colors = [
        { bg: 'bg-amber-50', border: 'border-amber-200', icon: 'text-amber-600', badge: 'bg-amber-100 text-amber-700' },
        { bg: 'bg-blue-50', border: 'border-blue-200', icon: 'text-blue-600', badge: 'bg-blue-100 text-blue-700' },
        { bg: 'bg-emerald-50', border: 'border-emerald-200', icon: 'text-emerald-600', badge: 'bg-emerald-100 text-emerald-700' },
        { bg: 'bg-purple-50', border: 'border-purple-200', icon: 'text-purple-600', badge: 'bg-purple-100 text-purple-700' },
        { bg: 'bg-rose-50', border: 'border-rose-200', icon: 'text-rose-600', badge: 'bg-rose-100 text-rose-700' },
        { bg: 'bg-cyan-50', border: 'border-cyan-200', icon: 'text-cyan-600', badge: 'bg-cyan-100 text-cyan-700' },
        { bg: 'bg-orange-50', border: 'border-orange-200', icon: 'text-orange-600', badge: 'bg-orange-100 text-orange-700' },
        { bg: 'bg-indigo-50', border: 'border-indigo-200', icon: 'text-indigo-600', badge: 'bg-indigo-100 text-indigo-700' },
    ];
    return colors[index % colors.length];
}
</script>

<template>
    <AdminLayout :title="$t('a_expense_categories')">
        <div class="space-y-6">
            <!-- Hero Header -->
            <div
                class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-gray-900 via-gray-800 to-gray-900 p-8 transition-all duration-700"
                :class="headerLoaded ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'"
            >
                <!-- Decorative -->
                <div class="absolute top-0 right-0 w-96 h-96 bg-gradient-radial from-amber-500/10 to-transparent rounded-full -translate-y-1/2 translate-x-1/2"></div>
                <div class="absolute bottom-0 left-0 w-64 h-64 bg-gradient-radial from-amber-600/5 to-transparent rounded-full translate-y-1/2 -translate-x-1/4"></div>

                <div class="relative z-10">
                    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                        <div class="flex items-center gap-4">
                            <div class="w-14 h-14 rounded-xl bg-gradient-to-br from-amber-400 to-amber-600 flex items-center justify-center shadow-lg shadow-amber-500/20">
                                <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                                </svg>
                            </div>
                            <div>
                                <h1 class="text-2xl font-bold text-white">{{ $t('a_expense_categories') }}</h1>
                                <p class="text-sm text-gray-400 mt-0.5">{{ locale === 'ar' ? 'إدارة وتنظيم تصنيفات المصروفات' : 'Manage and organize expense categories' }}</p>
                            </div>
                        </div>
                        <button
                            v-if="can('expense_categories.create')"
                            @click="openCreate"
                            class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-white text-sm font-semibold bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-600 hover:to-amber-700 shadow-lg shadow-amber-500/25 transition-all duration-200 hover:shadow-amber-500/40 hover:-translate-y-0.5"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
                            </svg>
                            {{ $t('a_add_category') }}
                        </button>
                    </div>

                    <!-- Stats Row -->
                    <div class="grid grid-cols-3 gap-4 mt-6">
                        <div class="bg-white/5 backdrop-blur-sm rounded-xl px-4 py-3 border border-white/10">
                            <p class="text-xs text-gray-400">{{ locale === 'ar' ? 'إجمالي التصنيفات' : 'Total Categories' }}</p>
                            <p class="text-xl font-bold text-white mt-0.5">{{ allCategories.length }}</p>
                        </div>
                        <div class="bg-white/5 backdrop-blur-sm rounded-xl px-4 py-3 border border-white/10">
                            <p class="text-xs text-gray-400">{{ locale === 'ar' ? 'إجمالي المصروفات' : 'Total Expenses' }}</p>
                            <p class="text-xl font-bold text-white mt-0.5">{{ totalExpenses }}</p>
                        </div>
                        <div class="bg-white/5 backdrop-blur-sm rounded-xl px-4 py-3 border border-white/10">
                            <p class="text-xs text-gray-400">{{ locale === 'ar' ? 'إجمالي البنود' : 'Total Items' }}</p>
                            <p class="text-xl font-bold text-white mt-0.5">{{ totalItems }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Search -->
            <div
                class="transition-all duration-500 delay-100"
                :class="headerLoaded ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'"
            >
                <div class="relative max-w-md">
                    <svg class="absolute top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" :class="isRtl ? 'right-3' : 'left-3'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    <input
                        v-model="searchQuery"
                        type="text"
                        :placeholder="locale === 'ar' ? 'بحث في التصنيفات...' : 'Search categories...'"
                        class="w-full py-2.5 border border-gray-200 rounded-xl text-sm bg-white shadow-sm focus:ring-2 focus:ring-amber-200 focus:border-amber-400 transition"
                        :class="isRtl ? 'pr-10 pl-4' : 'pl-10 pr-4'"
                    />
                </div>
            </div>

            <!-- Categories Grid -->
            <div
                class="transition-all duration-700"
                :class="cardsLoaded ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-6'"
            >
                <div v-if="filteredCategories.length" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                    <div
                        v-for="(category, idx) in filteredCategories"
                        :key="category.id"
                        class="group relative bg-white rounded-xl border border-gray-100 shadow-sm hover:shadow-md hover:-translate-y-0.5 transition-all duration-300 overflow-hidden"
                    >
                        <!-- Color Strip Top -->
                        <div class="h-1 w-full" :class="getCategoryColor(idx).badge.split(' ')[0]"></div>

                        <div class="p-5">
                            <!-- Icon & Names -->
                            <div class="flex items-start gap-3">
                                <div
                                    class="w-10 h-10 rounded-lg flex items-center justify-center flex-shrink-0 transition-colors"
                                    :class="[getCategoryColor(idx).bg, getCategoryColor(idx).icon]"
                                >
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A2 2 0 013 12V7a4 4 0 014-4z" />
                                    </svg>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <h3 class="font-semibold text-gray-900 text-sm truncate">{{ category.name_ar }}</h3>
                                    <p class="text-xs text-gray-500 mt-0.5 truncate">{{ category.name_en }}</p>
                                </div>
                            </div>

                            <!-- Stats -->
                            <div class="flex items-center gap-3 mt-4">
                                <div class="flex items-center gap-1.5">
                                    <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z" />
                                    </svg>
                                    <span class="text-xs text-gray-500">{{ category.expenses_count || 0 }} {{ locale === 'ar' ? 'مصروف' : 'expenses' }}</span>
                                </div>
                                <div class="flex items-center gap-1.5">
                                    <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16" />
                                    </svg>
                                    <span class="text-xs text-gray-500">{{ category.expense_items_count || 0 }} {{ locale === 'ar' ? 'بند' : 'items' }}</span>
                                </div>
                            </div>

                            <!-- Actions -->
                            <div class="flex items-center gap-2 mt-4 pt-3 border-t border-gray-100">
                                <button
                                    v-if="can('expense_categories.update')"
                                    @click="openEdit(category)"
                                    class="flex-1 flex items-center justify-center gap-1.5 px-3 py-1.5 text-xs font-medium text-blue-600 bg-blue-50 rounded-lg hover:bg-blue-100 transition"
                                >
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                    {{ $t('a_edit') }}
                                </button>
                                <button
                                    v-if="can('expense_categories.delete')"
                                    @click="deleteCategory(category.id)"
                                    class="flex items-center justify-center gap-1.5 px-3 py-1.5 text-xs font-medium text-red-600 bg-red-50 rounded-lg hover:bg-red-100 transition"
                                >
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                    {{ $t('a_delete') }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Empty State -->
                <div v-else class="text-center py-16 bg-white rounded-xl border border-gray-100">
                    <div class="w-16 h-16 mx-auto bg-gray-100 rounded-full flex items-center justify-center mb-4">
                        <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                        </svg>
                    </div>
                    <p class="text-gray-500 text-sm">{{ searchQuery ? (locale === 'ar' ? 'لا توجد نتائج للبحث' : 'No results found') : $t('a_no_expense_categories_found') }}</p>
                    <button
                        v-if="!searchQuery && can('expense_categories.create')"
                        @click="openCreate"
                        class="mt-4 inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-amber-700 bg-amber-50 rounded-lg hover:bg-amber-100 transition"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        {{ $t('a_add_category') }}
                    </button>
                </div>
            </div>
        </div>

        <!-- Modal -->
        <Teleport to="body">
            <Transition
                enter-active-class="transition-opacity duration-200"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="transition-opacity duration-150"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                    <!-- Backdrop -->
                    <div class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm" @click="closeModal"></div>

                    <!-- Modal Content -->
                    <Transition
                        enter-active-class="transition-all duration-300"
                        enter-from-class="opacity-0 scale-95 translate-y-4"
                        enter-to-class="opacity-100 scale-100 translate-y-0"
                        leave-active-class="transition-all duration-200"
                        leave-from-class="opacity-100 scale-100"
                        leave-to-class="opacity-0 scale-95"
                        appear
                    >
                        <div class="relative bg-white rounded-2xl shadow-2xl z-10 w-full max-w-md overflow-hidden">
                            <!-- Modal Header -->
                            <div class="bg-gradient-to-r from-gray-900 to-gray-800 px-6 py-4">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-3">
                                        <div class="w-9 h-9 rounded-lg bg-amber-500/20 flex items-center justify-center">
                                            <svg class="w-5 h-5 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A2 2 0 013 12V7a4 4 0 014-4z" />
                                            </svg>
                                        </div>
                                        <h2 class="text-lg font-semibold text-white">
                                            {{ editingCategory ? $t('a_edit_category') : $t('a_add_category') }}
                                        </h2>
                                    </div>
                                    <button @click="closeModal" class="text-gray-400 hover:text-white transition p-1">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </button>
                                </div>
                            </div>

                            <!-- Modal Body -->
                            <form @submit.prevent="submit" class="p-6 space-y-5">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1.5">
                                        {{ $t('a_name_ar') }}
                                        <span class="text-red-500">*</span>
                                    </label>
                                    <input
                                        v-model="form.name_ar"
                                        type="text"
                                        dir="rtl"
                                        :placeholder="locale === 'ar' ? 'أدخل اسم التصنيف بالعربية' : 'Enter category name in Arabic'"
                                        class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm bg-gray-50 focus:bg-white focus:ring-2 focus:ring-amber-200 focus:border-amber-400 transition"
                                    />
                                    <p v-if="form.errors.name_ar" class="mt-1.5 text-xs text-red-600 flex items-center gap-1">
                                        <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" /></svg>
                                        {{ form.errors.name_ar }}
                                    </p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1.5">
                                        {{ $t('a_name_en') }}
                                        <span class="text-red-500">*</span>
                                    </label>
                                    <input
                                        v-model="form.name_en"
                                        type="text"
                                        dir="ltr"
                                        :placeholder="locale === 'ar' ? 'أدخل اسم التصنيف بالإنجليزية' : 'Enter category name in English'"
                                        class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm bg-gray-50 focus:bg-white focus:ring-2 focus:ring-amber-200 focus:border-amber-400 transition"
                                    />
                                    <p v-if="form.errors.name_en" class="mt-1.5 text-xs text-red-600 flex items-center gap-1">
                                        <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" /></svg>
                                        {{ form.errors.name_en }}
                                    </p>
                                </div>

                                <!-- Actions -->
                                <div class="flex items-center gap-3 pt-2">
                                    <button
                                        type="button"
                                        @click="closeModal"
                                        class="flex-1 px-4 py-2.5 text-sm font-medium text-gray-700 bg-gray-100 rounded-xl hover:bg-gray-200 transition"
                                    >
                                        {{ $t('a_cancel') }}
                                    </button>
                                    <button
                                        type="submit"
                                        :disabled="form.processing"
                                        class="flex-1 px-4 py-2.5 rounded-xl text-white text-sm font-semibold bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-600 hover:to-amber-700 shadow-md shadow-amber-500/20 transition-all disabled:opacity-50 disabled:cursor-not-allowed"
                                    >
                                        <span v-if="form.processing" class="flex items-center justify-center gap-2">
                                            <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" /><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" /></svg>
                                            {{ $t('a_saving') }}
                                        </span>
                                        <span v-else>{{ editingCategory ? $t('a_update') : $t('a_create') }}</span>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </Transition>
                </div>
            </Transition>
        </Teleport>
    </AdminLayout>
</template>
