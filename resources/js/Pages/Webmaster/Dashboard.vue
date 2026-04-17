<script setup>
import { computed } from 'vue';
import WebmasterLayout from '@/Layouts/WebmasterLayout.vue';
import { Link , usePage } from '@inertiajs/vue3';

const props = defineProps({
    stats: Object,
    recentPosts: Array,
});

const __page = usePage();
const locale = computed(() => __page.props.locale || 'ar');
const isRtl = computed(() => (__page.props.dir || 'rtl') === 'rtl');


const statCards = [
    { label: 'Services', value: props.stats.services, icon: 'sparkles', href: '/webmaster/services', color: 'purple' },
    { label: 'Doctors', value: props.stats.doctors, icon: 'user', href: '/webmaster/doctors', color: 'blue' },
    { label: 'Gallery Items', value: props.stats.gallery, icon: 'image', href: '/webmaster/gallery', color: 'pink' },
    { label: 'Active Offers', value: props.stats.offers, icon: 'tag', href: '/webmaster/offers', color: 'amber' },
    { label: 'Blog Posts', value: props.stats.posts, icon: 'document', href: '/webmaster/posts', color: 'green' },
    { label: 'Testimonials', value: props.stats.testimonials, icon: 'star', href: '/webmaster/testimonials', color: 'yellow' },
    { label: 'FAQs', value: props.stats.faqs, icon: 'question', href: '/webmaster/faqs', color: 'indigo' },
    { label: 'Active Slides', value: props.stats.slides, icon: 'slides', href: '/webmaster/slider', color: 'rose' },
];

const colorMap = {
    purple: { bg: 'bg-slate-50', text: 'text-[#1B365D]', icon: 'bg-slate-100' },
    blue: { bg: 'bg-slate-50', text: 'text-[#1B365D]', icon: 'bg-slate-100' },
    pink: { bg: 'bg-amber-50', text: 'text-[#C4A265]', icon: 'bg-amber-100' },
    amber: { bg: 'bg-amber-50', text: 'text-amber-600', icon: 'bg-amber-100' },
    green: { bg: 'bg-emerald-50', text: 'text-emerald-600', icon: 'bg-emerald-100' },
    yellow: { bg: 'bg-yellow-50', text: 'text-amber-600', icon: 'bg-yellow-100' },
    indigo: { bg: 'bg-slate-50', text: 'text-[#1B365D]', icon: 'bg-slate-100' },
    rose: { bg: 'bg-amber-50', text: 'text-[#C4A265]', icon: 'bg-amber-100' },
};
</script>

<template>
    <WebmasterLayout>
        <div class="space-y-4 md:space-y-6">
            <!-- Doctorato Navy Hero -->
            <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-[#1B365D] via-[#1B365D] to-[#0F2444] shadow-xl">
                <div class="pointer-events-none absolute -top-16 -end-16 h-56 w-56 rounded-full bg-[#C4A265]/20 blur-3xl"></div>
                <div class="absolute inset-x-0 top-0 h-[2px] bg-gradient-to-r from-transparent via-[#C4A265] to-transparent"></div>
                <div class="relative p-4 md:p-7 flex items-start gap-3 md:gap-4">
                    <div class="w-12 h-12 md:w-14 md:h-14 rounded-xl bg-gradient-to-br from-[#C4A265] to-[#8B7043] flex items-center justify-center shadow-lg flex-shrink-0">
                        <svg class="w-6 h-6 md:w-7 md:h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4" />
                        </svg>
                    </div>
                    <div class="min-w-0">
                        <div class="flex items-center gap-2 mb-1">
                            <span class="h-[3px] w-6 bg-[#C4A265] rounded-full"></span>
                            <span class="text-[10px] font-bold text-[#C4A265] tracking-[0.25em] uppercase">{{ isRtl ? 'المدير' : 'Webmaster' }}</span>
                        </div>
                        <h1 class="text-xl md:text-3xl font-extrabold text-white tracking-tight">{{ $t('a_webmaster_dashboard') }}</h1>
                        <p class="text-xs md:text-sm text-white/70 mt-1 max-w-xl">{{ $t('a_website_content_overview') }}</p>
                    </div>
                </div>
            </div>

            <!-- Stats Grid -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <Link
                    v-for="card in statCards"
                    :key="card.label"
                    :href="card.href"
                    class="relative bg-white rounded-xl border border-[#C4A265]/20 p-5 hover:shadow-lg transition-all duration-200 group overflow-hidden"
                >
                    <div class="absolute top-0 inset-x-0 h-1 bg-gradient-to-r from-[#C4A265] to-[#8B7043]"></div>
                    <div class="flex items-center justify-between mb-3">
                        <div :class="[colorMap[card.color].icon]" class="w-10 h-10 rounded-lg flex items-center justify-center">
                            <svg v-if="card.icon === 'sparkles'" :class="[colorMap[card.color].text]" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" /></svg>
                            <svg v-else-if="card.icon === 'user'" :class="[colorMap[card.color].text]" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                            <svg v-else-if="card.icon === 'image'" :class="[colorMap[card.color].text]" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                            <svg v-else-if="card.icon === 'tag'" :class="[colorMap[card.color].text]" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 7h.01M7 3h5a1.99 1.99 0 011.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" /></svg>
                            <svg v-else-if="card.icon === 'document'" :class="[colorMap[card.color].text]" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                            <svg v-else-if="card.icon === 'star'" :class="[colorMap[card.color].text]" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" /></svg>
                            <svg v-else-if="card.icon === 'question'" :class="[colorMap[card.color].text]" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            <svg v-else-if="card.icon === 'slides'" :class="[colorMap[card.color].text]" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909M3.75 21h16.5a1.5 1.5 0 001.5-1.5V5.25a1.5 1.5 0 00-1.5-1.5H3.75a1.5 1.5 0 00-1.5 1.5v14.25a1.5 1.5 0 001.5 1.5zm14.25-11.25a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0z" /></svg>
                        </div>
                        <svg class="w-4 h-4 text-gray-300 group-hover:text-slate-400 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                    </div>
                    <p class="text-2xl font-bold text-gray-900">{{ card.value }}</p>
                    <p class="text-xs text-gray-500 mt-0.5">{{ card.label }}</p>
                </Link>
            </div>

            <!-- Recent Posts -->
            <div class="bg-white rounded-xl border border-gray-200/60 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                    <h2 class="text-lg font-semibold text-gray-900">{{ $t('a_recent_blog_posts') }}</h2>
                    <Link href="/webmaster/posts" class="text-sm text-[#1B365D] hover:text-[#1B365D] font-medium">{{ $t('a_view_all') }}</Link>
                </div>
                <div v-if="recentPosts.length" class="divide-y divide-gray-50">
                    <Link
                        v-for="post in recentPosts"
                        :key="post.id"
                        :href="`/webmaster/posts/${post.id}/edit`"
                        class="flex items-center justify-between px-6 py-3 hover:bg-gray-50/50 transition-colors"
                    >
                        <div>
                            <p class="text-sm font-medium text-gray-800">{{ post.title_en || post.title_ar }}</p>
                            <p class="text-xs text-gray-400 mt-0.5">{{ new Date(post.created_at).toLocaleDateString() }}</p>
                        </div>
                        <span
                            :class="post.status === 'published' ? 'bg-emerald-50 text-emerald-600' : 'bg-yellow-50 text-amber-600'"
                            class="text-[11px] font-medium px-2 py-0.5 rounded-full"
                        >
                            {{ post.status === 'published' ? $t('a_published') : $t('a_draft') }}
                        </span>
                    </Link>
                </div>
                <div v-else class="px-6 py-8 text-center text-gray-400 text-sm">
                    No blog posts yet.
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                <Link href="/webmaster/posts/create" class="bg-white rounded-xl border border-gray-200/60 p-4 text-center hover:shadow-md hover:border-slate-200 transition-all duration-200 group">
                    <div class="w-10 h-10 mx-auto mb-2 rounded-lg bg-slate-50 flex items-center justify-center group-hover:bg-slate-100 transition-colors">
                        <svg class="w-5 h-5 text-[#1B365D]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4v16m8-8H4" /></svg>
                    </div>
                    <p class="text-xs font-medium text-gray-700">{{ $t('a_new_post') }}</p>
                </Link>
                <Link href="/webmaster/services/create" class="bg-white rounded-xl border border-gray-200/60 p-4 text-center hover:shadow-md hover:border-slate-200 transition-all duration-200 group">
                    <div class="w-10 h-10 mx-auto mb-2 rounded-lg bg-slate-50 flex items-center justify-center group-hover:bg-slate-100 transition-colors">
                        <svg class="w-5 h-5 text-[#1B365D]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4v16m8-8H4" /></svg>
                    </div>
                    <p class="text-xs font-medium text-gray-700">{{ $t('a_new_service') }}</p>
                </Link>
                <Link href="/webmaster/gallery/create" class="bg-white rounded-xl border border-gray-200/60 p-4 text-center hover:shadow-md hover:border-slate-200 transition-all duration-200 group">
                    <div class="w-10 h-10 mx-auto mb-2 rounded-lg bg-amber-50 flex items-center justify-center group-hover:bg-amber-100 transition-colors">
                        <svg class="w-5 h-5 text-[#C4A265]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4v16m8-8H4" /></svg>
                    </div>
                    <p class="text-xs font-medium text-gray-700">{{ $t('a_add_gallery') }}</p>
                </Link>
                <Link href="/webmaster/seo-pages" class="bg-white rounded-xl border border-gray-200/60 p-4 text-center hover:shadow-md hover:border-slate-200 transition-all duration-200 group">
                    <div class="w-10 h-10 mx-auto mb-2 rounded-lg bg-slate-50 flex items-center justify-center group-hover:bg-slate-100 transition-colors">
                        <svg class="w-5 h-5 text-[#1B365D]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                    </div>
                    <p class="text-xs font-medium text-gray-700">{{ $t('a_manage_seo') }}</p>
                </Link>
            </div>
        </div>
    </WebmasterLayout>
</template>
