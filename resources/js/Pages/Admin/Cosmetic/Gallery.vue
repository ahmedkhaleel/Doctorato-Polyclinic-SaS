<script setup>
import { ref, computed, watch } from 'vue';
import { usePage, router, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import FormErrors from '@/Components/Ui/FormErrors.vue';
import { useConfirm } from '@/Composables/useConfirm.js';

defineOptions({ layout: AdminLayout });

const { confirm } = useConfirm();
const page = usePage();
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const props = defineProps({ photos: Object, filters: Object, categories: Array, patients: Array, procedures: Array });

const pid = ref(props.filters?.patient_id || '');
const cat = ref(props.filters?.category || '');
const procId = ref(props.filters?.procedure_id || '');
watch([pid, cat, procId], () => {
    router.get('/admin/cosmetic/gallery', {
        patient_id: pid.value || undefined, category: cat.value || undefined, procedure_id: procId.value || undefined,
    }, { preserveState: true, preserveScroll: true });
});

const showUpload = ref(false);
const form = useForm({ patient_id: '', procedure_id: '', category: 'before', body_area: '', taken_at: '', image: null, notes: '' });
function submit() {
    form.post('/admin/cosmetic/gallery', { preserveScroll: true, onSuccess: () => { showUpload.value = false; form.reset(); } });
}
function remove(p) {
    confirm(isRtl.value ? 'حذف الصورة؟' : 'Delete photo?', () => {
        router.delete(`/admin/cosmetic/gallery/${p.id}`, { preserveScroll: true });
    });
}
function t(en, ar) { return isRtl.value ? ar : en; }
</script>

<template>
    <div class="space-y-6 pb-10">
        <!-- Navy Hero -->
        <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-[#1B365D] via-[#1B365D] to-[#0F2444] shadow-xl">
            <div class="pointer-events-none absolute -top-16 -end-16 h-56 w-56 rounded-full bg-[#C4A265]/20 blur-3xl"></div>
            <div class="pointer-events-none absolute -bottom-20 start-1/3 h-48 w-48 rounded-full bg-[#C4A265]/10 blur-3xl"></div>
            <div class="absolute inset-x-0 top-0 h-[2px] bg-gradient-to-r from-transparent via-[#C4A265] to-transparent"></div>
            <div class="relative p-4 md:p-7 flex flex-col md:flex-row md:items-center gap-4 md:gap-5 justify-between">
                <div class="flex items-start gap-3 md:gap-4 min-w-0">
                    <div class="w-12 h-12 md:w-14 md:h-14 rounded-xl bg-gradient-to-br from-[#C4A265] to-[#8B7043] flex items-center justify-center shadow-lg flex-shrink-0">
                        <svg class="w-6 h-6 md:w-7 md:h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    </div>
                    <div class="min-w-0">
                        <div class="flex items-center gap-2 mb-1">
                            <span class="h-[3px] w-6 bg-[#C4A265] rounded-full"></span>
                            <span class="text-[10px] font-bold text-[#C4A265] tracking-[0.25em] uppercase">{{ isRtl ? 'الجلدية والتجميل' : 'DERMA & COSMETIC' }}</span>
                        </div>
                        <h1 class="text-xl md:text-3xl font-extrabold text-white tracking-tight">{{ t('Before / After Gallery', 'معرض قبل/بعد') }}</h1>
                        <p class="text-xs md:text-sm text-white/70 mt-1">{{ t('Cosmetic procedure photo documentation', 'توثيق صور إجراءات التجميل') }}</p>
                    </div>
                </div>
                <button @click="showUpload = true" class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-[#C4A265] to-[#8B7043] hover:from-[#8B7043] hover:to-[#C4A265] text-white font-bold px-4 md:px-5 py-2.5 shadow-md hover:shadow-lg transition flex-shrink-0">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    {{ t('Upload', 'رفع') }}
                </button>
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-4 flex flex-wrap gap-3">
            <select v-model="pid" class="doctorato-input px-4 py-2.5 border border-slate-200 rounded-xl text-sm">
                <option value="">{{ t('All patients', 'كل المرضى') }}</option>
                <option v-for="p in patients" :key="p.id" :value="p.id">{{ p.full_name }}</option>
            </select>
            <select v-model="procId" class="doctorato-input px-4 py-2.5 border border-slate-200 rounded-xl text-sm">
                <option value="">{{ t('All procedures', 'كل الإجراءات') }}</option>
                <option v-for="p in procedures" :key="p.id" :value="p.id">{{ p.name_ar }}</option>
            </select>
            <select v-model="cat" class="doctorato-input px-4 py-2.5 border border-slate-200 rounded-xl text-sm">
                <option value="">{{ t('All categories', 'كل التصنيفات') }}</option>
                <option v-for="c in categories" :key="c" :value="c">{{ c }}</option>
            </select>
        </div>

        <div v-if="photos.data.length" class="grid grid-cols-2 md:grid-cols-4 gap-3 md:gap-4">
            <div v-for="p in photos.data" :key="p.id" class="bg-white rounded-xl border border-[#C4A265]/20 overflow-hidden hover:shadow-lg hover:shadow-[#C4A265]/10 transition">
                <img :src="`/storage/${p.image_path}`" class="w-full h-40 object-cover" />
                <div class="p-3">
                    <div class="text-xs font-bold text-[#1B365D]">{{ p.patient?.full_name }}</div>
                    <div class="text-[10px] text-[#C4A265] font-bold uppercase mt-1 tracking-wider">{{ p.category }} · {{ p.procedure?.name_ar || '' }}</div>
                    <button @click="remove(p)" class="text-[10px] text-red-600 hover:text-red-800 font-semibold mt-2">{{ t('Delete', 'حذف') }}</button>
                </div>
            </div>
        </div>
        <div v-else class="bg-gradient-to-br from-white to-slate-50 rounded-2xl border-2 border-dashed border-[#C4A265]/30 p-10 md:p-16 text-center">
            <div class="w-16 h-16 md:w-20 md:h-20 mx-auto mb-4 rounded-full bg-gradient-to-br from-[#1B365D]/10 to-[#C4A265]/10 flex items-center justify-center">
                <svg class="w-8 h-8 md:w-10 md:h-10 text-[#1B365D]/40" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
            </div>
            <h3 class="text-base md:text-lg font-bold text-[#1B365D]">{{ t('No photos yet', 'لا توجد صور') }}</h3>
            <p class="text-xs md:text-sm text-slate-500 mt-1">{{ t('Upload a photo to get started', 'ارفع صورة للبدء') }}</p>
        </div>

        <div v-if="showUpload" v-focus-trap="() => (showUpload = false)" role="dialog" aria-modal="true" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4" @click.self="showUpload = false">
            <div class="bg-white rounded-2xl w-full max-w-full sm:max-w-md p-4 md:p-6 max-h-[90vh] overflow-y-auto">
                <h2 class="text-lg font-bold mb-4">{{ t('Upload photo', 'رفع صورة') }}</h2>
                <form @submit.prevent="submit" class="space-y-3">
                    <FormErrors :errors="form.errors" />
                    <select v-model="form.patient_id" required class="doctorato-input w-full px-3 py-2 border rounded-lg text-sm">
                        <option value="">{{ t('Select patient', 'اختر المريض') }}</option>
                        <option v-for="p in patients" :key="p.id" :value="p.id">{{ p.full_name }}</option>
                    </select>
                    <select v-model="form.procedure_id" class="doctorato-input w-full px-3 py-2 border rounded-lg text-sm">
                        <option value="">{{ t('Procedure (optional)', 'الإجراء (اختياري)') }}</option>
                        <option v-for="p in procedures" :key="p.id" :value="p.id">{{ p.name_ar }}</option>
                    </select>
                    <select v-model="form.category" required class="doctorato-input w-full px-3 py-2 border rounded-lg text-sm">
                        <option v-for="c in categories" :key="c" :value="c">{{ c }}</option>
                    </select>
                    <input v-model="form.body_area" :placeholder="t('Body area', 'المنطقة')" class="doctorato-input w-full px-3 py-2 border rounded-lg text-sm" />
                    <input v-model="form.taken_at" type="date" class="doctorato-input w-full px-3 py-2 border rounded-lg text-sm" />
                    <input type="file" accept="image/*" @change="e => form.image = e.target.files[0]" required class="w-full text-sm" />
                    <textarea v-model="form.notes" :placeholder="t('Notes', 'ملاحظات')" rows="2" class="doctorato-input w-full px-3 py-2 border rounded-lg text-sm"></textarea>
                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" @click="showUpload = false" class="px-4 py-2 rounded-lg bg-gray-100 text-sm">{{ t('Cancel', 'إلغاء') }}</button>
                        <button :disabled="form.processing" class="px-5 py-2 rounded-lg bg-gradient-to-r from-[#C4A265] to-[#8B7043] text-white text-sm font-bold">{{ t('Upload', 'رفع') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>
