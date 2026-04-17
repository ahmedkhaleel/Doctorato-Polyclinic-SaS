<script setup>
import { computed } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { useLocale } from '@/Composables/useLocale.js';

const { t } = useLocale();

const page = usePage();
const locale = computed(() => page.props.locale || 'ar');
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const props = defineProps({
    message: Object,
});

function deleteMessage() {
    if (window.confirm(t('a_confirm_delete_message'))) {
        router.post(`/admin/contact-messages/${props.message.id}/delete`);
    }
}

function formatDate(dateString) {
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}
</script>

<template>
    <AdminLayout :title="$t('a_message_details')">
        <div class="space-y-6">
            <div class="flex items-center justify-between">
                <h1 class="text-xl md:text-2xl font-bold text-gray-800">{{ $t('a_message_details') }}</h1>
                <Link href="/admin/contact-messages" class="text-sm text-gray-500 hover:text-gray-700">{{ $t('a_back_to_messages') }}</Link>
            </div>

            <div class="max-w-4xl">
                <div class="bg-white rounded-lg shadow-sm p-4 md:p-6 space-y-5">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div>
                            <label class="block text-sm font-medium text-gray-500 mb-1">{{ $t('a_name') }}</label>
                            <p class="text-sm text-gray-900">{{ message.name }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-500 mb-1">{{ $t('a_email') }}</label>
                            <p class="text-sm text-gray-900">{{ message.email }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-500 mb-1">{{ $t('a_phone') }}</label>
                            <p class="text-sm text-gray-900">{{ message.phone || '-' }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-500 mb-1">{{ $t('a_date') }}</label>
                            <p class="text-sm text-gray-900">{{ formatDate(message.created_at) }}</p>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-500 mb-1">{{ $t('a_subject') }}</label>
                        <p class="text-sm text-gray-900">{{ message.subject || '-' }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-500 mb-1">{{ $t('a_message') }}</label>
                        <div class="mt-1 p-4 bg-gray-50 rounded-lg text-sm text-gray-900 whitespace-pre-wrap">{{ message.message }}</div>
                    </div>

                    <div class="flex space-x-3 pt-4 border-t border-gray-200 rtl:space-x-reverse">
                        <Link href="/admin/contact-messages" class="py-2.5 px-4 md:px-6 rounded-lg bg-gray-200 text-gray-700 text-sm font-medium hover:bg-gray-300 transition">
                            {{ $t('a_back_to_messages') }}
                        </Link>
                        <button
                            @click="deleteMessage"
                            class="py-2.5 px-4 md:px-6 rounded-lg bg-red-600 text-white text-sm font-medium hover:bg-red-700 transition"
                        >
                            {{ $t('a_delete_message') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
