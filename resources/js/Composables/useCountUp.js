import { ref, onMounted } from 'vue';

/**
 * Shared "count-up" entrance animation for dashboard stat numbers — the
 * reusable motion primitive for the doctor/admin panels. Eases each target
 * value from 0 with easeOutCubic, and honours prefers-reduced-motion (jumps
 * straight to the final values).
 *
 * Usage:
 *   const { values, mounted } = useCountUp({ patients: 12, visits: 4 });
 *   // template: {{ values.patients }}  +  :class="mounted ? '...' : '...'"
 *
 * @param {Record<string, number>} targets  key → final numeric value
 * @param {number} duration                 animation length in ms
 */
export function useCountUp(targets = {}, duration = 1100) {
    const values = ref(Object.fromEntries(Object.keys(targets).map((k) => [k, 0])));
    const mounted = ref(false);

    onMounted(() => {
        setTimeout(() => { mounted.value = true; }, 50);

        const reduce = window.matchMedia?.('(prefers-reduced-motion: reduce)').matches;
        if (reduce) {
            values.value = { ...targets };
            return;
        }

        const start = performance.now();
        const step = (now) => {
            const progress = Math.min((now - start) / duration, 1);
            const eased = 1 - Math.pow(1 - progress, 3); // easeOutCubic
            for (const k in targets) {
                values.value[k] = Math.round((targets[k] || 0) * eased);
            }
            if (progress < 1) requestAnimationFrame(step);
        };
        requestAnimationFrame(step);
    });

    return { values, mounted };
}
