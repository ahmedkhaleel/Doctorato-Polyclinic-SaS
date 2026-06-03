<script setup>
import { ref, watch, computed } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { usePermissions } from '@/Composables/usePermissions.js';
import { useLocale } from '@/Composables/useLocale.js';
import { useConfirm } from '@/Composables/useConfirm.js';

const { can } = usePermissions();
const { t } = useLocale();
const { confirm } = useConfirm();

const page = usePage();
const locale = computed(() => page.props.locale || 'ar');
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const props = defineProps({
    messages: Object,
    filters: Object,
});

const search = ref(props.filters?.search || '');
const isReadFilter = ref(props.filters?.is_read ?? '');
let searchTimeout = null;

watch(search, (val) => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        router.get('/admin/contact-messages', {
            search: val || undefined,
            is_read: isReadFilter.value !== '' ? isReadFilter.value : undefined,
        }, {
            preserveState: true,
            replace: true,
        });
    }, 400);
});

watch(isReadFilter, (val) => {
    router.get('/admin/contact-messages', {
        search: search.value || undefined,
        is_read: val !== '' ? val : undefined,
    }, {
        preserveState: true,
        replace: true,
    });
});

function deleteMessage(id) {
    confirm(t('a_confirm_delete_message'), () => {
        router.post(`/admin/contact-messages/${id}/delete`);
    });
}

function formatDate(dateString) {
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}
</script>

<template>
    <AdminLayout :title="$t('a_messages')">
        <div class="space-y-6">
            <div class="flex items-center justify-between">
                <h1 class="text-xl md:text-2xl font-bold text-gray-800">{{ $t('a_messages') }}</h1>
            </div>

            <div class="bg-white rounded-lg shadow-sm p-4 flex flex-wrap gap-4">
                <input
                    v-model="search"
                    type="text"
                    :placeholder="$t('a_search') + '...'"
                    class="doctorato-input w-full sm:w-80 px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#C4A265]/30 focus:border-transparent"
                />
                <select
                    v-model="isReadFilter"
                    class="doctorato-input px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#C4A265]/30 focus:border-transparent"
                >
                    <option value="">{{ $t('a_all_messages') }}</option>
                    <option value="0">{{ $t('a_unread') }}</option>
                    <option value="1">{{ $t('a_read') }}</option>
                </select>
            </div>

            <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 md:px-6 py-3 ltr:text-left rtl:text-right text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                                <th class="px-4 md:px-6 py-3 ltr:text-left rtl:text-right text-xs font-medium text-gray-500 uppercase tracking-wider">{{ $t('a_name') }}</th>
                                <th class="px-4 md:px-6 py-3 ltr:text-left rtl:text-right text-xs font-medium text-gray-500 uppercase tracking-wider">{{ $t('a_email') }}</th>
                                <th class="px-4 md:px-6 py-3 ltr:text-left rtl:text-right text-xs font-medium text-gray-500 uppercase tracking-wider">{{ $t('a_subject') }}</th>
                                <th class="px-4 md:px-6 py-3 ltr:text-left rtl:text-right text-xs font-medium text-gray-500 uppercase tracking-wider">{{ $t('a_status') }}</th>
                                <th class="px-4 md:px-6 py-3 ltr:text-left rtl:text-right text-xs font-medium text-gray-500 uppercase tracking-wider">{{ $t('a_created_at') }}</th>
                                <th class="px-4 md:px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">{{ $t('a_actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr
                                v-for="(msg, i) in messages.data"
                                :key="msg.id"
                                class="lst-row hover:bg-gray-50"
                                :class="{ 'bg-amber-50': !msg.is_read }"
                                :style="{ '--row-i': i }"
                            >
                                <td class="px-4 md:px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ msg.id }}</td>
                                <td class="px-4 md:px-6 py-4 text-sm text-gray-900" :class="{ 'font-bold': !msg.is_read }">{{ msg.name }}</td>
                                <td class="px-4 md:px-6 py-4 text-sm text-gray-500">{{ msg.email }}</td>
                                <td class="px-4 md:px-6 py-4 text-sm text-gray-500 max-w-xs truncate">{{ msg.subject || '-' }}</td>
                                <td class="px-4 md:px-6 py-4 whitespace-nowrap text-sm">
                                    <span
                                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                                        :class="msg.is_read ? 'bg-emerald-100 text-emerald-800' : 'bg-red-100 text-red-800'"
                                    >
                                        {{ msg.is_read ? $t('a_read') : $t('a_unread') }}
                                    </span>
                                </td>
                                <td class="px-4 md:px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ formatDate(msg.created_at) }}</td>
                                <td class="px-4 md:px-6 py-4 whitespace-nowrap text-right text-sm space-x-3 rtl:space-x-reverse">
                                    <Link :href="`/admin/contact-messages/${msg.id}`" class="font-medium hover:underline" style="color: #C4A265;">{{ $t('a_view') }}</Link>
                                    <button v-if="can('contact_messages.delete')" @click="deleteMessage(msg.id)" class="font-medium text-red-600 hover:underline">{{ $t('a_delete') }}</button>
                                </td>
                            </tr>
                            <tr v-if="!messages.data || messages.data.length === 0">
                                <td colspan="7" class="px-4 md:px-6 py-8 text-center text-sm text-gray-500">{{ $t('a_no_messages_found') }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="messages.links && messages.links.length > 3" class="px-4 md:px-6 py-3 border-t border-gray-200 flex items-center justify-between">
                    <p class="text-sm text-gray-500">{{ $t('a_showing') }} {{ messages.from }} {{ $t('a_to') }} {{ messages.to }} {{ $t('a_of') }} {{ messages.total }} {{ $t('a_results') }}</p>
                    <nav class="flex space-x-1 rtl:space-x-reverse">
                        <template v-for="link in messages.links" :key="link.label">
                            <Link
                                v-if="link.url"
                                :href="link.url"
                                v-html="link.label"
                                class="px-3 py-1 text-sm rounded border transition"
                                :class="link.active ? 'text-white border-transparent' : 'text-gray-600 border-gray-300 hover:bg-gray-50'"
                                :style="link.active ? 'background-color: #C4A265;' : ''"
                                preserve-state
                            />
                            <span v-else v-html="link.label" class="px-3 py-1 text-sm text-gray-400" />
                        </template>
                    </nav>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<style scoped>
.lst-row {
    animation: lstRowIn 0.4s cubic-bezier(0.22, 0.61, 0.36, 1) both;
    animation-delay: calc(var(--row-i, 0) * 35ms);
}
@keyframes lstRowIn {
    from { opacity: 0; transform: translateY(8px); }
    to { opacity: 1; transform: none; }
}
@media (prefers-reduced-motion: reduce) {
    .lst-row { animation: none !important; }
}
</style>
