<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { Link, usePage, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import ConfirmModal from '@/Components/Admin/ConfirmModal.vue';
import { useCurrency } from '@/Composables/useCurrency';

const page = usePage();
const { formatCurrency } = useCurrency();
const locale = computed(() => page.props.locale || 'ar');
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const props = defineProps({
    patient: Object,
    chart: Object,
    treatments: Object,
    entries: Object,
    doctors: { type: Array, default: () => [] },
    conditions: Array,
    surfaces: Array,
    allTeeth: Array,
    deciduousTeeth: { type: Array, default: () => [] },
    isChild: { type: Boolean, default: false },
});

// ─── State ──────────────────────────────────────────
const selectedTooth = ref(null);
const editForm = ref({ condition: 'healthy', surfaces: [], notes: '', status: 'present' });
const showModal = ref(false);
const showEntryModal = ref(false);
const mounted = ref(false);
const hoveredTooth = ref(null);
const saving = ref(false);
const savingEntry = ref(false);
const entryTab = ref('history');
const mediaPreview = ref([]);
const chartMode = ref(props.isChild ? 'deciduous' : 'adult');
const surfaceEditMode = ref(false); // direct surface drawing mode
const showToothDetail = ref(false); // detail flyout

const entryForm = ref({
    entry_type: 'examination', title: '', description: '', condition_before: '',
    condition_after: '', surfaces: [], cost: '', status: 'recorded', doctor_id: '',
    entry_date: new Date().toISOString().split('T')[0], files: [],
});

onMounted(() => { setTimeout(() => { mounted.value = true; }, 80); });

// ─── FDI Layout ─────────────────────────────────────
const upperRight = [18, 17, 16, 15, 14, 13, 12, 11];
const upperLeft  = [21, 22, 23, 24, 25, 26, 27, 28];
const lowerRight = [48, 47, 46, 45, 44, 43, 42, 41];
const lowerLeft  = [31, 32, 33, 34, 35, 36, 37, 38];

// Deciduous FDI Layout
const decUpperRight = [55, 54, 53, 52, 51];
const decUpperLeft  = [61, 62, 63, 64, 65];
const decLowerRight = [85, 84, 83, 82, 81];
const decLowerLeft  = [71, 72, 73, 74, 75];

// Active layout based on mode
const activeUpperRight = computed(() => chartMode.value === 'deciduous' ? decUpperRight : upperRight);
const activeUpperLeft  = computed(() => chartMode.value === 'deciduous' ? decUpperLeft : upperLeft);
const activeLowerRight = computed(() => chartMode.value === 'deciduous' ? decLowerRight : lowerRight);
const activeLowerLeft  = computed(() => chartMode.value === 'deciduous' ? decLowerLeft : lowerLeft);
const allActiveTeeth = computed(() => [...activeUpperRight.value, ...activeUpperLeft.value, ...activeLowerRight.value, ...activeLowerLeft.value]);

// ─── Tooth Type Maps ────────────────────────────────
const toothTypeMap = {
    18:'molar3',17:'molar2',16:'molar1',15:'premolar2',14:'premolar1',13:'canine',12:'lateral',11:'central',
    21:'central',22:'lateral',23:'canine',24:'premolar1',25:'premolar2',26:'molar1',27:'molar2',28:'molar3',
    31:'central',32:'lateral',33:'canine',34:'premolar1',35:'premolar2',36:'molar1',37:'molar2',38:'molar3',
    41:'central',42:'lateral',43:'canine',44:'premolar1',45:'premolar2',46:'molar1',47:'molar2',48:'molar3',
    // Deciduous teeth mapping
    51:'dec_central',52:'dec_lateral',53:'dec_canine',54:'dec_molar1',55:'dec_molar2',
    61:'dec_central',62:'dec_lateral',63:'dec_canine',64:'dec_molar1',65:'dec_molar2',
    71:'dec_central',72:'dec_lateral',73:'dec_canine',74:'dec_molar1',75:'dec_molar2',
    81:'dec_central',82:'dec_lateral',83:'dec_canine',84:'dec_molar1',85:'dec_molar2',
};

// ─── SVG Paths with 3D-like depth ───────────────────
const sideViewPaths = {
    central:   { crown: 'M10,32 C10,28 8,24 8,20 C8,16 10,14 12,13 C14,12 18,12 20,13 C22,14 24,16 24,20 C24,24 22,28 22,32 Z', roots: ['M14,13 C14,10 13,6 13,3 C13,1 15,0 16,0 C17,0 19,1 19,3 C19,6 18,10 18,13'], enamel: 'M10,32 C10,29 9,25 9,22 C9,19 11,17 13,16 C15,15 17,15 19,16 C21,17 23,19 23,22 C23,25 22,29 22,32 Z', highlight: 'M12,20 C12,18 13,16 15,15.5 C16,15.3 17,15.5 17,16 C17,17 15,18 14,20 C13,21 12,21 12,20 Z', width: 32, height: 36 },
    lateral:   { crown: 'M11,32 C11,28 9,24 9,21 C9,17 11,15 13,14 C15,13 17,13 19,14 C21,15 23,17 23,21 C23,24 21,28 21,32 Z', roots: ['M14,14 C14,11 13,7 13,3 C13,1 15,0 16,0 C17,0 18,1 18,3 C18,7 17,11 17,14'], enamel: 'M11,32 C11,29 10,25 10,22 C10,19 12,17 14,16 C15,15.5 17,15.5 18,16 C20,17 22,19 22,22 C22,25 21,29 21,32 Z', highlight: 'M13,21 C13,19 14,17 15.5,16.5 C16,16.3 17,16.5 16.5,17 C16,18 15,19 14,21 C13.5,22 13,21.5 13,21 Z', width: 32, height: 36 },
    canine:    { crown: 'M10,32 C10,28 8,24 8,20 C8,16 10,14 13,12 C15,11 16,10.5 17,11 C18,11.5 20,14 22,16 C24,18 24,24 24,28 C24,30 22,32 22,32 Z', roots: ['M14,12 C14,9 13,5 12,2 C12,0.5 14,0 15,0 C16,0 18,0.5 18,2 C17,5 16,9 16,12'], enamel: 'M10,32 C10,29 9,25 9,22 C9,18 11,16 14,14 C15,13 17,14 19,16 C21,18 23,20 23,24 C23,28 22,30 22,32 Z', highlight: 'M12,22 C12,20 13,17 15,15 C16,14 17,15 16,16 C15,18 14,20 13,22 C12.5,23 12,22.5 12,22 Z', width: 32, height: 36 },
    premolar1: { crown: 'M8,32 C8,28 7,24 7,20 C7,17 9,14 11,12 C12,11 14,10 16,10 C18,10 20,11 21,12 C23,14 25,17 25,20 C25,24 24,28 24,32 Z', roots: ['M12,12 C12,9 11,6 10,3 C10,1 12,0 13,0 C14,0 15,1 15,3 C14,6 14,9 14,11','M19,12 C19,9 20,6 21,3 C21,1 19,0 18,0 C17,0 16,1 16,3 C17,6 17,9 18,11'], enamel: 'M9,32 C9,28 8,24 8,21 C8,18 10,16 12,14 C14,13 18,13 20,14 C22,16 24,18 24,21 C24,24 23,28 23,32 Z', highlight: 'M11,21 C11,19 12,16 14,14.5 C15,14 16,14.5 15.5,15 C14.5,16.5 13,18 12,20.5 C11.5,21.5 11,21.5 11,21 Z', width: 32, height: 36 },
    premolar2: { crown: 'M8,32 C8,28 7,24 7,20 C7,17 9,14 11,12 C12,11 14,10.5 16,10.5 C18,10.5 20,11 21,12 C23,14 25,17 25,20 C25,24 24,28 24,32 Z', roots: ['M14,12 C14,9 13,5 13,2 C13,0.5 15,0 16,0 C17,0 19,0.5 19,2 C19,5 18,9 18,12'], enamel: 'M9,32 C9,28 8,24 8,21 C8,18 10,16 12,14 C14,13 18,13 20,14 C22,16 24,18 24,21 C24,24 23,28 23,32 Z', highlight: 'M11,21 C11,19 12,16 14,14.5 C15,14 16,14.5 15.5,15 C14.5,16.5 13,18 12,20.5 C11.5,21.5 11,21.5 11,21 Z', width: 32, height: 36 },
    molar1:    { crown: 'M5,32 C5,28 4,24 4,20 C4,16 6,13 9,11 C11,10 14,9.5 18,9.5 C22,9.5 25,10 27,11 C30,13 32,16 32,20 C32,24 31,28 31,32 Z', roots: ['M10,11 C10,8 9,5 8,2 C8,0.5 10,0 11,0 C12,0 13,0.5 13,2 C12,5 12,8 12,11','M17,10 C17,7 17,4 17,2 C17,0.5 18,0 19,0 C20,0 20,0.5 20,2 C20,4 20,7 20,10','M25,11 C25,8 26,5 27,2 C27,0.5 25,0 24,0 C23,0 22,0.5 22,2 C23,5 23,8 24,11'], enamel: 'M6,32 C6,28 5,24 5,21 C5,18 7,15 10,13 C12,12 16,11 20,11 C24,11 26,12 28,14 C30,16 31,19 31,22 C31,25 30,28 30,32 Z', highlight: 'M8,21 C8,18 10,15 13,13 C14,12.5 15,13 14,14 C12,16 10,18 9,21 C8.5,22 8,22 8,21 Z', width: 36, height: 36 },
    molar2:    { crown: 'M6,32 C6,28 5,24 5,20 C5,16 7,13 10,11 C12,10 15,9.5 18,9.5 C21,9.5 24,10 26,11 C29,13 31,16 31,20 C31,24 30,28 30,32 Z', roots: ['M11,11 C11,8 10,5 9,2 C9,0.5 11,0 12,0 C13,0 14,1 13,3 C13,5 12,8 12,11','M18,10 C18,7 18,4 18,2 C18,0.5 19,0 20,0 C21,0 21,1 21,2 C21,4 20,7 20,10','M24,11 C24,8 25,5 26,2 C26,0.5 24,0 23,0 C22,0 22,1 22,3 C23,5 23,8 23,11'], enamel: 'M7,32 C7,28 6,24 6,21 C6,18 8,15 11,13 C13,12 16,11.5 19,11.5 C22,11.5 24,12 26,14 C28,16 29,19 29,22 C29,25 28,28 28,32 Z', highlight: 'M9,21 C9,18 11,15 14,13 C15,12.5 16,13 15,14 C13,16 11,18 10,21 C9.5,22 9,22 9,21 Z', width: 36, height: 36 },
    molar3:    { crown: 'M7,32 C7,29 6,25 6,21 C6,17 8,14 11,12 C13,11 15,10.5 17,10.5 C19,10.5 22,11 24,12 C27,14 29,17 29,21 C29,25 28,29 28,32 Z', roots: ['M12,12 C12,9 11,6 10,3 C10,1 12,0 13,0 C14,0 15,1 15,3 C14,6 14,9 14,12','M22,12 C22,9 23,6 24,3 C24,1 22,0 21,0 C20,0 19,1 19,3 C20,6 20,9 20,12'], enamel: 'M8,32 C8,29 7,25 7,22 C7,19 9,16 12,14 C14,13 16,12.5 18,12.5 C20,12.5 22,13 24,15 C26,17 27,20 27,23 C27,26 26,29 26,32 Z', highlight: 'M10,22 C10,19 12,16 14,14.5 C15,14 16,14.5 15,15.5 C13.5,17 12,19 11,22 C10.5,23 10,22.5 10,22 Z', width: 34, height: 36 },
    // Deciduous teeth - rounder, smaller
    dec_central: { crown: 'M11,30 C11,27 10,24 10,21 C10,18 12,16 14,15 C15,14.5 17,14.5 18,15 C20,16 22,18 22,21 C22,24 21,27 21,30 Z', roots: ['M15,15 C15,12 14,8 14,5 C14,3 15.5,2 16,2 C16.5,2 18,3 18,5 C18,8 17,12 17,15'], enamel: 'M12,30 C12,27 11,24 11,22 C11,19 13,17 15,16 C16,15.5 17,15.5 18,16 C20,17 21,19 21,22 C21,24 20,27 20,30 Z', highlight: 'M14,22 C14,20 15,18 16,17 C16.5,16.5 17,17 16.5,17.5 C16,18.5 15,20 14.5,22 C14.3,22.5 14,22.3 14,22 Z', width: 32, height: 32 },
    dec_lateral: { crown: 'M12,30 C12,27 11,24 11,22 C11,19 12,17 14,16 C15,15 17,15 18,16 C20,17 21,19 21,22 C21,24 20,27 20,30 Z', roots: ['M15,16 C15,13 14,9 14,6 C14,4 15.5,3 16,3 C16.5,3 18,4 18,6 C18,9 17,13 17,16'], enamel: 'M12.5,30 C12.5,27 12,24 12,22 C12,20 13,18 15,17 C16,16.5 17,16.5 18,17 C19,18 20,20 20,22 C20,24 19.5,27 19.5,30 Z', highlight: 'M14.5,22 C14.5,20 15,18 16,17.5 C16.5,17 17,17.5 16.5,18 C16,19 15.5,20 15,22 C14.8,22.5 14.5,22.3 14.5,22 Z', width: 32, height: 32 },
    dec_canine:  { crown: 'M11,30 C11,27 10,24 10,21 C10,18 12,15 14,14 C15,13 16,12.5 17,13 C18,13.5 20,16 21,18 C22,20 22,24 22,27 C22,29 21,30 21,30 Z', roots: ['M14,14 C14,11 13,7 13,4 C13,2 14.5,1 15.5,1 C16.5,1 18,2 18,4 C17,7 17,11 16,14'], enamel: 'M12,30 C12,27 11,24 11,22 C11,19 13,17 15,15.5 C16,15 17,15.5 18.5,17 C20,19 21,21 21,24 C21,27 20.5,29 20.5,30 Z', highlight: 'M13,22 C13,20 14,17 15.5,16 C16,15.5 17,16 16,17 C15,18.5 14,20 13.5,22 C13.3,22.5 13,22.3 13,22 Z', width: 32, height: 32 },
    dec_molar1:  { crown: 'M8,30 C8,27 7,24 7,21 C7,18 9,15 11,13 C13,12 15,11 17,11 C19,11 21,12 23,13 C25,15 27,18 27,21 C27,24 26,27 26,30 Z', roots: ['M12,13 C12,10 11,7 10,4 C10,2 12,1 13,1 C14,1 15,2 15,4 C14,7 14,10 14,13','M20,13 C20,10 21,7 22,4 C22,2 20,1 19,1 C18,1 17,2 17,4 C18,7 18,10 19,13'], enamel: 'M9,30 C9,27 8,24 8,22 C8,19 10,17 12,15 C14,14 18,14 20,15 C22,17 24,19 24,22 C24,24 23,27 23,30 Z', highlight: 'M11,22 C11,19 12,17 14,15.5 C15,15 16,15.5 15,16 C13.5,17.5 12.5,19.5 12,22 C11.5,23 11,22.5 11,22 Z', width: 34, height: 32 },
    dec_molar2:  { crown: 'M7,30 C7,27 6,24 6,21 C6,17 8,14 11,12 C13,11 15,10.5 18,10.5 C21,10.5 23,11 25,12 C28,14 30,17 30,21 C30,24 29,27 29,30 Z', roots: ['M11,12 C11,9 10,6 9,3 C9,1 11,0 12,0 C13,0 14,1 14,3 C13,6 13,9 12,12','M24,12 C24,9 25,6 26,3 C26,1 24,0 23,0 C22,0 21,1 21,3 C22,6 22,9 23,12'], enamel: 'M8,30 C8,27 7,24 7,22 C7,19 9,16 12,14 C14,13 17,12 20,12 C23,12 25,13 27,15 C29,17 29,20 29,22 C29,25 28,28 28,30 Z', highlight: 'M10,22 C10,19 12,16 14,14 C15,13.5 16,14 15,15 C13.5,17 12,19 11,22 C10.5,23 10,22.5 10,22 Z', width: 36, height: 32 },
};

// ─── Occlusal Shapes ────────────────────────────────
const occlusalShapes = {
    anterior:  { outer: 'M6,2 C10,0 18,0 22,2 C26,5 28,10 28,16 C28,22 26,27 22,30 C18,32 10,32 6,30 C2,27 0,22 0,16 C0,10 2,5 6,2Z', buccal: 'M6,2 C10,0 18,0 22,2 C20,7 18,10 14,10 C10,10 8,7 6,2Z', lingual: 'M6,30 C10,32 18,32 22,30 C20,25 18,22 14,22 C10,22 8,25 6,30Z', mesial: 'M6,2 C2,5 0,10 0,16 C0,22 2,27 6,30 C7,24 7,20 7,16 C7,12 7,8 6,2Z', distal: 'M22,2 C26,5 28,10 28,16 C28,22 26,27 22,30 C21,24 21,20 21,16 C21,12 21,8 22,2Z', occlusal: 'M7,10 C10,9 18,9 21,10 C22,13 22,19 21,22 C18,23 10,23 7,22 C6,19 6,13 7,10Z', size: 28 },
    posterior: { outer: 'M4,3 C8,0 24,0 28,3 C32,7 34,12 34,16 C34,21 32,26 28,29 C24,32 8,32 4,29 C0,26 -2,21 -2,16 C-2,12 0,7 4,3Z', buccal: 'M4,3 C8,0 24,0 28,3 C25,8 22,10 16,10 C10,10 7,8 4,3Z', lingual: 'M4,29 C8,32 24,32 28,29 C25,24 22,22 16,22 C10,22 7,24 4,29Z', mesial: 'M4,3 C0,7 -2,12 -2,16 C-2,21 0,26 4,29 C5,23 5,19 5,16 C5,13 5,9 4,3Z', distal: 'M28,3 C32,7 34,12 34,16 C34,21 32,26 28,29 C27,23 27,19 27,16 C27,13 27,9 28,3Z', occlusal: 'M6,10 C10,9 22,9 26,10 C27,13 27,19 26,22 C22,23 10,23 6,22 C5,19 5,13 6,10Z', size: 32 },
};

// ─── Condition Theming ──────────────────────────────
const conditionTheme = {
    healthy:    { fill: '#F0FDF4', stroke: '#86EFAC', inner: '#BBF7D0', text: '#166534', accent: '#22C55E', gradient: ['#D1FAE5','#F5E7C8'], icon: 'check' },
    decayed:    { fill: '#FEF2F2', stroke: '#FCA5A5', inner: '#FECACA', text: '#991B1B', accent: '#EF4444', gradient: ['#FEE2E2','#FECACA'], icon: 'bacteria' },
    filled:     { fill: '#EFF6FF', stroke: '#93C5FD', inner: '#BFDBFE', text: '#0F2444', accent: '#1B365D', gradient: ['#F5E7C8','#BFDBFE'], icon: 'fill' },
    missing:    { fill: '#F9FAFB', stroke: '#D1D5DB', inner: '#E5E7EB', text: '#6B7280', accent: '#9CA3AF', gradient: ['#F3F4F6','#E5E7EB'], icon: 'x' },
    crown:      { fill: '#FEFCE8', stroke: '#FDE047', inner: '#FEF08A', text: '#854D0E', accent: '#C4A265', gradient: ['#FEF9C3','#F5E7C8'], icon: 'crown' },
    bridge:     { fill: '#FAF5FF', stroke: '#2C4E7A', inner: '#DDD6FE', text: '#6B21A8', accent: '#1B365D', gradient: ['#EDE9FE','#DDD6FE'], icon: 'bridge' },
    implant:    { fill: '#EEF2FF', stroke: '#A5B4FC', inner: '#C7D2FE', text: '#3730A3', accent: '#1B365D', gradient: ['#F5E7C8','#C7D2FE'], icon: 'screw' },
    root_canal: { fill: '#FFF7ED', stroke: '#FDBA74', inner: '#FED7AA', text: '#9A3412', accent: '#C4A265', gradient: ['#FFEDD5','#FED7AA'], icon: 'canal' },
    extracted:  { fill: '#F3F4F6', stroke: '#9CA3AF', inner: '#D1D5DB', text: '#374151', accent: '#6B7280', gradient: ['#E5E7EB','#D1D5DB'], icon: 'extracted' },
};

// ─── Condition Icons (SVG path data for 16x16 viewBox) ─
const conditionIcons = {
    check:     'M13.78 4.22a.75.75 0 010 1.06l-7.25 7.25a.75.75 0 01-1.06 0L2.22 9.28a.75.75 0 011.06-1.06L6 10.94l6.72-6.72a.75.75 0 011.06 0z',
    bacteria:  'M8 1a1 1 0 011 1v1.5a.5.5 0 001 1H11.5a.5.5 0 00.5-.5V3a1 1 0 112 0v1a2.5 2.5 0 01-2.5 2.5H10v1h1.5A2.5 2.5 0 0114 10v1a1 1 0 11-2 0v-.5a.5.5 0 00-.5-.5H10v1.5A2.5 2.5 0 017.5 14H7a1 1 0 110-2h.5a.5.5 0 00.5-.5V10H6.5A2.5 2.5 0 014 7.5V7a1 1 0 012 0v.5a.5.5 0 00.5.5H8V6.5A2.5 2.5 0 015.5 4H5a1 1 0 010-2h.5A2.5 2.5 0 018 4.5V6h.5A.5.5 0 009 5.5V2a1 1 0 010-2z',
    fill:      'M8 2L3 7l3 3h4l3-3L8 2zM4 12a1 1 0 000 2h8a1 1 0 100-2H4z',
    x:         'M4.22 4.22a.75.75 0 011.06 0L8 6.94l2.72-2.72a.75.75 0 111.06 1.06L9.06 8l2.72 2.72a.75.75 0 11-1.06 1.06L8 9.06l-2.72 2.72a.75.75 0 01-1.06-1.06L6.94 8 4.22 5.28a.75.75 0 010-1.06z',
    crown:     'M2 12l2-8 2.5 4L8 3l1.5 5L12 4l2 8H2z M3 13h10v1H3v-1z',
    bridge:    'M1 7h3v6H1V7zm5-3h4v9H6V4zm5 3h3v6h-3V7z',
    screw:     'M7 1h2v2H7V1zM6 4h4v1H6V4zm0 2h4v1H6V6zm0 2h4v1H6V8zm0 2h4v1H6v-1zm1 2h2v3H7v-3z',
    canal:     'M7 1a1 1 0 012 0v4l2 2v6a1 1 0 01-2 0V8L8 7 7 8v5a1 1 0 01-2 0V7l2-2V1z',
    extracted: 'M8 1L6 5H4l3 4-1 5h4l-1-5 3-4h-2L8 1z',
};

const conditionLabels = computed(() => ({
    healthy: isRtl.value ? 'سليم' : 'Healthy', decayed: isRtl.value ? 'متسوس' : 'Decayed',
    filled: isRtl.value ? 'محشو' : 'Filled', missing: isRtl.value ? 'مفقود' : 'Missing',
    crown: isRtl.value ? 'تاج' : 'Crown', bridge: isRtl.value ? 'جسر' : 'Bridge',
    implant: isRtl.value ? 'زرعة' : 'Implant', root_canal: isRtl.value ? 'علاج عصب' : 'Root Canal',
    extracted: isRtl.value ? 'مخلوع' : 'Extracted',
}));

const surfaceLabels = computed(() => ({
    mesial: isRtl.value ? 'وسطي' : 'Mesial', distal: isRtl.value ? 'بعيد' : 'Distal',
    buccal: isRtl.value ? 'خدي' : 'Buccal', lingual: isRtl.value ? 'لساني' : 'Lingual',
    occlusal: isRtl.value ? 'إطباقي' : 'Occlusal',
}));

const entryTypeLabels = computed(() => ({
    examination: isRtl.value ? 'فحص' : 'Examination', treatment: isRtl.value ? 'علاج' : 'Treatment',
    note: isRtl.value ? 'ملاحظة' : 'Note', follow_up: isRtl.value ? 'متابعة' : 'Follow-up',
    complication: isRtl.value ? 'مضاعفة' : 'Complication', media_only: isRtl.value ? 'صور/ملفات' : 'Media',
}));

const entryTypeIcons = {};

// Deciduous tooth name mapping
const deciduousNames = computed(() => ({
    51: isRtl.value ? 'القاطعة المركزية' : 'Central Incisor',
    52: isRtl.value ? 'القاطعة الجانبية' : 'Lateral Incisor',
    53: isRtl.value ? 'الناب' : 'Canine',
    54: isRtl.value ? 'الضرس الأول' : '1st Molar',
    55: isRtl.value ? 'الضرس الثاني' : '2nd Molar',
    61: isRtl.value ? 'القاطعة المركزية' : 'Central Incisor',
    62: isRtl.value ? 'القاطعة الجانبية' : 'Lateral Incisor',
    63: isRtl.value ? 'الناب' : 'Canine',
    64: isRtl.value ? 'الضرس الأول' : '1st Molar',
    65: isRtl.value ? 'الضرس الثاني' : '2nd Molar',
    71: isRtl.value ? 'القاطعة المركزية' : 'Central Incisor',
    72: isRtl.value ? 'القاطعة الجانبية' : 'Lateral Incisor',
    73: isRtl.value ? 'الناب' : 'Canine',
    74: isRtl.value ? 'الضرس الأول' : '1st Molar',
    75: isRtl.value ? 'الضرس الثاني' : '2nd Molar',
    81: isRtl.value ? 'القاطعة المركزية' : 'Central Incisor',
    82: isRtl.value ? 'القاطعة الجانبية' : 'Lateral Incisor',
    83: isRtl.value ? 'الناب' : 'Canine',
    84: isRtl.value ? 'الضرس الأول' : '1st Molar',
    85: isRtl.value ? 'الضرس الثاني' : '2nd Molar',
}));

// ─── Stats ──────────────────────────────────────────
const chartStats = computed(() => {
    const stats = {};
    for (const cond of Object.keys(conditionTheme)) stats[cond] = 0;
    for (const tooth of allActiveTeeth.value) {
        const c = getToothData(tooth).condition;
        if (stats[c] !== undefined) stats[c]++;
    }
    return stats;
});
const totalRecorded = computed(() => {
    let count = 0;
    for (const tooth of allActiveTeeth.value) {
        if (props.chart[tooth]) count++;
    }
    return count;
});
const totalTeeth = computed(() => allActiveTeeth.value.length);

// ─── Helpers ────────────────────────────────────────
function getToothData(num) { return props.chart[num] || { tooth_number: num, condition: 'healthy', status: 'present', surfaces: [] }; }
function getToothType(num) { return toothTypeMap[num] || 'premolar1'; }
function getSidePaths(num) {
    const type = getToothType(num);
    return sideViewPaths[type] || sideViewPaths['premolar1'];
}
function getSideViewBox(num) { const p = getSidePaths(num); return `0 0 ${p.width} ${p.height}`; }
function hasSurface(num, surface) { return (getToothData(num).surfaces || []).includes(surface); }
function getSurfaceFill(num, surface) {
    const data = getToothData(num);
    const cond = data.condition || 'healthy';
    const theme = conditionTheme[cond];
    if ((data.surfaces || []).includes(surface)) return theme.accent;
    if (cond !== 'healthy') return theme.inner;
    return '#FFFFFF';
}
function getToothTreatments(num) { return props.treatments[num] || []; }
function getToothEntries(num) { return props.entries?.[num] || []; }
function formatDate(d) { if (!d) return '-'; return new Date(d).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' }); }
function formatBytes(b) { if (!b) return ''; if (b < 1024) return b+'B'; if (b < 1048576) return (b/1024).toFixed(1)+'KB'; return (b/1048576).toFixed(1)+'MB'; }
function getOcclusalType(num) {
    const type = toothTypeMap[num] || '';
    if (['central','lateral','canine','dec_central','dec_lateral','dec_canine'].includes(type)) return 'anterior';
    return 'posterior';
}

function getToothName(num) {
    if (chartMode.value === 'deciduous' && deciduousNames.value[num]) return deciduousNames.value[num];
    const type = toothTypeMap[num];
    const names = {
        central: isRtl.value ? 'القاطعة المركزية' : 'Central Incisor',
        lateral: isRtl.value ? 'القاطعة الجانبية' : 'Lateral Incisor',
        canine: isRtl.value ? 'الناب' : 'Canine',
        premolar1: isRtl.value ? 'الضاحك الأول' : '1st Premolar',
        premolar2: isRtl.value ? 'الضاحك الثاني' : '2nd Premolar',
        molar1: isRtl.value ? 'الضرس الأول' : '1st Molar',
        molar2: isRtl.value ? 'الضرس الثاني' : '2nd Molar',
        molar3: isRtl.value ? 'ضرس العقل' : 'Wisdom Tooth',
    };
    return names[type] || type;
}

function getQuadrantLabel(num) {
    if (num >= 51 && num <= 55) return isRtl.value ? 'علوي أيمن' : 'Upper Right';
    if (num >= 61 && num <= 65) return isRtl.value ? 'علوي أيسر' : 'Upper Left';
    if (num >= 71 && num <= 75) return isRtl.value ? 'سفلي أيسر' : 'Lower Left';
    if (num >= 81 && num <= 85) return isRtl.value ? 'سفلي أيمن' : 'Lower Right';
    if (num >= 11 && num <= 18) return isRtl.value ? 'علوي أيمن' : 'Upper Right';
    if (num >= 21 && num <= 28) return isRtl.value ? 'علوي أيسر' : 'Upper Left';
    if (num >= 31 && num <= 38) return isRtl.value ? 'سفلي أيسر' : 'Lower Left';
    if (num >= 41 && num <= 48) return isRtl.value ? 'سفلي أيمن' : 'Lower Right';
    return '';
}

// ─── Surface Drawing on Chart ───────────────────────
function handleSurfaceClick(num, surface) {
    if (!surfaceEditMode.value) return;
    // Toggle surface directly on chart and save
    const data = getToothData(num);
    const currentSurfaces = data.surfaces ? [...data.surfaces] : [];
    const idx = currentSurfaces.indexOf(surface);
    if (idx > -1) currentSurfaces.splice(idx, 1);
    else currentSurfaces.push(surface);

    router.post(`/admin/dental/chart/${props.patient.id}/tooth/${num}`, {
        condition: data.condition || 'healthy',
        surfaces: currentSurfaces,
        notes: data.notes || '',
        status: data.status || 'present',
    }, { preserveScroll: true });
}

// ─── Selection & Modals ─────────────────────────────
function selectToothInfo(num) {
    if (surfaceEditMode.value) return; // don't open detail in surface edit mode
    selectedTooth.value = selectedTooth.value === num ? null : num;
    entryTab.value = 'history';
    showToothDetail.value = !!selectedTooth.value;
}

function selectTooth(num) {
    if (surfaceEditMode.value) return;
    selectedTooth.value = num;
    const data = getToothData(num);
    editForm.value = { condition: data.condition || 'healthy', surfaces: data.surfaces ? [...data.surfaces] : [], notes: data.notes || '', status: data.status || 'present' };
    showModal.value = true;
}

function openEntryModal(num) {
    selectedTooth.value = num;
    const data = getToothData(num);
    entryForm.value = {
        entry_type: 'examination', title: '', description: '', condition_before: data.condition || 'healthy',
        condition_after: '', surfaces: [], cost: '', status: 'recorded', doctor_id: '',
        entry_date: new Date().toISOString().split('T')[0], files: [],
    };
    mediaPreview.value = [];
    showEntryModal.value = true;
}

function saveTooth() {
    saving.value = true;
    router.post(`/admin/dental/chart/${props.patient.id}/tooth/${selectedTooth.value}`, editForm.value, {
        preserveScroll: true, onSuccess: () => { showModal.value = false; }, onFinish: () => { saving.value = false; },
    });
}

function saveEntry() {
    savingEntry.value = true;
    const formData = new FormData();
    formData.append('entry_type', entryForm.value.entry_type);
    formData.append('entry_date', entryForm.value.entry_date);
    if (entryForm.value.title) formData.append('title', entryForm.value.title);
    if (entryForm.value.description) formData.append('description', entryForm.value.description);
    if (entryForm.value.condition_before) formData.append('condition_before', entryForm.value.condition_before);
    if (entryForm.value.condition_after) formData.append('condition_after', entryForm.value.condition_after);
    if (entryForm.value.cost) formData.append('cost', entryForm.value.cost);
    if (entryForm.value.status) formData.append('status', entryForm.value.status);
    if (entryForm.value.doctor_id) formData.append('doctor_id', entryForm.value.doctor_id);
    (entryForm.value.surfaces || []).forEach(s => formData.append('surfaces[]', s));
    (entryForm.value.files || []).forEach(f => formData.append('files[]', f));

    router.post(`/admin/dental/chart/${props.patient.id}/tooth/${selectedTooth.value}/entry`, formData, {
        preserveScroll: true, forceFormData: true,
        onSuccess: () => { showEntryModal.value = false; entryTab.value = 'history'; },
        onFinish: () => { savingEntry.value = false; },
    });
}

const showDeleteEntryModal = ref(false);
const pendingDeleteEntryId = ref(null);

function deleteEntry(entryId) {
    pendingDeleteEntryId.value = entryId;
    showDeleteEntryModal.value = true;
}

function executeDeleteEntry() {
    if (!pendingDeleteEntryId.value) return;
    router.post(`/admin/dental/chart/${props.patient.id}/entry/${pendingDeleteEntryId.value}/delete`, { preserveScroll: true });
    showDeleteEntryModal.value = false;
    pendingDeleteEntryId.value = null;
}

function handleFileSelect(e) {
    const files = Array.from(e.target.files);
    entryForm.value.files = [...entryForm.value.files, ...files];
    files.forEach(f => {
        const url = URL.createObjectURL(f);
        mediaPreview.value.push({ name: f.name, url, type: f.type.startsWith('video') ? 'video' : f.type.startsWith('image') ? 'image' : 'file', size: f.size });
    });
}
function removeFile(idx) { entryForm.value.files.splice(idx, 1); mediaPreview.value.splice(idx, 1); }
function toggleSurface(surface) { const idx = editForm.value.surfaces.indexOf(surface); if (idx > -1) editForm.value.surfaces.splice(idx, 1); else editForm.value.surfaces.push(surface); }
function toggleEntrySurface(surface) { const idx = entryForm.value.surfaces.indexOf(surface); if (idx > -1) entryForm.value.surfaces.splice(idx, 1); else entryForm.value.surfaces.push(surface); }

const showInitializeModal = ref(false);

function confirmInitializeChart() {
    showInitializeModal.value = true;
}

function executeInitializeChart() {
    showInitializeModal.value = false;
    router.post(`/admin/dental/chart/${props.patient.id}/initialize`, { mode: chartMode.value }, { preserveScroll: true });
}

// Selected tooth computed data
const selectedToothData = computed(() => selectedTooth.value ? getToothData(selectedTooth.value) : null);
const selectedToothEntries = computed(() => selectedTooth.value ? getToothEntries(selectedTooth.value) : []);
const selectedToothTreatments = computed(() => selectedTooth.value ? getToothTreatments(selectedTooth.value) : []);
</script>

<template>
<AdminLayout :title="isRtl ? 'مخطط الأسنان' : 'Dental Chart'">
<div class="space-y-5">
    <!-- ═══ HERO ═══ -->
    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-[#1B365D] via-[#1B365D] to-[#0F2444] p-5 sm:p-7 dental-hero-enter">
        <div class="pointer-events-none absolute -top-16 -end-16 w-72 h-72 bg-[#C4A265]/20 rounded-full blur-3xl"></div>
        <div class="pointer-events-none absolute -bottom-12 start-1/3 w-48 h-48 bg-[#C4A265]/10 rounded-full blur-3xl"></div>
        <div class="absolute inset-x-0 top-0 h-[2px] bg-gradient-to-r from-transparent via-[#C4A265] to-transparent"></div>
        <!-- Floating tooth motif -->
        <div class="pointer-events-none absolute ltr:right-6 rtl:left-6 top-1/2 -translate-y-1/2 opacity-[0.06]">
            <svg class="w-40 h-40 text-white dental-float" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="0.5" d="M4.26 10.147a60.436 60.436 0 00-.491 6.347A48.627 48.627 0 0112 20.904a48.627 48.627 0 018.232-4.41 60.46 60.46 0 00-.491-6.347m-15.482 0a50.57 50.57 0 00-2.658-.813A59.905 59.905 0 0112 3.493a59.902 59.902 0 0110.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.697 50.697 0 0112 13.489a50.702 50.702 0 017.74-3.342" /></svg>
        </div>
        <div class="relative z-10">
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                <div>
                    <div class="flex items-center gap-2 mb-1">
                        <span class="h-[3px] w-6 bg-[#C4A265] rounded-full"></span>
                        <span class="text-[10px] font-bold text-[#C4A265] tracking-[0.25em] uppercase">{{ isRtl ? 'طب الأسنان' : 'DENTAL' }}</span>
                    </div>
                    <h1 class="text-2xl sm:text-3xl font-bold text-white">{{ isRtl ? 'مخطط الأسنان المحسّن' : 'Enhanced Dental Chart' }}</h1>
                    <div class="flex items-center gap-3 mt-2">
                        <span class="text-gray-300 text-sm">{{ patient.full_name }}</span>
                        <span class="text-gray-500 font-mono text-xs bg-white/10 px-2 py-0.5 rounded">{{ patient.file_number }}</span>
                    </div>
                </div>
                <div class="flex items-center gap-2 flex-wrap">
                    <!-- Chart Mode Toggle -->
                    <div class="flex items-center bg-white/10 rounded-xl p-0.5 border border-white/10">
                        <button @click="chartMode='adult'" class="px-3 py-1.5 text-xs font-semibold rounded-lg transition-all" :class="chartMode==='adult' ? 'bg-[#1B365D] text-white shadow-lg' : 'text-gray-400 hover:text-white'">
                            {{ isRtl ? 'بالغ' : 'Adult' }} (32)
                        </button>
                        <button @click="chartMode='deciduous'" class="px-3 py-1.5 text-xs font-semibold rounded-lg transition-all" :class="chartMode==='deciduous' ? 'bg-[#C4A265] text-white shadow-lg' : 'text-gray-400 hover:text-white'">
                            {{ isRtl ? 'لبنية' : 'Deciduous' }} (20)
                        </button>
                    </div>
                    <!-- Surface Edit Mode Toggle -->
                    <button @click="surfaceEditMode = !surfaceEditMode" class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-medium rounded-xl border transition-all" :class="surfaceEditMode ? 'bg-amber-500/20 text-amber-300 border-amber-500/30' : 'text-gray-400 bg-white/5 border-white/10 hover:text-white hover:bg-white/10'">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" /></svg>
                        {{ isRtl ? 'رسم مباشر' : 'Draw' }}
                    </button>
                    <Link :href="`/admin/dental/periodontal/${patient.id}`" class="inline-flex items-center px-3 py-2 text-xs font-medium text-emerald-300 bg-emerald-500/15 rounded-xl hover:bg-emerald-500/25 border border-emerald-500/20 transition-all">
                        {{ isRtl ? 'اللثة' : 'Perio' }}
                    </Link>
                    <Link :href="`/admin/dental/treatment-plans?patient_id=${patient.id}`" class="inline-flex items-center px-3 py-2 text-xs font-medium text-slate-300 bg-[#1B365D]/15 rounded-xl hover:bg-[#1B365D]/25 border border-[#1B365D]/20 transition-all">
                        {{ isRtl ? 'خطط العلاج' : 'Plans' }}
                    </Link>
                    <Link :href="`/admin/dental/xrays/patient/${patient.id}`" class="inline-flex items-center px-3 py-2 text-xs font-medium text-slate-300 bg-[#1B365D]/15 rounded-xl hover:bg-[#1B365D]/25 border border-[#1B365D]/20 transition-all">
                        {{ isRtl ? 'الأشعة' : 'X-Rays' }}
                    </Link>
                    <button @click="confirmInitializeChart" class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-medium text-[#C4A265] bg-[#C4A265]/10 rounded-xl hover:bg-[#C4A265]/20 border border-[#C4A265]/25 transition-all">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>
                        {{ isRtl ? 'تهيئة' : 'Init' }}
                    </button>
                </div>
            </div>

            <!-- Stats Bar -->
            <div class="flex flex-wrap gap-2 mt-4">
                <template v-for="cond in Object.keys(conditionTheme)" :key="cond">
                    <div v-if="chartStats[cond] > 0" class="bg-white/5 backdrop-blur-sm rounded-lg px-2.5 py-1.5 border border-white/10 flex items-center gap-1.5">
                        <svg class="w-3 h-3" :fill="conditionTheme[cond].accent" viewBox="0 0 16 16"><path :d="conditionIcons[conditionTheme[cond].icon]" /></svg>
                        <span class="text-[10px] text-gray-400">{{ conditionLabels[cond] }}</span>
                        <span class="text-xs font-bold text-white">{{ chartStats[cond] }}</span>
                    </div>
                </template>
                <div class="bg-white/5 backdrop-blur-sm rounded-lg px-2.5 py-1.5 border border-white/10 flex items-center gap-1.5 ms-auto">
                    <span class="text-[10px] text-gray-400">{{ isRtl ? 'مسجل' : 'Recorded' }}</span>
                    <span class="text-xs font-bold text-[#C4A265]">{{ totalRecorded }}/{{ totalTeeth }}</span>
                </div>
            </div>

            <!-- Surface Edit Mode Banner -->
            <Transition enter-active-class="transition-all duration-300" enter-from-class="opacity-0 -translate-y-2" enter-to-class="opacity-100 translate-y-0" leave-active-class="transition-all duration-200" leave-from-class="opacity-100" leave-to-class="opacity-0">
                <div v-if="surfaceEditMode" class="mt-3 bg-amber-500/15 border border-amber-500/30 rounded-xl px-4 py-2.5 flex items-center gap-3">
                    <div class="w-6 h-6 rounded-lg bg-amber-500/30 flex items-center justify-center flex-shrink-0">
                        <svg class="w-3.5 h-3.5 text-amber-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" /></svg>
                    </div>
                    <p class="text-xs text-amber-200">{{ isRtl ? 'وضع الرسم المباشر: اضغط على أي سطح في المنظر الإطباقي لتحديده/إلغاء تحديده' : 'Direct drawing mode: Click any surface on occlusal view to toggle it' }}</p>
                    <button @click="surfaceEditMode = false" class="text-amber-300 hover:text-amber-100 ms-auto">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>
            </Transition>
        </div>
    </div>

    <!-- ═══ CHART ═══ -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100/80 overflow-hidden dental-card-enter" style="animation-delay:0.15s">
        <!-- Legend -->
        <div class="px-4 sm:px-6 py-3 bg-gray-50/80 border-b border-gray-100 flex flex-wrap items-center gap-x-4 gap-y-1.5">
            <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">{{ isRtl ? 'دليل' : 'Legend' }}</span>
            <div v-for="cond in Object.keys(conditionTheme)" :key="cond" class="flex items-center gap-1 cursor-default group">
                <svg class="w-3 h-3 transition-transform group-hover:scale-125" :fill="conditionTheme[cond].accent" viewBox="0 0 16 16"><path :d="conditionIcons[conditionTheme[cond].icon]" /></svg>
                <span class="w-2.5 h-2.5 rounded-sm border transition-transform group-hover:scale-125" :style="{ backgroundColor: conditionTheme[cond].inner, borderColor: conditionTheme[cond].stroke }"></span>
                <span class="text-[10px] text-gray-500 group-hover:text-gray-700 transition-colors">{{ conditionLabels[cond] }}</span>
            </div>
            <div v-if="chartMode === 'deciduous'" class="ms-auto flex items-center gap-1">
                <span class="w-2 h-2 rounded-full bg-[#D4B57E] animate-pulse"></span>
                <span class="text-[10px] font-semibold text-[#C4A265]">{{ isRtl ? 'أسنان لبنية' : 'Deciduous' }}</span>
            </div>
        </div>

        <!-- SVG Gradient Defs (shared) -->
        <svg width="0" height="0" style="position:absolute">
            <defs>
                <template v-for="cond in Object.keys(conditionTheme)" :key="'grad-'+cond">
                    <linearGradient :id="'toothGrad_'+cond" x1="0" y1="0" x2="0" y2="1">
                        <stop offset="0%" :stop-color="conditionTheme[cond].gradient[0]" />
                        <stop offset="100%" :stop-color="conditionTheme[cond].gradient[1]" />
                    </linearGradient>
                    <radialGradient :id="'toothHighlight_'+cond" cx="0.35" cy="0.35" r="0.65">
                        <stop offset="0%" stop-color="white" stop-opacity="0.5" />
                        <stop offset="100%" stop-color="white" stop-opacity="0" />
                    </radialGradient>
                </template>
                <filter id="toothShadow">
                    <feDropShadow dx="0" dy="1" stdDeviation="1.5" flood-opacity="0.15" />
                </filter>
                <filter id="tooth3D">
                    <feGaussianBlur in="SourceAlpha" stdDeviation="1" result="blur" />
                    <feOffset dx="0" dy="1.5" result="offsetBlur" />
                    <feFlood flood-color="#000" flood-opacity="0.12" result="color" />
                    <feComposite in="color" in2="offsetBlur" operator="in" result="shadow" />
                    <feMerge>
                        <feMergeNode in="shadow" />
                        <feMergeNode in="SourceGraphic" />
                    </feMerge>
                </filter>
            </defs>
        </svg>

        <div class="px-2 sm:px-4 md:px-6 py-4">
            <!-- UPPER JAW -->
            <div class="text-center mb-2">
                <span class="text-[10px] font-bold uppercase tracking-widest" :class="chartMode === 'deciduous' ? 'text-[#D4B57E]' : 'text-gray-400'">{{ isRtl ? 'الفك العلوي' : 'Upper Jaw' }}</span>
            </div>

            <!-- Upper Side View -->
            <div class="flex justify-center items-end gap-px sm:gap-0.5 mb-0.5 dental-row" :class="mounted ? 'dental-row-enter' : ''">
                <template v-for="(num, i) in activeUpperRight" :key="'us-'+num">
                    <div class="dental-tooth-cell" :style="{ animationDelay: (i*30)+'ms' }" @click="selectToothInfo(num)" @dblclick="selectTooth(num)" @mouseenter="hoveredTooth=num" @mouseleave="hoveredTooth=null">
                        <svg :viewBox="getSideViewBox(num)" class="dental-side-svg upper-tooth" :class="{'dental-hovered':hoveredTooth===num,'dental-selected':selectedTooth===num,'dental-missing':getToothData(num).condition==='missing'||getToothData(num).condition==='extracted'}" :style="selectedTooth===num?{filter:'drop-shadow(0 2px 6px '+conditionTheme[getToothData(num).condition].accent+'60)'}:{}" filter="url(#tooth3D)">
                            <!-- Roots -->
                            <g class="tooth-roots" opacity="0.6"><path v-for="(rp,ri) in getSidePaths(num).roots" :key="ri" :d="rp" :fill="`url(#toothGrad_${getToothData(num).condition})`" :stroke="conditionTheme[getToothData(num).condition].stroke" stroke-width="0.8" /></g>
                            <!-- Crown with gradient fill -->
                            <path :d="getSidePaths(num).crown" :fill="`url(#toothGrad_${getToothData(num).condition})`" :stroke="conditionTheme[getToothData(num).condition].stroke" stroke-width="1" />
                            <!-- Enamel layer -->
                            <path :d="getSidePaths(num).enamel" :fill="conditionTheme[getToothData(num).condition].fill" :stroke="conditionTheme[getToothData(num).condition].stroke" stroke-width="0.5" opacity="0.5" />
                            <!-- 3D Highlight -->
                            <path :d="getSidePaths(num).highlight" fill="white" opacity="0.4" />
                            <!-- Condition icon badge -->
                            <g v-if="getToothData(num).condition !== 'healthy'">
                                <circle :cx="getSidePaths(num).width - 5" cy="28" r="5" :fill="conditionTheme[getToothData(num).condition].accent" />
                                <g :transform="`translate(${getSidePaths(num).width - 9}, 24) scale(0.5)`">
                                    <path :d="conditionIcons[conditionTheme[getToothData(num).condition].icon]" fill="white" />
                                </g>
                            </g>
                            <!-- Entry count badge -->
                            <g v-else-if="getToothEntries(num).length > 0"><circle cx="4" cy="4" r="4" fill="#1B365D" /><text x="4" y="5.5" text-anchor="middle" fill="white" font-size="5" font-weight="bold">{{ getToothEntries(num).length }}</text></g>
                        </svg>
                    </div>
                </template>
                <div class="dental-center-line"></div>
                <template v-for="(num, i) in activeUpperLeft" :key="'us-'+num">
                    <div class="dental-tooth-cell" :style="{ animationDelay: ((activeUpperRight.length+i)*30)+'ms' }" @click="selectToothInfo(num)" @dblclick="selectTooth(num)" @mouseenter="hoveredTooth=num" @mouseleave="hoveredTooth=null">
                        <svg :viewBox="getSideViewBox(num)" class="dental-side-svg upper-tooth" :class="{'dental-hovered':hoveredTooth===num,'dental-selected':selectedTooth===num,'dental-missing':getToothData(num).condition==='missing'||getToothData(num).condition==='extracted'}" :style="selectedTooth===num?{filter:'drop-shadow(0 2px 6px '+conditionTheme[getToothData(num).condition].accent+'60)'}:{}" filter="url(#tooth3D)">
                            <g class="tooth-roots" opacity="0.6"><path v-for="(rp,ri) in getSidePaths(num).roots" :key="ri" :d="rp" :fill="`url(#toothGrad_${getToothData(num).condition})`" :stroke="conditionTheme[getToothData(num).condition].stroke" stroke-width="0.8" /></g>
                            <path :d="getSidePaths(num).crown" :fill="`url(#toothGrad_${getToothData(num).condition})`" :stroke="conditionTheme[getToothData(num).condition].stroke" stroke-width="1" />
                            <path :d="getSidePaths(num).enamel" :fill="conditionTheme[getToothData(num).condition].fill" :stroke="conditionTheme[getToothData(num).condition].stroke" stroke-width="0.5" opacity="0.5" />
                            <path :d="getSidePaths(num).highlight" fill="white" opacity="0.4" />
                            <g v-if="getToothData(num).condition !== 'healthy'">
                                <circle :cx="getSidePaths(num).width - 5" cy="28" r="5" :fill="conditionTheme[getToothData(num).condition].accent" />
                                <g :transform="`translate(${getSidePaths(num).width - 9}, 24) scale(0.5)`"><path :d="conditionIcons[conditionTheme[getToothData(num).condition].icon]" fill="white" /></g>
                            </g>
                            <g v-else-if="getToothEntries(num).length > 0"><circle cx="4" cy="4" r="4" fill="#1B365D" /><text x="4" y="5.5" text-anchor="middle" fill="white" font-size="5" font-weight="bold">{{ getToothEntries(num).length }}</text></g>
                        </svg>
                    </div>
                </template>
            </div>

            <div class="dental-gum-line upper-gum"></div>

            <!-- Upper Occlusal (interactive surfaces) -->
            <div class="flex justify-center items-center gap-px sm:gap-0.5 my-1 dental-row" :class="mounted ? 'dental-row-enter' : ''" style="animation-delay: 0.2s">
                <template v-for="(num, i) in [...activeUpperRight, ...activeUpperLeft]" :key="'uo-'+num">
                    <div v-if="num === activeUpperLeft[0]" class="dental-center-line short"></div>
                    <div class="dental-occlusal-cell" :class="{'dental-surface-edit': surfaceEditMode}" :style="{ animationDelay: (i*30+200)+'ms' }" @click="!surfaceEditMode && selectToothInfo(num)" @dblclick="!surfaceEditMode && selectTooth(num)" @mouseenter="hoveredTooth=num" @mouseleave="hoveredTooth=null">
                        <svg :viewBox="getOcclusalType(num)==='anterior'?'0 0 28 32':'-2 0 36 32'" class="dental-occlusal-svg" :class="{'dental-hovered':hoveredTooth===num,'dental-selected':selectedTooth===num,'dental-missing':getToothData(num).condition==='missing'||getToothData(num).condition==='extracted'}">
                            <path :d="occlusalShapes[getOcclusalType(num)].outer" :fill="conditionTheme[getToothData(num).condition].fill" :stroke="conditionTheme[getToothData(num).condition].stroke" stroke-width="1" />
                            <path :d="occlusalShapes[getOcclusalType(num)].buccal" :fill="getSurfaceFill(num,'buccal')" :stroke="conditionTheme[getToothData(num).condition].stroke" stroke-width="0.5" :opacity="surfaceEditMode ? 1 : 0.8" class="dental-surface-path" :class="{'surface-active': hasSurface(num,'buccal'), 'surface-clickable': surfaceEditMode}" @click.stop="handleSurfaceClick(num,'buccal')" />
                            <path :d="occlusalShapes[getOcclusalType(num)].lingual" :fill="getSurfaceFill(num,'lingual')" :stroke="conditionTheme[getToothData(num).condition].stroke" stroke-width="0.5" :opacity="surfaceEditMode ? 1 : 0.8" class="dental-surface-path" :class="{'surface-active': hasSurface(num,'lingual'), 'surface-clickable': surfaceEditMode}" @click.stop="handleSurfaceClick(num,'lingual')" />
                            <path :d="occlusalShapes[getOcclusalType(num)].mesial" :fill="getSurfaceFill(num,'mesial')" :stroke="conditionTheme[getToothData(num).condition].stroke" stroke-width="0.5" :opacity="surfaceEditMode ? 1 : 0.8" class="dental-surface-path" :class="{'surface-active': hasSurface(num,'mesial'), 'surface-clickable': surfaceEditMode}" @click.stop="handleSurfaceClick(num,'mesial')" />
                            <path :d="occlusalShapes[getOcclusalType(num)].distal" :fill="getSurfaceFill(num,'distal')" :stroke="conditionTheme[getToothData(num).condition].stroke" stroke-width="0.5" :opacity="surfaceEditMode ? 1 : 0.8" class="dental-surface-path" :class="{'surface-active': hasSurface(num,'distal'), 'surface-clickable': surfaceEditMode}" @click.stop="handleSurfaceClick(num,'distal')" />
                            <path :d="occlusalShapes[getOcclusalType(num)].occlusal" :fill="getSurfaceFill(num,'occlusal')" :stroke="conditionTheme[getToothData(num).condition].stroke" stroke-width="0.6" class="dental-surface-path" :class="{'surface-active': hasSurface(num,'occlusal'), 'surface-clickable': surfaceEditMode}" @click.stop="handleSurfaceClick(num,'occlusal')" />
                            <!-- 3D shine on occlusal -->
                            <ellipse v-if="!surfaceEditMode" :cx="getOcclusalType(num)==='anterior' ? 11 : 13" :cy="getOcclusalType(num)==='anterior' ? 12 : 13" :rx="getOcclusalType(num)==='anterior' ? 4 : 5" :ry="3" fill="white" opacity="0.15" />
                        </svg>
                        <!-- Tooltip -->
                        <div v-if="hoveredTooth===num" class="dental-tooltip above">
                            <span class="font-bold">{{ num }}</span>
                            <span class="mx-1 opacity-50">|</span>
                            {{ conditionLabels[getToothData(num).condition] }}
                            <span v-if="getToothData(num).surfaces?.length" class="text-slate-300 ms-1">[{{ (getToothData(num).surfaces || []).map(s => surfaceLabels[s]?.substring(0,1)).join('') }}]</span>
                            <span v-if="getToothEntries(num).length" class="text-[#2C4E7A] ms-1">({{ getToothEntries(num).length }})</span>
                        </div>
                    </div>
                </template>
            </div>

            <!-- Numbers Upper -->
            <div class="flex justify-center items-center gap-px sm:gap-0.5 my-1.5">
                <template v-for="num in [...activeUpperRight, ...activeUpperLeft]" :key="'n-u-'+num">
                    <div v-if="num === activeUpperLeft[0]" class="dental-center-line short"></div>
                    <div class="dental-number-cell" :class="{'dental-number-active':selectedTooth===num||hoveredTooth===num}" @click="selectToothInfo(num)">{{ num }}</div>
                </template>
            </div>

            <!-- Midline -->
            <div class="relative my-2">
                <div class="border-t border-dashed border-gray-300"></div>
                <div class="absolute inset-x-0 top-1/2 -translate-y-1/2 flex justify-center">
                    <span class="bg-white px-3 text-[9px] font-bold text-gray-300 uppercase tracking-[0.2em]">{{ isRtl ? 'يمين' : 'RIGHT' }} &bull; {{ isRtl ? 'يسار' : 'LEFT' }}</span>
                </div>
            </div>

            <!-- Numbers Lower -->
            <div class="flex justify-center items-center gap-px sm:gap-0.5 my-1.5">
                <template v-for="num in [...activeLowerRight, ...activeLowerLeft]" :key="'n-l-'+num">
                    <div v-if="num === activeLowerLeft[0]" class="dental-center-line short"></div>
                    <div class="dental-number-cell" :class="{'dental-number-active':selectedTooth===num||hoveredTooth===num}" @click="selectToothInfo(num)">{{ num }}</div>
                </template>
            </div>

            <!-- Lower Occlusal -->
            <div class="flex justify-center items-center gap-px sm:gap-0.5 my-1 dental-row" :class="mounted ? 'dental-row-enter' : ''" style="animation-delay: 0.4s">
                <template v-for="(num, i) in [...activeLowerRight, ...activeLowerLeft]" :key="'lo-'+num">
                    <div v-if="num === activeLowerLeft[0]" class="dental-center-line short"></div>
                    <div class="dental-occlusal-cell" :class="{'dental-surface-edit': surfaceEditMode}" :style="{ animationDelay: ((totalTeeth/2+i)*30+200)+'ms' }" @click="!surfaceEditMode && selectToothInfo(num)" @dblclick="!surfaceEditMode && selectTooth(num)" @mouseenter="hoveredTooth=num" @mouseleave="hoveredTooth=null">
                        <svg :viewBox="getOcclusalType(num)==='anterior'?'0 0 28 32':'-2 0 36 32'" class="dental-occlusal-svg" :class="{'dental-hovered':hoveredTooth===num,'dental-selected':selectedTooth===num,'dental-missing':getToothData(num).condition==='missing'||getToothData(num).condition==='extracted'}">
                            <path :d="occlusalShapes[getOcclusalType(num)].outer" :fill="conditionTheme[getToothData(num).condition].fill" :stroke="conditionTheme[getToothData(num).condition].stroke" stroke-width="1" />
                            <path :d="occlusalShapes[getOcclusalType(num)].buccal" :fill="getSurfaceFill(num,'buccal')" :stroke="conditionTheme[getToothData(num).condition].stroke" stroke-width="0.5" :opacity="surfaceEditMode ? 1 : 0.8" class="dental-surface-path" :class="{'surface-active': hasSurface(num,'buccal'), 'surface-clickable': surfaceEditMode}" @click.stop="handleSurfaceClick(num,'buccal')" />
                            <path :d="occlusalShapes[getOcclusalType(num)].lingual" :fill="getSurfaceFill(num,'lingual')" :stroke="conditionTheme[getToothData(num).condition].stroke" stroke-width="0.5" :opacity="surfaceEditMode ? 1 : 0.8" class="dental-surface-path" :class="{'surface-active': hasSurface(num,'lingual'), 'surface-clickable': surfaceEditMode}" @click.stop="handleSurfaceClick(num,'lingual')" />
                            <path :d="occlusalShapes[getOcclusalType(num)].mesial" :fill="getSurfaceFill(num,'mesial')" :stroke="conditionTheme[getToothData(num).condition].stroke" stroke-width="0.5" :opacity="surfaceEditMode ? 1 : 0.8" class="dental-surface-path" :class="{'surface-active': hasSurface(num,'mesial'), 'surface-clickable': surfaceEditMode}" @click.stop="handleSurfaceClick(num,'mesial')" />
                            <path :d="occlusalShapes[getOcclusalType(num)].distal" :fill="getSurfaceFill(num,'distal')" :stroke="conditionTheme[getToothData(num).condition].stroke" stroke-width="0.5" :opacity="surfaceEditMode ? 1 : 0.8" class="dental-surface-path" :class="{'surface-active': hasSurface(num,'distal'), 'surface-clickable': surfaceEditMode}" @click.stop="handleSurfaceClick(num,'distal')" />
                            <path :d="occlusalShapes[getOcclusalType(num)].occlusal" :fill="getSurfaceFill(num,'occlusal')" :stroke="conditionTheme[getToothData(num).condition].stroke" stroke-width="0.6" class="dental-surface-path" :class="{'surface-active': hasSurface(num,'occlusal'), 'surface-clickable': surfaceEditMode}" @click.stop="handleSurfaceClick(num,'occlusal')" />
                            <ellipse v-if="!surfaceEditMode" :cx="getOcclusalType(num)==='anterior' ? 11 : 13" :cy="getOcclusalType(num)==='anterior' ? 12 : 13" :rx="getOcclusalType(num)==='anterior' ? 4 : 5" :ry="3" fill="white" opacity="0.15" />
                        </svg>
                        <div v-if="hoveredTooth===num" class="dental-tooltip below">
                            <span class="font-bold">{{ num }}</span>
                            <span class="mx-1 opacity-50">|</span>
                            {{ conditionLabels[getToothData(num).condition] }}
                            <span v-if="getToothData(num).surfaces?.length" class="text-slate-300 ms-1">[{{ (getToothData(num).surfaces || []).map(s => surfaceLabels[s]?.substring(0,1)).join('') }}]</span>
                            <span v-if="getToothEntries(num).length" class="text-[#2C4E7A] ms-1">({{ getToothEntries(num).length }})</span>
                        </div>
                    </div>
                </template>
            </div>

            <div class="dental-gum-line lower-gum"></div>

            <!-- Lower Side View -->
            <div class="flex justify-center items-start gap-px sm:gap-0.5 mt-0.5 dental-row" :class="mounted ? 'dental-row-enter' : ''" style="animation-delay: 0.5s">
                <template v-for="(num, i) in [...activeLowerRight, ...activeLowerLeft]" :key="'ls-'+num">
                    <div v-if="num === activeLowerLeft[0]" class="dental-center-line"></div>
                    <div class="dental-tooth-cell" :style="{ animationDelay: ((totalTeeth/2+i)*30+400)+'ms' }" @click="selectToothInfo(num)" @dblclick="selectTooth(num)" @mouseenter="hoveredTooth=num" @mouseleave="hoveredTooth=null">
                        <svg :viewBox="getSideViewBox(num)" class="dental-side-svg lower-tooth" :class="{'dental-hovered':hoveredTooth===num,'dental-selected':selectedTooth===num,'dental-missing':getToothData(num).condition==='missing'||getToothData(num).condition==='extracted'}" :style="selectedTooth===num?{filter:'drop-shadow(0 2px 6px '+conditionTheme[getToothData(num).condition].accent+'60)'}:{}" filter="url(#tooth3D)">
                            <path :d="getSidePaths(num).crown" :fill="`url(#toothGrad_${getToothData(num).condition})`" :stroke="conditionTheme[getToothData(num).condition].stroke" stroke-width="1" />
                            <path :d="getSidePaths(num).enamel" :fill="conditionTheme[getToothData(num).condition].fill" :stroke="conditionTheme[getToothData(num).condition].stroke" stroke-width="0.5" opacity="0.5" />
                            <path :d="getSidePaths(num).highlight" fill="white" opacity="0.4" />
                            <g class="tooth-roots" opacity="0.6"><path v-for="(rp,ri) in getSidePaths(num).roots" :key="ri" :d="rp" :fill="`url(#toothGrad_${getToothData(num).condition})`" :stroke="conditionTheme[getToothData(num).condition].stroke" stroke-width="0.8" /></g>
                            <g v-if="getToothData(num).condition !== 'healthy'">
                                <circle :cx="getSidePaths(num).width - 5" cy="5" r="5" :fill="conditionTheme[getToothData(num).condition].accent" />
                                <g :transform="`translate(${getSidePaths(num).width - 9}, 1) scale(0.5)`"><path :d="conditionIcons[conditionTheme[getToothData(num).condition].icon]" fill="white" /></g>
                            </g>
                            <g v-else-if="getToothEntries(num).length > 0"><circle cx="4" cy="32" r="4" fill="#1B365D" /><text x="4" y="33.5" text-anchor="middle" fill="white" font-size="5" font-weight="bold">{{ getToothEntries(num).length }}</text></g>
                        </svg>
                    </div>
                </template>
            </div>

            <div class="text-center mt-2"><span class="text-[10px] font-bold uppercase tracking-widest" :class="chartMode === 'deciduous' ? 'text-[#D4B57E]' : 'text-gray-400'">{{ isRtl ? 'الفك السفلي' : 'Lower Jaw' }}</span></div>
            <div class="text-center mt-3 mb-1"><span class="text-[10px] text-gray-300">{{ surfaceEditMode ? (isRtl ? 'اضغط على أي سطح لتحديده' : 'Click any surface to select it') : (isRtl ? 'اضغط لعرض التفاصيل • دبل كليك لتعديل الحالة' : 'Click for details \u2022 Double-click to edit condition') }}</span></div>
        </div>
    </div>

    <!-- ═══ SELECTED TOOTH DETAIL PANEL ═══ -->
    <Transition enter-active-class="transition-all duration-400 ease-out" enter-from-class="opacity-0 translate-y-3" enter-to-class="opacity-100 translate-y-0" leave-active-class="transition-all duration-200 ease-in" leave-from-class="opacity-100" leave-to-class="opacity-0 -translate-y-2">
    <div v-if="selectedTooth && !showModal && !showEntryModal" class="bg-white rounded-2xl shadow-sm border border-gray-100/80 overflow-hidden">
        <!-- Tooth Header with 3D Preview -->
        <div class="flex items-stretch border-b border-gray-100">
            <!-- Large Tooth Preview -->
            <div class="w-32 sm:w-40 flex-shrink-0 bg-gradient-to-br from-gray-50 to-white flex flex-col items-center justify-center py-5 px-3 border-e border-gray-100">
                <svg :viewBox="getSideViewBox(selectedTooth)" class="w-20 h-28 sm:w-24 sm:h-32" filter="url(#tooth3D)">
                    <g class="tooth-roots" opacity="0.6"><path v-for="(rp,ri) in getSidePaths(selectedTooth).roots" :key="ri" :d="rp" :fill="`url(#toothGrad_${getToothData(selectedTooth).condition})`" :stroke="conditionTheme[getToothData(selectedTooth).condition].stroke" stroke-width="0.8" /></g>
                    <path :d="getSidePaths(selectedTooth).crown" :fill="`url(#toothGrad_${getToothData(selectedTooth).condition})`" :stroke="conditionTheme[getToothData(selectedTooth).condition].stroke" stroke-width="1.2" />
                    <path :d="getSidePaths(selectedTooth).enamel" :fill="conditionTheme[getToothData(selectedTooth).condition].fill" :stroke="conditionTheme[getToothData(selectedTooth).condition].stroke" stroke-width="0.5" opacity="0.5" />
                    <path :d="getSidePaths(selectedTooth).highlight" fill="white" opacity="0.5" />
                </svg>
                <div class="mt-2 text-center">
                    <div class="text-2xl font-black font-mono" :style="{ color: conditionTheme[getToothData(selectedTooth).condition].accent }">{{ selectedTooth }}</div>
                    <div class="text-[9px] text-gray-400 mt-0.5">{{ getQuadrantLabel(selectedTooth) }}</div>
                </div>
            </div>

            <!-- Tooth Info -->
            <div class="flex-1 p-4 sm:p-5">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h3 class="text-lg font-bold text-gray-900">{{ getToothName(selectedTooth) }}</h3>
                        <div class="flex items-center gap-2 mt-1.5">
                            <span class="inline-flex items-center gap-1.5 text-xs px-2.5 py-1 rounded-full font-semibold" :style="{ backgroundColor: conditionTheme[getToothData(selectedTooth).condition].fill, color: conditionTheme[getToothData(selectedTooth).condition].text, border: '1px solid '+conditionTheme[getToothData(selectedTooth).condition].stroke }">
                                <svg class="w-3 h-3" :fill="conditionTheme[getToothData(selectedTooth).condition].text" viewBox="0 0 16 16"><path :d="conditionIcons[conditionTheme[getToothData(selectedTooth).condition].icon]" /></svg>
                                {{ conditionLabels[getToothData(selectedTooth).condition] }}
                            </span>
                        </div>
                        <!-- Surfaces Display -->
                        <div v-if="getToothData(selectedTooth).surfaces?.length" class="flex flex-wrap gap-1 mt-2">
                            <span v-for="s in getToothData(selectedTooth).surfaces" :key="s" class="text-[10px] font-medium px-2 py-0.5 rounded-md" :style="{ backgroundColor: conditionTheme[getToothData(selectedTooth).condition].fill, color: conditionTheme[getToothData(selectedTooth).condition].text }">{{ surfaceLabels[s] }}</span>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <button @click="openEntryModal(selectedTooth)" class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-semibold text-white bg-[#1B365D] hover:bg-[#1B365D] rounded-xl transition-all shadow-lg shadow-[#1B365D]/20">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                            {{ isRtl ? 'إضافة سجل' : 'Add Entry' }}
                        </button>
                        <button @click="selectTooth(selectedTooth)" class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-medium text-[#1B365D] bg-slate-50 hover:bg-slate-100 rounded-xl border border-slate-200 transition-all">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                            {{ isRtl ? 'تعديل' : 'Edit' }}
                        </button>
                    </div>
                </div>

                <!-- Quick Stats -->
                <div class="grid grid-cols-3 gap-3 mt-4">
                    <div class="bg-gray-50 rounded-xl p-2.5 text-center">
                        <div class="text-lg font-bold text-gray-800">{{ selectedToothEntries.length }}</div>
                        <div class="text-[10px] text-gray-400">{{ isRtl ? 'سجلات' : 'Entries' }}</div>
                    </div>
                    <div class="bg-gray-50 rounded-xl p-2.5 text-center">
                        <div class="text-lg font-bold text-gray-800">{{ selectedToothTreatments.length }}</div>
                        <div class="text-[10px] text-gray-400">{{ isRtl ? 'علاجات' : 'Treatments' }}</div>
                    </div>
                    <div class="bg-gray-50 rounded-xl p-2.5 text-center">
                        <div class="text-lg font-bold text-gray-800">{{ (getToothData(selectedTooth).surfaces || []).length }}</div>
                        <div class="text-[10px] text-gray-400">{{ isRtl ? 'أسطح' : 'Surfaces' }}</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Notes -->
        <div v-if="getToothData(selectedTooth).notes" class="px-5 py-3 bg-amber-50/50 border-b border-amber-100 flex items-start gap-2">
            <svg class="w-4 h-4 text-amber-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z" /></svg>
            <p class="text-sm text-amber-800">{{ getToothData(selectedTooth).notes }}</p>
        </div>

        <!-- Tabs -->
        <div class="flex border-b border-gray-100">
            <button @click="entryTab='history'" class="flex-1 py-3 text-xs font-semibold text-center transition-all" :class="entryTab==='history' ? 'text-[#1B365D] border-b-2 border-[#1B365D] bg-slate-50/30' : 'text-gray-400 hover:text-gray-600'">
                {{ isRtl ? 'السجل والملفات' : 'History & Files' }} ({{ selectedToothEntries.length }})
            </button>
            <button @click="entryTab='treatments'" class="flex-1 py-3 text-xs font-semibold text-center transition-all" :class="entryTab==='treatments' ? 'text-[#1B365D] border-b-2 border-[#1B365D] bg-slate-50/30' : 'text-gray-400 hover:text-gray-600'">
                {{ isRtl ? 'العلاجات' : 'Treatments' }} ({{ selectedToothTreatments.length }})
            </button>
        </div>

        <!-- History Tab - Timeline Style -->
        <div v-if="entryTab==='history'">
            <div v-if="selectedToothEntries.length === 0" class="px-5 py-10 text-center">
                <div class="w-14 h-14 mx-auto mb-3 rounded-2xl bg-gray-50 flex items-center justify-center"><svg class="w-7 h-7 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6v6m0 0v6m0-6h6m-6 0H6" /></svg></div>
                <p class="text-sm text-gray-400 mb-3">{{ isRtl ? 'لا يوجد سجلات بعد' : 'No entries yet' }}</p>
                <button @click="openEntryModal(selectedTooth)" class="text-xs font-semibold text-[#1B365D] hover:text-[#1B365D]">{{ isRtl ? 'إضافة أول سجل' : 'Add first entry' }}</button>
            </div>
            <div v-else class="relative ps-8 pe-5 py-4">
                <!-- Timeline line -->
                <div class="absolute start-[18px] top-0 bottom-0 w-px bg-gradient-to-b from-gray-200 via-gray-200 to-transparent"></div>
                <div v-for="(entry, ei) in selectedToothEntries" :key="entry.id" class="relative mb-5 last:mb-0 group">
                    <!-- Timeline dot -->
                    <div class="absolute -start-[22px] top-1 w-3 h-3 rounded-full border-2 border-white shadow-sm" :class="{'bg-[#1B365D]':entry.entry_type==='examination','bg-emerald-500':entry.entry_type==='treatment','bg-[#C4A265]':entry.entry_type==='note','bg-[#1B365D]':entry.entry_type==='follow_up','bg-red-500':entry.entry_type==='complication','bg-gray-400':entry.entry_type==='media_only'}"></div>
                    <div class="bg-gray-50/50 rounded-xl p-3.5 hover:bg-gray-50 transition-colors border border-transparent hover:border-gray-100">
                        <div class="flex items-center justify-between gap-2 mb-1.5">
                            <div class="flex items-center gap-2 flex-wrap">
                                <span class="text-xs font-bold px-2 py-0.5 rounded-full" :class="{'bg-slate-100 text-[#1B365D]':entry.entry_type==='examination','bg-emerald-100 text-emerald-700':entry.entry_type==='treatment','bg-[#F5E7C8]/60 text-[#8B7043]':entry.entry_type==='note','bg-slate-100 text-[#1B365D]':entry.entry_type==='follow_up','bg-red-100 text-red-700':entry.entry_type==='complication','bg-gray-100 text-gray-600':entry.entry_type==='media_only'}">{{ entryTypeLabels[entry.entry_type] }}</span>
                                <span v-if="entry.title" class="text-sm font-semibold text-gray-800">{{ entry.title }}</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="text-[10px] text-gray-300">{{ formatDate(entry.entry_date || entry.created_at) }}</span>
                                <button @click="deleteEntry(entry.id)" class="opacity-0 group-hover:opacity-100 p-1 text-gray-300 hover:text-red-500 rounded-lg hover:bg-red-50 transition-all">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                </button>
                            </div>
                        </div>
                        <p v-if="entry.description" class="text-sm text-gray-600 mt-1">{{ entry.description }}</p>
                        <!-- Condition change -->
                        <div v-if="entry.condition_before || entry.condition_after" class="flex items-center gap-2 mt-2">
                            <span v-if="entry.condition_before" class="inline-flex items-center gap-1 text-[10px] px-1.5 py-0.5 rounded" :style="{ backgroundColor: conditionTheme[entry.condition_before]?.fill, color: conditionTheme[entry.condition_before]?.text }">
                                <svg class="w-2.5 h-2.5" :fill="conditionTheme[entry.condition_before]?.text" viewBox="0 0 16 16"><path :d="conditionIcons[conditionTheme[entry.condition_before]?.icon]" /></svg>
                                {{ conditionLabels[entry.condition_before] }}
                            </span>
                            <svg v-if="entry.condition_after" class="w-3 h-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" /></svg>
                            <span v-if="entry.condition_after" class="inline-flex items-center gap-1 text-[10px] px-1.5 py-0.5 rounded" :style="{ backgroundColor: conditionTheme[entry.condition_after]?.fill, color: conditionTheme[entry.condition_after]?.text }">
                                <svg class="w-2.5 h-2.5" :fill="conditionTheme[entry.condition_after]?.text" viewBox="0 0 16 16"><path :d="conditionIcons[conditionTheme[entry.condition_after]?.icon]" /></svg>
                                {{ conditionLabels[entry.condition_after] }}
                            </span>
                        </div>
                        <!-- Doctor & Cost -->
                        <div class="flex items-center gap-3 mt-1.5">
                            <span v-if="entry.doctor" class="text-[10px] text-gray-400">{{ locale==='ar' ? entry.doctor.name_ar : entry.doctor.name_en }}</span>
                            <span v-if="entry.cost > 0" class="text-[10px] font-bold text-emerald-600">{{ formatCurrency(entry.cost) }}</span>
                        </div>
                        <!-- Media Gallery -->
                        <div v-if="entry.media?.length" class="flex flex-wrap gap-2 mt-2">
                            <template v-for="(m, mi) in entry.media" :key="mi">
                                <a v-if="m.type==='image'" :href="'/storage/'+m.path" target="_blank" class="w-16 h-16 rounded-lg overflow-hidden border border-gray-200 hover:border-slate-300 transition-all hover:shadow-md">
                                    <img :src="'/storage/'+m.path" :alt="m.original_name" class="w-full h-full object-cover" />
                                </a>
                                <a v-else-if="m.type==='video'" :href="'/storage/'+m.path" target="_blank" class="w-16 h-16 rounded-lg overflow-hidden border border-gray-200 bg-gray-900 flex items-center justify-center hover:border-slate-300 transition-all">
                                    <svg class="w-6 h-6 text-white/80" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z" /></svg>
                                </a>
                                <a v-else :href="'/storage/'+m.path" target="_blank" class="flex items-center gap-1.5 px-3 py-2 rounded-lg border border-gray-200 hover:border-slate-300 transition-all bg-gray-50">
                                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" /></svg>
                                    <span class="text-[10px] text-gray-600 max-w-[80px] truncate">{{ m.original_name }}</span>
                                </a>
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Treatments Tab -->
        <div v-if="entryTab==='treatments'">
            <div v-if="selectedToothTreatments.length === 0" class="px-5 py-8 text-center">
                <p class="text-xs text-gray-300">{{ isRtl ? 'لا يوجد علاجات مسجلة' : 'No treatments recorded' }}</p>
            </div>
            <div v-else class="divide-y divide-gray-50">
                <div v-for="tr in selectedToothTreatments" :key="tr.id" class="px-5 py-3 flex items-center justify-between hover:bg-gray-50/50 transition-colors">
                    <div class="flex items-center gap-2.5">
                        <div class="w-1.5 h-1.5 rounded-full flex-shrink-0" :class="{'bg-emerald-500':tr.status==='completed','bg-[#1B365D]':tr.status==='in_progress','bg-gray-300':tr.status==='planned','bg-red-400':tr.status==='cancelled'}"></div>
                        <div>
                            <span class="text-sm font-medium text-gray-800">{{ tr.treatment_type?.replace?.(/_/g,' ') || '-' }}</span>
                            <div class="flex items-center gap-2 mt-0.5">
                                <span v-if="tr.doctor" class="text-[10px] text-gray-400">{{ locale==='ar' ? tr.doctor.name_ar : tr.doctor.name_en }}</span>
                                <span class="text-[10px] text-gray-300">{{ formatDate(tr.created_at) }}</span>
                            </div>
                        </div>
                    </div>
                    <span class="text-[10px] font-semibold px-2 py-0.5 rounded-full" :class="{'bg-emerald-50 text-emerald-700':tr.status==='completed','bg-slate-50 text-[#1B365D]':tr.status==='in_progress','bg-gray-100 text-gray-500':tr.status==='planned','bg-red-50 text-red-600':tr.status==='cancelled'}">{{ tr.status }}</span>
                </div>
            </div>
        </div>
    </div>
    </Transition>
</div>

<!-- ═══ EDIT CONDITION MODAL ═══ -->
<Teleport to="body">
<Transition enter-active-class="transition-all duration-300 ease-out" enter-from-class="opacity-0" enter-to-class="opacity-100" leave-active-class="transition-all duration-200 ease-in" leave-from-class="opacity-100" leave-to-class="opacity-0">
<div v-if="showModal" class="fixed inset-0 bg-black/60 backdrop-blur-sm z-50 flex items-center justify-center p-4" @click.self="showModal=false">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg overflow-hidden">
        <div class="bg-gradient-to-r from-[#1B365D] to-[#1B365D] px-6 py-4 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-white/20 flex items-center justify-center"><span class="text-white font-bold font-mono text-lg">{{ selectedTooth }}</span></div>
                <div>
                    <h3 class="text-white font-bold">{{ isRtl ? 'تعديل حالة السن' : 'Edit Tooth Condition' }}</h3>
                    <p class="text-slate-100 text-xs">{{ getToothName(selectedTooth) }} - {{ conditionLabels[editForm.condition] }}</p>
                </div>
            </div>
            <button @click="showModal=false" class="text-slate-200 hover:text-white"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg></button>
        </div>
        <div class="p-6 space-y-5">
            <!-- Condition Grid with Icons -->
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-3">{{ isRtl ? 'حالة السن' : 'Condition' }}</label>
                <div class="grid grid-cols-3 gap-2">
                    <button v-for="cond in conditions" :key="cond" type="button" @click="editForm.condition=cond" class="relative px-2.5 py-2.5 rounded-xl text-xs font-medium border-2 transition-all duration-200" :class="editForm.condition===cond?'scale-[1.02] shadow-md':'border-gray-100 hover:border-gray-200'" :style="editForm.condition===cond?{borderColor:conditionTheme[cond].stroke,backgroundColor:conditionTheme[cond].fill,color:conditionTheme[cond].text}:{}">
                        <span class="flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5" :fill="editForm.condition===cond ? conditionTheme[cond].text : '#9CA3AF'" viewBox="0 0 16 16"><path :d="conditionIcons[conditionTheme[cond].icon]" /></svg>
                            {{ conditionLabels[cond] }}
                        </span>
                    </button>
                </div>
            </div>
            <!-- Surfaces with Visual Diagram -->
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-3">{{ isRtl ? 'الأسطح' : 'Surfaces' }}</label>
                <div class="flex items-center gap-4">
                    <!-- Mini occlusal diagram for surface selection -->
                    <svg viewBox="-2 0 36 32" class="w-24 h-24 flex-shrink-0">
                        <path :d="occlusalShapes.posterior.outer" fill="#F9FAFB" stroke="#D1D5DB" stroke-width="1" />
                        <path :d="occlusalShapes.posterior.buccal" :fill="editForm.surfaces.includes('buccal') ? conditionTheme[editForm.condition].accent : '#F3F4F6'" :stroke="editForm.surfaces.includes('buccal') ? conditionTheme[editForm.condition].stroke : '#D1D5DB'" stroke-width="0.5" class="cursor-pointer hover:opacity-80" @click="toggleSurface('buccal')" />
                        <path :d="occlusalShapes.posterior.lingual" :fill="editForm.surfaces.includes('lingual') ? conditionTheme[editForm.condition].accent : '#F3F4F6'" :stroke="editForm.surfaces.includes('lingual') ? conditionTheme[editForm.condition].stroke : '#D1D5DB'" stroke-width="0.5" class="cursor-pointer hover:opacity-80" @click="toggleSurface('lingual')" />
                        <path :d="occlusalShapes.posterior.mesial" :fill="editForm.surfaces.includes('mesial') ? conditionTheme[editForm.condition].accent : '#F3F4F6'" :stroke="editForm.surfaces.includes('mesial') ? conditionTheme[editForm.condition].stroke : '#D1D5DB'" stroke-width="0.5" class="cursor-pointer hover:opacity-80" @click="toggleSurface('mesial')" />
                        <path :d="occlusalShapes.posterior.distal" :fill="editForm.surfaces.includes('distal') ? conditionTheme[editForm.condition].accent : '#F3F4F6'" :stroke="editForm.surfaces.includes('distal') ? conditionTheme[editForm.condition].stroke : '#D1D5DB'" stroke-width="0.5" class="cursor-pointer hover:opacity-80" @click="toggleSurface('distal')" />
                        <path :d="occlusalShapes.posterior.occlusal" :fill="editForm.surfaces.includes('occlusal') ? conditionTheme[editForm.condition].accent : '#F9FAFB'" :stroke="editForm.surfaces.includes('occlusal') ? conditionTheme[editForm.condition].stroke : '#D1D5DB'" stroke-width="0.6" class="cursor-pointer hover:opacity-80" @click="toggleSurface('occlusal')" />
                        <!-- Labels -->
                        <text x="16" y="-1" text-anchor="middle" font-size="5" fill="#9CA3AF">{{ isRtl ? 'خ' : 'B' }}</text>
                        <text x="16" y="37" text-anchor="middle" font-size="5" fill="#9CA3AF">{{ isRtl ? 'ل' : 'L' }}</text>
                        <text x="-6" y="17" text-anchor="middle" font-size="5" fill="#9CA3AF">{{ isRtl ? 'و' : 'M' }}</text>
                        <text x="38" y="17" text-anchor="middle" font-size="5" fill="#9CA3AF">{{ isRtl ? 'ب' : 'D' }}</text>
                    </svg>
                    <div class="flex flex-wrap gap-2">
                        <button v-for="s in surfaces" :key="s" type="button" @click="toggleSurface(s)" class="px-4 py-2 rounded-xl text-sm font-medium border-2 transition-all" :class="editForm.surfaces.includes(s)?'bg-[#1B365D] text-white border-[#1B365D] shadow-md shadow-[#1B365D]/20':'bg-gray-50 text-gray-600 border-gray-100 hover:border-gray-200'">{{ surfaceLabels[s] || s }}</button>
                    </div>
                </div>
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-3">{{ isRtl ? 'وجود السن' : 'Presence' }}</label>
                <div class="grid grid-cols-3 gap-2">
                    <button type="button" @click="editForm.status='present'" class="px-3 py-2.5 rounded-xl text-sm font-medium border-2 transition-all" :class="editForm.status==='present'?'border-emerald-500 bg-emerald-50 text-emerald-700':'border-gray-100 text-gray-500'">{{ isRtl ? 'موجود' : 'Present' }}</button>
                    <button type="button" @click="editForm.status='missing'" class="px-3 py-2.5 rounded-xl text-sm font-medium border-2 transition-all" :class="editForm.status==='missing'?'border-gray-500 bg-gray-100 text-gray-700':'border-gray-100 text-gray-500'">{{ isRtl ? 'مفقود' : 'Missing' }}</button>
                    <button type="button" @click="editForm.status='implant'" class="px-3 py-2.5 rounded-xl text-sm font-medium border-2 transition-all" :class="editForm.status==='implant'?'border-[#1B365D] bg-slate-50 text-[#1B365D]':'border-gray-100 text-gray-500'">{{ isRtl ? 'زرعة' : 'Implant' }}</button>
                </div>
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">{{ isRtl ? 'ملاحظات' : 'Notes' }}</label>
                <textarea v-model="editForm.notes" rows="2" class="doctorato-input w-full border-2 border-gray-100 rounded-xl px-4 py-3 text-sm focus:ring-2 focus:ring-[#C4A265]/20 focus:border-[#1B365D] transition-all resize-none" :placeholder="isRtl ? 'أضف ملاحظات...' : 'Add notes...'"></textarea>
            </div>
        </div>
        <div class="px-6 py-4 bg-gray-50 border-t flex gap-3 justify-end">
            <button @click="showModal=false" class="px-5 py-2.5 border-2 border-gray-200 rounded-xl text-sm font-medium text-gray-600 hover:bg-white transition-all">{{ isRtl ? 'إلغاء' : 'Cancel' }}</button>
            <button @click="saveTooth" :disabled="saving" class="px-6 py-2.5 bg-[#1B365D] text-white rounded-xl text-sm font-semibold hover:bg-[#1B365D] disabled:opacity-50 transition-all shadow-lg shadow-[#1B365D]/20 flex items-center gap-2">
                <svg v-if="saving" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" /><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" /></svg>
                {{ isRtl ? 'حفظ' : 'Save' }}
            </button>
        </div>
    </div>
</div>
</Transition>
</Teleport>

<!-- ═══ ADD ENTRY MODAL ═══ -->
<Teleport to="body">
<Transition enter-active-class="transition-all duration-300 ease-out" enter-from-class="opacity-0" enter-to-class="opacity-100" leave-active-class="transition-all duration-200 ease-in" leave-from-class="opacity-100" leave-to-class="opacity-0">
<div v-if="showEntryModal" class="fixed inset-0 bg-black/60 backdrop-blur-sm z-50 flex items-start justify-center p-4 overflow-y-auto" @click.self="showEntryModal=false">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl my-8 overflow-hidden">
        <div class="bg-gradient-to-r from-[#1B365D] via-[#1B365D] to-emerald-600 px-6 py-5 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-11 h-11 rounded-xl bg-white/20 flex items-center justify-center"><span class="text-white font-bold font-mono text-lg">{{ selectedTooth }}</span></div>
                <div>
                    <h3 class="text-white font-bold text-lg">{{ isRtl ? 'إضافة سجل جديد' : 'Add New Entry' }}</h3>
                    <p class="text-slate-100 text-xs">{{ getToothName(selectedTooth) }} — {{ patient.full_name }}</p>
                </div>
            </div>
            <button @click="showEntryModal=false" class="text-slate-200 hover:text-white"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg></button>
        </div>

        <div class="p-6 space-y-5 max-h-[70vh] overflow-y-auto">
            <!-- Entry Type -->
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-3">{{ isRtl ? 'نوع السجل' : 'Entry Type' }} *</label>
                <div class="grid grid-cols-3 sm:grid-cols-6 gap-2">
                    <button v-for="(label, type) in entryTypeLabels" :key="type" type="button" @click="entryForm.entry_type=type"
                        class="flex flex-col items-center gap-1 p-3 rounded-xl border-2 text-center transition-all"
                        :class="entryForm.entry_type===type ? 'border-[#1B365D] bg-slate-50 text-[#1B365D] shadow-md' : 'border-gray-100 text-gray-500 hover:border-gray-200'">
                        <span class="text-lg">{{ entryTypeIcons[type] }}</span>
                        <span class="text-[10px] font-semibold">{{ label }}</span>
                    </button>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1.5">{{ isRtl ? 'العنوان' : 'Title' }}</label>
                    <input v-model="entryForm.title" type="text" class="doctorato-input w-full border-2 border-gray-100 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-[#C4A265]/20 focus:border-[#1B365D] transition-all" :placeholder="isRtl ? 'مثال: فحص أولي' : 'e.g. Initial examination'" />
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1.5">{{ isRtl ? 'التاريخ' : 'Date' }}</label>
                    <input v-model="entryForm.entry_date" type="date" class="doctorato-input w-full border-2 border-gray-100 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-[#C4A265]/20 focus:border-[#1B365D] transition-all" />
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1.5">{{ isRtl ? 'الطبيب' : 'Doctor' }}</label>
                    <select v-model="entryForm.doctor_id" class="doctorato-input w-full border-2 border-gray-100 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-[#C4A265]/20 focus:border-[#1B365D] transition-all">
                        <option value="">{{ isRtl ? 'اختر الطبيب' : 'Select doctor' }}</option>
                        <option v-for="doc in doctors" :key="doc.id" :value="doc.id">{{ isRtl ? doc.name_ar : doc.name_en }}</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1.5">{{ isRtl ? 'التكلفة' : 'Cost' }}</label>
                    <input v-model="entryForm.cost" type="number" step="0.01" class="doctorato-input w-full border-2 border-gray-100 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-[#C4A265]/20 focus:border-[#1B365D] transition-all" placeholder="0.00" />
                </div>
            </div>

            <div v-if="entryForm.entry_type==='treatment' || entryForm.entry_type==='examination'" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1.5">{{ isRtl ? 'الحالة قبل' : 'Condition Before' }}</label>
                    <select v-model="entryForm.condition_before" class="doctorato-input w-full border-2 border-gray-100 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-[#C4A265]/20 focus:border-[#1B365D] transition-all">
                        <option value="">-</option>
                        <option v-for="c in conditions" :key="c" :value="c">{{ conditionLabels[c] }}</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1.5">{{ isRtl ? 'الحالة بعد' : 'Condition After' }}</label>
                    <select v-model="entryForm.condition_after" class="doctorato-input w-full border-2 border-gray-100 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-[#C4A265]/20 focus:border-[#1B365D] transition-all">
                        <option value="">-</option>
                        <option v-for="c in conditions" :key="c" :value="c">{{ conditionLabels[c] }}</option>
                    </select>
                </div>
            </div>

            <div v-if="['treatment','examination'].includes(entryForm.entry_type)">
                <label class="block text-xs font-semibold text-gray-600 mb-1.5">{{ isRtl ? 'الأسطح المعالجة' : 'Affected Surfaces' }}</label>
                <div class="flex flex-wrap gap-2">
                    <button v-for="s in surfaces" :key="s" type="button" @click="toggleEntrySurface(s)" class="px-3 py-1.5 rounded-lg text-xs font-medium border-2 transition-all" :class="entryForm.surfaces.includes(s)?'bg-[#1B365D] text-white border-[#1B365D]':'bg-gray-50 text-gray-500 border-gray-100 hover:border-gray-200'">{{ surfaceLabels[s] }}</button>
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1.5">{{ isRtl ? 'الوصف / التفاصيل' : 'Description / Details' }}</label>
                <textarea v-model="entryForm.description" rows="3" class="doctorato-input w-full border-2 border-gray-100 rounded-xl px-4 py-3 text-sm focus:ring-2 focus:ring-[#C4A265]/20 focus:border-[#1B365D] transition-all resize-none" :placeholder="isRtl ? 'اكتب تفاصيل الفحص أو العلاج...' : 'Write examination or treatment details...'"></textarea>
            </div>

            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1.5">{{ isRtl ? 'الحالة' : 'Status' }}</label>
                <div class="flex gap-2">
                    <button type="button" @click="entryForm.status='recorded'" class="px-4 py-2 rounded-xl text-xs font-medium border-2 transition-all" :class="entryForm.status==='recorded'?'border-gray-500 bg-gray-50 text-gray-700':'border-gray-100 text-gray-400'">{{ isRtl ? 'مسجل' : 'Recorded' }}</button>
                    <button type="button" @click="entryForm.status='in_progress'" class="px-4 py-2 rounded-xl text-xs font-medium border-2 transition-all" :class="entryForm.status==='in_progress'?'border-[#1B365D] bg-slate-50 text-[#1B365D]':'border-gray-100 text-gray-400'">{{ isRtl ? 'جاري' : 'In Progress' }}</button>
                    <button type="button" @click="entryForm.status='completed'" class="px-4 py-2 rounded-xl text-xs font-medium border-2 transition-all" :class="entryForm.status==='completed'?'border-emerald-500 bg-emerald-50 text-emerald-700':'border-gray-100 text-gray-400'">{{ isRtl ? 'مكتمل' : 'Completed' }}</button>
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1.5">{{ isRtl ? 'الصور والملفات' : 'Images & Files' }}</label>
                <div class="border-2 border-dashed border-gray-200 rounded-xl p-4 text-center hover:border-slate-300 transition-all cursor-pointer relative">
                    <input type="file" multiple accept="image/*,video/*,.pdf,.doc,.docx" @change="handleFileSelect" class="absolute inset-0 opacity-0 cursor-pointer" />
                    <svg class="w-8 h-8 text-gray-300 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                    <p class="text-xs text-gray-400">{{ isRtl ? 'اسحب الملفات أو اضغط للرفع' : 'Drag files or click to upload' }}</p>
                    <p class="text-[10px] text-gray-300 mt-1">{{ isRtl ? 'صور، فيديو، PDF، مستندات (حد أقصى 20MB)' : 'Images, video, PDF, docs (max 20MB each)' }}</p>
                </div>
                <div v-if="mediaPreview.length" class="flex flex-wrap gap-2 mt-3">
                    <div v-for="(p, pi) in mediaPreview" :key="pi" class="relative group">
                        <div class="w-20 h-20 rounded-lg border border-gray-200 overflow-hidden">
                            <img v-if="p.type==='image'" :src="p.url" class="w-full h-full object-cover" />
                            <div v-else-if="p.type==='video'" class="w-full h-full bg-gray-900 flex items-center justify-center"><svg class="w-6 h-6 text-white/80" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z" /></svg></div>
                            <div v-else class="w-full h-full bg-gray-50 flex flex-col items-center justify-center p-1">
                                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" /></svg>
                                <span class="text-[8px] text-gray-400 truncate w-full text-center mt-0.5">{{ p.name }}</span>
                            </div>
                        </div>
                        <button @click="removeFile(pi)" class="absolute -top-1.5 -right-1.5 w-5 h-5 bg-red-500 text-white rounded-full flex items-center justify-center text-xs opacity-0 group-hover:opacity-100 transition-opacity shadow-md">&times;</button>
                        <p class="text-[8px] text-gray-400 text-center mt-0.5">{{ formatBytes(p.size) }}</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="px-6 py-4 bg-gray-50 border-t flex gap-3 justify-end">
            <button @click="showEntryModal=false" class="px-5 py-2.5 border-2 border-gray-200 rounded-xl text-sm font-medium text-gray-600 hover:bg-white transition-all">{{ isRtl ? 'إلغاء' : 'Cancel' }}</button>
            <button @click="saveEntry" :disabled="savingEntry" class="px-6 py-2.5 bg-gradient-to-r from-[#1B365D] to-emerald-600 text-white rounded-xl text-sm font-semibold hover:from-[#1B365D] hover:to-emerald-700 disabled:opacity-50 transition-all shadow-lg shadow-[#1B365D]/20 flex items-center gap-2">
                <svg v-if="savingEntry" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" /><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" /></svg>
                {{ isRtl ? 'حفظ السجل' : 'Save Entry' }}
            </button>
        </div>
    </div>
</div>
</Transition>
</Teleport>

<!-- Initialize Chart Confirm Modal -->
<ConfirmModal
    :show="showInitializeModal"
    :title="isRtl ? 'تهيئة المخطط' : 'Initialize Chart'"
    :message="isRtl ? (chartMode === 'deciduous' ? 'هل أنت متأكد من تهيئة أسنان الأطفال؟' : 'هل أنت متأكد من تهيئة المخطط؟') : (chartMode === 'deciduous' ? 'Initialize deciduous chart?' : 'Initialize chart?')"
    :confirmText="isRtl ? 'تهيئة' : 'Initialize'"
    :cancelText="isRtl ? 'إلغاء' : 'Cancel'"
    confirmColor="cyan"
    @confirm="executeInitializeChart"
    @cancel="showInitializeModal = false"
/>

<!-- Delete Entry Confirm Modal -->
<ConfirmModal
    :show="showDeleteEntryModal"
    :title="isRtl ? 'حذف السجل' : 'Delete Entry'"
    :message="isRtl ? 'هل أنت متأكد من حذف هذا السجل؟ لا يمكن التراجع عن هذا الإجراء.' : 'Are you sure you want to delete this entry? This action cannot be undone.'"
    :confirmText="isRtl ? 'حذف' : 'Delete'"
    :cancelText="isRtl ? 'إلغاء' : 'Cancel'"
    confirmColor="red"
    @confirm="executeDeleteEntry"
    @cancel="showDeleteEntryModal = false"
/>
</AdminLayout>
</template>

<style scoped>
.dental-tooth-cell { cursor: pointer; position: relative; flex-shrink: 0; }
.dental-side-svg { width: 28px; height: 44px; transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1); }
@media (min-width: 640px) { .dental-side-svg { width: 34px; height: 52px; } }
@media (min-width: 768px) { .dental-side-svg { width: 40px; height: 60px; } }
.upper-tooth { transform: scaleY(-1); }
.dental-occlusal-cell { cursor: pointer; position: relative; flex-shrink: 0; }
.dental-occlusal-svg { width: 26px; height: 28px; transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1); }
@media (min-width: 640px) { .dental-occlusal-svg { width: 32px; height: 34px; } }
@media (min-width: 768px) { .dental-occlusal-svg { width: 38px; height: 38px; } }
.dental-hovered { transform: scale(1.12); filter: drop-shadow(0 2px 4px rgba(0,0,0,0.15)); z-index: 10; }
.upper-tooth.dental-hovered { transform: scaleY(-1) scale(1.12); }
.dental-selected { transform: scale(1.15); z-index: 10; }
.upper-tooth.dental-selected { transform: scaleY(-1) scale(1.15); }
.dental-missing { opacity: 0.3; }
.upper-tooth.dental-missing { opacity: 0.3; transform: scaleY(-1); }
.dental-number-cell { width: 26px; text-align: center; font-size: 9px; font-weight: 700; font-family: ui-monospace, monospace; color: #9CA3AF; cursor: pointer; padding: 2px 0; border-radius: 4px; transition: all 0.2s; flex-shrink: 0; }
@media (min-width: 640px) { .dental-number-cell { width: 32px; font-size: 10px; } }
@media (min-width: 768px) { .dental-number-cell { width: 38px; font-size: 11px; } }
.dental-number-cell:hover, .dental-number-active { color: #1B365D; background: rgba(8, 145, 178, 0.08); }
.dental-center-line { width: 1px; align-self: stretch; background: linear-gradient(to bottom, transparent, #1B365D, transparent); opacity: 0.3; margin: 0 4px; flex-shrink: 0; }
@media (min-width: 640px) { .dental-center-line { margin: 0 6px; } }
.dental-center-line.short { height: 20px; align-self: center; }
.dental-gum-line { height: 3px; margin: 0 8px; border-radius: 2px; }
.upper-gum { background: linear-gradient(90deg, transparent, #FDA4AF, #D4B57E, #FDA4AF, transparent); opacity: 0.5; }
.lower-gum { background: linear-gradient(90deg, transparent, #FDA4AF, #D4B57E, #FDA4AF, transparent); opacity: 0.5; }
.dental-tooltip { position: absolute; left: 50%; transform: translateX(-50%); background: #1F2937; color: white; font-size: 10px; padding: 4px 10px; border-radius: 8px; white-space: nowrap; z-index: 30; pointer-events: none; box-shadow: 0 4px 12px rgba(0,0,0,0.3); }
.dental-tooltip.above { bottom: calc(100% + 6px); }
.dental-tooltip.above::after { content: ''; position: absolute; top: 100%; left: 50%; transform: translateX(-50%); border: 4px solid transparent; border-top-color: #1F2937; }
.dental-tooltip.below { top: calc(100% + 6px); }
.dental-tooltip.below::after { content: ''; position: absolute; bottom: 100%; left: 50%; transform: translateX(-50%); border: 4px solid transparent; border-bottom-color: #1F2937; }

/* Surface Drawing Mode */
.dental-surface-edit .dental-occlusal-svg { cursor: crosshair; }
.dental-surface-path { transition: all 0.2s ease; }
.surface-clickable { cursor: pointer; }
.surface-clickable:hover { opacity: 0.7 !important; stroke-width: 1.5 !important; }
.surface-active { stroke-width: 1.2; }

/* Animations */
.dental-row-enter .dental-tooth-cell, .dental-row-enter .dental-occlusal-cell { animation: toothFadeIn 0.4s cubic-bezier(0.16, 1, 0.3, 1) forwards; opacity: 0; }
@keyframes toothFadeIn { from { opacity: 0; transform: translateY(8px) scale(0.9); } to { opacity: 1; transform: translateY(0) scale(1); } }

@keyframes dentalHeroEnter {
    from { opacity: 0; transform: translateY(16px); }
    to   { opacity: 1; transform: translateY(0); }
}
@keyframes dentalCardEnter {
    from { opacity: 0; transform: translateY(20px); }
    to   { opacity: 1; transform: translateY(0); }
}
.dental-hero-enter { animation: dentalHeroEnter 0.6s cubic-bezier(0.16, 1, 0.3, 1) both; }
.dental-card-enter { animation: dentalCardEnter 0.6s cubic-bezier(0.16, 1, 0.3, 1) both; }

/* Floating hero tooth motif */
@keyframes dentalFloat {
    0%, 100% { transform: translateY(0) rotate(0deg); }
    50%      { transform: translateY(-10px) rotate(3deg); }
}
.dental-float { animation: dentalFloat 7s ease-in-out infinite; }

/* ── Accessibility: honor reduced-motion ───────────────── */
@media (prefers-reduced-motion: reduce) {
    .dental-hero-enter,
    .dental-card-enter,
    .dental-row-enter .dental-tooth-cell,
    .dental-row-enter .dental-occlusal-cell {
        animation: none !important;
        opacity: 1 !important;
        transform: none !important;
    }
    .dental-float { animation: none !important; }
    .dental-side-svg,
    .dental-occlusal-svg { transition: none !important; }
    /* Preserve the upper-jaw mirror flip even with motion off */
    .upper-tooth { transform: scaleY(-1) !important; }
}
</style>
