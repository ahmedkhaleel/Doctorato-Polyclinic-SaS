<script setup>
import { ref, computed } from 'vue';
import { Link, useForm , usePage } from '@inertiajs/vue3';
import WebmasterLayout from '@/Layouts/WebmasterLayout.vue';
import RichTextEditor from '@/Components/Admin/RichTextEditor.vue';
import SearchableSelect from '@/Components/Admin/SearchableSelect.vue';
import { useCurrency } from '@/Composables/useCurrency.js';

const { formatCurrency, currencyCode } = useCurrency();

const props = defineProps({
    users: Array,
    services: Array,
});

const __page = usePage();
const locale = computed(() => __page.props.locale || 'ar');
const isRtl = computed(() => (__page.props.dir || 'rtl') === 'rtl');


const activeSection = ref('basic');

const sections = [
    { id: 'basic', label: 'Basic Info' },
    { id: 'clinic', label: 'Clinic Settings' },
    { id: 'schedule', label: 'Schedule' },
    { id: 'rates', label: 'Service Rates' },
];

const dayNames = ['Saturday', 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];

const form = useForm({
    name_ar: '',
    name_en: '',
    photo: null,
    specialization_ar: '',
    specialization_en: '',
    bio_ar: '',
    bio_en: '',
    qualifications_ar: '',
    qualifications_en: '',
    display_order: 0,
    status: 'active',
    // Clinic fields
    user_id: '',
    phone: '',
    email: '',
    consultation_fee: '',
    default_commission_percentage: '',
    clinic_notes: '',
    // Auto-create user account
    create_user_account: false,
    create_user_password: '',
    // Nested
    schedules: dayNames.map((_, i) => ({
        day_of_week: i,
        start_time: '09:00',
        end_time: '17:00',
        is_active: false,
    })),
    vacations: [],
    service_rates: [],
});

function submit() {
    form.post('/webmaster/doctors', {
        forceFormData: true,
    });
}

// Service rates management
function addServiceRate() {
    form.service_rates.push({ service_id: '', commission_percentage: '' });
}

function removeServiceRate(index) {
    form.service_rates.splice(index, 1);
}

const userOptions = computed(() => (props.users || []).map(u => ({ value: u.id, label: u.name + ' (' + u.email + ')' })));

function serviceRateOptions(currentIndex) {
    return (props.services || []).map(svc => ({
        value: svc.id,
        label: svc.name_en,
        disabled: form.service_rates.some((r, ri) => ri !== currentIndex && Number(r.service_id) === svc.id),
    })).filter(opt => !opt.disabled);
}
</script>

<template>
    <WebmasterLayout title="Create Doctor">
        <div class="space-y-6">
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-bold text-gray-800">{{ $t('a_create_doctor') }}</h1>
                <Link href="/webmaster/doctors" class="text-sm text-gray-500 hover:text-gray-700">{{ $t('a_back_to_doctors') }}</Link>
            </div>

            <form @submit.prevent="submit">
                <!-- Section Navigation -->
                <div class="bg-white rounded-lg shadow-sm mb-6">
                    <nav class="flex border-b border-gray-200 overflow-x-auto">
                        <button
                            v-for="section in sections"
                            :key="section.id"
                            type="button"
                            @click="activeSection = section.id"
                            class="px-5 py-3 text-sm font-medium border-b-2 transition whitespace-nowrap"
                            :class="activeSection === section.id
                                ? 'border-current text-[#C4A265]'
                                : 'border-transparent text-gray-500 hover:text-gray-700'"
                            :style="activeSection === section.id ? 'color: #C4A265;' : ''"
                        >
                            {{ section.label }}
                        </button>
                    </nav>
                </div>

                <!-- ==================== BASIC INFO ==================== -->
                <div v-show="activeSection === 'basic'" class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <div class="lg:col-span-2 space-y-6">
                        <div class="bg-white rounded-lg shadow-sm p-6 space-y-5">
                            <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wider">{{ $t('a_names_specialization') }}</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('a_name_en') }} <span class="text-red-500">*</span></label>
                                    <input v-model="form.name_en" type="text" class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-yellow-200 focus:border-transparent" />
                                    <p v-if="form.errors.name_en" class="mt-1 text-sm text-red-600">{{ form.errors.name_en }}</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('a_name_ar') }} <span class="text-red-500">*</span></label>
                                    <input v-model="form.name_ar" type="text" dir="rtl" class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-yellow-200 focus:border-transparent" />
                                    <p v-if="form.errors.name_ar" class="mt-1 text-sm text-red-600">{{ form.errors.name_ar }}</p>
                                </div>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Specialization (English) <span class="text-red-500">*</span></label>
                                    <input v-model="form.specialization_en" type="text" class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-yellow-200 focus:border-transparent" />
                                    <p v-if="form.errors.specialization_en" class="mt-1 text-sm text-red-600">{{ form.errors.specialization_en }}</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Specialization (Arabic) <span class="text-red-500">*</span></label>
                                    <input v-model="form.specialization_ar" type="text" dir="rtl" class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-yellow-200 focus:border-transparent" />
                                    <p v-if="form.errors.specialization_ar" class="mt-1 text-sm text-red-600">{{ form.errors.specialization_ar }}</p>
                                </div>
                            </div>
                        </div>

                        <div class="bg-white rounded-lg shadow-sm p-6 space-y-5">
                            <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wider">{{ $t('a_bio_qualifications') }}</h3>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Bio (English)</label>
                                <RichTextEditor v-model="form.bio_en" dir="ltr" placeholder="Write bio..." />
                                <p v-if="form.errors.bio_en" class="mt-1 text-sm text-red-600">{{ form.errors.bio_en }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Bio (Arabic)</label>
                                <RichTextEditor v-model="form.bio_ar" dir="rtl" placeholder="اكتب السيرة الذاتية..." />
                                <p v-if="form.errors.bio_ar" class="mt-1 text-sm text-red-600">{{ form.errors.bio_ar }}</p>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Qualifications (English)</label>
                                    <textarea v-model="form.qualifications_en" rows="4" placeholder="One qualification per line" class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-yellow-200 focus:border-transparent"></textarea>
                                    <p v-if="form.errors.qualifications_en" class="mt-1 text-sm text-red-600">{{ form.errors.qualifications_en }}</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Qualifications (Arabic)</label>
                                    <textarea v-model="form.qualifications_ar" rows="4" dir="rtl" class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-yellow-200 focus:border-transparent"></textarea>
                                    <p v-if="form.errors.qualifications_ar" class="mt-1 text-sm text-red-600">{{ form.errors.qualifications_ar }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-6">
                        <div class="bg-white rounded-lg shadow-sm p-6 space-y-5">
                            <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wider">{{ $t('a_status_order') }}</h3>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('a_status') }}</label>
                                <select v-model="form.status" class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-yellow-200 focus:border-transparent">
                                    <option value="active">{{ $t('a_active') }}</option>
                                    <option value="inactive">{{ $t('a_inactive') }}</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('a_display_order') }}</label>
                                <input v-model="form.display_order" type="number" min="0" class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-yellow-200 focus:border-transparent" />
                            </div>
                        </div>

                        <div class="bg-white rounded-lg shadow-sm p-6 space-y-5">
                            <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wider">{{ $t('a_photo') }}</h3>
                            <input
                                type="file"
                                accept="image/*"
                                @input="form.photo = $event.target.files[0]"
                                class="w-full text-sm text-gray-500 ltr:file:mr-4 rtl:file:ml-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-gray-100 file:text-gray-700 hover:file:bg-gray-200"
                            />
                            <p v-if="form.errors.photo" class="mt-1 text-sm text-red-600">{{ form.errors.photo }}</p>
                        </div>
                    </div>
                </div>

                <!-- ==================== CLINIC SETTINGS ==================== -->
                <div v-show="activeSection === 'clinic'" class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <div class="bg-white rounded-lg shadow-sm p-6 space-y-5">
                        <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wider">{{ $t('a_contact_account') }}</h3>

                        <!-- Option: Link existing user only (no create new user for webmaster) -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Link to User Account</label>
                            <SearchableSelect v-model="form.user_id" :options="userOptions" placeholder="-- No user linked --" searchPlaceholder="Search users..." />
                            <p class="text-xs text-gray-400 mt-1">Link doctor to an existing system user for login access</p>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Phone</label>
                                <input v-model="form.phone" type="text" placeholder="01xxxxxxxxx" class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-yellow-200 focus:border-transparent" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                                <input v-model="form.email" type="email" placeholder="doctor@clinic.com" class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-yellow-200 focus:border-transparent" />
                            </div>
                        </div>
                    </div>

                    <div class="space-y-6">
                        <div class="bg-white rounded-lg shadow-sm p-6 space-y-5">
                            <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wider">{{ $t('a_financial_settings') }}</h3>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Consultation Fee ({{ currencyCode }})</label>
                                <input v-model="form.consultation_fee" type="number" step="0.01" min="0" placeholder="0.00" class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-yellow-200 focus:border-transparent" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Default Commission Rate (%)</label>
                                <input v-model="form.default_commission_percentage" type="number" step="0.01" min="0" max="100" placeholder="0.00" class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-yellow-200 focus:border-transparent" />
                                <p class="text-xs text-gray-400 mt-1">Fallback rate when no per-service rate is set</p>
                            </div>
                        </div>

                        <div class="bg-white rounded-lg shadow-sm p-6 space-y-5">
                            <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wider">{{ $t('a_clinic_notes') }}</h3>
                            <textarea v-model="form.clinic_notes" rows="5" placeholder="Internal notes about this doctor..." class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-yellow-200 focus:border-transparent"></textarea>
                        </div>
                    </div>
                </div>

                <!-- ==================== SCHEDULE ==================== -->
                <div v-show="activeSection === 'schedule'">
                    <div class="bg-white rounded-lg shadow-sm p-6">
                        <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wider mb-4">{{ $t('a_weekly_schedule') }}</h3>
                        <p class="text-xs text-gray-400 mb-4">Toggle days on/off and set working hours for each day.</p>
                        <div class="space-y-3">
                            <div
                                v-for="(schedule, i) in form.schedules"
                                :key="i"
                                class="flex items-center gap-4 p-3 rounded-lg border transition"
                                :class="schedule.is_active ? 'border-green-200 bg-green-50' : 'border-gray-200 bg-gray-50'"
                            >
                                <label class="flex items-center cursor-pointer min-w-[140px]">
                                    <input type="checkbox" v-model="schedule.is_active" class="w-4 h-4 rounded border-gray-300 text-green-600 focus:ring-green-200 mr-3" />
                                    <span class="text-sm font-medium" :class="schedule.is_active ? 'text-gray-900' : 'text-gray-400'">
                                        {{ ['Saturday','Sunday','Monday','Tuesday','Wednesday','Thursday','Friday'][i] }}
                                    </span>
                                </label>
                                <div class="flex items-center gap-2" :class="!schedule.is_active && 'opacity-40 pointer-events-none'">
                                    <input v-model="schedule.start_time" type="time" class="px-3 py-1.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-yellow-200 focus:border-transparent" />
                                    <span class="text-gray-400">to</span>
                                    <input v-model="schedule.end_time" type="time" class="px-3 py-1.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-yellow-200 focus:border-transparent" />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ==================== SERVICE RATES ==================== -->
                <div v-show="activeSection === 'rates'">
                    <div class="bg-white rounded-lg shadow-sm p-6">
                        <div class="flex items-center justify-between mb-4">
                            <div>
                                <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wider">{{ $t('a_per_service_commission_rates') }}</h3>
                                <p class="text-xs text-gray-400 mt-1">Override the default commission rate for specific services.</p>
                            </div>
                            <button type="button" @click="addServiceRate" class="inline-flex items-center px-3 py-1.5 rounded-lg text-white text-xs font-medium transition" style="background-color: #C4A265;">
                                <svg class="w-3.5 h-3.5 ltr:mr-1 rtl:ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                                Add Rate
                            </button>
                        </div>
                        <div v-if="form.service_rates.length > 0" class="space-y-3">
                            <div v-for="(rate, i) in form.service_rates" :key="i" class="flex items-center gap-4 p-4 rounded-lg border border-gray-200 bg-gray-50">
                                <div class="flex-1 grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-xs font-medium text-gray-500 mb-1">Service</label>
                                        <SearchableSelect v-model="rate.service_id" :options="serviceRateOptions(i)" placeholder="-- Select Service --" searchPlaceholder="Search services..." />
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-500 mb-1">Commission Rate (%)</label>
                                        <input v-model="rate.commission_percentage" type="number" step="0.01" min="0" max="100" placeholder="0.00" class="w-full px-3 py-1.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-yellow-200 focus:border-transparent" />
                                    </div>
                                </div>
                                <button type="button" @click="removeServiceRate(i)" class="mt-5 p-1.5 text-red-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition" title="Remove">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                </button>
                            </div>
                        </div>
                        <p v-else class="text-sm text-gray-400 text-center py-8">No custom service rates configured.</p>
                    </div>
                </div>

                <!-- Save Button -->
                <div class="mt-6 flex items-center justify-between bg-white rounded-lg shadow-sm p-4">
                    <p v-if="Object.keys(form.errors).length > 0" class="text-sm text-red-600 font-medium">
                        {{ $t('a_please_fix') }} {{ Object.keys(form.errors).length }} {{ $t('a_errors_before_saving') }}
                    </p>
                    <span v-else></span>
                    <div class="flex ltr:space-x-3 rtl:space-x-reverse rtl:space-x-3">
                        <Link href="/webmaster/doctors" class="px-4 py-2.5 rounded-lg bg-gray-200 text-gray-700 text-sm font-medium hover:bg-gray-300 transition">{{ $t('a_cancel') }}</Link>
                        <button type="submit" :disabled="form.processing" class="px-6 py-2.5 rounded-lg text-white font-medium text-sm transition disabled:opacity-50" style="background-color: #C4A265;">
                            {{ form.processing ? $t('a_creating') : $t('a_create_doctor') }}
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </WebmasterLayout>
</template>
