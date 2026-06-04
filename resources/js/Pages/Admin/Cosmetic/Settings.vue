<script setup>
import { ref, computed } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import FormErrors from '@/Components/Ui/FormErrors.vue';

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
        <!-- Navy Hero -->
        <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-[#1B365D] via-[#1B365D] to-[#0F2444] shadow-xl">
            <div class="pointer-events-none absolute -top-16 -end-16 h-56 w-56 rounded-full bg-[#C4A265]/20 blur-3xl"></div>
            <div class="pointer-events-none absolute -bottom-20 start-1/3 h-48 w-48 rounded-full bg-[#C4A265]/10 blur-3xl"></div>
            <div class="absolute inset-x-0 top-0 h-[2px] bg-gradient-to-r from-transparent via-[#C4A265] to-transparent"></div>
            <div class="relative p-4 md:p-7 flex items-start gap-3 md:gap-4">
                <div class="w-12 h-12 md:w-14 md:h-14 rounded-xl bg-gradient-to-br from-[#C4A265] to-[#8B7043] flex items-center justify-center shadow-lg flex-shrink-0">
                    <svg class="w-6 h-6 md:w-7 md:h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                </div>
                <div class="min-w-0">
                    <div class="flex items-center gap-2 mb-1">
                        <span class="h-[3px] w-6 bg-[#C4A265] rounded-full"></span>
                        <span class="text-[10px] font-bold text-[#C4A265] tracking-[0.25em] uppercase">{{ isRtl ? 'الجلدية والتجميل' : 'DERMA & COSMETIC' }}</span>
                    </div>
                    <h1 class="text-xl md:text-3xl font-extrabold text-white tracking-tight">{{ t('Cosmetic Settings', 'إعدادات التجميل') }}</h1>
                    <p class="text-xs md:text-sm text-white/70 mt-1">{{ t('Configure cosmetic module', 'تكوين قسم التجميل') }}</p>
                </div>
            </div>
        </div>

        <Transition enter-active-class="transition duration-300" enter-from-class="opacity-0" enter-to-class="opacity-100">
            <div v-if="saved" class="fixed top-6 ltr:right-6 rtl:left-6 z-50 px-4 py-3 rounded-xl bg-emerald-600 text-white shadow-xl text-sm">{{ t('Settings saved', 'تم حفظ الإعدادات') }}</div>
        </Transition>

        <form @submit.prevent="submit" class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 space-y-4">
            <FormErrors :errors="form.errors" />
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium mb-1">{{ t('Name (Arabic)', 'الاسم (عربي)') }}</label>
                    <input v-model="form.name_ar" type="text" class="doctorato-input w-full px-3 py-2 border rounded-lg" />
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">{{ t('Name (English)', 'الاسم (إنجليزي)') }}</label>
                    <input v-model="form.name_en" type="text" class="doctorato-input w-full px-3 py-2 border rounded-lg" />
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">{{ t('Consultation Fee', 'رسوم الاستشارة') }}</label>
                    <input v-model="form.consultation_fee" type="number" min="0" step="0.01" class="doctorato-input w-full px-3 py-2 border rounded-lg" />
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">{{ t('Follow-up Fee', 'رسوم المتابعة') }}</label>
                    <input v-model="form.followup_fee" type="number" min="0" step="0.01" class="doctorato-input w-full px-3 py-2 border rounded-lg" />
                </div>
            </div>
            <div class="pt-2">
                <button :disabled="form.processing" class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-[#C4A265] to-[#8B7043] hover:from-[#8B7043] hover:to-[#C4A265] text-white font-bold px-5 py-2.5 shadow-md hover:shadow-lg transition disabled:opacity-60 text-sm">
                    {{ t('Save', 'حفظ') }}
                </button>
            </div>
        </form>
    </div>
</template>
