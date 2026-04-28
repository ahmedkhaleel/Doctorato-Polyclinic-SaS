<script setup>
/**
 * Inline display of a patient's phone number with a click-to-call link
 * AND a click-to-WhatsApp icon next to it. Egyptian-context: phones
 * stored as "01012345678" still produce valid wa.me links.
 *
 * Usage:
 *   <PhoneWithWhatsApp :phone="patient.phone" />
 *   <PhoneWithWhatsApp :phone="patient.phone" message="Reminder for tomorrow" />
 *   <PhoneWithWhatsApp :phone="patient.phone" :show-whatsapp="false" />
 */
import { computed } from 'vue';
import { useWhatsApp } from '@/Composables/useWhatsApp';

const props = defineProps({
    phone: { type: String, default: '' },
    message: { type: String, default: '' },
    showWhatsapp: { type: Boolean, default: true },
    /** when true, render the phone number as a tel: link */
    callable: { type: Boolean, default: true },
    /** "primary" inline style or "compact" pill style */
    variant: { type: String, default: 'primary' },
});

const { whatsappLink } = useWhatsApp();
const waLink = computed(() => whatsappLink(props.phone, props.message));
</script>

<template>
    <span v-if="phone" :dir="'ltr'" class="inline-flex items-center gap-1.5">
        <a v-if="callable" :href="`tel:${phone}`"
           class="text-inherit hover:text-emerald-600 transition-colors">
            <slot>{{ phone }}</slot>
        </a>
        <span v-else><slot>{{ phone }}</slot></span>

        <a v-if="showWhatsapp && waLink" :href="waLink" target="_blank" rel="noopener"
           :title="'WhatsApp'"
           :class="variant === 'compact'
               ? 'inline-flex items-center justify-center w-5 h-5 rounded-full bg-emerald-500 hover:bg-emerald-600 text-white'
               : 'inline-flex items-center justify-center w-6 h-6 rounded-md bg-emerald-50 hover:bg-emerald-100 text-emerald-600 transition-colors'">
            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 24 24">
                <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51l-.57-.01c-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/>
            </svg>
        </a>
    </span>
</template>
