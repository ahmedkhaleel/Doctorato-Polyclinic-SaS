<script setup>
import { ref, computed, watch } from 'vue';
import { usePage, router, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

defineOptions({ layout: AdminLayout });

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
    if (!confirm(isRtl.value ? 'حذف الصورة؟' : 'Delete photo?')) return;
    router.delete(`/admin/cosmetic/gallery/${p.id}`, { preserveScroll: true });
}
function t(en, ar) { return isRtl.value ? ar : en; }
</script>

<template>
    <div class="space-y-6 pb-10">
        <div class="bg-gradient-to-br from-[#1B365D] to-[#C4A265] rounded-2xl p-6 shadow-lg flex items-center justify-between">
            <h1 class="text-2xl font-bold text-white">{{ t('Before / After Gallery', 'معرض قبل/بعد') }}</h1>
            <button @click="showUpload = true" class="px-4 py-2 bg-white/15 hover:bg-white/25 text-white rounded-xl text-sm font-semibold ring-1 ring-white/30">+ {{ t('Upload', 'رفع') }}</button>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 flex flex-wrap gap-3">
            <select v-model="pid" class="doctorato-input px-4 py-2.5 border rounded-xl text-sm">
                <option value="">{{ t('All patients', 'كل المرضى') }}</option>
                <option v-for="p in patients" :key="p.id" :value="p.id">{{ p.full_name }}</option>
            </select>
            <select v-model="procId" class="doctorato-input px-4 py-2.5 border rounded-xl text-sm">
                <option value="">{{ t('All procedures', 'كل الإجراءات') }}</option>
                <option v-for="p in procedures" :key="p.id" :value="p.id">{{ p.name_ar }}</option>
            </select>
            <select v-model="cat" class="doctorato-input px-4 py-2.5 border rounded-xl text-sm">
                <option value="">{{ t('All categories', 'كل التصنيفات') }}</option>
                <option v-for="c in categories" :key="c" :value="c">{{ c }}</option>
            </select>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div v-for="p in photos.data" :key="p.id" class="bg-white rounded-xl border border-gray-100 overflow-hidden">
                <img :src="`/storage/${p.image_path}`" class="w-full h-40 object-cover" />
                <div class="p-3">
                    <div class="text-xs font-semibold text-gray-800">{{ p.patient?.full_name }}</div>
                    <div class="text-[10px] text-[#1B365D] uppercase mt-1">{{ p.category }} · {{ p.procedure?.name_ar || '' }}</div>
                    <button @click="remove(p)" class="text-[10px] text-red-600 mt-2">{{ t('Delete', 'حذف') }}</button>
                </div>
            </div>
            <div v-if="!photos.data.length" class="col-span-full text-center py-12 text-gray-400">{{ t('No photos yet', 'لا توجد صور') }}</div>
        </div>

        <div v-if="showUpload" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4" @click.self="showUpload = false">
            <div class="bg-white rounded-2xl w-full max-w-full sm:max-w-md p-4 md:p-6 max-h-[90vh] overflow-y-auto">
                <h2 class="text-lg font-bold mb-4">{{ t('Upload photo', 'رفع صورة') }}</h2>
                <form @submit.prevent="submit" class="space-y-3">
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
                        <button :disabled="form.processing" class="px-5 py-2 rounded-lg bg-[#1B365D] text-white text-sm font-semibold">{{ t('Upload', 'رفع') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>
