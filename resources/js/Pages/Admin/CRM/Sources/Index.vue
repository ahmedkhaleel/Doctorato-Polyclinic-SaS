<script setup>
import { ref, onMounted , computed } from 'vue';
import { useForm, router , usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { usePermissions } from '@/Composables/usePermissions.js';

const { can } = usePermissions();

const props = defineProps({ sources: Array });

const page = usePage();
const locale = computed(() => page.props.locale || 'ar');
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const mounted = ref(false);
onMounted(() => setTimeout(() => mounted.value = true, 50));

const showForm = ref(false);
const editingId = ref(null);

const form = useForm({
    name_en: '', name_ar: '', slug: '', icon: '', color: '#3B82F6',
    channel_type: 'online', sort_order: 0, is_active: true,
});

function openCreate() {
    editingId.value = null;
    form.reset();
    form.color = '#3B82F6';
    showForm.value = true;
}

function openEdit(source) {
    editingId.value = source.id;
    form.name_en = source.name_en;
    form.name_ar = source.name_ar;
    form.slug = source.slug;
    form.icon = source.icon || '';
    form.color = source.color || '#3B82F6';
    form.channel_type = source.channel_type;
    form.sort_order = source.sort_order;
    form.is_active = source.is_active;
    showForm.value = true;
}

function submit() {
    if (editingId.value) {
        form.post(`/admin/lead-sources/${editingId.value}`, {
            preserveScroll: true,
            onSuccess: () => { showForm.value = false; form.reset(); editingId.value = null; },
        });
    } else {
        form.post('/admin/lead-sources', {
            preserveScroll: true,
            onSuccess: () => { showForm.value = false; form.reset(); },
        });
    }
}

function deleteSource(id) {
    if (confirm('Are you sure? Sources with existing leads cannot be deleted.')) {
        router.post(`/admin/lead-sources/${id}/delete`, { preserveScroll: true });
    }
}

function generateSlug() {
    form.slug = form.name_en.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/(^-|-$)/g, '');
}

const channelIcons = {
    online: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9" />',
    offline: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />',
    referral: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />',
};
</script>

<template>
    <AdminLayout :title="$t('a_lead_sources')">
        <div class="space-y-6">
            <!-- Header -->
            <div :class="mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'" class="transition-all duration-500 flex items-center justify-between">
                <div>
                    <h1 class="text-xl md:text-2xl font-bold text-gray-800">{{ $t('a_lead_sources') }}</h1>
                    <p class="text-sm text-gray-500 mt-1">{{ $t('a_manage_sources_description') }}</p>
                </div>
                <button v-if="can('lead_sources.create')" @click="openCreate"
                    class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-white text-sm font-semibold transition-all duration-200 shadow-lg shadow-[#C4A265]/20 hover:shadow-xl hover:shadow-[#C4A265]/30 hover:-translate-y-0.5"
                    style="background: linear-gradient(135deg, #C4A265, #D4B87A);"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>{{ $t('a_add_source') }}</button>
            </div>

            <!-- Inline Form - Glass Card -->
            <div v-if="showForm"
                :class="mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'"
                class="transition-all duration-500 delay-100 relative overflow-hidden rounded-2xl border border-white/60 bg-white/80 backdrop-blur-xl shadow-xl p-8"
            >
                <!-- Decorative gradient -->
                <div class="absolute top-0 ltr:left-0 rtl:right-0 ltr:right-0 rtl:left-0 h-1 bg-gradient-to-r from-[#C4A265] via-[#D4B87A] to-[#C4A265]"></div>

                <div class="flex items-center gap-3 mb-6">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-[#C4A265] to-[#D4B87A] flex items-center justify-center shadow-lg shadow-[#C4A265]/20">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" /></svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-gray-800">{{ editingId ? $t('a_edit_source') : $t('a_new_source') }}</h3>
                        <p class="text-xs text-gray-400">{{ editingId ? $t('a_edit_source_description') : $t('a_new_source_description') }}</p>
                    </div>
                </div>

                <form @submit.prevent="submit" class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">{{ $t('a_name_en') }}<span class="text-red-400">*</span></label>
                            <input v-model="form.name_en" @blur="!editingId && generateSlug()" type="text"
                                class="w-full px-4 py-3 text-sm bg-gray-50/80 border border-gray-200/80 rounded-xl focus:ring-2 focus:ring-[#C4A265]/30 focus:border-[#C4A265] focus:bg-white transition-all duration-200 placeholder-gray-300"
                                :placeholder="$t('a_source_name_placeholder')" />
                            <p v-if="form.errors.name_en" class="text-xs text-red-500 mt-1.5">{{ form.errors.name_en }}</p>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">{{ $t('a_name_ar') }}<span class="text-red-400">*</span></label>
                            <input v-model="form.name_ar" type="text" dir="rtl"
                                class="w-full px-4 py-3 text-sm bg-gray-50/80 border border-gray-200/80 rounded-xl focus:ring-2 focus:ring-[#C4A265]/30 focus:border-[#C4A265] focus:bg-white transition-all duration-200 placeholder-gray-300" />
                            <p v-if="form.errors.name_ar" class="text-xs text-red-500 mt-1.5">{{ form.errors.name_ar }}</p>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">{{ $t('a_slug') }}<span class="text-red-400">*</span></label>
                            <input v-model="form.slug" type="text"
                                class="w-full px-4 py-3 text-sm bg-gray-50/80 border border-gray-200/80 rounded-xl focus:ring-2 focus:ring-[#C4A265]/30 focus:border-[#C4A265] focus:bg-white transition-all duration-200 font-mono placeholder-gray-300"
                                placeholder="auto-generated" />
                            <p v-if="form.errors.slug" class="text-xs text-red-500 mt-1.5">{{ form.errors.slug }}</p>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">{{ $t('a_channel_type') }}</label>
                            <div class="flex gap-2">
                                <button v-for="ch in ['online', 'offline', 'referral']" :key="ch" type="button"
                                    @click="form.channel_type = ch"
                                    :class="form.channel_type === ch
                                        ? 'bg-[#C4A265]/10 border-[#C4A265] text-[#C4A265] ring-2 ring-[#C4A265]/20'
                                        : 'bg-gray-50/80 border-gray-200/80 text-gray-500 hover:bg-gray-100'"
                                    class="flex-1 px-3 py-2.5 text-xs font-semibold capitalize border rounded-xl transition-all duration-200 flex items-center justify-center gap-1.5">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" v-html="channelIcons[ch]"></svg>
                                    {{ ch }}
                                </button>
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">{{ $t('a_color') }}</label>
                            <div class="flex items-center gap-3">
                                <div class="relative">
                                    <input v-model="form.color" type="color" class="w-12 h-12 rounded-xl border-2 border-gray-200 cursor-pointer appearance-none" style="padding: 2px;" />
                                    <div class="absolute inset-0 rounded-xl pointer-events-none" :style="{ boxShadow: `0 4px 12px ${form.color}40` }"></div>
                                </div>
                                <input v-model="form.color" type="text"
                                    class="flex-1 px-4 py-3 text-sm bg-gray-50/80 border border-gray-200/80 rounded-xl font-mono focus:ring-2 focus:ring-[#C4A265]/30 focus:border-[#C4A265] focus:bg-white transition-all duration-200" />
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">{{ $t('a_sort_order') }}</label>
                            <input v-model.number="form.sort_order" type="number" min="0"
                                class="w-full px-4 py-3 text-sm bg-gray-50/80 border border-gray-200/80 rounded-xl focus:ring-2 focus:ring-[#C4A265]/30 focus:border-[#C4A265] focus:bg-white transition-all duration-200" />
                        </div>
                    </div>

                    <!-- Active Toggle -->
                    <div class="flex items-center gap-3 pt-2">
                        <button type="button" @click="form.is_active = !form.is_active"
                            :class="form.is_active ? 'bg-[#C4A265]' : 'bg-gray-300'"
                            class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full transition-colors duration-200 ease-in-out">
                            <span :class="form.is_active ? 'ltr:translate-x-5 rtl:-translate-x-5' : 'translate-x-0.5'"
                                class="inline-block h-5 w-5 transform rounded-full bg-white shadow-md ring-0 transition-transform duration-200 ease-in-out mt-0.5" />
                        </button>
                        <span class="text-sm font-medium" :class="form.is_active ? 'text-gray-800' : 'text-gray-400'">{{ form.is_active ? $t('a_active') : $t('a_inactive') }}</span>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-100">
                        <button type="button" @click="showForm = false"
                            class="px-5 py-2.5 text-sm font-medium text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded-xl transition-all duration-200">{{ $t('a_cancel') }}</button>
                        <button type="submit" :disabled="form.processing"
                            class="px-4 md:px-6 py-2.5 rounded-xl text-white text-sm font-semibold transition-all duration-200 shadow-lg shadow-[#C4A265]/20 hover:shadow-xl hover:shadow-[#C4A265]/30 disabled:opacity-50 disabled:cursor-not-allowed"
                            style="background: linear-gradient(135deg, #C4A265, #D4B87A);">
                            <span class="flex items-center gap-2">
                                <svg v-if="form.processing" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                                {{ editingId ? $t('a_update_source') : $t('a_create_source') }}
                            </span>
                        </button>
                    </div>
                </form>
            </div>

            <!-- Sources Table -->
            <div :class="mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'" class="transition-all duration-500 delay-200 bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="ltr:text-left rtl:text-right text-[10px] text-gray-400 uppercase tracking-wider border-b border-gray-100 bg-gray-50/80">
                                <th class="px-4 md:px-6 py-4 font-semibold w-12">#</th>
                                <th class="px-4 md:px-6 py-4 font-semibold">{{ $t('a_source') }}</th>
                                <th class="px-4 md:px-6 py-4 font-semibold">{{ $t('a_slug') }}</th>
                                <th class="px-4 md:px-6 py-4 font-semibold text-center">{{ $t('a_channel') }}</th>
                                <th class="px-4 md:px-6 py-4 font-semibold text-center">{{ $t('a_leads') }}</th>
                                <th class="px-4 md:px-6 py-4 font-semibold text-center">{{ $t('a_status') }}</th>
                                <th class="px-4 md:px-6 py-4 font-semibold ltr:text-right rtl:text-left">{{ $t('a_actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <tr v-for="(s, idx) in sources" :key="s.id"
                                :style="{ transitionDelay: (idx * 30) + 'ms' }"
                                :class="mounted ? 'opacity-100 translate-x-0' : 'opacity-0 -translate-x-4'"
                                class="transition-all duration-300 hover:bg-gradient-to-r hover:from-[#C4A265]/[0.02] hover:to-transparent group">
                                <td class="px-4 md:px-6 py-4">
                                    <span class="text-xs font-mono text-gray-300 group-hover:text-gray-400 transition">{{ s.sort_order }}</span>
                                </td>
                                <td class="px-4 md:px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-xl flex items-center justify-center shadow-sm"
                                            :style="{ background: `linear-gradient(135deg, ${s.color}20, ${s.color}40)` }">
                                            <div class="w-4 h-4 rounded-full shadow-sm" :style="{ background: `linear-gradient(135deg, ${s.color}, ${s.color}CC)`, boxShadow: `0 2px 8px ${s.color}50` }"></div>
                                        </div>
                                        <div>
                                            <p class="font-semibold text-gray-800">{{ s.name_en }}</p>
                                            <p class="text-xs text-gray-400 mt-0.5" dir="rtl">{{ s.name_ar }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 md:px-6 py-4">
                                    <code class="text-xs bg-gray-100 px-2.5 py-1 rounded-lg font-mono text-gray-500">{{ s.slug }}</code>
                                </td>
                                <td class="px-4 md:px-6 py-4 text-center">
                                    <span class="inline-flex items-center gap-1.5 text-xs px-3 py-1.5 rounded-full font-semibold capitalize"
                                        :class="s.channel_type === 'online' ? 'bg-slate-50 text-[#1B365D] ring-1 ring-slate-100' : s.channel_type === 'referral' ? 'bg-amber-50 text-amber-600 ring-1 ring-amber-100' : 'bg-gray-50 text-gray-500 ring-1 ring-gray-200'">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" v-html="channelIcons[s.channel_type]"></svg>
                                        {{ s.channel_type }}
                                    </span>
                                </td>
                                <td class="px-4 md:px-6 py-4 text-center">
                                    <span class="inline-flex items-center justify-center min-w-[2rem] h-7 px-2 rounded-lg text-xs font-bold"
                                        :class="s.leads_count > 0 ? 'bg-[#C4A265]/10 text-[#C4A265]' : 'bg-gray-50 text-gray-300'">
                                        {{ s.leads_count }}
                                    </span>
                                </td>
                                <td class="px-4 md:px-6 py-4 text-center">
                                    <div class="inline-flex items-center gap-2">
                                        <span class="relative flex h-2.5 w-2.5">
                                            <span v-if="s.is_active" class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                                            <span class="relative inline-flex rounded-full h-2.5 w-2.5" :class="s.is_active ? 'bg-emerald-500' : 'bg-gray-300'"></span>
                                        </span>
                                        <span class="text-xs font-semibold" :class="s.is_active ? 'text-emerald-600' : 'text-gray-400'">{{ s.is_active ? $t('a_active') : $t('a_inactive') }}</span>
                                    </div>
                                </td>
                                <td class="px-4 md:px-6 py-4 ltr:text-right rtl:text-left">
                                    <div class="flex items-center justify-end gap-1">
                                        <button v-if="can('lead_sources.update')" @click="openEdit(s)"
                                            class="p-2 rounded-lg text-gray-400 hover:text-[#C4A265] hover:bg-[#C4A265]/5 transition-all duration-200" :title="$t('a_edit')">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                                        </button>
                                        <button v-if="can('lead_sources.delete') && s.leads_count === 0" @click="deleteSource(s.id)"
                                            class="p-2 rounded-lg text-gray-400 hover:text-red-500 hover:bg-red-50 transition-all duration-200" :title="$t('a_delete')">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div v-if="!sources?.length" class="px-4 md:px-6 py-20 text-center">
                    <div class="w-16 h-16 mx-auto mb-4 rounded-2xl bg-gray-50 flex items-center justify-center">
                        <svg class="w-8 h-8 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" /></svg>
                    </div>
                    <p class="text-sm text-gray-400 font-medium">{{ $t('a_no_sources_configured') }}</p>
                    <p class="text-xs text-gray-300 mt-1">{{ $t('a_create_first_source_hint') }}</p>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
