import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

export function useSettings() {
    const page = usePage();

    const settings = computed(() => page.props.settings || {});

    function setting(key, defaultValue = '') {
        return settings.value[key] || defaultValue;
    }

    const phone1 = computed(() => setting('phone_1', '+971557961688'));
    const phone2 = computed(() => setting('phone_2', ''));
    const whatsapp = computed(() => setting('whatsapp', '+971557961688'));
    const email = computed(() => setting('email', 'info@doctorato.com'));
    const facebook = computed(() => setting('facebook', 'https://www.facebook.com/doctoratoclinic'));
    const instagram = computed(() => setting('instagram', 'https://www.instagram.com/doctoratoclinic'));
    const tiktok = computed(() => setting('tiktok', 'https://www.tiktok.com/@doctoratoclinic'));
    const googleMaps = computed(() => setting('google_maps', 'https://maps.app.goo.gl/AGMjNFK4ketaUnGH8'));

    /**
     * Build a wa.me link from the whatsapp number.
     * - International format ('+971557961688') → strip the '+' and use as-is.
     * - Legacy EG format ('01xxxxxxxxx') → prepend country code '20'.
     */
    const whatsappLink = computed(() => {
        let digits = (whatsapp.value || '').toString().replace(/\D/g, ''); // remove +, spaces, dashes
        if (digits.startsWith('0')) {
            digits = '20' + digits.substring(1);
        }
        return `https://wa.me/${digits}?text=${encodeURIComponent('مرحباً، أريد الاستفسار عن خدمات عيادة دكتوراتو')}`;
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
