<script setup>
import { computed } from 'vue';
import { Link, useForm, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    tag: Object,
});

const page = usePage();
const locale = computed(() => page.props.locale || 'ar');
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');


const form = useForm({
    name_en: props.tag.name_en || '',
    name_ar: props.tag.name_ar || '',
});

function submit() {
    form.post(`/admin/tags/${props.tag.id}/update`);
}
</script>

<template>
    <AdminLayout :title="$t('a_edit_tag')">
        <div class="space-y-6">
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-bold text-gray-800">{{ $t('a_edit_tag') }}</h1>
                <Link href="/admin/tags" class="text-sm text-gray-500 hover:text-gray-700">{{ $t('a_back_to_tags') }}</Link>
            </div>

            <form @submit.prevent="submit" class="max-w-4xl">
                <div class="bg-white rounded-lg shadow-sm p-6 space-y-5">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('a_name_en') }}</label>
                        <input v-model="form.name_en" type="text" class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-yellow-200 focus:border-transparent" />
                        <p v-if="form.errors.name_en" class="mt-1 text-sm text-red-600">{{ form.errors.name_en }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('a_name_ar') }}</label>
                        <input v-model="form.name_ar" type="text" dir="rtl" class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-yellow-200 focus:border-transparent" />
                        <p v-if="form.errors.name_ar" class="mt-1 text-sm text-red-600">{{ form.errors.name_ar }}</p>
                    </div>

                    <div class="flex space-x-3 pt-4 border-t border-gray-200">
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="py-2.5 px-6 rounded-lg text-white font-medium text-sm transition disabled:opacity-50"
                            style="background-color: #C4A265;"
                        >
                            {{ form.processing ? $t('a_saving') : $t('a_update_tag') }}
                        </button>
                        <Link href="/admin/tags" class="py-2.5 px-6 rounded-lg bg-gray-200 text-gray-700 text-sm font-medium hover:bg-gray-300 transition">
                            {{ $t('a_cancel') }}
                        </Link>
                    </div>
                </div>
            </form>
        </div>
    </AdminLayout>
</template>
