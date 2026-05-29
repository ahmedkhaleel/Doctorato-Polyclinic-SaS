<script setup>
import { computed, ref, onMounted } from 'vue';
import { router, useForm, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import ConfirmModal from '@/Components/Admin/ConfirmModal.vue';

defineOptions({ layout: AdminLayout });

const props = defineProps({
    templates: { type: Array, default: () => [] },
    procedures: { type: Array, default: () => [] },
});

const page = usePage();
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

function procName(p) { return p ? (isRtl.value ? p.name_ar : (p.name_en || p.name_ar)) : (isRtl.value ? 'كل الإجراءات' : 'All procedures'); }
function tplTitle(t) { return isRtl.value ? (t.title_ar || t.title_en) : (t.title_en || t.title_ar); }

// ─── Create / edit ─────────────────────────────────
const showForm = ref(false);
const editing = ref(null);
const form = useForm({
    procedure_id: '', title_ar: '', title_en: '', body_ar: '', body_en: '',
    requires_signature: true, is_active: true,
});

function openCreate() { editing.value = null; form.reset(); form.clearErrors(); showForm.value = true; }
function openEdit(t) {
    editing.value = t;
    form.clearErrors();
    form.procedure_id = t.procedure_id || '';
    form.title_ar = t.title_ar || '';
    form.title_en = t.title_en || '';
    form.body_ar = t.body_ar || '';
    form.body_en = t.body_en || '';
    form.requires_signature = !!t.requires_signature;
    form.is_active = !!t.is_active;
    showForm.value = true;
}
function submit() {
    const url = editing.value
        ? `/admin/cosmetic/consent-templates/${editing.value.id}`
        : '/admin/cosmetic/consent-templates';
    form.post(url, { preserveScroll: true, onSuccess: () => { showForm.value = false; form.reset(); } });
}

const showDelete = ref(false);
const delTarget = ref(null);
function askDelete(t) { delTarget.value = t; showDelete.value = true; }
function doDelete() {
    if (!delTarget.value) return;
    router.delete(`/admin/cosmetic/consent-templates/${delTarget.value.id}`, { preserveScroll: true });
    showDelete.value = false; delTarget.value = null;
}

const mounted = ref(false);
onMounted(() => { requestAnimationFrame(() => { mounted.value = true; }); });
</script>

<template>
    <div class="ct-root p-4 lg:p-6" :class="{ 'is-mounted': mounted }">
        <!-- Hero -->
        <div class="ct-hero ct-stagger" style="--i:0">
            <div class="ct-hero-orb"></div>
            <div class="absolute inset-x-0 top-0 h-[2px] bg-gradient-to-r from-transparent via-[#C4A265] to-transparent"></div>
            <div class="relative z-10 flex items-center justify-between gap-4 flex-wrap">
                <div class="flex items-center gap-4">
                    <div class="ct-hero-badge">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                    </div>
                    <div>
                        <div class="flex items-center gap-2 mb-1">
                            <span class="h-[3px] w-6 bg-[#C4A265] rounded-full"></span>
                            <span class="text-[10px] font-bold text-[#C4A265] tracking-[0.25em] uppercase">{{ isRtl ? 'الجلدية والتجميل' : 'Derma & Cosmetic' }}</span>
                        </div>
                        <h1 class="text-xl md:text-2xl font-extrabold text-white tracking-tight">{{ isRtl ? 'قوالب الموافقة' : 'Consent Templates' }}</h1>
                        <p class="text-xs md:text-sm text-white/70 mt-1">{{ isRtl ? 'قوالب موحّدة؛ القالب المعلّم «توقيع إلزامي» يمنع الجلسة دون موافقة موقّعة' : 'Reusable templates; a "signature required" template blocks sessions without a signed consent' }}</p>
                    </div>
                </div>
                <button @click="openCreate" class="ct-new-btn">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" /></svg>
                    {{ isRtl ? 'قالب جديد' : 'New Template' }}
                </button>
            </div>
        </div>

        <!-- Templates -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mt-6">
            <div v-for="(t, i) in templates" :key="t.id" class="ct-card ct-stagger" :style="{ '--i': i + 1 }">
                <div class="flex items-start justify-between gap-3 mb-2">
                    <div class="min-w-0">
                        <p class="font-bold text-gray-800">{{ tplTitle(t) }}</p>
                        <p class="text-[11px] text-gray-400 mt-0.5">{{ procName(t.procedure) }}</p>
                    </div>
                    <div class="flex items-center gap-1.5 flex-shrink-0">
                        <span v-if="t.requires_signature" class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 text-amber-700">{{ isRtl ? 'توقيع إلزامي' : 'Signature' }}</span>
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold" :class="t.is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-gray-100 text-gray-400'">{{ t.is_active ? (isRtl ? 'مفعّل' : 'Active') : (isRtl ? 'معطّل' : 'Off') }}</span>
                    </div>
                </div>
                <p class="text-xs text-gray-500 line-clamp-3 leading-relaxed">{{ isRtl ? (t.body_ar || t.body_en) : (t.body_en || t.body_ar) }}</p>
                <div class="flex justify-end gap-2 mt-3 pt-3 border-t border-gray-50">
                    <button @click="openEdit(t)" class="text-xs font-semibold text-[#1B365D] hover:bg-slate-50 px-3 py-1 rounded-lg transition">{{ isRtl ? 'تعديل' : 'Edit' }}</button>
                    <button @click="askDelete(t)" class="text-xs font-semibold text-red-500 hover:bg-red-50 px-3 py-1 rounded-lg transition">{{ isRtl ? 'حذف' : 'Delete' }}</button>
                </div>
            </div>

            <div v-if="!templates.length" class="ct-card ct-stagger col-span-full text-center py-16" style="--i:1">
                <div class="text-5xl mb-3 opacity-40">📋</div>
                <p class="text-sm text-gray-400">{{ isRtl ? 'لا توجد قوالب موافقة بعد' : 'No consent templates yet' }}</p>
            </div>
        </div>

        <!-- Create / edit modal -->
        <Teleport to="body">
            <Transition name="ct-modal">
                <div v-if="showForm" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                    <div class="absolute inset-0 bg-[#0F2444]/50 backdrop-blur-sm" @click="showForm = false"></div>
                    <div class="ct-dialog relative w-full max-w-2xl bg-white rounded-2xl shadow-2xl overflow-hidden">
                        <div class="px-6 py-4 bg-gradient-to-br from-[#1B365D] to-[#0F2444] flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-white/15 flex items-center justify-center">
                                <svg class="w-5 h-5 text-[#C4A265]" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                            </div>
                            <h3 class="text-white font-bold">{{ editing ? (isRtl ? 'تعديل القالب' : 'Edit Template') : (isRtl ? 'قالب موافقة جديد' : 'New Consent Template') }}</h3>
                        </div>
                        <form @submit.prevent="submit" class="p-6 space-y-4 max-h-[72vh] overflow-y-auto">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="ct-label">{{ isRtl ? 'العنوان (عربي)' : 'Title (Arabic)' }} *</label>
                                    <input v-model="form.title_ar" type="text" required class="ct-field" />
                                    <p v-if="form.errors.title_ar" class="text-[11px] text-red-500 mt-1">{{ form.errors.title_ar }}</p>
                                </div>
                                <div>
                                    <label class="ct-label">{{ isRtl ? 'العنوان (إنجليزي)' : 'Title (English)' }}</label>
                                    <input v-model="form.title_en" type="text" class="ct-field" />
                                </div>
                            </div>
                            <div>
                                <label class="ct-label">{{ isRtl ? 'الإجراء (اتركه فارغاً = عام)' : 'Procedure (blank = general)' }}</label>
                                <select v-model="form.procedure_id" class="ct-field">
                                    <option value="">{{ isRtl ? 'كل الإجراءات' : 'All procedures' }}</option>
                                    <option v-for="p in procedures" :key="p.id" :value="p.id">{{ procName(p) }}</option>
                                </select>
                            </div>
                            <div>
                                <label class="ct-label">{{ isRtl ? 'نص الموافقة (عربي)' : 'Consent body (Arabic)' }} *</label>
                                <textarea v-model="form.body_ar" rows="4" required class="ct-field resize-none" :placeholder="isRtl ? 'أقر أنا الموقّع أدناه بموافقتي على إجراء...' : '...'"></textarea>
                                <p v-if="form.errors.body_ar" class="text-[11px] text-red-500 mt-1">{{ form.errors.body_ar }}</p>
                            </div>
                            <div>
                                <label class="ct-label">{{ isRtl ? 'نص الموافقة (إنجليزي)' : 'Consent body (English)' }}</label>
                                <textarea v-model="form.body_en" rows="3" class="ct-field resize-none"></textarea>
                            </div>
                            <div class="flex flex-wrap gap-4">
                                <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer">
                                    <input type="checkbox" v-model="form.requires_signature" class="ct-check" />
                                    {{ isRtl ? 'توقيع إلزامي قبل الجلسة' : 'Require signature before session' }}
                                </label>
                                <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer">
                                    <input type="checkbox" v-model="form.is_active" class="ct-check" />
                                    {{ isRtl ? 'مفعّل' : 'Active' }}
                                </label>
                            </div>
                            <p class="text-[11px] text-gray-500 bg-amber-50 border border-amber-100 rounded-lg px-3 py-2">
                                ⚠️ {{ isRtl ? 'عند تفعيل «توقيع إلزامي»، لن يُسمح بتسجيل جلسة مكتملة لهذا الإجراء قبل وجود موافقة موقّعة للمريض.' : 'With "require signature" on, a completed session for this procedure is blocked until the patient has a signed consent.' }}
                            </p>
                            <div class="flex justify-end gap-3 pt-1">
                                <button type="button" @click="showForm = false" class="px-4 py-2 rounded-lg text-sm font-medium text-gray-600 hover:bg-gray-100 transition">{{ isRtl ? 'إلغاء' : 'Cancel' }}</button>
                                <button type="submit" :disabled="form.processing" class="ct-submit">{{ form.processing ? (isRtl ? 'جارٍ...' : 'Saving...') : (editing ? (isRtl ? 'حفظ' : 'Save') : (isRtl ? 'إنشاء' : 'Create')) }}</button>
                            </div>
                        </form>
                    </div>
                </div>
            </Transition>
        </Teleport>

        <ConfirmModal
            :show="showDelete"
            :title="isRtl ? 'حذف القالب' : 'Delete Template'"
            :message="isRtl ? 'سيتم حذف قالب الموافقة. الموافقات الموقّعة سابقاً لا تتأثر.' : 'The template will be deleted. Previously signed consents are unaffected.'"
            :confirmText="isRtl ? 'حذف' : 'Delete'"
            :cancelText="isRtl ? 'رجوع' : 'Back'"
            confirmColor="red"
            @confirm="doDelete"
            @cancel="showDelete = false"
        />
    </div>
</template>

<style scoped>
.ct-hero { position: relative; overflow: hidden; border-radius: 1rem; padding: 22px 24px; background: linear-gradient(135deg, #1B365D 0%, #1B365D 45%, #0F2444 100%); box-shadow: 0 18px 40px -20px rgba(27,54,93,0.5); }
.ct-hero-orb { position: absolute; top: -80px; inset-inline-end: -60px; width: 220px; height: 220px; border-radius: 50%; background: radial-gradient(circle, rgba(196,162,101,0.25), transparent 70%); filter: blur(20px); pointer-events: none; }
.ct-hero-badge { width: 52px; height: 52px; border-radius: 14px; flex-shrink: 0; background: linear-gradient(135deg, #C4A265, #8B7043); display: flex; align-items: center; justify-content: center; box-shadow: 0 8px 20px rgba(196,162,101,0.35); }
.ct-new-btn { display: inline-flex; align-items: center; gap: 6px; padding: 9px 18px; border-radius: 10px; background: linear-gradient(135deg, #C4A265, #8B7043); color: #fff; font-size: 13px; font-weight: 700; box-shadow: 0 8px 20px -8px rgba(196,162,101,0.6); transition: transform 0.15s, box-shadow 0.3s; }
.ct-new-btn:hover { box-shadow: 0 12px 26px -8px rgba(196,162,101,0.75); }
.ct-new-btn:active { transform: translateY(1px); }

.ct-card { background: #fff; border: 1px solid #eef0f3; border-radius: 14px; padding: 16px; box-shadow: 0 10px 30px -24px rgba(27,54,93,0.2); }
.line-clamp-3 { display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden; }

.ct-label { display: block; font-size: 11px; font-weight: 700; color: #6b7280; margin-bottom: 5px; }
.ct-field { width: 100%; padding: 9px 12px; border: 1px solid #d1d5db; border-radius: 9px; font-size: 14px; transition: all 0.2s; background: #fff; }
.ct-field:focus { outline: 0; border-color: #C4A265; box-shadow: 0 0 0 3px rgba(196,162,101,0.15); }
.ct-check { width: 16px; height: 16px; accent-color: #1B365D; }
.ct-submit { padding: 9px 20px; border-radius: 9px; background: linear-gradient(135deg, #1B365D, #22406F); color: #fff; font-size: 13px; font-weight: 700; box-shadow: 0 6px 18px -8px rgba(27,54,93,0.6); transition: box-shadow 0.3s; }
.ct-submit:hover:not(:disabled) { box-shadow: 0 10px 24px -8px rgba(27,54,93,0.7); }
.ct-submit:disabled { opacity: 0.6; cursor: not-allowed; }

.ct-stagger { opacity: 0; transform: translateY(14px); transition: opacity 0.6s cubic-bezier(0.25,0.46,0.45,0.94), transform 0.6s cubic-bezier(0.25,0.46,0.45,0.94); transition-delay: calc(var(--i,0) * 60ms + 80ms); }
.is-mounted .ct-stagger { opacity: 1; transform: translateY(0); }
.ct-modal-enter-active, .ct-modal-leave-active { transition: opacity 0.25s ease; }
.ct-modal-enter-from, .ct-modal-leave-to { opacity: 0; }
.ct-modal-enter-active .ct-dialog, .ct-modal-leave-active .ct-dialog { transition: transform 0.3s cubic-bezier(0.16,1,0.3,1); }
.ct-modal-enter-from .ct-dialog { transform: translateY(20px) scale(0.97); }
@media (prefers-reduced-motion: reduce) { .ct-stagger { transition-duration: 0.01s; transition-delay: 0s; } .ct-modal-enter-active .ct-dialog { transition: none; } }
</style>
