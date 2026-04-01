<script setup>
import { ref, computed } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const page = usePage();
const locale = computed(() => page.props.locale || 'ar');
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const props = defineProps({
    modules: Array,
});

const togglingModule = ref(null);

function toggleModule(mod) {
    if (mod.is_core) return;
    togglingModule.value = mod.slug;
    router.post(`/admin/settings/modules/${mod.slug}/toggle`, {
        enabled: !mod.enabled,
    }, {
        preserveScroll: true,
        onFinish: () => {
            togglingModule.value = null;
        },
    });
}
</script>

<template>
    <AdminLayout :title="isRtl ? 'إدارة المديولات' : 'Module Management'">
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">{{ isRtl ? 'إدارة المديولات' : 'Module Management' }}</h1>
                    <p class="text-gray-500 text-sm mt-1">{{ isRtl ? 'تفعيل وتعطيل وإعداد أقسام النظام' : 'Enable, disable and configure system modules' }}</p>
                </div>
                <Link href="/admin/settings" class="inline-flex items-center gap-2 text-sm text-gray-500 hover:text-gray-700 transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
                    {{ isRtl ? 'العودة للإعدادات' : 'Back to Settings' }}
                </Link>
            </div>

            <!-- Module Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <div
                    v-for="mod in modules"
                    :key="mod.slug"
                    class="bg-white rounded-2xl shadow-sm border overflow-hidden hover:shadow-md transition-all duration-300"
                    :class="mod.enabled ? 'border-opacity-40' : 'border-gray-200'"
                    :style="mod.enabled ? `border-color: ${mod.color || '#6B7280'}40` : ''"
                >
                    <div class="p-6">
                        <div class="flex items-start justify-between">
                            <div class="flex items-center gap-3">
                                <!-- Module Icon -->
                                <div
                                    class="w-12 h-12 rounded-xl flex items-center justify-center transition-colors"
                                    :style="{ backgroundColor: (mod.color || '#6B7280') + (mod.enabled ? '20' : '10') }"
                                >
                                    <svg
                                        class="w-6 h-6"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                        :style="{ color: mod.enabled ? (mod.color || '#6B7280') : '#9CA3AF' }"
                                    >
                                        <path v-if="mod.icon" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="mod.icon" />
                                        <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-900">
                                        {{ isRtl ? mod.name_ar : mod.name_en }}
                                    </h3>
                                    <p class="text-xs text-gray-400 mt-0.5 font-mono">{{ mod.slug }}</p>
                                </div>
                            </div>

                            <!-- Toggle Switch -->
                            <div class="flex flex-col items-center">
                                <button
                                    @click="toggleModule(mod)"
                                    :disabled="mod.is_core || togglingModule === mod.slug"
                                    class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none disabled:opacity-50 disabled:cursor-not-allowed"
                                    :class="mod.enabled ? 'bg-cyan-600' : 'bg-gray-200'"
                                    :style="mod.enabled && mod.color ? { backgroundColor: mod.color } : {}"
                                >
                                    <!-- Loading spinner -->
                                    <span v-if="togglingModule === mod.slug" class="absolute inset-0 flex items-center justify-center">
                                        <svg class="w-4 h-4 text-white animate-spin" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                                        </svg>
                                    </span>
                                    <span
                                        v-else
                                        class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"
                                        :class="mod.enabled ? 'ltr:translate-x-5 rtl:-translate-x-5' : 'translate-x-0'"
                                    ></span>
                                </button>
                                <span v-if="mod.is_core" class="text-[10px] text-gray-400 mt-1">{{ isRtl ? 'أساسي' : 'Core' }}</span>
                            </div>
                        </div>

                        <!-- Module Status & Actions -->
                        <div class="mt-5 flex items-center justify-between">
                            <span
                                class="px-2.5 py-1 rounded-full text-xs font-semibold"
                                :class="mod.enabled ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500'"
                            >
                                {{ mod.enabled ? (isRtl ? 'مفعّل' : 'Enabled') : (isRtl ? 'معطّل' : 'Disabled') }}
                            </span>

                            <Link
                                v-if="mod.enabled"
                                :href="`/admin/settings/modules/${mod.slug}`"
                                class="inline-flex items-center gap-1.5 text-sm font-medium hover:underline transition"
                                :style="{ color: mod.color || '#0891b2' }"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                                {{ isRtl ? 'إعدادات' : 'Configure' }}
                            </Link>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Info Note -->
            <div class="bg-amber-50 border border-amber-200 rounded-xl p-4 flex items-start gap-3">
                <svg class="w-5 h-5 text-amber-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                <div>
                    <p class="text-sm font-medium text-amber-800">{{ isRtl ? 'ملاحظة' : 'Note' }}</p>
                    <p class="text-xs text-amber-600 mt-0.5">{{ isRtl ? 'تعطيل أي مديول سيخفي جميع صفحاته وبياناته من كل البورتالات (الإدارة، الطبيب، السكرتارية، المريض) ولن يتم حذف أي بيانات.' : 'Disabling a module will hide all its pages and data from all portals (Admin, Doctor, Secretary, Patient). No data will be deleted.' }}</p>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
