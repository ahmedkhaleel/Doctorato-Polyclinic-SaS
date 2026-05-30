<script setup>
import { useForm, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import DoctorLayout from '@/Layouts/DoctorLayout.vue';

defineOptions({ layout: DoctorLayout });

const page = usePage();
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');
const ACCENT = '#DB2777';

const props = defineProps({
    papSmears: { type: Array, default: () => [] },
    contraception: { type: Array, default: () => [] },
    patients: { type: Array, default: () => [] },
});

const modal = ref(null); // 'pap' | 'contra'
const papForm = useForm({ patient_id: '', test_date: new Date().toISOString().slice(0, 10), result: 'normal', hpv_status: 'unknown', next_due_date: '', notes: '', bill: true });
const contraForm = useForm({ patient_id: '', method: '', start_date: new Date().toISOString().slice(0, 10), follow_up_date: '', status: 'active', notes: '' });

const close = () => { modal.value = null; };
function savePap() { papForm.post(route('doctor.obgyn.pap-smear.store'), { preserveScroll: true, onSuccess: () => { close(); papForm.reset(); } }); }
function saveContra() { contraForm.post(route('doctor.obgyn.contraception.store'), { preserveScroll: true, onSuccess: () => { close(); contraForm.reset(); } }); }

function fmt(d) { return d ? new Date(d).toLocaleDateString(isRtl.value ? 'ar-EG' : 'en-GB', { day: 'numeric', month: 'short', year: 'numeric' }) : '—'; }
function papBadge(r) { return r === 'normal' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700'; }
</script>

<template>
    <div class="space-y-6" :dir="isRtl ? 'rtl' : 'ltr'">
        <div class="flex items-center justify-between flex-wrap gap-3">
            <div class="flex items-center gap-3">
                <span class="w-2 h-8 rounded-full" :style="{ background: ACCENT }"></span>
                <h1 class="text-xl font-bold text-gray-800">{{ isRtl ? 'أمراض النساء' : 'Gynecology' }}</h1>
            </div>
            <div class="flex gap-2">
                <button @click="modal = 'pap'" class="inline-flex items-center gap-1.5 px-4 py-2.5 rounded-xl text-sm font-semibold text-white" :style="{ background: ACCENT }">+ {{ isRtl ? 'مسحة عنق الرحم' : 'Pap Smear' }}</button>
                <button @click="modal = 'contra'" class="inline-flex items-center gap-1.5 px-4 py-2.5 rounded-xl text-sm font-semibold text-white" style="background:#1B365D">+ {{ isRtl ? 'منع الحمل' : 'Contraception' }}</button>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Pap smears -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
                <h2 class="font-bold text-gray-800 mb-3">{{ isRtl ? 'مسحات عنق الرحم' : 'Pap Smears' }}</h2>
                <div v-if="papSmears.length === 0" class="text-center text-gray-400 py-8 text-sm">{{ isRtl ? 'لا سجلات' : 'No records' }}</div>
                <ul v-else class="divide-y divide-gray-50">
                    <li v-for="s in papSmears" :key="s.id" class="py-2.5 flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-800">{{ s.patient?.full_name }}</p>
                            <p class="text-xs text-gray-400" dir="ltr">{{ fmt(s.test_date) }}<template v-if="s.next_due_date"> · {{ isRtl ? 'تجديد' : 'due' }} {{ fmt(s.next_due_date) }}</template></p>
                        </div>
                        <span v-if="s.result" class="text-xs font-semibold px-2.5 py-1 rounded-full uppercase" :class="papBadge(s.result)">{{ s.result }}</span>
                    </li>
                </ul>
            </div>

            <!-- Contraception -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
                <h2 class="font-bold text-gray-800 mb-3">{{ isRtl ? 'وسائل منع الحمل' : 'Contraception' }}</h2>
                <div v-if="contraception.length === 0" class="text-center text-gray-400 py-8 text-sm">{{ isRtl ? 'لا سجلات' : 'No records' }}</div>
                <ul v-else class="divide-y divide-gray-50">
                    <li v-for="c in contraception" :key="c.id" class="py-2.5 flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-800">{{ c.patient?.full_name }}</p>
                            <p class="text-xs text-gray-400">{{ c.method }}<template v-if="c.follow_up_date"> · <span dir="ltr">{{ fmt(c.follow_up_date) }}</span></template></p>
                        </div>
                        <span class="text-xs font-semibold px-2.5 py-1 rounded-full" :class="c.status === 'active' ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-100 text-gray-500'">{{ c.status === 'active' ? (isRtl ? 'نشط' : 'Active') : (isRtl ? 'موقوف' : 'Stopped') }}</span>
                    </li>
                </ul>
            </div>
        </div>

        <Teleport to="body">
            <Transition name="modal">
                <div v-if="modal" class="fixed inset-0 z-50 flex items-center justify-center p-4" :dir="isRtl ? 'rtl' : 'ltr'">
                    <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" @click="close"></div>
                    <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md">
                        <div class="p-5 border-b border-gray-100 flex items-center justify-between">
                            <h3 class="font-bold text-gray-800">{{ modal === 'pap' ? (isRtl ? 'مسحة عنق الرحم' : 'Pap Smear') : (isRtl ? 'سجل منع الحمل' : 'Contraception') }}</h3>
                            <button @click="close" class="text-gray-400 hover:text-gray-600"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
                        </div>

                        <!-- Pap -->
                        <form v-if="modal === 'pap'" @submit.prevent="savePap" class="p-5 space-y-3">
                            <div><label class="block text-xs font-medium text-gray-600 mb-1">{{ isRtl ? 'المريضة' : 'Patient' }} *</label>
                                <select v-model="papForm.patient_id" required class="w-full rounded-xl border-gray-200 text-sm focus:border-rose-400 focus:ring-rose-400">
                                    <option value="" disabled>{{ isRtl ? 'اختر…' : 'Select…' }}</option>
                                    <option v-for="p in patients" :key="p.id" :value="p.id">{{ p.full_name }} — {{ p.file_number }}</option>
                                </select>
                                <p v-if="papForm.errors.patient_id" class="text-xs text-red-600 mt-1">{{ papForm.errors.patient_id }}</p>
                            </div>
                            <div class="grid grid-cols-2 gap-3">
                                <div><label class="block text-xs font-medium text-gray-600 mb-1">{{ isRtl ? 'التاريخ' : 'Date' }} *</label><input v-model="papForm.test_date" type="date" required class="w-full rounded-xl border-gray-200 text-sm focus:border-rose-400 focus:ring-rose-400" /></div>
                                <div><label class="block text-xs font-medium text-gray-600 mb-1">{{ isRtl ? 'النتيجة' : 'Result' }}</label>
                                    <select v-model="papForm.result" class="w-full rounded-xl border-gray-200 text-sm focus:border-rose-400 focus:ring-rose-400"><option value="normal">Normal</option><option value="ascus">ASCUS</option><option value="lsil">LSIL</option><option value="hsil">HSIL</option><option value="cancer">Cancer</option></select>
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-3">
                                <div><label class="block text-xs font-medium text-gray-600 mb-1">HPV</label><select v-model="papForm.hpv_status" class="w-full rounded-xl border-gray-200 text-sm focus:border-rose-400 focus:ring-rose-400"><option value="unknown">{{ isRtl ? 'غير معروف' : 'Unknown' }}</option><option value="negative">{{ isRtl ? 'سلبي' : 'Negative' }}</option><option value="positive">{{ isRtl ? 'إيجابي' : 'Positive' }}</option></select></div>
                                <div><label class="block text-xs font-medium text-gray-600 mb-1">{{ isRtl ? 'موعد التجديد' : 'Next due' }}</label><input v-model="papForm.next_due_date" type="date" class="w-full rounded-xl border-gray-200 text-sm focus:border-rose-400 focus:ring-rose-400" /></div>
                            </div>
                            <label class="flex items-center gap-2 text-sm text-gray-700"><input v-model="papForm.bill" type="checkbox" class="rounded text-rose-600" /> {{ isRtl ? 'إصدار فاتورة' : 'Create invoice' }}</label>
                            <div class="flex justify-end gap-2 pt-2"><button type="button" @click="close" class="px-4 py-2.5 rounded-xl text-gray-600 hover:bg-gray-100 text-sm font-medium">{{ isRtl ? 'إلغاء' : 'Cancel' }}</button><button type="submit" :disabled="papForm.processing" class="px-5 py-2.5 rounded-xl text-white text-sm font-semibold disabled:opacity-50" :style="{ background: ACCENT }">{{ isRtl ? 'حفظ' : 'Save' }}</button></div>
                        </form>

                        <!-- Contraception -->
                        <form v-else @submit.prevent="saveContra" class="p-5 space-y-3">
                            <div><label class="block text-xs font-medium text-gray-600 mb-1">{{ isRtl ? 'المريضة' : 'Patient' }} *</label>
                                <select v-model="contraForm.patient_id" required class="w-full rounded-xl border-gray-200 text-sm focus:border-rose-400 focus:ring-rose-400">
                                    <option value="" disabled>{{ isRtl ? 'اختر…' : 'Select…' }}</option>
                                    <option v-for="p in patients" :key="p.id" :value="p.id">{{ p.full_name }} — {{ p.file_number }}</option>
                                </select>
                                <p v-if="contraForm.errors.patient_id" class="text-xs text-red-600 mt-1">{{ contraForm.errors.patient_id }}</p>
                            </div>
                            <div><label class="block text-xs font-medium text-gray-600 mb-1">{{ isRtl ? 'الوسيلة' : 'Method' }} *</label><input v-model="contraForm.method" type="text" required class="w-full rounded-xl border-gray-200 text-sm focus:border-rose-400 focus:ring-rose-400" placeholder="IUD, Pills, Implant…" /></div>
                            <div class="grid grid-cols-2 gap-3">
                                <div><label class="block text-xs font-medium text-gray-600 mb-1">{{ isRtl ? 'البداية' : 'Start' }}</label><input v-model="contraForm.start_date" type="date" class="w-full rounded-xl border-gray-200 text-sm focus:border-rose-400 focus:ring-rose-400" /></div>
                                <div><label class="block text-xs font-medium text-gray-600 mb-1">{{ isRtl ? 'متابعة' : 'Follow-up' }}</label><input v-model="contraForm.follow_up_date" type="date" class="w-full rounded-xl border-gray-200 text-sm focus:border-rose-400 focus:ring-rose-400" /></div>
                            </div>
                            <div class="flex justify-end gap-2 pt-2"><button type="button" @click="close" class="px-4 py-2.5 rounded-xl text-gray-600 hover:bg-gray-100 text-sm font-medium">{{ isRtl ? 'إلغاء' : 'Cancel' }}</button><button type="submit" :disabled="contraForm.processing" class="px-5 py-2.5 rounded-xl text-white text-sm font-semibold disabled:opacity-50" style="background:#1B365D">{{ isRtl ? 'حفظ' : 'Save' }}</button></div>
                        </form>
                    </div>
                </div>
            </Transition>
        </Teleport>
    </div>
</template>

<style scoped>
.modal-enter-active, .modal-leave-active { transition: opacity .25s ease; }
.modal-enter-from, .modal-leave-to { opacity: 0; }
@media (prefers-reduced-motion: reduce) { .modal-enter-active, .modal-leave-active { transition: none; } }
</style>
