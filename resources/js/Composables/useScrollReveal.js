/**
 * useScrollReveal — Premium scroll-triggered animation composable
 *
 * Uses IntersectionObserver for performant, GPU-accelerated reveal animations.
 * Supports staggered children, parallax, counter animations, and magnetic hover.
 *
 * Usage:
 *   <div v-scroll-reveal>...</div>
 *   <div v-scroll-reveal="{ type: 'fade-up', delay: 200 }">...</div>
 *   <div v-scroll-reveal="{ type: 'stagger', staggerDelay: 80 }">...</div>
 *   <div v-scroll-reveal="{ type: 'counter', target: 500 }">...</div>
 *   <div v-magnetic>...</div>
 */
import { onMounted, onUnmounted } from 'vue';

// ─── Animation Presets ──────────────────────────────────────
const presets = {
    'fade-up':    { from: 'translate3d(0, 50px, 0) scale(0.97)', opacity: 0 },
    'fade-down':  { from: 'translate3d(0, -50px, 0) scale(0.97)', opacity: 0 },
    'fade-left':  { from: 'translate3d(-60px, 0, 0)', opacity: 0 },
    'fade-right': { from: 'translate3d(60px, 0, 0)', opacity: 0 },
    'zoom-in':    { from: 'scale(0.85)', opacity: 0 },
    'zoom-out':   { from: 'scale(1.15)', opacity: 0 },
    'flip-up':    { from: 'perspective(1200px) rotateX(20deg) translate3d(0, 30px, 0)', opacity: 0 },
    'flip-left':  { from: 'perspective(1200px) rotateY(15deg) translate3d(-30px, 0, 0)', opacity: 0 },
    'blur-in':    { from: 'translate3d(0, 20px, 0)', opacity: 0, filter: 'blur(12px)' },
    'slide-up':   { from: 'translate3d(0, 100%, 0)', opacity: 1, clipPath: 'inset(100% 0 0 0)' },
    'reveal-left':{ from: 'translate3d(0, 0, 0)', opacity: 1, clipPath: 'inset(0 100% 0 0)' },
    'reveal-right':{ from: 'translate3d(0, 0, 0)', opacity: 1, clipPath: 'inset(0 0 0 100%)' },
    'rotate-in':  { from: 'rotate(-8deg) scale(0.9) translate3d(0, 40px, 0)', opacity: 0 },
    'bounce-in':  { from: 'translate3d(0, 60px, 0) scale(0.9)', opacity: 0, easing: 'cubic-bezier(0.34, 1.56, 0.64, 1)' },
    'stagger':    { from: 'translate3d(0, 40px, 0) scale(0.96)', opacity: 0 },
};

// ─── Scroll Reveal Directive ────────────────────────────────
const observerMap = new WeakMap();

function applyInitialState(el, preset, opts) {
    el.style.willChange = 'transform, opacity, filter, clip-path';
    el.style.transition = 'none';
    el.style.opacity = preset.opacity ?? 0;
    el.style.transform = preset.from || '';
    if (preset.filter) el.style.filter = preset.filter;
    if (preset.clipPath) el.style.clipPath = preset.clipPath;
}

function animateIn(el, preset, opts) {
    const duration = opts.duration || 800;
    const delay = opts.delay || 0;
    const easing = preset.easing || opts.easing || 'cubic-bezier(0.22, 1, 0.36, 1)';

    requestAnimationFrame(() => {
        el.style.transition = [
            `transform ${duration}ms ${easing} ${delay}ms`,
            `opacity ${duration}ms ${easing} ${delay}ms`,
            preset.filter ? `filter ${duration}ms ${easing} ${delay}ms` : '',
            preset.clipPath ? `clip-path ${duration}ms ${easing} ${delay}ms` : '',
        ].filter(Boolean).join(', ');
        el.style.opacity = '1';
        el.style.transform = 'translate3d(0, 0, 0) scale(1) rotate(0deg)';
        if (preset.filter) el.style.filter = 'blur(0px)';
        if (preset.clipPath) el.style.clipPath = 'inset(0 0 0 0)';
    });

    // Cleanup will-change after animation
    setTimeout(() => {
        el.style.willChange = 'auto';
    }, duration + delay + 100);
}

function animateStagger(el, preset, opts) {
    const children = Array.from(el.children);
    const staggerDelay = opts.staggerDelay || 80;
    const duration = opts.duration || 700;
    const easing = preset.easing || opts.easing || 'cubic-bezier(0.22, 1, 0.36, 1)';
    const baseDelay = opts.delay || 0;

    children.forEach((child, i) => {
        child.style.willChange = 'transform, opacity';
        child.style.opacity = '0';
        child.style.transform = preset.from || 'translate3d(0, 40px, 0) scale(0.96)';

        const itemDelay = baseDelay + (i * staggerDelay);
        requestAnimationFrame(() => {
            child.style.transition = `transform ${duration}ms ${easing} ${itemDelay}ms, opacity ${duration}ms ${easing} ${itemDelay}ms`;
            child.style.opacity = '1';
            child.style.transform = 'translate3d(0, 0, 0) scale(1)';
        });

        setTimeout(() => {
            child.style.willChange = 'auto';
        }, duration + itemDelay + 100);
    });
}

function animateCounter(el, opts) {
    const target = opts.target || parseInt(el.textContent) || 0;
    const duration = opts.duration || 2000;
    const suffix = opts.suffix || '';
    const prefix = opts.prefix || '';
    const start = performance.now();

    el.style.opacity = '1';
    el.style.transform = 'none';

    function update(now) {
        const elapsed = now - start;
        const progress = Math.min(elapsed / duration, 1);
        // Ease out cubic
        const eased = 1 - Math.pow(1 - progress, 3);
        const current = Math.round(target * eased);
        el.textContent = prefix + current.toLocaleString() + suffix;
        if (progress < 1) requestAnimationFrame(update);
    }
    requestAnimationFrame(update);
}

const scrollRevealDirective = {
    mounted(el, binding) {
        // Respect reduced motion
        if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;

        const opts = typeof binding.value === 'object' ? binding.value : {};
        const type = opts.type || 'fade-up';
        const preset = presets[type] || presets['fade-up'];
        const threshold = opts.threshold || 0.15;
        const once = opts.once !== false; // default true

        if (type === 'counter') {
            el.style.opacity = '0';
        } else if (type === 'stagger') {
            // Don't hide parent, hide children
            Array.from(el.children).forEach(child => {
                child.style.opacity = '0';
                child.style.transform = preset.from || '';
            });
        } else {
            applyInitialState(el, preset, opts);
        }

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    if (type === 'counter') {
                        animateCounter(el, opts);
                    } else if (type === 'stagger') {
                        animateStagger(el, preset, opts);
                    } else {
                        animateIn(el, preset, opts);
                    }
                    if (once) observer.unobserve(el);
                }
            });
        }, { threshold, rootMargin: '0px 0px -40px 0px' });

        observer.observe(el);
        observerMap.set(el, observer);
    },

    unmounted(el) {
        const observer = observerMap.get(el);
        if (observer) {
            observer.unobserve(el);
            observerMap.delete(el);
        }
    },
};

// ─── Magnetic Hover Directive ───────────────────────────────
const magneticDirective = {
    mounted(el, binding) {
        if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;

        const strength = binding.value?.strength || 0.3;
        el.style.transition = 'transform 0.3s cubic-bezier(0.22, 1, 0.36, 1)';

        const onMove = (e) => {
            const rect = el.getBoundingClientRect();
            const x = e.clientX - rect.left - rect.width / 2;
            const y = e.clientY - rect.top - rect.height / 2;
            el.style.transform = `translate3d(${x * strength}px, ${y * strength}px, 0)`;
        };

        const onLeave = () => {
            el.style.transform = 'translate3d(0, 0, 0)';
        };

        el.addEventListener('mousemove', onMove);
        el.addEventListener('mouseleave', onLeave);
        el._magneticCleanup = () => {
            el.removeEventListener('mousemove', onMove);
            el.removeEventListener('mouseleave', onLeave);
        };
    },
    unmounted(el) {
        el._magneticCleanup?.();
    },
};

// ─── Parallax Directive ─────────────────────────────────────
const parallaxDirective = {
    mounted(el, binding) {
        if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;

        const speed = binding.value?.speed || 0.15;
        el.style.willChange = 'transform';

        const onScroll = () => {
            const rect = el.getBoundingClientRect();
            const center = rect.top + rect.height / 2 - window.innerHeight / 2;
            el.style.transform = `translate3d(0, ${center * speed}px, 0)`;
        };

        window.addEventListener('scroll', onScroll, { passive: true });
        el._parallaxCleanup = () => window.removeEventListener('scroll', onScroll);
        onScroll();
    },
    unmounted(el) {
        el._parallaxCleanup?.();
    },
};

// ─── Tilt 3D Directive ──────────────────────────────────────
const tiltDirective = {
    mounted(el, binding) {
        if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;

        const maxTilt = binding.value?.max || 8;
        const perspective = binding.value?.perspective || 1000;
        const scale = binding.value?.scale || 1.02;

        el.style.transformStyle = 'preserve-3d';
        el.style.transition = 'transform 0.4s cubic-bezier(0.22, 1, 0.36, 1)';

        const onMove = (e) => {
            const rect = el.getBoundingClientRect();
            const x = (e.clientX - rect.left) / rect.width;
            const y = (e.clientY - rect.top) / rect.height;
            const tiltX = (maxTilt * (0.5 - y)).toFixed(2);
            const tiltY = (maxTilt * (x - 0.5)).toFixed(2);
            el.style.transform = `perspective(${perspective}px) rotateX(${tiltX}deg) rotateY(${tiltY}deg) scale(${scale})`;
        };

        const onLeave = () => {
            el.style.transform = `perspective(${perspective}px) rotateX(0deg) rotateY(0deg) scale(1)`;
        };

        el.addEventListener('mousemove', onMove);
        el.addEventListener('mouseleave', onLeave);
        el._tiltCleanup = () => {
            el.removeEventListener('mousemove', onMove);
            el.removeEventListener('mouseleave', onLeave);
        };
    },
    unmounted(el) {
        el._tiltCleanup?.();
    },
};

// ─── Plugin Install ─────────────────────────────────────────
export const ScrollRevealPlugin = {
    install(app) {
        app.directive('scroll-reveal', scrollRevealDirective);
        app.directive('magnetic', magneticDirective);
        app.directive('parallax', parallaxDirective);
        app.directive('tilt', tiltDirective);
    },
};

export {
    scrollRevealDirective,
    magneticDirective,
    parallaxDirective,
    tiltDirective,
};
