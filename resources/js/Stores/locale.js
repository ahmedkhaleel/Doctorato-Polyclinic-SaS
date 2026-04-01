import { defineStore } from 'pinia';
import { ref, computed } from 'vue';

export const useLocaleStore = defineStore('locale', () => {
    const locale = ref('ar');
    const dir = computed(() => locale.value === 'ar' ? 'rtl' : 'ltr');
    const isRtl = computed(() => dir.value === 'rtl');

    function setLocale(newLocale) {
        locale.value = newLocale;
    }

    function toggleLocale() {
        locale.value = locale.value === 'ar' ? 'en' : 'ar';
    }

    return {
        locale,
        dir,
        isRtl,
        setLocale,
        toggleLocale,
    };
});
