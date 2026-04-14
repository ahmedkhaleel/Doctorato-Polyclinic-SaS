import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

export function useSettings() {
    const page = usePage();

    const settings = computed(() => page.props.settings || {});

    function setting(key, defaultValue = '') {
        return settings.value[key] || defaultValue;
    }

    const phone1 = computed(() => setting('phone_1', '01007729159'));
    const phone2 = computed(() => setting('phone_2', '0238244047'));
    const whatsapp = computed(() => setting('whatsapp', '01007729159'));
    const email = computed(() => setting('email', 'info@doctorato.com'));
    const facebook = computed(() => setting('facebook', 'https://www.facebook.com/doctoratoclinic'));
    const instagram = computed(() => setting('instagram', 'https://www.instagram.com/doctoratoclinic'));
    const tiktok = computed(() => setting('tiktok', 'https://www.tiktok.com/@doctoratoclinic'));
    const googleMaps = computed(() => setting('google_maps', 'https://maps.app.goo.gl/AGMjNFK4ketaUnGH8'));

    const whatsappLink = computed(() => {
        const number = whatsapp.value.replace(/^0/, '20');
        return `https://wa.me/${number}?text=${encodeURIComponent('مرحباً، أريد الاستفسار عن خدمات عيادة دكتوراتو')}`;
    });

    return {
        settings,
        setting,
        phone1,
        phone2,
        whatsapp,
        email,
        facebook,
        instagram,
        tiktok,
        googleMaps,
        whatsappLink,
    };
}
