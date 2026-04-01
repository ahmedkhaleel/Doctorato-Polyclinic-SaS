<script setup>
import { ref, watch, onMounted , computed } from 'vue';
import { Link, router , usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { usePermissions } from '@/Composables/usePermissions.js';

const { can } = usePermissions();

const props = defineProps({ templates: Object, filters: Object });

const page = usePage();
const locale = computed(() => page.props.locale || 'ar');
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const mounted = ref(false);
onMounted(() => setTimeout(() => mounted.value = true, 50));

const channelFilter = ref(props.filters?.channel || '');
const categoryFilter = ref(props.filters?.category || '');
const hoveredPreview = ref(null);

const channels = [
    { value: 'whatsapp', label: 'WhatsApp', color: 'bg-green-100 text-green-700', icon: 'M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z' },
    { value: 'sms', label: 'SMS', color: 'bg-blue-100 text-blue-700', icon: 'M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z' },
    { value: 'email', label: 'Email', color: 'bg-purple-100 text-purple-700', icon: 'M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z' },
];

const categories = [
    { value: 'welcome', label: 'Welcome' },
    { value: 'follow_up', label: 'Follow Up' },
    { value: 'appointment_reminder', label: 'Appointment Reminder' },
    { value: 'promotion', label: 'Promotion' },
    { value: 're_engagement', label: 'Re-engagement' },
    { value: 'thank_you', label: 'Thank You' },
    { value: 'custom', label: 'Custom' },
];

function applyFilters() {
    router.get('/admin/templates', {
        channel: channelFilter.value || undefined,
        category: categoryFilter.value || undefined,
    }, { preserveState: true, replace: true });
}

watch([channelFilter, categoryFilter], applyFilters);

function channelBadge(ch) {
    return channels.find(c => c.value === ch) || { label: ch, color: 'bg-gray-100 text-gray-600', icon: '' };
}

function categoryLabel(cat) {
    return categories.find(c => c.value === cat)?.label || cat;
}

function deleteTemplate(id) {
    if (confirm('Are you sure you want to delete this template?')) {
        router.delete(`/admin/templates/${id}`);
    }
}
</script>

<template>
    <AdminLayout :title="$t('a_communication_templates')">
        <div class="space-y-6">
            <!-- Header -->
            <div :class="mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'" class="transition-all duration-500 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">{{ $t('a_communication_templates') }}</h1>
                    <p class="text-sm text-gray-500 mt-1">{{ $t('a_manage_templates_description') }}</p>
                </div>
                <Link v-if="can('communication_templates.create')" href="/admin/templates/create"
                    class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-white text-sm font-semibold transition-all duration-200 shadow-lg shadow-[#C4A265]/20 hover:shadow-xl hover:shadow-[#C4A265]/30 hover:-translate-y-0.5"
                    style="background: linear-gradient(135deg, #C4A265, #D4B87A);">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>{{ $t('a_new_template') }}</Link>
            </div>

            <!-- Channel Filter Pills -->
            <div :class="mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'" class="transition-all duration-500 delay-100 flex flex-wrap items-center gap-3">
                <div class="flex items-center bg-white rounded-xl border border-gray-100 shadow-sm p-1 gap-1">
                    <button @click="channelFilter = ''"
                        :class="channelFilter === '' ? 'bg-gray-800 text-white shadow-md' : 'text-gray-500 hover:text-gray-700 hover:bg-gray-50'"
                        class="px-4 py-2 text-xs font-semibold rounded-lg transition-all duration-200">{{ $t('a_all_channels') }}</button>
                    <button v-for="ch in channels" :key="ch.value" @click="channelFilter = ch.value"
                        :class="channelFilter === ch.value
                            ? (ch.value === 'whatsapp' ? 'bg-green-600 text-white shadow-md' : ch.value === 'sms' ? 'bg-blue-600 text-white shadow-md' : 'bg-purple-600 text-white shadow-md')
                            : 'text-gray-500 hover:text-gray-700 hover:bg-gray-50'"
                        class="px-4 py-2 text-xs font-semibold rounded-lg transition-all duration-200 flex items-center gap-1.5">
                        <svg v-if="ch.value === 'whatsapp'" class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24"><path :d="ch.icon"/></svg>
                        <svg v-else class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="ch.icon"/></svg>
                        {{ ch.label }}
                    </button>
                </div>

                <select v-model="categoryFilter"
                    class="px-4 py-2.5 text-xs font-semibold border border-gray-200 rounded-xl bg-white focus:ring-2 focus:ring-[#C4A265]/30 focus:border-[#C4A265] transition-all duration-200">
                    <option value="">{{ $t('a_all_categories') }}</option>
                    <option v-for="cat in categories" :key="cat.value" :value="cat.value">{{ cat.label }}</option>
                </select>
            </div>

            <!-- Templates Table -->
            <div :class="mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'" class="transition-all duration-500 delay-200 bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="ltr:text-left rtl:text-right text-[10px] text-gray-400 uppercase tracking-wider border-b border-gray-100 bg-gray-50/80">
                                <th class="px-6 py-4 font-semibold">{{ $t('a_name') }}</th>
                                <th class="px-6 py-4 font-semibold text-center">{{ $t('a_channel') }}</th>
                                <th class="px-6 py-4 font-semibold text-center">{{ $t('a_category') }}</th>
                                <th class="px-6 py-4 font-semibold text-center">{{ $t('a_status') }}</th>
                                <th class="px-6 py-4 font-semibold">{{ $t('a_preview_en') }}</th>
                                <th class="px-6 py-4 font-semibold ltr:text-right rtl:text-left">{{ $t('a_actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <tr v-for="(tpl, idx) in templates.data" :key="tpl.id"
                                :style="{ transitionDelay: (idx * 30) + 'ms' }"
                                :class="mounted ? 'opacity-100 translate-x-0' : 'opacity-0 -translate-x-4'"
                                class="transition-all duration-300 hover:bg-gradient-to-r hover:from-[#C4A265]/[0.02] hover:to-transparent group">
                                <td class="px-6 py-4">
                                    <p class="font-semibold text-gray-800">{{ tpl.name }}</p>
                                    <p v-if="tpl.subject" class="text-xs text-gray-400 mt-0.5 flex items-center gap-1">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z" /></svg>
                                        {{ tpl.subject }}
                                    </p>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span :class="channelBadge(tpl.channel).color"
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 text-[10px] font-bold rounded-full uppercase ring-1 ring-inset"
                                        :style="{ '--tw-ring-color': tpl.channel === 'whatsapp' ? '#dcfce740' : tpl.channel === 'sms' ? '#dbeafe40' : '#f3e8ff40' }">
                                        <svg v-if="tpl.channel === 'whatsapp'" class="w-3 h-3" fill="currentColor" viewBox="0 0 24 24"><path :d="channelBadge(tpl.channel).icon"/></svg>
                                        <svg v-else class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="channelBadge(tpl.channel).icon"/></svg>
                                        {{ channelBadge(tpl.channel).label }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span class="text-xs text-gray-500 bg-gray-50 px-2.5 py-1 rounded-lg font-medium">{{ categoryLabel(tpl.category) }}</span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <div class="inline-flex items-center gap-2">
                                        <span class="relative flex h-2 w-2">
                                            <span v-if="tpl.is_active" class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                                            <span class="relative inline-flex rounded-full h-2 w-2" :class="tpl.is_active ? 'bg-green-500' : 'bg-gray-300'"></span>
                                        </span>
                                        <span class="text-xs font-semibold" :class="tpl.is_active ? 'text-green-600' : 'text-gray-400'">{{ tpl.is_active ? $t('a_active') : $t('a_inactive') }}</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 relative" @mouseenter="hoveredPreview = tpl.id" @mouseleave="hoveredPreview = null">
                                    <p class="text-xs text-gray-500 truncate max-w-[200px]">{{ (tpl.body_en || '').replace(/\n/g, ' ').substring(0, 60) }}{{ (tpl.body_en || '').length > 60 ? '...' : '' }}</p>
                                    <!-- Expanded Preview -->
                                    <div v-if="hoveredPreview === tpl.id && tpl.body_en"
                                        class="absolute z-20 ltr:left-0 rtl:right-0 top-full mt-1 w-96 p-4 bg-white rounded-xl shadow-xl border border-gray-100 text-xs text-gray-600 leading-relaxed">
                                        <div class="absolute -top-1.5 ltr:left-6 rtl:right-6 w-3 h-3 bg-white border-t border-l border-gray-100 transform rotate-45"></div>
                                        <div v-html="(tpl.body_en || '').replace(/\n/g, '<br>')"></div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 ltr:text-right rtl:text-left">
                                    <div class="flex items-center justify-end gap-1">
                                        <Link v-if="can('communication_templates.update')" :href="`/admin/templates/${tpl.id}/edit`"
                                            class="p-2 rounded-lg text-gray-400 hover:text-[#C4A265] hover:bg-[#C4A265]/5 transition-all duration-200" :title="$t('a_edit')">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                                        </Link>
                                        <button v-if="can('communication_templates.delete')" @click="deleteTemplate(tpl.id)"
                                            class="p-2 rounded-lg text-gray-400 hover:text-red-500 hover:bg-red-50 transition-all duration-200" :title="$t('a_delete')">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div v-if="!templates.data?.length" class="px-6 py-20 text-center">
                    <div class="w-16 h-16 mx-auto mb-4 rounded-2xl bg-gray-50 flex items-center justify-center">
                        <svg class="w-8 h-8 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" /></svg>
                    </div>
                    <p class="text-sm text-gray-400 font-medium">{{ $t('a_no_templates_found') }}</p>
                    <p class="text-xs text-gray-300 mt-1">{{ $t('a_no_templates_hint') }}</p>
                </div>

                <!-- Pagination -->
                <div v-if="templates.links?.length > 3" class="px-6 py-4 border-t border-gray-100 flex items-center justify-between bg-gray-50/30">
                    <p class="text-xs text-gray-400 font-medium">{{ $t('a_showing') }} {{ templates.from }}-{{ templates.to }} {{ $t('a_of') }} {{ templates.total }}</p>
                    <div class="flex gap-1">
                        <template v-for="link in templates.links" :key="link.label">
                            <Link v-if="link.url" :href="link.url"
                                :class="link.active ? 'bg-[#C4A265] text-white shadow-sm' : 'text-gray-500 hover:bg-gray-100'"
                                class="px-3 py-1.5 text-xs font-medium rounded-lg transition-all duration-200" v-html="link.label" />
                            <span v-else class="px-3 py-1.5 text-xs text-gray-300" v-html="link.label" />
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
