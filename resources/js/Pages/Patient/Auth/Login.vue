<script setup>
import { useForm, usePage, Link } from '@inertiajs/vue3';
import { computed, ref, onMounted } from 'vue';
import { usePatientLocale } from '@/Composables/usePatientLocale';

const showPassword = ref(false);
const mounted = ref(false);

const { lp } = usePatientLocale();

const page = usePage();
const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const locale = computed(() => page.props.locale || 'ar');
const dir = computed(() => page.props.dir || 'rtl');
const isRtl = computed(() => dir.value === 'rtl');
const translations = computed(() => page.props.translations || {});
function t(key) { return translations.value[key] || key; }

const flashError = computed(() => page.props.flash?.error);

function submit() {
    form.post(lp('/login'), {
        onFinish: () => form.reset('password'),
    });
}

// Mouse-tracked subtle parallax for the background orbs.
const mouseX = ref(0);
const mouseY = ref(0);
function trackMouse(e) {
    const cx = window.innerWidth / 2;
    const cy = window.innerHeight / 2;
    mouseX.value = (e.clientX - cx) / cx;
    mouseY.value = (e.clientY - cy) / cy;
}

onMounted(() => {
    // small delay so the entrance transitions actually run
    requestAnimationFrame(() => { mounted.value = true; });
});
</script>

<template>
    <div :dir="dir"
         @mousemove="trackMouse"
         class="login-shell"
         :class="{ 'is-mounted': mounted }"
         :style="{ fontFamily: isRtl ? '\'Tajawal\', \'Poppins\', sans-serif' : '\'Poppins\', sans-serif' }">

        <!-- Animated ambient background orbs -->
        <div class="orb orb-gold"
             :style="{ transform: `translate(${mouseX * -18}px, ${mouseY * -18}px)` }"></div>
        <div class="orb orb-navy"
             :style="{ transform: `translate(${mouseX * 22}px, ${mouseY * 22}px)` }"></div>
        <div class="orb orb-mid"
             :style="{ transform: `translate(${mouseX * -10}px, ${mouseY * 14}px)` }"></div>

        <!-- Fine dot grid texture -->
        <div class="bg-grid"></div>

        <!-- Top accent line -->
        <div class="top-accent"></div>

        <div class="login-grid">

            <!-- ════════════════════════════════════════════════════ -->
            <!-- LEFT: Brand panel (hidden on mobile)                  -->
            <!-- ════════════════════════════════════════════════════ -->
            <aside class="brand-panel">
                <!-- Animated heartbeat SVG -->
                <svg class="heartbeat-line" viewBox="0 0 800 80" preserveAspectRatio="none" aria-hidden="true">
                    <path d="M0 40 L150 40 L170 20 L185 60 L200 10 L215 70 L235 40 L380 40 L400 25 L415 55 L430 15 L445 65 L465 40 L800 40"
                          fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"
                          class="heartbeat-path" />
                </svg>

                <div class="brand-inner">
                    <div class="brand-eyebrow stagger" style="--i:0">
                        <span class="dash"></span>
                        <span>{{ isRtl ? 'بوابة المريض' : 'Patient Portal' }}</span>
                    </div>

                    <img src="/images/logo/logo-light.png" alt="Doctorato Polyclinic" class="brand-logo stagger" style="--i:1" />

                    <h1 class="brand-title stagger" style="--i:2">
                        {{ isRtl ? 'صحتك تحت عناية متكاملة' : 'Your health, end-to-end care' }}
                    </h1>
                    <p class="brand-sub stagger" style="--i:3">
                        {{ isRtl
                            ? 'سجل دخولك للوصول إلى مواعيدك، وصفاتك، فواتيرك، ونقاط الولاء — في مكان واحد آمن.'
                            : 'Log in to access your appointments, prescriptions, invoices, and loyalty — all in one secure place.' }}
                    </p>

                    <!-- Trust pills -->
                    <ul class="trust-pills">
                        <li class="stagger" style="--i:4">
                            <span class="dot dot-emerald"></span>
                            {{ isRtl ? 'مشفّر بالكامل' : 'End-to-end encrypted' }}
                        </li>
                        <li class="stagger" style="--i:5">
                            <span class="dot dot-gold"></span>
                            {{ isRtl ? 'أطباء معتمدون' : 'Board-certified doctors' }}
                        </li>
                        <li class="stagger" style="--i:6">
                            <span class="dot dot-blue"></span>
                            {{ isRtl ? 'دعم على مدار الساعة' : '24/7 support' }}
                        </li>
                    </ul>
                </div>

                <p class="brand-footer stagger" style="--i:7">
                    &copy; {{ new Date().getFullYear() }} {{ isRtl ? 'عيادة دكتوراتو' : 'Doctorato Polyclinic' }}
                </p>
            </aside>

            <!-- ════════════════════════════════════════════════════ -->
            <!-- RIGHT: Login form                                     -->
            <!-- ════════════════════════════════════════════════════ -->
            <main class="form-panel">
                <!-- Compact brand header (mobile only) -->
                <div class="mobile-brand">
                    <img src="/images/logo/logo-light.png" alt="Doctorato Polyclinic" />
                    <p>{{ isRtl ? 'بوابة المريض' : 'Patient Portal' }}</p>
                </div>

                <div class="login-card" :class="{ 'has-error': flashError }">
                    <div class="card-glow"></div>

                    <div class="card-head">
                        <h2>{{ t('p_patient_login') }}</h2>
                        <p>{{ isRtl ? 'مرحباً بعودتك — سجّل الدخول للمتابعة' : 'Welcome back — sign in to continue' }}</p>
                    </div>

                    <!-- Flash Error -->
                    <Transition name="shake">
                        <div v-if="flashError" class="flash-error">
                            <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                            </svg>
                            <span>{{ flashError }}</span>
                        </div>
                    </Transition>

                    <form @submit.prevent="submit" class="form-body">
                        <!-- Email -->
                        <div class="field stagger" style="--i:1">
                            <label for="email">{{ isRtl ? 'البريد الإلكتروني' : 'Email Address' }}</label>
                            <div class="input-wrap" :class="{ 'has-error': form.errors.email }">
                                <svg class="input-icon" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                </svg>
                                <input id="email" v-model="form.email" type="email" required autofocus
                                       :placeholder="isRtl ? 'patient@example.com' : 'patient@example.com'" />
                            </div>
                            <Transition name="slide-down">
                                <p v-if="form.errors.email" class="field-error">{{ form.errors.email }}</p>
                            </Transition>
                        </div>

                        <!-- Password -->
                        <div class="field stagger" style="--i:2">
                            <label for="password">{{ isRtl ? 'كلمة المرور' : 'Password' }}</label>
                            <div class="input-wrap" :class="{ 'has-error': form.errors.password }">
                                <svg class="input-icon" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                </svg>
                                <input :id="'password'" v-model="form.password" :type="showPassword ? 'text' : 'password'" required
                                       :placeholder="isRtl ? 'أدخل كلمة المرور' : 'Enter your password'" />
                                <button type="button" @click="showPassword = !showPassword" class="reveal-btn"
                                        :title="showPassword ? (isRtl ? 'إخفاء' : 'Hide') : (isRtl ? 'إظهار' : 'Show')">
                                    <svg v-if="!showPassword" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                    <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                                    </svg>
                                </button>
                            </div>
                            <Transition name="slide-down">
                                <p v-if="form.errors.password" class="field-error">{{ form.errors.password }}</p>
                            </Transition>
                        </div>

                        <!-- Remember + Forgot -->
                        <div class="row-between stagger" style="--i:3">
                            <label class="check">
                                <input type="checkbox" v-model="form.remember" />
                                <span class="box"></span>
                                <span class="lbl">{{ isRtl ? 'تذكرني' : 'Remember me' }}</span>
                            </label>
                            <Link :href="lp('/forgot-password')" class="forgot">
                                {{ isRtl ? 'نسيت كلمة المرور؟' : 'Forgot password?' }}
                            </Link>
                        </div>

                        <!-- Submit -->
                        <button type="submit" :disabled="form.processing" class="submit-btn stagger" style="--i:4">
                            <span class="btn-shine"></span>
                            <span v-if="form.processing" class="flex items-center justify-center gap-2">
                                <svg class="spinner" fill="none" viewBox="0 0 24 24">
                                    <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-dasharray="40 60"/>
                                </svg>
                                {{ isRtl ? 'جارٍ التسجيل...' : 'Logging in...' }}
                            </span>
                            <span v-else class="flex items-center justify-center gap-2">
                                {{ isRtl ? 'تسجيل الدخول' : 'Sign in' }}
                                <svg class="arrow rtl:rotate-180" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 5l7 7-7 7M5 12h15"/>
                                </svg>
                            </span>
                        </button>
                    </form>

                    <div class="card-foot stagger" style="--i:5">
                        <span>{{ isRtl ? 'ليس لديك حساب؟' : "Don't have an account?" }}</span>
                        <Link :href="lp('/register')">{{ isRtl ? 'سجّل الآن' : 'Register' }}</Link>
                    </div>
                </div>

                <p class="mobile-footer">
                    &copy; {{ new Date().getFullYear() }} {{ isRtl ? 'عيادة دكتوراتو · بوابة المريض' : 'Doctorato Polyclinic · Patient Portal' }}
                </p>
            </main>
        </div>
    </div>
</template>

<style scoped>
/* ═══════════════════════════════════════════════════════════
   SHELL + BACKGROUND
   ═══════════════════════════════════════════════════════════ */
.login-shell {
    position: relative;
    min-height: 100vh;
    color: #fff;
    background:
        radial-gradient(at 30% 20%, rgba(196,162,101,0.10), transparent 55%),
        radial-gradient(at 80% 80%, rgba(27,54,93,0.55), transparent 50%),
        linear-gradient(135deg, #0F1B2D 0%, #16213E 50%, #0F1B2D 100%);
    overflow: hidden;
}

/* Ambient floating orbs */
.orb {
    position: absolute;
    border-radius: 50%;
    filter: blur(80px);
    pointer-events: none;
    transition: transform 0.5s cubic-bezier(0.25, 0.46, 0.45, 0.94);
}
.orb-gold {
    top: -120px;
    left: -120px;
    width: 460px;
    height: 460px;
    background: radial-gradient(circle, rgba(196,162,101,0.28), transparent 70%);
    animation: float-slow 16s ease-in-out infinite;
}
.orb-navy {
    bottom: -180px;
    right: -180px;
    width: 560px;
    height: 560px;
    background: radial-gradient(circle, rgba(34,64,111,0.55), transparent 70%);
    animation: float-slow 22s ease-in-out infinite reverse;
}
.orb-mid {
    top: 40%;
    left: 50%;
    width: 320px;
    height: 320px;
    transform: translate(-50%, -50%);
    background: radial-gradient(circle, rgba(196,162,101,0.10), transparent 70%);
    animation: pulse-gentle 8s ease-in-out infinite;
}

/* Fine dot grid for texture */
.bg-grid {
    position: absolute;
    inset: 0;
    background-image: radial-gradient(rgba(255,255,255,0.03) 1px, transparent 1px);
    background-size: 28px 28px;
    pointer-events: none;
}

/* Top accent line */
.top-accent {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 2px;
    background: linear-gradient(90deg, transparent, rgba(196,162,101,0.6), transparent);
    z-index: 2;
}

/* ═══════════════════════════════════════════════════════════
   GRID LAYOUT
   ═══════════════════════════════════════════════════════════ */
.login-grid {
    position: relative;
    z-index: 1;
    min-height: 100vh;
    display: grid;
    grid-template-columns: 1fr;
    align-items: stretch;
}

@media (min-width: 1024px) {
    .login-grid {
        grid-template-columns: 1.1fr 1fr;
    }
}

/* ═══════════════════════════════════════════════════════════
   BRAND PANEL (LEFT)
   ═══════════════════════════════════════════════════════════ */
.brand-panel {
    display: none;
    position: relative;
    padding: 64px 56px;
    flex-direction: column;
    justify-content: space-between;
    overflow: hidden;
}

@media (min-width: 1024px) {
    .brand-panel { display: flex; }
}

.brand-panel::before {
    content: '';
    position: absolute;
    top: 0;
    inset-inline-end: 0;
    bottom: 0;
    width: 1px;
    background: linear-gradient(to bottom, transparent, rgba(196,162,101,0.25), transparent);
}

.heartbeat-line {
    position: absolute;
    top: 50%;
    left: -10%;
    width: 120%;
    height: 80px;
    color: rgba(196,162,101,0.18);
    transform: translateY(-50%);
    pointer-events: none;
}
.heartbeat-path {
    stroke-dasharray: 2000;
    stroke-dashoffset: 2000;
    animation: heartbeat-draw 4s cubic-bezier(0.4, 0, 0.2, 1) forwards infinite;
}

.brand-inner {
    position: relative;
    max-width: 540px;
}

.brand-eyebrow {
    display: inline-flex;
    align-items: center;
    gap: 12px;
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 0.28em;
    text-transform: uppercase;
    color: #C4A265;
    margin-bottom: 24px;
}
.brand-eyebrow .dash {
    display: inline-block;
    width: 32px;
    height: 2px;
    background: linear-gradient(90deg, #C4A265, rgba(196,162,101,0.2));
    border-radius: 2px;
}

.brand-logo {
    height: 56px;
    width: auto;
    margin-bottom: 36px;
    filter: drop-shadow(0 4px 18px rgba(196,162,101,0.25));
}

.brand-title {
    font-size: 38px;
    line-height: 1.18;
    font-weight: 800;
    color: #fff;
    margin: 0 0 16px;
    letter-spacing: -0.01em;
}

.brand-sub {
    font-size: 15px;
    line-height: 1.65;
    color: rgba(255,255,255,0.55);
    margin: 0 0 32px;
    max-width: 460px;
}

.trust-pills {
    list-style: none;
    padding: 0;
    margin: 0;
    display: flex;
    flex-direction: column;
    gap: 10px;
}
.trust-pills li {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    font-size: 13px;
    color: rgba(255,255,255,0.7);
    background: rgba(255,255,255,0.03);
    border: 1px solid rgba(255,255,255,0.06);
    border-radius: 999px;
    padding: 8px 16px;
    width: fit-content;
    backdrop-filter: blur(8px);
}
.trust-pills .dot {
    width: 6px;
    height: 6px;
    border-radius: 50%;
}
.dot-emerald { background: #10b981; box-shadow: 0 0 8px #10b981; }
.dot-gold    { background: #C4A265; box-shadow: 0 0 8px #C4A265; }
.dot-blue    { background: #3b82f6; box-shadow: 0 0 8px #3b82f6; }

.brand-footer {
    position: relative;
    font-size: 11px;
    color: rgba(255,255,255,0.25);
    margin: 0;
}

/* ═══════════════════════════════════════════════════════════
   FORM PANEL (RIGHT)
   ═══════════════════════════════════════════════════════════ */
.form-panel {
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    padding: 32px 20px;
    min-height: 100vh;
}

@media (min-width: 1024px) {
    .form-panel { padding: 56px 64px; }
}

.mobile-brand {
    text-align: center;
    margin-bottom: 28px;
}
.mobile-brand img {
    height: 44px;
    width: auto;
    margin: 0 auto 8px;
    filter: drop-shadow(0 4px 14px rgba(196,162,101,0.3));
}
.mobile-brand p {
    font-size: 11px;
    letter-spacing: 0.28em;
    text-transform: uppercase;
    color: rgba(196,162,101,0.7);
    margin: 0;
}
@media (min-width: 1024px) {
    .mobile-brand { display: none; }
}

/* ═══════════════════════════════════════════════════════════
   LOGIN CARD
   ═══════════════════════════════════════════════════════════ */
.login-card {
    position: relative;
    width: 100%;
    max-width: 440px;
    padding: 36px 32px;
    background: rgba(255,255,255,0.04);
    border: 1px solid rgba(255,255,255,0.08);
    border-radius: 24px;
    backdrop-filter: blur(20px) saturate(140%);
    -webkit-backdrop-filter: blur(20px) saturate(140%);
    box-shadow:
        0 24px 60px rgba(0,0,0,0.4),
        inset 0 1px 0 rgba(255,255,255,0.05);
    overflow: hidden;
}
.card-glow {
    position: absolute;
    top: -1px; left: -1px; right: -1px;
    height: 1px;
    background: linear-gradient(90deg, transparent, rgba(196,162,101,0.5), transparent);
    pointer-events: none;
}

.card-head {
    text-align: center;
    margin-bottom: 24px;
}
.card-head h2 {
    font-size: 22px;
    font-weight: 700;
    color: #fff;
    margin: 0 0 6px;
    letter-spacing: -0.01em;
}
.card-head p {
    font-size: 13px;
    color: rgba(255,255,255,0.45);
    margin: 0;
}

/* Flash error */
.flash-error {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 11px 14px;
    margin-bottom: 18px;
    background: rgba(239,68,68,0.08);
    border: 1px solid rgba(239,68,68,0.2);
    border-radius: 12px;
    color: #fca5a5;
    font-size: 13px;
}

/* ═══════════════════════════════════════════════════════════
   FORM FIELDS
   ═══════════════════════════════════════════════════════════ */
.form-body {
    display: flex;
    flex-direction: column;
    gap: 18px;
}

.field label {
    display: block;
    font-size: 12px;
    font-weight: 600;
    color: rgba(255,255,255,0.65);
    margin-bottom: 8px;
    letter-spacing: 0.02em;
}

.input-wrap {
    position: relative;
    display: flex;
    align-items: center;
    background: rgba(255,255,255,0.04);
    border: 1px solid rgba(255,255,255,0.1);
    border-radius: 12px;
    transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
}
.input-wrap:focus-within {
    background: rgba(255,255,255,0.06);
    border-color: rgba(196,162,101,0.55);
    box-shadow:
        0 0 0 4px rgba(196,162,101,0.10),
        0 0 24px rgba(196,162,101,0.12);
}
.input-wrap.has-error {
    border-color: rgba(239,68,68,0.5);
}
.input-wrap.has-error:focus-within {
    box-shadow: 0 0 0 4px rgba(239,68,68,0.1);
}

.input-icon {
    width: 18px;
    height: 18px;
    flex-shrink: 0;
    margin-inline-start: 14px;
    color: rgba(255,255,255,0.4);
    transition: color 0.25s;
}
.input-wrap:focus-within .input-icon {
    color: #C4A265;
}

.input-wrap input {
    flex: 1;
    background: transparent;
    border: 0;
    outline: 0;
    padding: 14px 14px;
    color: #fff;
    font-size: 14px;
    font-family: inherit;
}
.input-wrap input::placeholder {
    color: rgba(255,255,255,0.25);
}

.reveal-btn {
    background: transparent;
    border: 0;
    cursor: pointer;
    padding: 8px;
    margin-inline-end: 6px;
    color: rgba(255,255,255,0.3);
    transition: color 0.2s;
    display: flex;
    align-items: center;
}
.reveal-btn:hover { color: rgba(255,255,255,0.7); }

.field-error {
    margin: 6px 0 0;
    font-size: 12px;
    color: #fca5a5;
}

/* ═══════════════════════════════════════════════════════════
   REMEMBER + FORGOT ROW
   ═══════════════════════════════════════════════════════════ */
.row-between {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
}

.check {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    cursor: pointer;
    user-select: none;
}
.check input {
    position: absolute;
    opacity: 0;
    pointer-events: none;
}
.check .box {
    position: relative;
    display: inline-block;
    width: 16px;
    height: 16px;
    border: 1.5px solid rgba(255,255,255,0.25);
    border-radius: 4px;
    background: rgba(255,255,255,0.04);
    transition: all 0.2s;
}
.check input:checked + .box {
    background: linear-gradient(135deg, #C4A265, #D9B985);
    border-color: #C4A265;
}
.check input:checked + .box::after {
    content: '';
    position: absolute;
    inset: 0;
    background-image: url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 16 16' stroke='%231B365D' stroke-width='2.5'><path stroke-linecap='round' stroke-linejoin='round' d='M3 8l3 3 7-7'/></svg>");
    background-repeat: no-repeat;
    background-position: center;
    background-size: 12px;
}
.check .lbl {
    font-size: 13px;
    color: rgba(255,255,255,0.55);
}

.forgot {
    font-size: 13px;
    color: rgba(196,162,101,0.85);
    text-decoration: none;
    transition: color 0.2s;
}
.forgot:hover { color: #D9B985; }

/* ═══════════════════════════════════════════════════════════
   SUBMIT BUTTON
   ═══════════════════════════════════════════════════════════ */
.submit-btn {
    position: relative;
    width: 100%;
    padding: 14px 16px;
    border: 0;
    border-radius: 12px;
    background: linear-gradient(135deg, #C4A265 0%, #D9B985 50%, #C4A265 100%);
    background-size: 200% 100%;
    background-position: 0% 0%;
    color: #1B365D;
    font-size: 14px;
    font-weight: 700;
    cursor: pointer;
    overflow: hidden;
    transition:
        background-position 0.5s ease,
        transform 0.15s ease,
        box-shadow 0.3s ease;
    box-shadow:
        0 8px 24px rgba(196,162,101,0.3),
        inset 0 1px 0 rgba(255,255,255,0.4);
    margin-top: 4px;
}
.submit-btn:hover:not(:disabled) {
    background-position: 100% 0%;
    box-shadow:
        0 12px 32px rgba(196,162,101,0.45),
        inset 0 1px 0 rgba(255,255,255,0.5);
}
.submit-btn:active:not(:disabled) {
    transform: translateY(1px);
}
.submit-btn:disabled {
    opacity: 0.7;
    cursor: not-allowed;
}

.btn-shine {
    position: absolute;
    top: 0;
    inset-inline-start: -150%;
    width: 60%;
    height: 100%;
    background: linear-gradient(120deg,
        transparent 30%,
        rgba(255,255,255,0.45) 50%,
        transparent 70%);
    transform: skewX(-25deg);
    transition: inset-inline-start 0.7s ease;
}
.submit-btn:hover:not(:disabled) .btn-shine {
    inset-inline-start: 150%;
}

.arrow {
    width: 16px;
    height: 16px;
    transition: transform 0.2s;
}
.submit-btn:hover:not(:disabled) .arrow {
    transform: translateX(3px);
}
[dir="rtl"] .submit-btn:hover:not(:disabled) .arrow {
    transform: translateX(-3px) rotate(180deg);
}

.spinner {
    width: 16px;
    height: 16px;
    animation: spin 0.8s linear infinite;
}

/* ═══════════════════════════════════════════════════════════
   CARD FOOTER
   ═══════════════════════════════════════════════════════════ */
.card-foot {
    margin-top: 22px;
    padding-top: 18px;
    border-top: 1px solid rgba(255,255,255,0.06);
    text-align: center;
    font-size: 13px;
    color: rgba(255,255,255,0.5);
}
.card-foot a {
    color: #C4A265;
    text-decoration: none;
    font-weight: 600;
    margin-inline-start: 4px;
    transition: color 0.2s;
}
.card-foot a:hover { color: #D9B985; }

.mobile-footer {
    margin-top: 28px;
    font-size: 11px;
    color: rgba(255,255,255,0.25);
    text-align: center;
}
@media (min-width: 1024px) {
    .mobile-footer { display: none; }
}

/* ═══════════════════════════════════════════════════════════
   STAGGERED ENTRANCE ANIMATIONS
   ═══════════════════════════════════════════════════════════ */
.stagger {
    opacity: 0;
    transform: translateY(12px);
    transition:
        opacity 0.6s cubic-bezier(0.25, 0.46, 0.45, 0.94),
        transform 0.6s cubic-bezier(0.25, 0.46, 0.45, 0.94);
    transition-delay: calc(var(--i, 0) * 80ms + 100ms);
}
.is-mounted .stagger {
    opacity: 1;
    transform: translateY(0);
}

/* ═══════════════════════════════════════════════════════════
   TRANSITIONS (flash error / field error)
   ═══════════════════════════════════════════════════════════ */
.shake-enter-active {
    animation: shake 0.5s cubic-bezier(0.36, 0.07, 0.19, 0.97);
}
.slide-down-enter-active {
    transition: all 0.25s cubic-bezier(0.25, 0.46, 0.45, 0.94);
}
.slide-down-enter-from {
    opacity: 0;
    transform: translateY(-4px);
}

/* ═══════════════════════════════════════════════════════════
   KEYFRAMES
   ═══════════════════════════════════════════════════════════ */
@keyframes float-slow {
    0%, 100% { transform: translate(0, 0); }
    50%      { transform: translate(20px, -25px); }
}

@keyframes pulse-gentle {
    0%, 100% { opacity: 0.5; transform: translate(-50%, -50%) scale(1); }
    50%      { opacity: 0.8; transform: translate(-50%, -50%) scale(1.1); }
}

@keyframes heartbeat-draw {
    0%   { stroke-dashoffset: 2000; opacity: 0; }
    10%  { opacity: 1; }
    60%  { stroke-dashoffset: 0; opacity: 1; }
    100% { stroke-dashoffset: 0; opacity: 0.5; }
}

@keyframes shake {
    10%, 90% { transform: translateX(-1px); }
    20%, 80% { transform: translateX(2px); }
    30%, 50%, 70% { transform: translateX(-3px); }
    40%, 60% { transform: translateX(3px); }
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

/* ═══════════════════════════════════════════════════════════
   ACCESSIBILITY: reduce motion
   ═══════════════════════════════════════════════════════════ */
@media (prefers-reduced-motion: reduce) {
    .orb, .heartbeat-path { animation: none !important; }
    .stagger {
        transition-duration: 0.01s;
        transition-delay: 0s;
    }
    .submit-btn .btn-shine { display: none; }
}
</style>
