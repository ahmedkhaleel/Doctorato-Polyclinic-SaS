<script setup>
import { ref, computed } from 'vue'
import { router, usePage } from '@inertiajs/vue3'

const props = defineProps({
    satisfaction: Object,
    improvementAreas: Object,
})

const page = usePage()
const locale = computed(() => page.props.locale || 'ar')
const isRtl = computed(() => locale.value === 'ar')

const form = ref({
    overall_rating: 0,
    doctor_rating: 0,
    staff_rating: 0,
    cleanliness_rating: 0,
    waiting_time_rating: 0,
    communication_rating: 0,
    comments: '',
    would_recommend: null,
    improvement_areas: [],
    nps_score: null,
})

const submitting = ref(false)
const step = ref(1)

function setRating(field, value) {
    form.value[field] = value
}

function toggleArea(area) {
    const idx = form.value.improvement_areas.indexOf(area)
    if (idx >= 0) form.value.improvement_areas.splice(idx, 1)
    else form.value.improvement_areas.push(area)
}

function submit() {
    submitting.value = true
    router.post(`/survey/${props.satisfaction.token}`, form.value, {
        onFinish: () => submitting.value = false,
    })
}
</script>

<template>
    <div class="min-h-screen bg-gradient-to-br from-cyan-50 via-white to-teal-50 flex items-center justify-center p-4" :dir="isRtl ? 'rtl' : 'ltr'">
        <div class="w-full max-w-lg">
            <!-- Header -->
            <div class="text-center mb-8">
                <div class="w-16 h-16 bg-cyan-600 rounded-2xl flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <h1 class="text-2xl font-bold text-gray-800">{{ isRtl ? 'رأيك يهمنا' : 'Your Feedback Matters' }}</h1>
                <p class="text-gray-500 mt-2 text-sm">{{ isRtl ? 'ساعدنا في تحسين خدماتنا' : 'Help us improve our services' }}</p>
                <p v-if="satisfaction.doctor" class="text-cyan-600 text-sm mt-1 font-medium">
                    {{ isRtl ? satisfaction.doctor?.name_ar : satisfaction.doctor?.name_en }}
                </p>
            </div>

            <form @submit.prevent="submit" class="bg-white rounded-2xl shadow-xl p-6 space-y-6">
                <!-- Step 1: Ratings -->
                <div v-show="step === 1" class="space-y-5">
                    <!-- Overall Rating -->
                    <div class="text-center">
                        <label class="block text-sm font-bold text-gray-700 mb-3">{{ isRtl ? 'التقييم العام *' : 'Overall Rating *' }}</label>
                        <div class="flex justify-center gap-2">
                            <button v-for="i in 5" :key="i" type="button" @click="setRating('overall_rating', i)"
                                class="text-4xl transition-transform hover:scale-110" :class="form.overall_rating >= i ? 'text-amber-400' : 'text-gray-200'">★</button>
                        </div>
                    </div>

                    <!-- Sub-ratings -->
                    <div class="grid grid-cols-2 gap-4">
                        <div v-for="field in [
                            { key: 'doctor_rating', ar: 'الطبيب', en: 'Doctor' },
                            { key: 'staff_rating', ar: 'الموظفين', en: 'Staff' },
                            { key: 'cleanliness_rating', ar: 'النظافة', en: 'Cleanliness' },
                            { key: 'waiting_time_rating', ar: 'وقت الانتظار', en: 'Waiting Time' },
                            { key: 'communication_rating', ar: 'التواصل', en: 'Communication' },
                        ]" :key="field.key" class="text-center">
                            <label class="block text-xs text-gray-500 mb-1.5">{{ isRtl ? field.ar : field.en }}</label>
                            <div class="flex justify-center gap-0.5">
                                <button v-for="i in 5" :key="i" type="button" @click="setRating(field.key, i)"
                                    class="text-xl transition" :class="form[field.key] >= i ? 'text-amber-400' : 'text-gray-200'">★</button>
                            </div>
                        </div>
                    </div>

                    <button type="button" @click="step = 2" :disabled="!form.overall_rating"
                        class="w-full py-3 bg-cyan-600 text-white rounded-xl font-medium disabled:opacity-50 disabled:cursor-not-allowed hover:bg-cyan-700 transition">
                        {{ isRtl ? 'التالي' : 'Next' }}
                    </button>
                </div>

                <!-- Step 2: Feedback -->
                <div v-show="step === 2" class="space-y-5">
                    <!-- NPS -->
                    <div class="text-center">
                        <label class="block text-sm font-bold text-gray-700 mb-3">
                            {{ isRtl ? 'ما مدى احتمال أن توصي بنا؟' : 'How likely are you to recommend us?' }}
                        </label>
                        <div class="flex justify-center gap-1.5 flex-wrap">
                            <button v-for="i in 11" :key="i-1" type="button" @click="form.nps_score = i - 1"
                                class="w-9 h-9 rounded-lg text-sm font-medium transition"
                                :class="form.nps_score === i - 1 ? (i <= 7 ? 'bg-red-500 text-white' : i <= 9 ? 'bg-yellow-500 text-white' : 'bg-green-500 text-white') : 'bg-gray-100 text-gray-600 hover:bg-gray-200'">
                                {{ i - 1 }}
                            </button>
                        </div>
                        <div class="flex justify-between text-xs text-gray-400 mt-1 px-2">
                            <span>{{ isRtl ? 'مستبعد جداً' : 'Not likely' }}</span>
                            <span>{{ isRtl ? 'مؤكد جداً' : 'Very likely' }}</span>
                        </div>
                    </div>

                    <!-- Would recommend -->
                    <div class="text-center">
                        <label class="block text-sm font-bold text-gray-700 mb-3">{{ isRtl ? 'هل توصي بنا؟' : 'Would you recommend us?' }}</label>
                        <div class="flex justify-center gap-4">
                            <button type="button" @click="form.would_recommend = true"
                                class="px-6 py-2 rounded-xl text-sm font-medium transition"
                                :class="form.would_recommend === true ? 'bg-green-500 text-white' : 'bg-gray-100 text-gray-600 hover:bg-green-50'">
                                {{ isRtl ? 'نعم' : 'Yes' }} 👍
                            </button>
                            <button type="button" @click="form.would_recommend = false"
                                class="px-6 py-2 rounded-xl text-sm font-medium transition"
                                :class="form.would_recommend === false ? 'bg-red-500 text-white' : 'bg-gray-100 text-gray-600 hover:bg-red-50'">
                                {{ isRtl ? 'لا' : 'No' }} 👎
                            </button>
                        </div>
                    </div>

                    <!-- Improvement areas -->
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-3">{{ isRtl ? 'ما الذي يمكننا تحسينه؟' : 'What can we improve?' }}</label>
                        <div class="flex flex-wrap gap-2">
                            <button v-for="(label, key) in improvementAreas" :key="key" type="button" @click="toggleArea(key)"
                                class="px-3 py-1.5 rounded-full text-xs font-medium transition"
                                :class="form.improvement_areas.includes(key) ? 'bg-cyan-600 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'">
                                {{ isRtl ? label.ar : label.en }}
                            </button>
                        </div>
                    </div>

                    <!-- Comments -->
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">{{ isRtl ? 'تعليقات إضافية' : 'Additional Comments' }}</label>
                        <textarea v-model="form.comments" rows="3" :placeholder="isRtl ? 'أخبرنا المزيد...' : 'Tell us more...'"
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl text-sm focus:ring-cyan-500 focus:border-cyan-500 resize-none" />
                    </div>

                    <div class="flex gap-3">
                        <button type="button" @click="step = 1" class="flex-1 py-3 bg-gray-100 text-gray-600 rounded-xl font-medium hover:bg-gray-200 transition">
                            {{ isRtl ? 'رجوع' : 'Back' }}
                        </button>
                        <button type="submit" :disabled="submitting"
                            class="flex-1 py-3 bg-cyan-600 text-white rounded-xl font-medium disabled:opacity-50 hover:bg-cyan-700 transition">
                            {{ submitting ? (isRtl ? 'جاري الإرسال...' : 'Submitting...') : (isRtl ? 'إرسال التقييم' : 'Submit Feedback') }}
                        </button>
                    </div>
                </div>
            </form>

            <p class="text-center text-xs text-gray-400 mt-4">{{ isRtl ? 'تقييمك سري ويساعدنا في التحسين المستمر' : 'Your feedback is confidential and helps us improve' }}</p>
        </div>
    </div>
</template>
