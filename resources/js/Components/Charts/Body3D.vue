<script setup>
/**
 * Body3D — optional interactive 3D body for pain mapping (PT-8). three.js is
 * dynamically imported so it ships in its OWN lazy chunk (never in the main
 * bundle), mirroring how VideoRoom lazy-loads Agora. If WebGL is unavailable or
 * the import fails, it degrades gracefully to the 2D BodyMap. Read-only: renders
 * the assessment's pain points as colour-coded markers on a rotatable humanoid
 * built from primitive geometry (no external model asset).
 */
import { onBeforeUnmount, onMounted, ref, shallowRef } from 'vue';
import BodyMap from './BodyMap.vue';

const props = defineProps({
    // [{ view:'front'|'back', x:0-100, y:0-100, intensity:0-10, pain_type }]
    painPoints: { type: Array, default: () => [] },
    height: { type: Number, default: 320 },
    isRtl: { type: Boolean, default: true },
    accent: { type: String, default: '#0D9488' },
});

const host = ref(null);
const failed = ref(false);
const ready = ref(false);
let renderer = null;
let raf = null;
let cleanup = null;

function hasWebGL() {
    try {
        const c = document.createElement('canvas');
        return !!(window.WebGLRenderingContext && (c.getContext('webgl') || c.getContext('experimental-webgl')));
    } catch {
        return false;
    }
}

function intensityColor(i) {
    const t = Math.max(0, Math.min(10, Number(i) || 0)) / 10;
    // green (low) → red (high)
    const r = Math.round(34 + t * (220 - 34));
    const g = Math.round(197 - t * (197 - 38));
    return (r << 16) | (g << 8) | 56;
}

onMounted(async () => {
    if (!hasWebGL()) { failed.value = true; return; }
    let THREE;
    try {
        THREE = await import('three');
    } catch {
        failed.value = true;
        return;
    }

    const el = host.value;
    if (!el) return;
    const w = el.clientWidth || 240;
    const h = props.height;

    const scene = new THREE.Scene();
    const camera = new THREE.PerspectiveCamera(40, w / h, 0.1, 100);
    camera.position.set(0, 0.2, 6.2);

    renderer = new THREE.WebGLRenderer({ antialias: true, alpha: true });
    renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
    renderer.setSize(w, h);
    el.appendChild(renderer.domElement);

    scene.add(new THREE.AmbientLight(0xffffff, 0.75));
    const key = new THREE.DirectionalLight(0xffffff, 0.9);
    key.position.set(3, 5, 4);
    scene.add(key);

    const body = new THREE.Group();
    const skin = new THREE.MeshStandardMaterial({ color: 0xcdd7e0, roughness: 0.85, metalness: 0.05 });
    const cap = (rt, rb, len, mat) => new THREE.Mesh(new THREE.CapsuleGeometry(rt, len, 6, 14), mat || skin);

    // Head
    const head = new THREE.Mesh(new THREE.SphereGeometry(0.42, 24, 18), skin);
    head.position.y = 2.35; body.add(head);
    // Torso
    const torso = cap(0.62, 0.62, 1.5); torso.position.y = 1.1; body.add(torso);
    // Hips
    const hips = cap(0.5, 0.5, 0.4); hips.position.y = 0.25; body.add(hips);
    // Arms
    const armL = cap(0.18, 0.18, 1.5); armL.position.set(-0.85, 1.2, 0); armL.rotation.z = 0.18; body.add(armL);
    const armR = armL.clone(); armR.position.x = 0.85; armR.rotation.z = -0.18; body.add(armR);
    // Legs
    const legL = cap(0.24, 0.24, 1.7); legL.position.set(-0.3, -1.0, 0); body.add(legL);
    const legR = legL.clone(); legR.position.x = 0.3; body.add(legR);
    scene.add(body);

    // Pain markers: map 2D (view,x,y) → 3D. x:0-100 → -1.2..1.2, y:0-100 → 2.7..-1.9, z by view.
    (props.painPoints || []).forEach((p) => {
        const mx = ((Number(p.x) / 100) - 0.5) * 2.4;
        const my = 2.7 - (Number(p.y) / 100) * 4.6;
        const mz = p.view === 'back' ? -0.72 : 0.72;
        const marker = new THREE.Mesh(
            new THREE.SphereGeometry(0.16 + 0.02 * (Number(p.intensity) || 0) / 10, 16, 12),
            new THREE.MeshStandardMaterial({ color: intensityColor(p.intensity), emissive: intensityColor(p.intensity), emissiveIntensity: 0.35 })
        );
        // mirror x for back view so left/right reads correctly from behind
        marker.position.set(p.view === 'back' ? -mx : mx, my, mz);
        body.add(marker);
    });

    const reduce = window.matchMedia?.('(prefers-reduced-motion: reduce)').matches;
    let dragging = false, lastX = 0;
    const onDown = (e) => { dragging = true; lastX = (e.touches ? e.touches[0].clientX : e.clientX); };
    const onMove = (e) => {
        if (!dragging) return;
        const x = (e.touches ? e.touches[0].clientX : e.clientX);
        body.rotation.y += (x - lastX) * 0.01;
        lastX = x;
    };
    const onUp = () => { dragging = false; };
    renderer.domElement.addEventListener('pointerdown', onDown);
    window.addEventListener('pointermove', onMove);
    window.addEventListener('pointerup', onUp);

    const onResize = () => {
        const nw = el.clientWidth || w;
        renderer.setSize(nw, h);
        camera.aspect = nw / h;
        camera.updateProjectionMatrix();
    };
    window.addEventListener('resize', onResize);

    const loop = () => {
        raf = requestAnimationFrame(loop);
        if (!dragging && !reduce) body.rotation.y += 0.004;
        renderer.render(scene, camera);
    };
    loop();
    ready.value = true;

    cleanup = () => {
        cancelAnimationFrame(raf);
        renderer.domElement.removeEventListener('pointerdown', onDown);
        window.removeEventListener('pointermove', onMove);
        window.removeEventListener('pointerup', onUp);
        window.removeEventListener('resize', onResize);
        scene.traverse((o) => { o.geometry?.dispose?.(); o.material?.dispose?.(); });
        renderer.dispose();
        renderer.domElement.remove();
        renderer = null;
    };
});

onBeforeUnmount(() => cleanup && cleanup());

// Map pain points to BodyMap's lesion shape for the 2D fallback.
const fallbackFront = () => (props.painPoints || []).filter((p) => p.view !== 'back').map((p) => ({ ...p, lesion_type: p.pain_type }));
</script>

<template>
    <div>
        <div v-if="!failed" ref="host" class="relative rounded-xl overflow-hidden bg-gradient-to-b from-slate-50 to-white cursor-grab active:cursor-grabbing"
            :style="{ height: height + 'px' }">
            <span class="absolute bottom-2 inset-inline-start-2 text-[10px] text-gray-400 select-none">{{ isRtl ? 'اسحب للتدوير' : 'Drag to rotate' }}</span>
        </div>
        <!-- Graceful 2D fallback -->
        <BodyMap v-else :lesions="fallbackFront()" view="front" :accent="'#EF4444'" :is-rtl="isRtl" :height="height" />
    </div>
</template>
