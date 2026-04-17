<script setup>
import { computed } from 'vue';
import { Link, useForm, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    modules: Object,
});

const page = usePage();
const locale = computed(() => page.props.locale || 'ar');
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const form = useForm({
    module: 'derma',
    name_en: '',
    name_ar: '',
    display_order: 0,
});

function submit() {
    form.post('/admin/service-categories', { forceFormData: true });
}
</script>

<template>
    <AdminLayout :title="$t('a_create_service_category')">
        <div class="space-y-6">
            <div class="flex items-center justify-between">
                <h1 class="text-xl md:text-2xl font-bold text-gray-800">{{ $t('a_create_service_category') }}</h1>
                <Link href="/admin/service-categories" class="text-sm text-gray-500 hover:text-gray-700">{{ $t('a_back_to_service_categories') }}</Link>
            </div>

            <form @submit.prevent="submit" class="max-w-4xl">
                <div class="bg-white rounded-lg shadow-sm p-4 md:p-6 space-y-5">
                    <!-- Module Selector -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">{{ $t('a_module') }}</label>
                        <div class="flex gap-2">
                            <button
                                v-for="(mod, slug) in modules"
                                :key="slug"
                                type="button"
                                @click="form.module = slug"
                                class="flex items-center gap-2 px-4 py-2.5 rounded-lg border-2 text-sm font-medium transition-all duration-200"
                                :class="form.module === slug ? 'border-transparent text-white shadow-sm' : 'border-gray-200 text-gray-600 hover:border-gray-300'"
                                :style="form.module === slug ? { backgroundColor: mod.color } : {}"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="mod.icon" /></svg>
                                <span>{{ locale === 'ar' ? mod.name_ar : mod.name_en }}</span>
                            </button>
                        </div>
                        <p v-if="form.errors.module" class="mt-1 text-sm text-red-600">{{ form.errors.module }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('a_name_en') }}</label>
                        <input v-model="form.name_en" type="text" class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-amber-200 focus:border-transparent" />
                        <p v-if="form.errors.name_en" class="mt-1 text-sm text-red-600">{{ form.errors.name_en }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('a_name_ar') }}</label>
                        <input v-model="form.name_ar" type="text" dir="rtl" class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-amber-200 focus:border-transparent" />
                        <p v-if="form.errors.name_ar" class="mt-1 text-sm text-red-600">{{ form.errors.name_ar }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('a_display_order') }}</label>
                        <input v-model="form.display_order" type="number" min="0" class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-amber-200 focus:border-transparent" />
                        <p v-if="form.errors.display_order" class="mt-1 text-sm text-red-600">{{ form.errors.display_order }}</p>
                    </div>

                    <div class="flex space-x-3 pt-4 border-t border-gray-200">
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="py-2.5 px-4 md:px-6 rounded-lg text-white font-medium text-sm transition disabled:opacity-50"
                            style="background-color: #C4A265;"
                        >
                            {{ form.processing ? $t('a_saving') : $t('a_create_category') }}
                        </button>
                        <Link href="/admin/service-categories" class="py-2.5 px-4 md:px-6 rounded-lg bg-gray-200 text-gray-700 text-sm font-medium hover:bg-gray-300 transition">
                            {{ $t('a_cancel') }}
                        </Link>
                    </div>
                </div>
            </form>
        </div>
    </AdminLayout>
</template>
