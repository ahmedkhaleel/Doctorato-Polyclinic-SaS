<script setup>
import { ref, computed } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

defineOptions({ layout: AdminLayout });

const page = usePage();
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const props = defineProps({ settings: Object });

const form = useForm({
    consultation_fee: props.settings?.consultation_fee ?? '0',
    followup_fee: props.settings?.followup_fee ?? '0',
    name_ar: props.settings?.name_ar ?? 'التجميل',
    name_en: props.settings?.name_en ?? 'Cosmetic Medicine',
});

const saved = ref(false);
function submit() {
    form.post('/admin/cosmetic/settings', { preserveScroll: true, onSuccess: () => { saved.value = true; setTimeout(() => saved.value = false, 3000); }});
}
function t(en, ar) { return isRtl.value ? ar : en; }
</script>

<template>
    <div class="space-y-6 pb-10 max-w-3xl">
        <div class="bg-gradient-to-br from-violet-600 to-fuchsia-500 rounded-2xl p-6 shadow-lg">
            <h1 class="text-2xl font-bold text-white">{{ t('Cosmetic Settings', 'إعدادات التجميل') }}</h1>
        </div>

        <Transition enter-active-class="transition duration-300" enter-from-class="opacity-0" enter-to-class="opacity-100">
            <div v-if="saved" class="fixed top-6 ltr:right-6 rtl:left-6 z-50 px-4 py-3 rounded-xl bg-emerald-600 text-white shadow-xl text-sm">{{ t('Settings saved', 'تم حفظ الإعدادات') }}</div>
        </Transition>

        <form @submit.prevent="submit" class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium mb-1">{{ t('Name (Arabic)', 'الاسم (عربي)') }}</label>
                    <input v-model="form.name_ar" type="text" class="w-full px-3 py-2 border rounded-lg" />
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">{{ t('Name (English)', 'الاسم (إنجليزي)') }}</label>
                    <input v-model="form.name_en" type="text" class="w-full px-3 py-2 border rounded-lg" />
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">{{ t('Consultation Fee', 'رسوم الاستشارة') }}</label>
                    <input v-model="form.consultation_fee" type="number" min="0" step="0.01" class="w-full px-3 py-2 border rounded-lg" />
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">{{ t('Follow-up Fee', 'رسوم المتابعة') }}</label>
                    <input v-model="form.followup_fee" type="number" min="0" step="0.01" class="w-full px-3 py-2 border rounded-lg" />
                </div>
            </div>
            <div class="pt-2">
                <button :disabled="form.processing" class="px-5 py-2.5 bg-violet-600 hover:bg-violet-700 text-white rounded-xl font-semibold text-sm disabled:opacity-60">
                    {{ t('Save', 'حفظ') }}
                </button>
            </div>
        </form>
    </div>
</template>
