<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { computed } from 'vue'
import { Link, useForm, usePage } from '@inertiajs/vue3'

defineOptions({ layout: AdminLayout })

const props = defineProps({
    companies: Array,
    plan: Object,
})

const page = usePage()
const locale = computed(() => page.props.locale || 'ar')
const isRtl = computed(() => locale.value === 'ar')
const isEdit = computed(() => !!props.plan)

const form = useForm({
    insurance_company_id: props.plan?.insurance_company_id || '',
    name_ar: props.plan?.name_ar || '',
    name_en: props.plan?.name_en || '',
    plan_code: props.plan?.plan_code || '',
    class: props.plan?.class || 'A',
    coverage_percentage: props.plan?.coverage_percentage || 80,
    max_coverage_amount: props.plan?.max_coverage_amount || '',
    copay_amount: props.plan?.copay_amount || '',
    deductible: props.plan?.deductible || '',
    covers_dental: props.plan?.covers_dental ?? false,
    covers_dermatology: props.plan?.covers_dermatology ?? true,
    covers_cosmetic: props.plan?.covers_cosmetic ?? false,
    covers_lab: props.plan?.covers_lab ?? true,
    covers_xray: props.plan?.covers_xray ?? true,
    covers_medication: props.plan?.covers_medication ?? true,
    exclusions: props.plan?.exclusions || '',
    notes: props.plan?.notes || '',
    is_active: props.plan?.is_active ?? true,
})

function submit() {
    if (isEdit.value) {
        form.post(`/admin/insurance/plans/${props.plan.id}/update`)
    } else {
        form.post('/admin/insurance/plans')
    }
}

const coverageToggles = [
    { key: 'covers_dental',       labelAr: 'الأسنان',        labelEn: 'Dental',       icon: 'M12 22s-8-4.5-8-11.8C4 6.4 7.4 3 11.6 3c1.6 0 3.1.5 4.4 1.4C17.3 3.5 18.8 3 20.4 3 24.6 3 28 6.4 28 10.2 28 17.5 20 22 20 22' },
    { key: 'covers_dermatology',  labelAr: 'الجلدية',        labelEn: 'Dermatology',  icon: 'M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z' },
    { key: 'covers_cosmetic',     labelAr: 'التجميل',        labelEn: 'Cosmetic',     icon: 'M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z' },
    { key: 'covers_lab',          labelAr: 'المختبر',        labelEn: 'Laboratory',   icon: 'M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 9H9L8 4z' },
    { key: 'covers_xray',         labelAr: 'الأشعة',         labelEn: 'X-Ray',        icon: 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6' },
    { key: 'covers_medication',   labelAr: 'الأدوية',        labelEn: 'Medication',   icon: 'M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 9H9L8 4z' },
]
</script>

<template>
    <div class="p-6 max-w-4xl mx-auto">
        <!-- Header -->
        <div class="relative overflow-hidden rounded-3xl mb-6 p-7"
             style="background: linear-gradient(135deg, #1B365D 0%, #254677 55%, #1B365D 100%);">
            <div class="absolute inset-0 opacity-20 pointer-events-none"
                 style="background-image: radial-gradient(circle at 80% 50%, #C4A265 0%, transparent 40%);"></div>
            <div class="relative flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-white">
                        {{ isEdit ? (isRtl ? 'تعديل باقة التأمين' : 'Edit Insurance Plan') : (isRtl ? 'إضافة باقة تأمين' : 'Add Insurance Plan') }}
                    </h1>
                    <p class="text-sm text-white/70 mt-1">{{ isRtl ? 'تفاصيل التغطية والفئة' : 'Coverage details and class' }}</p>
                </div>
                <Link href="/admin/insurance/plans" class="px-4 py-2 rounded-xl text-sm border border-white/30 text-white hover:bg-white/10">{{ isRtl ? 'رجوع' : 'Back' }}</Link>
            </div>
        </div>

        <form @submit.prevent="submit" class="space-y-5">
            <!-- Section: Basic -->
            <section class="bg-white rounded-2xl border border-gray-100 p-6">
                <h2 class="text-sm font-bold text-[#1B365D] mb-4 flex items-center gap-2">
                    <span class="w-1 h-5 rounded bg-[#C4A265]"></span>
                    {{ isRtl ? 'البيانات الأساسية' : 'Basic Information' }}
                </h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ isRtl ? 'شركة التأمين' : 'Insurance Company' }} *</label>
                        <select v-model="form.insurance_company_id" required class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-[#C4A265] focus:border-[#C4A265]">
                            <option value="">{{ isRtl ? 'اختر الشركة' : 'Select company' }}</option>
                            <option v-for="c in companies" :key="c.id" :value="c.id">{{ isRtl ? c.name_ar : c.name_en }}</option>
                        </select>
                        <p v-if="form.errors.insurance_company_id" class="text-xs text-red-600 mt-1">{{ form.errors.insurance_company_id }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ isRtl ? 'الفئة' : 'Class' }} *</label>
                        <select v-model="form.class" required class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-[#C4A265] focus:border-[#C4A265]">
                            <option value="VIP">VIP</option>
                            <option value="A">A</option><option value="B">B</option><option value="C">C</option>
                            <option value="D">D</option><option value="E">E</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ isRtl ? 'الاسم بالعربي' : 'Name (Arabic)' }} *</label>
                        <input v-model="form.name_ar" type="text" required class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-[#C4A265] focus:border-[#C4A265]" />
                        <p v-if="form.errors.name_ar" class="text-xs text-red-600 mt-1">{{ form.errors.name_ar }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ isRtl ? 'الاسم بالإنجليزي' : 'Name (English)' }} *</label>
                        <input v-model="form.name_en" type="text" required class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-[#C4A265] focus:border-[#C4A265]" />
                        <p v-if="form.errors.name_en" class="text-xs text-red-600 mt-1">{{ form.errors.name_en }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ isRtl ? 'كود الباقة' : 'Plan Code' }}</label>
                        <input v-model="form.plan_code" type="text" class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm font-mono focus:ring-[#C4A265] focus:border-[#C4A265]" />
                    </div>
                </div>
            </section>

            <!-- Section: Coverage -->
            <section class="bg-white rounded-2xl border border-gray-100 p-6">
                <h2 class="text-sm font-bold text-[#1B365D] mb-4 flex items-center gap-2">
                    <span class="w-1 h-5 rounded bg-[#C4A265]"></span>
                    {{ isRtl ? 'التغطية المالية' : 'Financial Coverage' }}
                </h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            {{ isRtl ? 'نسبة التغطية' : 'Coverage Percentage' }}:
                            <span class="font-bold text-[#1B365D]">{{ form.coverage_percentage }}%</span>
                        </label>
                        <input type="range" v-model.number="form.coverage_percentage" min="0" max="100" step="5"
                               class="w-full accent-[#C4A265]" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ isRtl ? 'الحد الأقصى السنوي' : 'Max Annual Amount' }}</label>
                        <input v-model="form.max_coverage_amount" type="number" step="0.01" min="0" class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-[#C4A265] focus:border-[#C4A265]" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ isRtl ? 'المشاركة (Copay)' : 'Copay Amount' }}</label>
                        <input v-model="form.copay_amount" type="number" step="0.01" min="0" class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-[#C4A265] focus:border-[#C4A265]" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ isRtl ? 'التحمل (Deductible)' : 'Deductible' }}</label>
                        <input v-model="form.deductible" type="number" step="0.01" min="0" class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-[#C4A265] focus:border-[#C4A265]" />
                    </div>
                </div>
            </section>

            <!-- Section: Coverage Toggles -->
            <section class="bg-white rounded-2xl border border-gray-100 p-6">
                <h2 class="text-sm font-bold text-[#1B365D] mb-4 flex items-center gap-2">
                    <span class="w-1 h-5 rounded bg-[#C4A265]"></span>
                    {{ isRtl ? 'الخدمات المغطاة' : 'Covered Services' }}
                </h2>
                <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                    <label v-for="t in coverageToggles" :key="t.key"
                           class="relative flex items-center gap-3 p-4 rounded-xl border-2 cursor-pointer transition"
                           :class="form[t.key] ? 'border-[#C4A265] bg-[#C4A265]/5' : 'border-gray-100 bg-gray-50 hover:border-gray-200'">
                        <input type="checkbox" v-model="form[t.key]" class="sr-only" />
                        <span class="w-9 h-9 rounded-lg flex items-center justify-center"
                              :class="form[t.key] ? 'bg-[#1B365D] text-white' : 'bg-white text-gray-400'">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" :d="t.icon" /></svg>
                        </span>
                        <span class="text-sm font-medium" :class="form[t.key] ? 'text-[#1B365D]' : 'text-gray-600'">
                            {{ isRtl ? t.labelAr : t.labelEn }}
                        </span>
                        <svg v-if="form[t.key]" class="w-4 h-4 absolute top-2 end-2 text-[#C4A265]" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                    </label>
                </div>
            </section>

            <!-- Section: Additional -->
            <section class="bg-white rounded-2xl border border-gray-100 p-6">
                <h2 class="text-sm font-bold text-[#1B365D] mb-4 flex items-center gap-2">
                    <span class="w-1 h-5 rounded bg-[#C4A265]"></span>
                    {{ isRtl ? 'معلومات إضافية' : 'Additional Info' }}
                </h2>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ isRtl ? 'الاستثناءات' : 'Exclusions' }}</label>
                        <textarea v-model="form.exclusions" rows="3" class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-[#C4A265] focus:border-[#C4A265]"></textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ isRtl ? 'ملاحظات' : 'Notes' }}</label>
                        <textarea v-model="form.notes" rows="2" class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-[#C4A265] focus:border-[#C4A265]"></textarea>
                    </div>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input v-model="form.is_active" type="checkbox" class="rounded text-[#1B365D] focus:ring-[#C4A265]" />
                        <span class="text-sm text-gray-700">{{ isRtl ? 'نشط' : 'Active' }}</span>
                    </label>
                </div>
            </section>

            <!-- Actions -->
            <div class="flex items-center justify-end gap-3 pt-2">
                <Link href="/admin/insurance/plans" class="px-5 py-2.5 text-gray-600 hover:bg-gray-100 rounded-xl text-sm font-medium">
                    {{ isRtl ? 'إلغاء' : 'Cancel' }}
                </Link>
                <button type="submit" :disabled="form.processing"
                        class="px-6 py-2.5 rounded-xl text-sm font-semibold text-white shadow-lg transition disabled:opacity-60"
                        style="background: #1B365D;">
                    {{ isEdit ? (isRtl ? 'تحديث' : 'Update') : (isRtl ? 'إنشاء' : 'Create') }}
                </button>
            </div>
        </form>
    </div>
</template>
