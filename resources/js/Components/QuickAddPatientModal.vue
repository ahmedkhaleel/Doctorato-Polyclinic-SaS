<script setup>
import { ref, watch, computed } from 'vue';
import { useNationalities } from '@/Composables/useNationalities.js';

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

function validate() {
    const errs = {};
    if (!form.value.full_name.trim()) errs.full_name = ['Full name is required.'];
    if (!form.value.phone.trim()) {
        errs.phone = ['Phone number is required.'];
    } else if (!/^[0-9+\-\s]+$/.test(form.value.phone)) {
        errs.phone = ['Phone must contain only numbers.'];
    }
    if (form.value.phone2 && !/^[0-9+\-\s]*$/.test(form.value.phone2)) {
        errs.phone2 = ['Phone must contain only numbers.'];
    }
    if (form.value.email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.value.email)) {
        errs.email = ['Please enter a valid email address.'];
    }
    if (!form.value.gender) errs.gender = ['Gender is required.'];
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
            errors.value = { general: 'Session expired. Please refresh the page and try again.' };
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
            errors.value = { general: 'Unexpected server response. Please refresh the page and try again.' };
            return;
        }

        const data = await response.json();

        if (!response.ok) {
            if (data.errors) {
                errors.value = data.errors;
            } else if (data.message) {
                errors.value = { general: data.message };
            } else {
                errors.value = { general: 'Failed to create patient. Status: ' + response.status };
            }
            return;
        }

        emit('created', data.patient);
        emit('close');
    } catch (err) {
        console.error('Quick patient create error:', err);
        errors.value = { general: 'Network error. Please check your connection and try again.' };
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
                                <h3 class="text-lg font-bold">Add New Patient</h3>
                                <p class="text-white/80 text-xs">Complete patient registration</p>
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
                    <div v-if="errors.general" class="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg text-xs text-red-600">
                        {{ errors.general }}
                    </div>

                    <!-- Section: Personal Information -->
                    <div class="mb-5">
                        <h4 class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-3 pb-1 border-b border-gray-100">Personal Information</h4>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <!-- Full Name -->
                            <div class="sm:col-span-2">
                                <label class="block text-xs font-medium text-gray-500 mb-1">Full Name <span class="text-red-500">*</span></label>
                                <input v-model="form.full_name" type="text" :class="['w-full px-4 py-2.5 bg-white border rounded-xl text-sm focus:ring-2 focus:border-transparent', errors.full_name ? 'border-red-300' : 'border-gray-200', themeFocusRing]" placeholder="Patient full name" />
                                <p v-if="errors.full_name" class="text-xs text-red-500 mt-1">{{ Array.isArray(errors.full_name) ? errors.full_name[0] : errors.full_name }}</p>
                            </div>
                            <!-- Gender -->
                            <div>
                                <label class="block text-xs font-medium text-gray-500 mb-1">Gender <span class="text-red-500">*</span></label>
                                <select v-model="form.gender" :class="['w-full px-4 py-2.5 bg-white border rounded-xl text-sm focus:ring-2 focus:border-transparent', errors.gender ? 'border-red-300' : 'border-gray-200', themeFocusRing]">
                                    <option value="female">Female</option>
                                    <option value="male">Male</option>
                                </select>
                                <p v-if="errors.gender" class="text-xs text-red-500 mt-1">{{ Array.isArray(errors.gender) ? errors.gender[0] : errors.gender }}</p>
                            </div>
                            <!-- Date of Birth -->
                            <div>
                                <label class="block text-xs font-medium text-gray-500 mb-1">Date of Birth</label>
                                <input v-model="form.date_of_birth" type="date" :class="['w-full px-4 py-2.5 bg-white border border-gray-200 rounded-xl text-sm focus:ring-2 focus:border-transparent', themeFocusRing]" />
                            </div>
                            <!-- Nationality -->
                            <div>
                                <label class="block text-xs font-medium text-gray-500 mb-1">Nationality</label>
                                <select v-model="form.nationality" :class="['w-full px-4 py-2.5 bg-white border border-gray-200 rounded-xl text-sm focus:ring-2 focus:border-transparent', themeFocusRing]">
                                    <option v-for="n in nationalities" :key="n" :value="n">{{ n }}</option>
                                </select>
                            </div>
                            <!-- Occupation -->
                            <div>
                                <label class="block text-xs font-medium text-gray-500 mb-1">Occupation</label>
                                <input v-model="form.occupation" type="text" :class="['w-full px-4 py-2.5 bg-white border border-gray-200 rounded-xl text-sm focus:ring-2 focus:border-transparent', themeFocusRing]" placeholder="e.g. Engineer, Teacher..." />
                            </div>
                        </div>
                    </div>

                    <!-- Section: Contact Information -->
                    <div class="mb-5">
                        <h4 class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-3 pb-1 border-b border-gray-100">Contact Information</h4>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <!-- Phone -->
                            <div>
                                <label class="block text-xs font-medium text-gray-500 mb-1">Phone <span class="text-red-500">*</span></label>
                                <input v-model="form.phone" @input="filterPhone('phone')" type="tel" :class="['w-full px-4 py-2.5 bg-white border rounded-xl text-sm focus:ring-2 focus:border-transparent', errors.phone ? 'border-red-300' : 'border-gray-200', themeFocusRing]" placeholder="01xxxxxxxxx" dir="ltr" />
                                <p v-if="errors.phone" class="text-xs text-red-500 mt-1">{{ Array.isArray(errors.phone) ? errors.phone[0] : errors.phone }}</p>
                            </div>
                            <!-- Phone 2 -->
                            <div>
                                <label class="block text-xs font-medium text-gray-500 mb-1">Phone 2</label>
                                <input v-model="form.phone2" @input="filterPhone('phone2')" type="tel" :class="['w-full px-4 py-2.5 bg-white border border-gray-200 rounded-xl text-sm focus:ring-2 focus:border-transparent', themeFocusRing]" placeholder="Optional second number" dir="ltr" />
                                <p v-if="errors.phone2" class="text-xs text-red-500 mt-1">{{ Array.isArray(errors.phone2) ? errors.phone2[0] : errors.phone2 }}</p>
                            </div>
                            <!-- Email -->
                            <div>
                                <label class="block text-xs font-medium text-gray-500 mb-1">Email</label>
                                <input v-model="form.email" type="email" :class="['w-full px-4 py-2.5 bg-white border rounded-xl text-sm focus:ring-2 focus:border-transparent', errors.email ? 'border-red-300' : 'border-gray-200', themeFocusRing]" placeholder="patient@email.com" dir="ltr" />
                                <p v-if="errors.email" class="text-xs text-red-500 mt-1">{{ Array.isArray(errors.email) ? errors.email[0] : errors.email }}</p>
                            </div>
                            <!-- Address -->
                            <div>
                                <label class="block text-xs font-medium text-gray-500 mb-1">Address</label>
                                <input v-model="form.address" type="text" :class="['w-full px-4 py-2.5 bg-white border border-gray-200 rounded-xl text-sm focus:ring-2 focus:border-transparent', themeFocusRing]" placeholder="Full address" />
                            </div>
                        </div>
                    </div>

                    <!-- Section: Referral Information -->
                    <div class="mb-5">
                        <h4 class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-3 pb-1 border-b border-gray-100">Referral Information</h4>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <!-- Referral Source -->
                            <div>
                                <label class="block text-xs font-medium text-gray-500 mb-1">Referral Source</label>
                                <select v-model="form.referral_source" :class="['w-full px-4 py-2.5 bg-white border border-gray-200 rounded-xl text-sm focus:ring-2 focus:border-transparent', themeFocusRing]">
                                    <option value="walk_in">Walk In</option>
                                    <option value="social_media">Social Media</option>
                                    <option value="google">Google</option>
                                    <option value="friend">Friend</option>
                                    <option value="doctor">Doctor</option>
                                    <option value="advertisement">Advertisement</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                            <!-- Referred By (conditional) -->
                            <div v-if="showReferredBy">
                                <label class="block text-xs font-medium text-gray-500 mb-1">Referred By</label>
                                <input v-model="form.referred_by" type="text" :class="['w-full px-4 py-2.5 bg-white border border-gray-200 rounded-xl text-sm focus:ring-2 focus:border-transparent', themeFocusRing]" :placeholder="form.referral_source === 'doctor' ? 'Doctor name' : 'Friend name'" />
                            </div>
                        </div>
                    </div>

                    <!-- Section: Medical Notes -->
                    <div>
                        <h4 class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-3 pb-1 border-b border-gray-100">Medical Notes</h4>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">Notes (allergies, chronic conditions, medications...)</label>
                            <textarea v-model="form.medical_notes" rows="3" :class="['w-full px-4 py-2.5 bg-white border border-gray-200 rounded-xl text-sm focus:ring-2 focus:border-transparent resize-none', themeFocusRing]" placeholder="Any important medical information..."></textarea>
                        </div>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="px-6 py-4 border-t border-gray-100 flex items-center justify-end gap-3 bg-gray-50/50 flex-shrink-0">
                    <button
                        type="button"
                        @click="$emit('close')"
                        class="px-4 py-2 text-sm font-medium text-gray-600 hover:text-gray-800 border border-gray-200 rounded-xl hover:bg-gray-50 transition"
                    >
                        Cancel
                    </button>
                    <button
                        type="button"
                        @click="submit"
                        :disabled="processing"
                        class="px-5 py-2 text-sm font-bold text-white rounded-xl hover:opacity-90 transition shadow-lg disabled:opacity-50"
                        :style="themeBtnBg"
                    >
                        <span v-if="processing" class="flex items-center gap-2">
                            <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Creating...
                        </span>
                        <span v-else>Create & Select Patient</span>
                    </button>
                </div>
            </div>
        </div>
    </Teleport>
</template>
