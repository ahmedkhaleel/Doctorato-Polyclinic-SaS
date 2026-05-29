<script setup>
import { computed, ref, onMounted } from 'vue';
import { useForm, usePage, router, Link } from '@inertiajs/vue3';

const page = usePage();
const locale = computed(() => page.props.locale || 'ar');
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const showPassword = ref(false);
const mounted = ref(false);

const form = useForm({
    login: '',
    password: '',
    remember: false,
});

function submit() {
    form.post('/admin/login', {
        onFinish: () => form.reset('password'),
    });
}

function switchLocale() {
    const newLocale = locale.value === 'ar' ? 'en' : 'ar';
    router.post('/admin/switch-locale-public', { locale: newLocale }, { preserveScroll: true });
}

// Live clock for the console header — pure cosmetic "command center" vibe.
const clock = ref('');
function tick() {
    const d = new Date();
    const hh = String(d.getHours()).padStart(2, '0');
    const mm = String(d.getMinutes()).padStart(2, '0');
    const ss = String(d.getSeconds()).padStart(2, '0');
    clock.value = `${hh}:${mm}:${ss}`;
}

onMounted(() => {
    tick();
    setInterval(tick, 1000);
    requestAnimationFrame(() => { mounted.value = true; });
});

const flashError = computed(() => page.props.flash?.error);
</script>

<template>
    <div :dir="isRtl ? 'rtl' : 'ltr'" class="adm-shell" :class="{ 'is-mounted': mounted }"
         :style="{ fontFamily: isRtl ? '\'Tajawal\', sans-serif' : '\'Poppins\', sans-serif' }">

        <!-- Animated security lattice + atmosphere -->
        <div class="adm-lattice" aria-hidden="true"></div>
        <div class="adm-orb adm-orb-gold" aria-hidden="true"></div>
        <div class="adm-orb adm-orb-navy" aria-hidden="true"></div>
        <div class="adm-scanline" aria-hidden="true"></div>
        <div class="adm-vignette" aria-hidden="true"></div>

        <!-- Language toggle -->
        <button @click="switchLocale" class="adm-lang" :class="isRtl ? 'adm-lang-start' : 'adm-lang-end'">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <span>{{ locale === 'ar' ? 'English' : 'العربية' }}</span>
        </button>

        <div class="adm-grid">

            <!-- ════════════════════════════════════════════════ -->
            <!-- LEFT: Identity / security panel (desktop only)    -->
            <!-- ════════════════════════════════════════════════ -->
            <aside class="adm-brand">
                <!-- Concentric shield rings emblem -->
                <div class="adm-emblem stagger" style="--i:0">
                    <svg viewBox="0 0 120 120" class="adm-emblem-svg">
                        <circle cx="60" cy="60" r="54" class="adm-ring adm-ring-1" />
                        <circle cx="60" cy="60" r="42" class="adm-ring adm-ring-2" />
                        <circle cx="60" cy="60" r="30" class="adm-ring adm-ring-3" />
                        <path d="M60 30 L82 40 V60 C82 76 60 90 60 90 C60 90 38 76 38 60 V40 Z"
                              class="adm-shield" />
                        <path d="M52 60 l6 6 12 -14" class="adm-check" />
                    </svg>
                </div>

                <span class="adm-brand-eyebrow stagger" style="--i:1">
                    <span class="adm-dot-live"></span>
                    {{ isRtl ? 'وصول آمن' : 'Secure Access' }}
                </span>

                <img src="/images/logo/logo-light.png" alt="Doctorato" class="adm-brand-logo stagger" style="--i:2" />

                <h1 class="adm-brand-title stagger" style="--i:3">
                    {{ isRtl ? 'مركز التحكّم الإداري' : 'Admin Command Center' }}
                </h1>
                <p class="adm-brand-sub stagger" style="--i:4">
                    {{ isRtl
                        ? 'منطقة محظورة — مخصصة للطاقم المُصرّح له فقط. كل محاولة دخول تُسجَّل.'
                        : 'Restricted area — authorized personnel only. Every access attempt is logged.' }}
                </p>

                <div class="adm-status stagger" style="--i:5">
                    <div class="adm-status-row">
                        <span class="adm-status-led"></span>
                        {{ isRtl ? 'الاتصال مشفّر (TLS)' : 'Connection encrypted (TLS)' }}
                    </div>
                    <div class="adm-status-row">
                        <span class="adm-status-led"></span>
                        {{ isRtl ? 'حماية ضد محاولات الدخول المتكررة' : 'Brute-force protection active' }}
                    </div>
                    <div class="adm-status-row">
                        <span class="adm-status-led"></span>
                        {{ isRtl ? 'تسجيل تدقيق كامل' : 'Full audit logging' }}
                    </div>
                </div>
            </aside>

            <!-- ════════════════════════════════════════════════ -->
            <!-- RIGHT: Login console                              -->
            <!-- ════════════════════════════════════════════════ -->
            <main class="adm-main">
                <!-- Mobile compact brand -->
                <div class="adm-mobile-brand">
                    <img src="/images/logo/logo-light.png" alt="Doctorato" />
                    <p>{{ isRtl ? 'لوحة الإدارة' : 'Admin Panel' }}</p>
                </div>

                <div class="adm-console stagger" style="--i:2">
                    <div class="adm-console-glow"></div>

                    <!-- Console header bar -->
                    <div class="adm-console-bar">
                        <div class="adm-console-dots">
                            <span></span><span></span><span></span>
                        </div>
                        <span class="adm-console-label">{{ isRtl ? 'تسجيل الدخول' : 'AUTHENTICATION' }}</span>
                        <span class="adm-console-clock">{{ clock }}</span>
                    </div>

                    <div class="adm-console-body">
                        <h2 class="adm-console-title">{{ $t('a_login_title') }}</h2>
                        <p class="adm-console-hint">{{ isRtl ? 'أدخل بيانات الاعتماد الإدارية للمتابعة' : 'Enter your admin credentials to continue' }}</p>

                        <!-- Flash error -->
                        <Transition name="adm-shake">
                            <div v-if="flashError" class="adm-flash">
                                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M5 19h14a2 2 0 001.84-2.75L13.74 4a2 2 0 00-3.48 0l-7.1 12.25A2 2 0 005 19z"/>
                                </svg>
                                <span>{{ flashError }}</span>
                            </div>
                        </Transition>

                        <form @submit.prevent="submit" class="adm-form">
                            <!-- Login -->
                            <div class="adm-field">
                                <label for="login">{{ $t('a_login_username_or_email') }}</label>
                                <div class="adm-input" :class="{ 'has-error': form.errors.login }">
                                    <svg class="adm-input-icon" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                    </svg>
                                    <input id="login" v-model="form.login" type="text" required autofocus
                                           :placeholder="$t('a_login_username_or_email_placeholder')" />
                                </div>
                                <p v-if="form.errors.login" class="adm-err">{{ form.errors.login }}</p>
                            </div>

                            <!-- Password -->
                            <div class="adm-field">
                                <label for="password">{{ $t('a_login_password') }}</label>
                                <div class="adm-input" :class="{ 'has-error': form.errors.password }">
                                    <svg class="adm-input-icon" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                    </svg>
                                    <input id="password" v-model="form.password" :type="showPassword ? 'text' : 'password'" required
                                           :placeholder="$t('a_enter_password')" />
                                    <button type="button" @click="showPassword = !showPassword" class="adm-reveal"
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
                                <p v-if="form.errors.password" class="adm-err">{{ form.errors.password }}</p>
                            </div>

                            <!-- Remember + forgot -->
                            <div class="adm-row">
                                <label class="adm-check">
                                    <input type="checkbox" v-model="form.remember" />
                                    <span class="adm-check-box"></span>
                                    <span class="adm-check-lbl">{{ $t('a_login_remember') }}</span>
                                </label>
                                <Link href="/admin/forgot-password" class="adm-forgot">
                                    {{ isRtl ? 'نسيت كلمة المرور؟' : 'Forgot password?' }}
                                </Link>
                            </div>

                            <!-- Submit -->
                            <button type="submit" :disabled="form.processing" class="adm-submit">
                                <span class="adm-submit-shine"></span>
                                <span v-if="form.processing" class="adm-submit-inner">
                                    <svg class="adm-spin" fill="none" viewBox="0 0 24 24">
                                        <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-dasharray="40 60"/>
                                    </svg>
                                    {{ $t('a_signing_in') }}
                                </span>
                                <span v-else class="adm-submit-inner">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h6a3 3 0 013 3v1"/>
                                    </svg>
                                    {{ $t('a_login_button') }}
                                </span>
                            </button>

                            <p v-if="form.errors.general" class="adm-err text-center">{{ form.errors.general }}</p>
                        </form>
                    </div>
                </div>

                <p class="adm-footer">
                    {{ isRtl ? 'عيادة دكتوراتو · لوحة الإدارة' : 'Doctorato Polyclinic · Admin Panel' }}
                </p>
            </main>
        </div>
    </div>
</template>

<style scoped>
/* ═══════════════════════════════════════════════════════════
   SHELL — secure command console (dark charcoal + gold)
   ═══════════════════════════════════════════════════════════ */
.adm-shell {
    position: relative;
    min-height: 100vh;
    overflow: hidden;
    color: #e5e7eb;
    background:
        radial-gradient(at 78% 12%, rgba(196,162,101,0.10), transparent 48%),
        radial-gradient(at 18% 92%, rgba(27,54,93,0.45), transparent 52%),
        linear-gradient(150deg, #161616 0%, #1E1E1E 45%, #232323 100%);
}

/* Animated security lattice */
.adm-lattice {
    position: absolute;
    inset: 0;
    z-index: 0;
    background-image:
        linear-gradient(rgba(196,162,101,0.045) 1px, transparent 1px),
        linear-gradient(90deg, rgba(196,162,101,0.045) 1px, transparent 1px);
    background-size: 46px 46px;
    mask-image: radial-gradient(ellipse 80% 70% at 50% 40%, #000 30%, transparent 80%);
    -webkit-mask-image: radial-gradient(ellipse 80% 70% at 50% 40%, #000 30%, transparent 80%);
    animation: admLatticeDrift 30s linear infinite;
}
@keyframes admLatticeDrift {
    0%   { background-position: 0 0, 0 0; }
    100% { background-position: 46px 46px, 46px 46px; }
}

/* Ambient orbs */
.adm-orb {
    position: absolute;
    border-radius: 50%;
    filter: blur(90px);
    pointer-events: none;
    z-index: 0;
}
.adm-orb-gold {
    top: -130px;
    inset-inline-end: -100px;
    width: 420px;
    height: 420px;
    background: radial-gradient(circle, rgba(196,162,101,0.28), transparent 70%);
    animation: admFloat 19s ease-in-out infinite;
}
.adm-orb-navy {
    bottom: -160px;
    inset-inline-start: -140px;
    width: 520px;
    height: 520px;
    background: radial-gradient(circle, rgba(27,54,93,0.5), transparent 70%);
    animation: admFloat 24s ease-in-out infinite reverse;
}
@keyframes admFloat {
    0%, 100% { transform: translate(0, 0); }
    50%      { transform: translate(22px, -28px); }
}

/* Slow vertical scan line — "monitoring" feel */
.adm-scanline {
    position: absolute;
    left: 0; right: 0;
    height: 180px;
    z-index: 0;
    pointer-events: none;
    background: linear-gradient(to bottom, transparent, rgba(196,162,101,0.04), transparent);
    animation: admScan 7s linear infinite;
}
@keyframes admScan {
    0%   { transform: translateY(-200px); }
    100% { transform: translateY(100vh); }
}

.adm-vignette {
    position: absolute;
    inset: 0;
    z-index: 0;
    pointer-events: none;
    background: radial-gradient(ellipse at center, transparent 40%, rgba(0,0,0,0.45) 100%);
}

/* ═══════════════════════════════════════════════════════════
   LANGUAGE TOGGLE
   ═══════════════════════════════════════════════════════════ */
.adm-lang {
    position: absolute;
    top: 22px;
    z-index: 10;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 8px 14px;
    border-radius: 999px;
    background: rgba(255,255,255,0.06);
    border: 1px solid rgba(255,255,255,0.1);
    backdrop-filter: blur(8px);
    color: #cbd5e1;
    font-size: 13px;
    font-weight: 500;
    transition: all 0.2s;
}
.adm-lang:hover { background: rgba(255,255,255,0.12); color: #fff; border-color: rgba(196,162,101,0.4); }
.adm-lang-start { inset-inline-start: 22px; }
.adm-lang-end   { inset-inline-end: 22px; }

/* ═══════════════════════════════════════════════════════════
   GRID
   ═══════════════════════════════════════════════════════════ */
.adm-grid {
    position: relative;
    z-index: 1;
    min-height: 100vh;
    display: grid;
    grid-template-columns: 1fr;
}
@media (min-width: 1024px) {
    .adm-grid { grid-template-columns: 1.05fr 1fr; }
}

/* ═══════════════════════════════════════════════════════════
   BRAND / SECURITY PANEL (LEFT)
   ═══════════════════════════════════════════════════════════ */
.adm-brand {
    display: none;
    position: relative;
    padding: 64px 56px;
    flex-direction: column;
    justify-content: center;
    align-items: flex-start;   /* prevent column-flex from stretching children (logo) */
}
@media (min-width: 1024px) {
    .adm-brand { display: flex; }
}
.adm-brand::after {
    content: '';
    position: absolute;
    top: 12%;
    bottom: 12%;
    inset-inline-end: 0;
    width: 1px;
    background: linear-gradient(to bottom, transparent, rgba(196,162,101,0.3), transparent);
}

/* Shield emblem with concentric rotating rings */
.adm-emblem {
    width: 120px;
    height: 120px;
    margin-bottom: 32px;
}
.adm-emblem-svg { width: 100%; height: 100%; overflow: visible; }
.adm-ring {
    fill: none;
    stroke: rgba(196,162,101,0.3);
    stroke-width: 1;
    transform-origin: 60px 60px;
    stroke-dasharray: 6 8;
}
.adm-ring-1 { animation: admRingSpin 40s linear infinite; stroke: rgba(196,162,101,0.25); }
.adm-ring-2 { animation: admRingSpin 28s linear infinite reverse; stroke: rgba(196,162,101,0.18); }
.adm-ring-3 { animation: admRingSpin 18s linear infinite; stroke: rgba(196,162,101,0.12); }
@keyframes admRingSpin {
    to { transform: rotate(360deg); }
}
.adm-shield {
    fill: rgba(196,162,101,0.08);
    stroke: #C4A265;
    stroke-width: 2;
    stroke-linejoin: round;
    filter: drop-shadow(0 0 12px rgba(196,162,101,0.4));
}
.adm-check {
    fill: none;
    stroke: #C4A265;
    stroke-width: 3;
    stroke-linecap: round;
    stroke-linejoin: round;
    stroke-dasharray: 30;
    stroke-dashoffset: 30;
    animation: admCheckDraw 2.4s ease-in-out 0.6s forwards infinite;
}
@keyframes admCheckDraw {
    0%   { stroke-dashoffset: 30; }
    30%  { stroke-dashoffset: 0; }
    85%  { stroke-dashoffset: 0; opacity: 1; }
    100% { stroke-dashoffset: 0; opacity: 0.6; }
}

.adm-brand-eyebrow {
    display: inline-flex;
    align-items: center;
    gap: 12px;
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 0.3em;
    text-transform: uppercase;
    color: #C4A265;
    margin-bottom: 20px;
}
.adm-dot-live {
    width: 7px;
    height: 7px;
    border-radius: 50%;
    background: #34d399;
    box-shadow: 0 0 0 0 rgba(52,211,153,0.6);
    animation: admPulseLive 2s ease-out infinite;
}
@keyframes admPulseLive {
    0%   { box-shadow: 0 0 0 0 rgba(52,211,153,0.5); }
    70%  { box-shadow: 0 0 0 8px rgba(52,211,153,0); }
    100% { box-shadow: 0 0 0 0 rgba(52,211,153,0); }
}

.adm-brand-logo {
    height: 46px;
    width: auto;
    max-width: 240px;
    object-fit: contain;
    align-self: flex-start;       /* don't let the column flex stretch it; direction-aware */
    margin-bottom: 28px;
    filter: drop-shadow(0 4px 16px rgba(196,162,101,0.25));
}
.adm-brand-title {
    font-size: 36px;
    line-height: 1.12;
    font-weight: 800;
    color: #fff;
    margin: 0 0 14px;
    letter-spacing: -0.015em;
}
.adm-brand-sub {
    font-size: 14px;
    line-height: 1.7;
    color: rgba(229,231,235,0.5);
    max-width: 420px;
    margin: 0 0 30px;
}

.adm-status {
    display: flex;
    flex-direction: column;
    gap: 12px;
}
.adm-status-row {
    display: inline-flex;
    align-items: center;
    gap: 12px;
    font-size: 12.5px;
    color: rgba(229,231,235,0.7);
}
.adm-status-led {
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: #34d399;
    box-shadow: 0 0 8px #34d399;
    flex-shrink: 0;
}

/* ═══════════════════════════════════════════════════════════
   MAIN (RIGHT)
   ═══════════════════════════════════════════════════════════ */
.adm-main {
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    padding: 40px 20px;
    min-height: 100vh;
}
@media (min-width: 1024px) {
    .adm-main { padding: 56px 64px; }
}

.adm-mobile-brand {
    text-align: center;
    margin-bottom: 26px;
}
.adm-mobile-brand img {
    height: 40px;
    width: auto;
    margin: 0 auto 8px;
    filter: drop-shadow(0 4px 14px rgba(196,162,101,0.3));
}
.adm-mobile-brand p {
    font-size: 11px;
    letter-spacing: 0.28em;
    text-transform: uppercase;
    color: rgba(196,162,101,0.7);
    margin: 0;
}
@media (min-width: 1024px) {
    .adm-mobile-brand { display: none; }
}

/* ═══════════════════════════════════════════════════════════
   CONSOLE CARD
   ═══════════════════════════════════════════════════════════ */
.adm-console {
    position: relative;
    width: 100%;
    max-width: 440px;
    border-radius: 18px;
    background: rgba(28,28,30,0.72);
    border: 1px solid rgba(255,255,255,0.08);
    backdrop-filter: blur(22px) saturate(130%);
    -webkit-backdrop-filter: blur(22px) saturate(130%);
    box-shadow:
        0 30px 70px rgba(0,0,0,0.55),
        inset 0 1px 0 rgba(255,255,255,0.05);
    overflow: hidden;
}
.adm-console-glow {
    position: absolute;
    top: -1px; left: -1px; right: -1px;
    height: 1px;
    background: linear-gradient(90deg, transparent, rgba(196,162,101,0.6), transparent);
    pointer-events: none;
}

/* Console title bar (terminal-like) */
.adm-console-bar {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 16px;
    background: rgba(0,0,0,0.25);
    border-bottom: 1px solid rgba(255,255,255,0.06);
}
.adm-console-dots {
    display: inline-flex;
    gap: 6px;
}
.adm-console-dots span {
    width: 9px;
    height: 9px;
    border-radius: 50%;
    background: rgba(255,255,255,0.15);
}
.adm-console-dots span:first-child { background: rgba(196,162,101,0.6); }
.adm-console-label {
    font-size: 10px;
    font-weight: 700;
    letter-spacing: 0.22em;
    color: rgba(196,162,101,0.7);
    margin-inline-start: 4px;
}
.adm-console-clock {
    margin-inline-start: auto;
    font-size: 11px;
    font-variant-numeric: tabular-nums;
    letter-spacing: 0.08em;
    color: rgba(229,231,235,0.4);
    font-family: ui-monospace, 'SF Mono', monospace;
}

.adm-console-body {
    padding: 28px 28px 30px;
}
.adm-console-title {
    font-size: 21px;
    font-weight: 700;
    color: #fff;
    margin: 0 0 5px;
    text-align: center;
}
.adm-console-hint {
    font-size: 12.5px;
    color: rgba(229,231,235,0.45);
    text-align: center;
    margin: 0 0 22px;
}

/* Flash error */
.adm-flash {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 11px 14px;
    margin-bottom: 18px;
    background: rgba(239,68,68,0.1);
    border: 1px solid rgba(239,68,68,0.25);
    border-radius: 11px;
    color: #fca5a5;
    font-size: 12.5px;
}

/* ═══════════════════════════════════════════════════════════
   FORM
   ═══════════════════════════════════════════════════════════ */
.adm-form {
    display: flex;
    flex-direction: column;
    gap: 16px;
}
.adm-field label {
    display: block;
    font-size: 12px;
    font-weight: 600;
    color: rgba(229,231,235,0.65);
    margin-bottom: 7px;
}
.adm-input {
    position: relative;
    display: flex;
    align-items: center;
    background: rgba(0,0,0,0.28);
    border: 1px solid rgba(255,255,255,0.1);
    border-radius: 11px;
    transition: all 0.25s cubic-bezier(0.4,0,0.2,1);
}
.adm-input:focus-within {
    border-color: rgba(196,162,101,0.6);
    background: rgba(0,0,0,0.35);
    box-shadow: 0 0 0 4px rgba(196,162,101,0.1), 0 0 22px rgba(196,162,101,0.12);
}
.adm-input.has-error { border-color: rgba(239,68,68,0.5); }
.adm-input-icon {
    width: 18px; height: 18px;
    flex-shrink: 0;
    margin-inline-start: 14px;
    color: rgba(229,231,235,0.4);
    transition: color 0.25s;
}
.adm-input:focus-within .adm-input-icon { color: #C4A265; }
.adm-input input {
    flex: 1;
    background: transparent;
    border: 0;
    outline: 0;
    padding: 13px 14px;
    color: #fff;
    font-size: 14px;
    font-family: inherit;
}
.adm-input input::placeholder { color: rgba(229,231,235,0.28); }
.adm-reveal {
    background: transparent;
    border: 0;
    cursor: pointer;
    padding: 8px;
    margin-inline-end: 6px;
    color: rgba(229,231,235,0.32);
    display: flex;
    transition: color 0.2s;
}
.adm-reveal:hover { color: rgba(229,231,235,0.7); }
.adm-err {
    margin: 6px 0 0;
    font-size: 12px;
    color: #fca5a5;
}

/* Remember + forgot */
.adm-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
}
.adm-check {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    cursor: pointer;
    user-select: none;
}
.adm-check input { position: absolute; opacity: 0; pointer-events: none; }
.adm-check-box {
    position: relative;
    width: 16px; height: 16px;
    border: 1.5px solid rgba(255,255,255,0.25);
    border-radius: 4px;
    background: rgba(0,0,0,0.3);
    transition: all 0.2s;
}
.adm-check input:checked + .adm-check-box {
    background: linear-gradient(135deg, #C4A265, #D4B87A);
    border-color: #C4A265;
}
.adm-check input:checked + .adm-check-box::after {
    content: '';
    position: absolute;
    inset: 0;
    background-image: url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 16 16' stroke='%231E1E1E' stroke-width='2.5'><path stroke-linecap='round' stroke-linejoin='round' d='M3 8l3 3 7-7'/></svg>");
    background-repeat: no-repeat;
    background-position: center;
    background-size: 12px;
}
.adm-check-lbl { font-size: 13px; color: rgba(229,231,235,0.55); }
.adm-forgot {
    font-size: 13px;
    color: rgba(196,162,101,0.85);
    text-decoration: none;
    transition: color 0.2s;
}
.adm-forgot:hover { color: #D4B87A; }

/* Submit */
.adm-submit {
    position: relative;
    width: 100%;
    padding: 13px 16px;
    margin-top: 4px;
    border: 0;
    border-radius: 11px;
    background: linear-gradient(135deg, #C4A265 0%, #D4B87A 50%, #C4A265 100%);
    background-size: 200% 100%;
    background-position: 0% 0%;
    color: #1E1E1E;
    font-size: 14px;
    font-weight: 700;
    cursor: pointer;
    overflow: hidden;
    transition: background-position 0.5s ease, transform 0.15s ease, box-shadow 0.3s ease;
    box-shadow: 0 8px 24px rgba(196,162,101,0.28), inset 0 1px 0 rgba(255,255,255,0.4);
}
.adm-submit:hover:not(:disabled) {
    background-position: 100% 0%;
    box-shadow: 0 12px 30px rgba(196,162,101,0.42), inset 0 1px 0 rgba(255,255,255,0.5);
}
.adm-submit:active:not(:disabled) { transform: translateY(1px); }
.adm-submit:disabled { opacity: 0.65; cursor: not-allowed; }
.adm-submit-inner {
    position: relative;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    width: 100%;
}
.adm-submit-shine {
    position: absolute;
    top: 0;
    inset-inline-start: -150%;
    width: 60%;
    height: 100%;
    background: linear-gradient(120deg, transparent 30%, rgba(255,255,255,0.5) 50%, transparent 70%);
    transform: skewX(-22deg);
    transition: inset-inline-start 0.7s ease;
}
.adm-submit:hover:not(:disabled) .adm-submit-shine { inset-inline-start: 150%; }
.adm-spin { width: 16px; height: 16px; animation: admSpin 0.8s linear infinite; }
@keyframes admSpin { to { transform: rotate(360deg); } }

.adm-footer {
    margin-top: 26px;
    font-size: 11px;
    color: rgba(229,231,235,0.3);
    text-align: center;
}

/* ═══════════════════════════════════════════════════════════
   STAGGER ENTRANCE
   ═══════════════════════════════════════════════════════════ */
.stagger {
    opacity: 0;
    transform: translateY(14px);
    transition:
        opacity 0.7s cubic-bezier(0.25,0.46,0.45,0.94),
        transform 0.7s cubic-bezier(0.25,0.46,0.45,0.94);
    transition-delay: calc(var(--i, 0) * 90ms + 120ms);
}
.is-mounted .stagger { opacity: 1; transform: translateY(0); }

/* Shake transition for flash */
.adm-shake-enter-active { animation: admShake 0.5s cubic-bezier(0.36,0.07,0.19,0.97); }
@keyframes admShake {
    10%, 90% { transform: translateX(-1px); }
    20%, 80% { transform: translateX(2px); }
    30%, 50%, 70% { transform: translateX(-3px); }
    40%, 60% { transform: translateX(3px); }
}

/* ═══════════════════════════════════════════════════════════
   ACCESSIBILITY
   ═══════════════════════════════════════════════════════════ */
@media (prefers-reduced-motion: reduce) {
    .adm-lattice, .adm-orb, .adm-scanline,
    .adm-ring-1, .adm-ring-2, .adm-ring-3,
    .adm-check, .adm-dot-live, .adm-submit-shine {
        animation: none !important;
    }
    .stagger { transition-duration: 0.01s; transition-delay: 0s; }
}
</style>
