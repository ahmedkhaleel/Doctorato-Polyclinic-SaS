<script setup>
import { computed } from 'vue';
import { Head } from '@inertiajs/vue3';

const props = defineProps({
    type: {
        type: String,
        default: 'MedicalBusiness',
    },
    data: {
        type: Object,
        default: () => ({}),
    },
});

const medicalBusinessSchema = {
    '@context': 'https://schema.org',
    '@type': 'MedicalBusiness',
    'name': 'Doctorato Polyclinic',
    'alternateName': '\u0639\u064A\u0627\u062F\u0629 \u062F\u0643\u062A\u0648\u0631\u0627\u062A\u0648 \u0627\u0644\u062A\u062E\u0635\u0635\u064A\u0629',
    'image': '/images/clinic-photo.jpg',
    'address': {
        '@type': 'PostalAddress',
        'streetAddress': '6th of October City',
        'addressLocality': 'Giza',
        'addressCountry': 'EG',
    },
    'telephone': '+201234567890',
    'url': 'https://doctorato.com',
    'priceRange': '$$',
    'medicalSpecialty': ['Dermatology', 'Dentistry', 'Pediatrics'],
    'openingHours': 'Sa-Th 10:00-22:00',
};

const articleSchema = computed(() => ({
    '@context': 'https://schema.org',
    '@type': 'Article',
    'headline': props.data.title || '',
    'description': props.data.description || '',
    'author': {
        '@type': 'Person',
        'name': props.data.author || 'Doctorato Polyclinic',
    },
    'datePublished': props.data.datePublished || '',
    'dateModified': props.data.dateModified || props.data.datePublished || '',
    'image': props.data.image || '/images/og-default.jpg',
    'publisher': {
        '@type': 'Organization',
        'name': 'Doctorato Polyclinic',
        'logo': {
            '@type': 'ImageObject',
            'url': 'https://doctorato.com/images/logo.png',
        },
    },
    'mainEntityOfPage': {
        '@type': 'WebPage',
        '@id': typeof window !== 'undefined' ? window.location.href : '',
    },
}));

const serviceSchema = computed(() => ({
    '@context': 'https://schema.org',
    '@type': 'MedicalProcedure',
    'name': props.data.name || '',
    'description': props.data.description || '',
    'image': props.data.image || '/images/og-default.jpg',
    'provider': {
        '@type': 'MedicalBusiness',
        'name': 'Doctorato Polyclinic',
        'url': 'https://doctorato.com',
    },
}));

const jsonLd = computed(() => {
    switch (props.type) {
        case 'Article':
            return articleSchema.value;
        case 'Service':
            return serviceSchema.value;
        case 'MedicalBusiness':
        default:
            return medicalBusinessSchema;
    }
});

const jsonLdString = computed(() => JSON.stringify(jsonLd.value));
</script>

<template>
    <Head>
        <script type="application/ld+json" v-html="jsonLdString" />
    </Head>
</template>
