<script setup>
import { computed } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';
import PatientLayout from '@/Layouts/PatientLayout.vue';
import { usePatientLocale } from '@/Composables/usePatientLocale';

const { lp } = usePatientLocale();

defineOptions({ layout: PatientLayout });

const props = defineProps({
    patient: Object,
});

const page = usePage();
const locale = computed(() => page.props.locale || 'ar');
const dir = computed(() => page.props.dir || 'rtl');
const isRtl = computed(() => dir.value === 'rtl');
const translations = computed(() => page.props.translations || {});
function t(key) { return translations.value[key] || key; }

// Profile form
const profileForm = useForm({
    email: props.patient?.email || '',
    phone2: props.patient?.phone2 || '',
    address: props.patient?.address || '',
    emergency_contact_name: props.patient?.emergency_contact_name || '',
    emergency_contact_phone: props.patient?.emergency_contact_phone || '',
});

// Password form
const passwordForm = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

function updateProfile() {
    profileForm.post(lp('/profile'), {
        preserveScroll: true,
    });
}

function updatePassword() {
    passwordForm.post(lp('/profile/password'), {
        preserveScroll: true,
        onSuccess: () => passwordForm.reset(),
    });
}

// Notification preferences form
const prefsForm = useForm({
    preferred_language:     props.patient?.preferred_language ?? 'ar',
    notify_email_bookings:  props.patient?.notify_email_bookings  ?? true,
    notify_email_reminders: props.patient?.notify_email_reminders ?? true,
    notify_email_marketing: props.patient?.notify_email_marketing ?? true,
    notify_sms_bookings:    props.patient?.notify_sms_bookings    ?? true,
    notify_sms_reminders:   props.patient?.notify_sms_reminders   ?? true,
    notify_sms_marketing:   props.patient?.notify_sms_marketing   ?? false,
});

function updatePreferences() {
    prefsForm.post(lp('/profile/preferences'), { preserveScroll: true });
}
</script>

<template>
    <div>
        <div class="flex items-center justify-between flex-wrap gap-3 mb-6">
            <h1 class="text-2xl font-bold text-gray-800">{{ t('p_my_profile') }}</h1>
            <a :href="lp('/file/download')"
               class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-gradient-to-r from-[#1B365D] to-[#22406F] hover:from-[#22406F] hover:to-[#1B365D] text-white text-sm font-semibold shadow-md transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                </svg>
                {{ isRtl ? 'تنزيل ملفي الطبي (PDF)' : 'Download my medical record (PDF)' }}
            </a>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Personal Info -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <h2 class="text-lg font-semibold text-gray-800 mb-5">{{ isRtl ? 'المعلومات الشخصية' : 'Personal Information' }}</h2>

                <!-- Read-only fields -->
                <div class="space-y-4 mb-6 pb-6 border-b border-gray-100">
                    <div>
                        <label class="block text-xs text-gray-400 font-medium mb-1">{{ isRtl ? 'الاسم الكامل' : 'Full Name' }}</label>
                        <p class="text-sm font-medium text-gray-800 bg-gray-50 px-4 py-2.5 rounded-xl">{{ patient?.full_name }}</p>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-400 font-medium mb-1">{{ isRtl ? 'رقم الملف' : 'File Number' }}</label>
                        <p class="text-sm font-medium text-gray-800 bg-gray-50 px-4 py-2.5 rounded-xl">{{ patient?.file_number || '—' }}</p>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-400 font-medium mb-1">{{ isRtl ? 'الهاتف الأساسي' : 'Primary Phone' }}</label>
                        <p class="text-sm font-medium text-gray-800 bg-gray-50 px-4 py-2.5 rounded-xl" dir="ltr">{{ patient?.phone || '—' }}</p>
                    </div>
                </div>

                <!-- Editable fields -->
                <form @submit.prevent="updateProfile" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">{{ isRtl ? 'البريد الإلكتروني' : 'Email' }}</label>
                        <input
                            v-model="profileForm.email"
                            type="email"
                            class="doctorato-input w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-[var(--brand-primary)]/50 focus:border-[var(--brand-primary)]/50 transition-all"
                            :class="profileForm.errors.email ? 'border-red-300' : ''"
                        />
                        <p v-if="profileForm.errors.email" class="mt-1 text-xs text-red-500">{{ profileForm.errors.email }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">{{ isRtl ? 'هاتف ثانوي' : 'Secondary Phone' }}</label>
                        <input
                            v-model="profileForm.phone2"
                            type="tel"
                            class="doctorato-input w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-[var(--brand-primary)]/50 focus:border-[var(--brand-primary)]/50 transition-all"
                            :class="profileForm.errors.phone2 ? 'border-red-300' : ''"
                        />
                        <p v-if="profileForm.errors.phone2" class="mt-1 text-xs text-red-500">{{ profileForm.errors.phone2 }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">{{ isRtl ? 'العنوان' : 'Address' }}</label>
                        <textarea
                            v-model="profileForm.address"
                            rows="2"
                            class="doctorato-input w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-[var(--brand-primary)]/50 focus:border-[var(--brand-primary)]/50 transition-all resize-none"
                            :class="profileForm.errors.address ? 'border-red-300' : ''"
                        ></textarea>
                        <p v-if="profileForm.errors.address" class="mt-1 text-xs text-red-500">{{ profileForm.errors.address }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">{{ isRtl ? 'اسم جهة الاتصال للطوارئ' : 'Emergency Contact Name' }}</label>
                        <input
                            v-model="profileForm.emergency_contact_name"
                            type="text"
                            class="doctorato-input w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-[var(--brand-primary)]/50 focus:border-[var(--brand-primary)]/50 transition-all"
                            :class="profileForm.errors.emergency_contact_name ? 'border-red-300' : ''"
                        />
                        <p v-if="profileForm.errors.emergency_contact_name" class="mt-1 text-xs text-red-500">{{ profileForm.errors.emergency_contact_name }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">{{ isRtl ? 'هاتف جهة الاتصال للطوارئ' : 'Emergency Contact Phone' }}</label>
                        <input
                            v-model="profileForm.emergency_contact_phone"
                            type="tel"
                            class="doctorato-input w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-[var(--brand-primary)]/50 focus:border-[var(--brand-primary)]/50 transition-all"
                            :class="profileForm.errors.emergency_contact_phone ? 'border-red-300' : ''"
                        />
                        <p v-if="profileForm.errors.emergency_contact_phone" class="mt-1 text-xs text-red-500">{{ profileForm.errors.emergency_contact_phone }}</p>
                    </div>

                    <button
                        type="submit"
                        :disabled="profileForm.processing"
                        class="w-full py-2.5 px-4 rounded-xl text-white font-semibold text-sm bg-gradient-to-r from-[var(--brand-primary)] to-[var(--brand-secondary)] hover:from-[var(--brand-primary-hover)] hover:to-[var(--brand-primary)] transition-all duration-300 disabled:opacity-50 shadow-md shadow-[var(--brand-primary)]/20"
                    >
                        <span v-if="profileForm.processing" class="flex items-center justify-center gap-2">
                            <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                            {{ isRtl ? 'جاري الحفظ...' : 'Saving...' }}
                        </span>
                        <span v-else>{{ isRtl ? 'حفظ المعلومات' : 'Save Information' }}</span>
                    </button>

                    <p v-if="profileForm.recentlySuccessful" class="text-sm text-emerald-600 text-center">
                        {{ isRtl ? 'تم الحفظ بنجاح' : 'Saved successfully' }}
                    </p>
                </form>
            </div>

            <!-- Change Password -->
            <div>
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <h2 class="text-lg font-semibold text-gray-800 mb-5">{{ isRtl ? 'تغيير كلمة المرور' : 'Change Password' }}</h2>

                    <form @submit.prevent="updatePassword" class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">{{ isRtl ? 'كلمة المرور الحالية' : 'Current Password' }}</label>
                            <input
                                v-model="passwordForm.current_password"
                                type="password"
                                class="doctorato-input w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-[var(--brand-primary)]/50 focus:border-[var(--brand-primary)]/50 transition-all"
                                :class="passwordForm.errors.current_password ? 'border-red-300' : ''"
                            />
                            <p v-if="passwordForm.errors.current_password" class="mt-1 text-xs text-red-500">{{ passwordForm.errors.current_password }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">{{ isRtl ? 'كلمة المرور الجديدة' : 'New Password' }}</label>
                            <input
                                v-model="passwordForm.password"
                                type="password"
                                class="doctorato-input w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-[var(--brand-primary)]/50 focus:border-[var(--brand-primary)]/50 transition-all"
                                :class="passwordForm.errors.password ? 'border-red-300' : ''"
                            />
                            <p v-if="passwordForm.errors.password" class="mt-1 text-xs text-red-500">{{ passwordForm.errors.password }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">{{ isRtl ? 'تأكيد كلمة المرور الجديدة' : 'Confirm New Password' }}</label>
                            <input
                                v-model="passwordForm.password_confirmation"
                                type="password"
                                class="doctorato-input w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-[var(--brand-primary)]/50 focus:border-[var(--brand-primary)]/50 transition-all"
                            />
                        </div>

                        <button
                            type="submit"
                            :disabled="passwordForm.processing"
                            class="w-full py-2.5 px-4 rounded-xl text-sm font-semibold border-2 border-[var(--brand-primary)] text-[var(--brand-primary)] hover:bg-[var(--brand-primary)] hover:text-white transition-all duration-300 disabled:opacity-50"
                        >
                            <span v-if="passwordForm.processing" class="flex items-center justify-center gap-2">
                                <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                </svg>
                                {{ isRtl ? 'جاري التحديث...' : 'Updating...' }}
                            </span>
                            <span v-else>{{ isRtl ? 'تحديث كلمة المرور' : 'Update Password' }}</span>
                        </button>

                        <p v-if="passwordForm.recentlySuccessful" class="text-sm text-emerald-600 text-center">
                            {{ isRtl ? 'تم تحديث كلمة المرور بنجاح' : 'Password updated successfully' }}
                        </p>
                    </form>
                </div>
            </div>

            <!-- ── Notification Preferences ────────────────────── -->
            <div class="mt-6 bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="p-5 border-b border-gray-100 flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-[#1B365D] to-[#22406F] flex items-center justify-center">
                        <svg class="w-4 h-4 text-[#C4A265]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="font-bold text-slate-800">{{ isRtl ? 'تفضيلات الإشعارات' : 'Notification Preferences' }}</h3>
                        <p class="text-xs text-slate-500">{{ isRtl ? 'اختر ما تريد استلامه ومتى' : 'Choose what you want to receive — and where' }}</p>
                    </div>
                </div>

                <form @submit.prevent="updatePreferences" class="p-5 space-y-5">
                    <!-- Language -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-2">
                            {{ isRtl ? 'لغة الإشعارات' : 'Notification Language' }}
                        </label>
                        <div class="flex gap-2">
                            <label class="flex-1 cursor-pointer">
                                <input v-model="prefsForm.preferred_language" type="radio" value="ar" class="peer sr-only" />
                                <div class="px-4 py-2.5 rounded-xl border-2 text-center text-sm font-semibold text-slate-600 border-slate-200 peer-checked:border-[#C4A265] peer-checked:bg-[#C4A265]/5 peer-checked:text-[#1B365D] transition">العربية</div>
                            </label>
                            <label class="flex-1 cursor-pointer">
                                <input v-model="prefsForm.preferred_language" type="radio" value="en" class="peer sr-only" />
                                <div class="px-4 py-2.5 rounded-xl border-2 text-center text-sm font-semibold text-slate-600 border-slate-200 peer-checked:border-[#C4A265] peer-checked:bg-[#C4A265]/5 peer-checked:text-[#1B365D] transition">English</div>
                            </label>
                        </div>
                    </div>

                    <!-- Channels grid -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <!-- Bookings -->
                        <div class="rounded-xl border border-slate-200 p-4 bg-slate-50/40">
                            <h4 class="font-bold text-slate-800 mb-3 text-sm">
                                📅 {{ isRtl ? 'الحجوزات' : 'Bookings' }}
                            </h4>
                            <p class="text-[11px] text-slate-500 mb-3">{{ isRtl ? 'تأكيد، إلغاء، تغيير موعد' : 'Confirmations, cancellations, changes' }}</p>
                            <label class="flex items-center gap-2 mb-2 cursor-pointer">
                                <input v-model="prefsForm.notify_email_bookings" type="checkbox" class="rounded text-[#C4A265]" />
                                <span class="text-sm text-slate-700">📧 {{ isRtl ? 'بريد إلكتروني' : 'Email' }}</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input v-model="prefsForm.notify_sms_bookings" type="checkbox" class="rounded text-[#C4A265]" />
                                <span class="text-sm text-slate-700">📱 {{ isRtl ? 'رسائل SMS' : 'SMS' }}</span>
                            </label>
                        </div>

                        <!-- Reminders -->
                        <div class="rounded-xl border border-slate-200 p-4 bg-slate-50/40">
                            <h4 class="font-bold text-slate-800 mb-3 text-sm">
                                ⏰ {{ isRtl ? 'تذكيرات' : 'Reminders' }}
                            </h4>
                            <p class="text-[11px] text-slate-500 mb-3">{{ isRtl ? 'قبل الموعد بـ 24 ساعة + يوم الموعد' : '24h before + same-day' }}</p>
                            <label class="flex items-center gap-2 mb-2 cursor-pointer">
                                <input v-model="prefsForm.notify_email_reminders" type="checkbox" class="rounded text-[#C4A265]" />
                                <span class="text-sm text-slate-700">📧 {{ isRtl ? 'بريد إلكتروني' : 'Email' }}</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input v-model="prefsForm.notify_sms_reminders" type="checkbox" class="rounded text-[#C4A265]" />
                                <span class="text-sm text-slate-700">📱 {{ isRtl ? 'رسائل SMS' : 'SMS' }}</span>
                            </label>
                        </div>

                        <!-- Marketing -->
                        <div class="rounded-xl border border-slate-200 p-4 bg-slate-50/40">
                            <h4 class="font-bold text-slate-800 mb-3 text-sm">
                                🎁 {{ isRtl ? 'العروض' : 'Marketing' }}
                            </h4>
                            <p class="text-[11px] text-slate-500 mb-3">{{ isRtl ? 'كوبونات خصم، عروض موسمية' : 'Promo codes, seasonal offers' }}</p>
                            <label class="flex items-center gap-2 mb-2 cursor-pointer">
                                <input v-model="prefsForm.notify_email_marketing" type="checkbox" class="rounded text-[#C4A265]" />
                                <span class="text-sm text-slate-700">📧 {{ isRtl ? 'بريد إلكتروني' : 'Email' }}</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input v-model="prefsForm.notify_sms_marketing" type="checkbox" class="rounded text-[#C4A265]" />
                                <span class="text-sm text-slate-700">📱 {{ isRtl ? 'رسائل SMS' : 'SMS' }}</span>
                            </label>
                        </div>
                    </div>

                    <button type="submit" :disabled="prefsForm.processing"
                        class="w-full py-2.5 rounded-xl bg-gradient-to-r from-[#1B365D] to-[#22406F] text-white font-bold text-sm hover:shadow-lg transition disabled:opacity-50">
                        {{ prefsForm.processing
                            ? (isRtl ? 'جارٍ الحفظ...' : 'Saving...')
                            : (isRtl ? 'حفظ التفضيلات' : 'Save Preferences') }}
                    </button>

                    <p v-if="prefsForm.recentlySuccessful" class="text-sm text-emerald-600 text-center">
                        ✓ {{ isRtl ? 'تم حفظ التفضيلات' : 'Preferences saved' }}
                    </p>
                </form>
            </div>
        </div>
    </div>
</template>
