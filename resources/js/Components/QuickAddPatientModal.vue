<script setup>
import { ref, watch, computed, onMounted, onUnmounted, nextTick } from 'vue';
import { useNationalities } from '@/Composables/useNationalities.js';
import { usePage } from '@inertiajs/vue3';
import { Transition } from 'vue';

const { nationalities } = useNationalities();

const props = defineProps({
    show: { type: Boolean, default: false },
    theme: { type: String, default: 'gold' }, // 'gold' or 'teal'
    prefill: { type: Object, default: () => ({}) },
    submitUrl: { type: String, required: true },
});

const emit = defineEmits(['close', 'created']);

const processing = ref(false);
const errors = ref({});

// Translation helper
const page = usePage();
function $t(key) {
    return page.props.translations?.[key] || key;
}

const form = ref(defaultForm());

function defaultForm() {
    return {
        full_name: '',
        phone: '',
        phone2: '',
        email: '',
        date_of_birth: '',
        gender: 'female',
        nationality: 'Egyptian',
        address: '',
        occupation: '',
        referral_source: 'walk_in',
        referred_by: '',
        medical_notes: '',
    };
}

// ── Searchable dropdown system ──
const openDropdown = ref(null);
const dropdownSearches = ref({});

function toggleDropdown(key) {
    if (openDropdown.value === key) {
        openDropdown.value = null;
    } else {
        openDropdown.value = key;
        dropdownSearches.value[key] = '';
        nextTick(() => {
            const el = document.querySelector(`.qap-dd[data-key="${key}"] input`);
            if (el) el.focus();
        });
    }
}

function handleClickOutside(e) {
    if (openDropdown.value && !e.target.closest('.qap-dd')) {
        openDropdown.value = null;
    }
}

onMounted(() => document.addEventListener('click', handleClickOutside));
onUnmounted(() => document.removeEventListener('click', handleClickOutside));

// Gender options
const genderOptions = computed(() => [
    { value: 'female', label: $t('a_female'), icon: '♀' },
    { value: 'male', label: $t('a_male'), icon: '♂' },
]);

function getGenderLabel(val) {
    const g = genderOptions.value.find(o => o.value === val);
    return g ? g.label : '';
}

// Referral source options
const referralOptions = computed(() => [
    { value: 'walk_in', label: $t('a_walk_in'), icon: '🚶' },
    { value: 'social_media', label: $t('a_social_media'), icon: '📱' },
    { value: 'google', label: $t('a_google'), icon: '🔍' },
    { value: 'friend', label: $t('a_friend'), icon: '👥' },
    { value: 'doctor', label: $t('a_doctor_referral'), icon: '🩺' },
    { value: 'advertisement', label: $t('a_advertisement'), icon: '📢' },
    { value: 'other', label: $t('a_other'), icon: '📋' },
]);

function getReferralLabel(val) {
    const r = referralOptions.value.find(o => o.value === val);
    return r ? r.label : '';
}

function filteredReferralOptions(key) {
    const q = (dropdownSearches.value[key] || '').toLowerCase();
    if (!q) return referralOptions.value;
    return referralOptions.value.filter(o => o.label.toLowerCase().includes(q));
}

// Nationality search
function filteredNationalities(key) {
    const q = (dropdownSearches.value[key] || '').toLowerCase();
    if (!q) return nationalities;
    return nationalities.filter(n => n.toLowerCase().includes(q));
}

// Reset form when modal opens
watch(() => props.show, (val) => {
    if (val) {
        form.value = {
            ...defaultForm(),
            full_name: props.prefill.full_name || '',
            phone: props.prefill.phone || '',
            email: props.prefill.email || '',
        };
        errors.value = {};
        openDropdown.value = null;
    }
});

const showReferredBy = computed(() => {
    return form.value.referral_source === 'friend' || form.value.referral_source === 'doctor';
});

// Allow only digits, +, spaces, dashes in phone fields
function filterPhone(field) {
    form.value[field] = form.value[field].replace(/[^0-9+\-\s]/g, '');
}

// Theme classes
const themeGradient = computed(() =>
    props.theme === 'teal'
        ? 'background: linear-gradient(135deg, #0d9488, #14b8a6);'
        : 'background: linear-gradient(135deg, #C4A265, #D4B87A);'
);
const themeBtnBg = computed(() =>
    props.theme === 'teal' ? 'background-color: #0d9488;' : 'background-color: #C4A265;'
);
const themeFocusRing = computed(() =>
    props.theme === 'teal' ? 'focus:ring-teal-200' : 'focus:ring-yellow-200'
);
const themeHoverBg = computed(() =>
    props.theme === 'teal' ? 'hover:bg-teal-50' : 'hover:bg-amber-50'
);
const themeActiveBg = computed(() =>
    props.theme === 'teal' ? 'bg-teal-50/60' : 'bg-amber-50/60'
);
const themeRingSearch = computed(() =>
    props.theme === 'teal' ? 'focus:ring-teal-200' : 'focus:ring-yellow-200'
);

function validate() {
    const errs = {};
    if (!form.value.full_name.trim()) errs.full_name = [$t('a_full_name_required')];
    if (!form.value.phone.trim()) {
        errs.phone = [$t('a_phone_required')];
    } else if (!/^[0-9+\-\s]+$/.test(form.value.phone)) {
        errs.phone = [$t('a_phone_numbers_only')];
    }
    if (form.value.phone2 && !/^[0-9+\-\s]*$/.test(form.value.phone2)) {
        errs.phone2 = [$t('a_phone_numbers_only')];
    }
    if (form.value.email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.value.email)) {
        errs.email = [$t('a_invalid_email')];
    }
    if (!form.value.gender) errs.gender = [$t('a_gender_required')];
    return errs;
}

async function submit() {
    processing.value = true;
    errors.value = {};

    // Client-side validation
    const clientErrors = validate();
    if (Object.keys(clientErrors).length > 0) {
        errors.value = clientErrors;
        processing.value = false;
        return;
    }

    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
        if (!csrfToken) {
            errors.value = { general: $t('a_session_expired') };
            return;
        }

        const response = await fetch(props.submitUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify(form.value),
        });

        const contentType = response.headers.get('content-type') || '';
        if (!contentType.includes('application/json')) {
            errors.value = { general: $t('a_unexpected_response') };
            return;
        }

        const data = await response.json();

        if (!response.ok) {
            if (data.errors) {
                errors.value = data.errors;
            } else if (data.message) {
                errors.value = { general: data.message };
            } else {
                errors.value = { general: $t('a_create_patient_failed') + response.status };
            }
            return;
        }

        emit('created', data.patient);
        emit('close');
    } catch (err) {
        console.error('Quick patient create error:', err);
        errors.value = { general: $t('a_network_error') };
    } finally {
        processing.value = false;
    }
}
</script>

<template>
    <Teleport to="body">
        <div v-if="show" class="fixed inset-0 z-[100] flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="$emit('close')"></div>
            <div class="relative w-full max-w-2xl bg-white rounded-2xl shadow-2xl overflow-hidden max-h-[90vh] flex flex-col">
                <!-- Modal Header -->
                <div class="px-6 py-4 text-white flex-shrink-0" :style="themeGradient">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-white/20 rounded-xl flex items-center justify-center">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" /></svg>
                            </div>
                            <div>
                                <h3 class="text-lg font-bold">{{ $t('a_add_new_patient') }}</h3>
                                <p class="text-white/80 text-xs">{{ $t('a_complete_patient_registration') }}</p>
                            </div>
                        </div>
                        <button @click="$emit('close')" class="text-white/80 hover:text-white transition">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                        </button>
                    </div>
                </div>

                <!-- Modal Body (scrollable) -->
                <div class="p-6 overflow-y-auto flex-1">
                    <!-- General error -->
                    <div v-if="errors.general" class="mb-4 p-3 bg-red-50 border border-red-200 rounded-xl text-xs text-red-600 flex items-center gap-2">
                        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        {{ errors.general }}
                    </div>

                    <!-- Section: Personal Information -->
                    <div class="mb-6">
                        <div class="flex items-center gap-2 mb-4 pb-2 border-b border-gray-100">
                            <div class="w-6 h-6 rounded-lg flex items-center justify-center" :style="themeBtnBg + 'opacity:0.15'">
                                <svg class="w-3.5 h-3.5" :style="'color:' + (theme === 'teal' ? '#0d9488' : '#C4A265')" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                            </div>
                            <h4 class="text-xs font-bold text-gray-600 uppercase tracking-wider">{{ $t('a_personal_information') }}</h4>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <!-- Full Name -->
                            <div class="sm:col-span-2">
                                <label class="block text-xs font-semibold text-gray-600 mb-1.5">{{ $t('a_full_name') }} <span class="text-red-500">*</span></label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 start-0 flex items-center ps-3.5 pointer-events-none">
                                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                                    </div>
                                    <input v-model="form.full_name" type="text"  class="doctorato-input" :class="['w-full ps-10 pe-4 py-2.5 bg-gray-50 border rounded-xl text-sm transition-all duration-200 focus:ring-2 focus:border-transparent focus:bg-white', errors.full_name ? 'border-red-300 bg-red-50/30' : 'border-gray-200 hover:border-gray-300', themeFocusRing]" :placeholder="$t('a_patient_full_name')" />
                                </div>
                                <p v-if="errors.full_name" class="text-xs text-red-500 mt-1 flex items-center gap-1">
                                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" /></svg>
                                    {{ Array.isArray(errors.full_name) ? errors.full_name[0] : errors.full_name }}
                                </p>
                            </div>

                            <!-- Gender - Searchable Dropdown -->
                            <div class="relative qap-dd" data-key="gender">
                                <label class="block text-xs font-semibold text-gray-600 mb-1.5">{{ $t('a_gender') }} <span class="text-red-500">*</span></label>
                                <button type="button" @click.stop="toggleDropdown('gender')"
                                    :class="['w-full flex items-center justify-between px-3.5 py-2.5 border rounded-xl text-sm bg-gray-50 transition-all duration-200 focus:ring-2 focus:border-transparent focus:bg-white', errors.gender ? 'border-red-300' : 'border-gray-200 hover:border-gray-300', themeFocusRing]">
                                    <span class="flex items-center gap-2" :class="form.gender ? 'text-gray-800' : 'text-gray-400'">
                                        <span v-if="form.gender" class="text-base">{{ form.gender === 'female' ? '♀' : '♂' }}</span>
                                        {{ getGenderLabel(form.gender) || $t('a_gender') }}
                                    </span>
                                    <svg class="w-4 h-4 text-gray-400 flex-shrink-0 transition-transform" :class="openDropdown === 'gender' ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                                </button>
                                <Transition enter-active-class="transition duration-150" enter-from-class="opacity-0 -translate-y-1" enter-to-class="opacity-100 translate-y-0" leave-active-class="transition duration-100" leave-from-class="opacity-100" leave-to-class="opacity-0">
                                    <div v-if="openDropdown === 'gender'" class="absolute z-50 left-0 right-0 mt-1 bg-white border border-gray-200 rounded-xl shadow-xl overflow-hidden">
                                        <div class="py-1">
                                            <button v-for="opt in genderOptions" :key="opt.value" type="button"
                                                @click="form.gender = opt.value; openDropdown = null"
                                                class="w-full flex items-center gap-2.5 px-3.5 py-2.5 text-sm text-start transition"
                                                :class="[form.gender === opt.value ? themeActiveBg + ' font-semibold' : '', themeHoverBg]">
                                                <span class="text-base w-5 text-center">{{ opt.icon }}</span>
                                                <span>{{ opt.label }}</span>
                                                <svg v-if="form.gender === opt.value" class="w-4 h-4 ms-auto" :style="'color:' + (theme === 'teal' ? '#0d9488' : '#C4A265')" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                                            </button>
                                        </div>
                                    </div>
                                </Transition>
                                <p v-if="errors.gender" class="text-xs text-red-500 mt-1 flex items-center gap-1">
                                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" /></svg>
                                    {{ Array.isArray(errors.gender) ? errors.gender[0] : errors.gender }}
                                </p>
                            </div>

                            <!-- Date of Birth -->
                            <div>
                                <label class="block text-xs font-semibold text-gray-600 mb-1.5">{{ $t('a_date_of_birth') }}</label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 start-0 flex items-center ps-3.5 pointer-events-none">
                                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                                    </div>
                                    <input v-model="form.date_of_birth" type="date"  class="doctorato-input" :class="['w-full ps-10 pe-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm transition-all duration-200 hover:border-gray-300 focus:ring-2 focus:border-transparent focus:bg-white', themeFocusRing]" />
                                </div>
                            </div>

                            <!-- Nationality - Searchable Dropdown -->
                            <div class="relative qap-dd" data-key="nationality">
                                <label class="block text-xs font-semibold text-gray-600 mb-1.5">{{ $t('a_nationality') }}</label>
                                <button type="button" @click.stop="toggleDropdown('nationality')"
                                    :class="['w-full flex items-center justify-between px-3.5 py-2.5 border rounded-xl text-sm bg-gray-50 transition-all duration-200 focus:ring-2 focus:border-transparent focus:bg-white border-gray-200 hover:border-gray-300', themeFocusRing]">
                                    <span class="flex items-center gap-2" :class="form.nationality ? 'text-gray-800' : 'text-gray-400'">
                                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                        <span class="truncate">{{ form.nationality || $t('a_nationality') }}</span>
                                    </span>
                                    <svg class="w-4 h-4 text-gray-400 flex-shrink-0 transition-transform" :class="openDropdown === 'nationality' ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                                </button>
                                <Transition enter-active-class="transition duration-150" enter-from-class="opacity-0 -translate-y-1" enter-to-class="opacity-100 translate-y-0" leave-active-class="transition duration-100" leave-from-class="opacity-100" leave-to-class="opacity-0">
                                    <div v-if="openDropdown === 'nationality'" class="absolute z-50 left-0 right-0 mt-1 bg-white border border-gray-200 rounded-xl shadow-xl overflow-hidden">
                                        <div class="p-2 border-b border-gray-100">
                                            <div class="relative">
                                                <svg class="absolute start-2.5 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                                                <input v-model="dropdownSearches['nationality']" type="text" :placeholder="$t('search') + '...'"  class="doctorato-input" :class="['w-full ps-8 pe-3 py-1.5 text-sm border border-gray-200 rounded-lg focus:ring-1 focus:border-transparent', themeRingSearch]" @click.stop />
                                            </div>
                                        </div>
                                        <div class="max-h-48 overflow-y-auto">
                                            <button v-for="n in filteredNationalities('nationality')" :key="n" type="button"
                                                @click="form.nationality = n; openDropdown = null"
                                                class="w-full flex items-center justify-between px-3.5 py-2 text-sm text-start transition"
                                                :class="[form.nationality === n ? themeActiveBg + ' font-semibold' : '', themeHoverBg]">
                                                <span class="truncate">{{ n }}</span>
                                                <svg v-if="form.nationality === n" class="w-4 h-4 flex-shrink-0" :style="'color:' + (theme === 'teal' ? '#0d9488' : '#C4A265')" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                                            </button>
                                            <p v-if="!filteredNationalities('nationality').length" class="text-xs text-gray-400 text-center py-3">{{ $t('a_no_results') }}</p>
                                        </div>
                                    </div>
                                </Transition>
                            </div>

                            <!-- Occupation -->
                            <div>
                                <label class="block text-xs font-semibold text-gray-600 mb-1.5">{{ $t('a_occupation') }}</label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 start-0 flex items-center ps-3.5 pointer-events-none">
                                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg>
                                    </div>
                                    <input v-model="form.occupation" type="text"  class="doctorato-input" :class="['w-full ps-10 pe-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm transition-all duration-200 hover:border-gray-300 focus:ring-2 focus:border-transparent focus:bg-white', themeFocusRing]" :placeholder="$t('a_occupation_placeholder')" />
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Section: Contact Information -->
                    <div class="mb-6">
                        <div class="flex items-center gap-2 mb-4 pb-2 border-b border-gray-100">
                            <div class="w-6 h-6 rounded-lg flex items-center justify-center" :style="themeBtnBg + 'opacity:0.15'">
                                <svg class="w-3.5 h-3.5" :style="'color:' + (theme === 'teal' ? '#0d9488' : '#C4A265')" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" /></svg>
                            </div>
                            <h4 class="text-xs font-bold text-gray-600 uppercase tracking-wider">{{ $t('a_contact_information') }}</h4>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <!-- Phone -->
                            <div>
                                <label class="block text-xs font-semibold text-gray-600 mb-1.5">{{ $t('a_phone') }} <span class="text-red-500">*</span></label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 start-0 flex items-center ps-3.5 pointer-events-none">
                                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" /></svg>
                                    </div>
                                    <input v-model="form.phone" @input="filterPhone('phone')" type="tel"  class="doctorato-input" :class="['w-full ps-10 pe-4 py-2.5 bg-gray-50 border rounded-xl text-sm transition-all duration-200 focus:ring-2 focus:border-transparent focus:bg-white', errors.phone ? 'border-red-300 bg-red-50/30' : 'border-gray-200 hover:border-gray-300', themeFocusRing]" placeholder="01xxxxxxxxx" dir="ltr" />
                                </div>
                                <p v-if="errors.phone" class="text-xs text-red-500 mt-1 flex items-center gap-1">
                                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" /></svg>
                                    {{ Array.isArray(errors.phone) ? errors.phone[0] : errors.phone }}
                                </p>
                            </div>
                            <!-- Phone 2 -->
                            <div>
                                <label class="block text-xs font-semibold text-gray-600 mb-1.5">{{ $t('a_phone2') }}</label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 start-0 flex items-center ps-3.5 pointer-events-none">
                                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" /></svg>
                                    </div>
                                    <input v-model="form.phone2" @input="filterPhone('phone2')" type="tel"  class="doctorato-input" :class="['w-full ps-10 pe-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm transition-all duration-200 hover:border-gray-300 focus:ring-2 focus:border-transparent focus:bg-white', themeFocusRing]" :placeholder="$t('a_optional_second_number')" dir="ltr" />
                                </div>
                                <p v-if="errors.phone2" class="text-xs text-red-500 mt-1 flex items-center gap-1">
                                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" /></svg>
                                    {{ Array.isArray(errors.phone2) ? errors.phone2[0] : errors.phone2 }}
                                </p>
                            </div>
                            <!-- Email -->
                            <div>
                                <label class="block text-xs font-semibold text-gray-600 mb-1.5">{{ $t('a_email') }}</label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 start-0 flex items-center ps-3.5 pointer-events-none">
                                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg>
                                    </div>
                                    <input v-model="form.email" type="email"  class="doctorato-input" :class="['w-full ps-10 pe-4 py-2.5 bg-gray-50 border rounded-xl text-sm transition-all duration-200 focus:ring-2 focus:border-transparent focus:bg-white', errors.email ? 'border-red-300 bg-red-50/30' : 'border-gray-200 hover:border-gray-300', themeFocusRing]" placeholder="patient@email.com" dir="ltr" />
                                </div>
                                <p v-if="errors.email" class="text-xs text-red-500 mt-1 flex items-center gap-1">
                                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" /></svg>
                                    {{ Array.isArray(errors.email) ? errors.email[0] : errors.email }}
                                </p>
                            </div>
                            <!-- Address -->
                            <div>
                                <label class="block text-xs font-semibold text-gray-600 mb-1.5">{{ $t('a_address') }}</label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 start-0 flex items-center ps-3.5 pointer-events-none">
                                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                                    </div>
                                    <input v-model="form.address" type="text"  class="doctorato-input" :class="['w-full ps-10 pe-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm transition-all duration-200 hover:border-gray-300 focus:ring-2 focus:border-transparent focus:bg-white', themeFocusRing]" :placeholder="$t('a_full_address')" />
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Section: Referral Information -->
                    <div class="mb-6">
                        <div class="flex items-center gap-2 mb-4 pb-2 border-b border-gray-100">
                            <div class="w-6 h-6 rounded-lg flex items-center justify-center" :style="themeBtnBg + 'opacity:0.15'">
                                <svg class="w-3.5 h-3.5" :style="'color:' + (theme === 'teal' ? '#0d9488' : '#C4A265')" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" /></svg>
                            </div>
                            <h4 class="text-xs font-bold text-gray-600 uppercase tracking-wider">{{ $t('a_referral_information') }}</h4>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <!-- Referral Source - Searchable Dropdown -->
                            <div class="relative qap-dd" data-key="referral">
                                <label class="block text-xs font-semibold text-gray-600 mb-1.5">{{ $t('a_referral_source') }}</label>
                                <button type="button" @click.stop="toggleDropdown('referral')"
                                    :class="['w-full flex items-center justify-between px-3.5 py-2.5 border rounded-xl text-sm bg-gray-50 transition-all duration-200 focus:ring-2 focus:border-transparent focus:bg-white border-gray-200 hover:border-gray-300', themeFocusRing]">
                                    <span class="flex items-center gap-2 text-gray-800">
                                        <span class="text-base">{{ referralOptions.find(o => o.value === form.referral_source)?.icon || '📋' }}</span>
                                        {{ getReferralLabel(form.referral_source) || $t('a_referral_source') }}
                                    </span>
                                    <svg class="w-4 h-4 text-gray-400 flex-shrink-0 transition-transform" :class="openDropdown === 'referral' ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                                </button>
                                <Transition enter-active-class="transition duration-150" enter-from-class="opacity-0 -translate-y-1" enter-to-class="opacity-100 translate-y-0" leave-active-class="transition duration-100" leave-from-class="opacity-100" leave-to-class="opacity-0">
                                    <div v-if="openDropdown === 'referral'" class="absolute z-50 left-0 right-0 mt-1 bg-white border border-gray-200 rounded-xl shadow-xl overflow-hidden">
                                        <div class="p-2 border-b border-gray-100">
                                            <div class="relative">
                                                <svg class="absolute start-2.5 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                                                <input v-model="dropdownSearches['referral']" type="text" :placeholder="$t('search') + '...'"  class="doctorato-input" :class="['w-full ps-8 pe-3 py-1.5 text-sm border border-gray-200 rounded-lg focus:ring-1 focus:border-transparent', themeRingSearch]" @click.stop />
                                            </div>
                                        </div>
                                        <div class="max-h-48 overflow-y-auto py-1">
                                            <button v-for="opt in filteredReferralOptions('referral')" :key="opt.value" type="button"
                                                @click="form.referral_source = opt.value; openDropdown = null"
                                                class="w-full flex items-center gap-2.5 px-3.5 py-2.5 text-sm text-start transition"
                                                :class="[form.referral_source === opt.value ? themeActiveBg + ' font-semibold' : '', themeHoverBg]">
                                                <span class="text-base w-5 text-center">{{ opt.icon }}</span>
                                                <span>{{ opt.label }}</span>
                                                <svg v-if="form.referral_source === opt.value" class="w-4 h-4 ms-auto" :style="'color:' + (theme === 'teal' ? '#0d9488' : '#C4A265')" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                                            </button>
                                            <p v-if="!filteredReferralOptions('referral').length" class="text-xs text-gray-400 text-center py-3">{{ $t('a_no_results') }}</p>
                                        </div>
                                    </div>
                                </Transition>
                            </div>

                            <!-- Referred By (conditional) -->
                            <div v-if="showReferredBy">
                                <label class="block text-xs font-semibold text-gray-600 mb-1.5">{{ $t('a_referred_by') }}</label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 start-0 flex items-center ps-3.5 pointer-events-none">
                                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                                    </div>
                                    <input v-model="form.referred_by" type="text"  class="doctorato-input" :class="['w-full ps-10 pe-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm transition-all duration-200 hover:border-gray-300 focus:ring-2 focus:border-transparent focus:bg-white', themeFocusRing]" :placeholder="form.referral_source === 'doctor' ? $t('a_doctor_name') : $t('a_friend_name')" />
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Section: Medical Notes -->
                    <div>
                        <div class="flex items-center gap-2 mb-4 pb-2 border-b border-gray-100">
                            <div class="w-6 h-6 rounded-lg flex items-center justify-center" :style="themeBtnBg + 'opacity:0.15'">
                                <svg class="w-3.5 h-3.5" :style="'color:' + (theme === 'teal' ? '#0d9488' : '#C4A265')" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                            </div>
                            <h4 class="text-xs font-bold text-gray-600 uppercase tracking-wider">{{ $t('a_medical_notes') }}</h4>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1.5">{{ $t('a_medical_notes_label') }}</label>
                            <div class="relative">
                                <textarea v-model="form.medical_notes" rows="3"  class="doctorato-input" :class="['w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm transition-all duration-200 hover:border-gray-300 focus:ring-2 focus:border-transparent focus:bg-white resize-none', themeFocusRing]" :placeholder="$t('a_medical_notes_placeholder')"></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="px-6 py-4 border-t border-gray-100 flex items-center justify-end gap-3 bg-gray-50/50 flex-shrink-0">
                    <button
                        type="button"
                        @click="$emit('close')"
                        class="px-5 py-2.5 text-sm font-medium text-gray-600 hover:text-gray-800 border border-gray-200 rounded-xl hover:bg-gray-50 transition-all duration-200 hover:shadow-sm"
                    >
                        {{ $t('a_cancel') }}
                    </button>
                    <button
                        type="button"
                        @click="submit"
                        :disabled="processing"
                        class="px-6 py-2.5 text-sm font-bold text-white rounded-xl hover:opacity-90 transition-all duration-200 shadow-lg hover:shadow-xl disabled:opacity-50 flex items-center gap-2"
                        :style="themeBtnBg"
                    >
                        <span v-if="processing" class="flex items-center gap-2">
                            <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            {{ $t('a_creating') }}
                        </span>
                        <template v-else>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" /></svg>
                            {{ $t('a_create_select_patient') }}
                        </template>
                    </button>
                </div>
            </div>
        </div>
    </Teleport>
</template>
