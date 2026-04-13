<script setup>
import { computed, ref, watch, onMounted, onBeforeUnmount, nextTick } from 'vue';
import { Link, useForm, usePage } from '@inertiajs/vue3';
import SecretaryLayout from '@/Layouts/SecretaryLayout.vue';

const page = usePage();
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const props = defineProps({
    lead: Object,
    sources: Array,
    campaigns: Array,
    services: Array,
});

const isEdit = computed(() => !!props.lead);
const mounted = ref(false);
onMounted(() => { setTimeout(() => { mounted.value = true; }, 50); });

const form = useForm({
    full_name: props.lead?.full_name || '',
    phone: props.lead?.phone || '',
    phone2: props.lead?.phone2 || '',
    email: props.lead?.email || '',
    gender: props.lead?.gender || '',
    date_of_birth: props.lead?.date_of_birth ? props.lead.date_of_birth.substring(0,10) : '',
    city: props.lead?.city || '',
    nationality: props.lead?.nationality || '',
    lead_source_id: props.lead?.lead_source_id || '',
    campaign_id: props.lead?.campaign_id || '',
    priority: props.lead?.priority || 2,
    interested_services: props.lead?.interested_services || [],
    notes: props.lead?.notes || '',
});

function submit() {
    const url = isEdit.value
        ? `/secretary/crm/leads/${props.lead.id}`
        : '/secretary/crm/leads';
    form.post(url);
}

const duplicateWarning = ref(null);
const checkingDuplicate = ref(false);

async function checkDuplicate() {
    const phone = form.phone.trim();
    if (!phone || phone.length < 7) {
        duplicateWarning.value = null;
        return;
    }
    checkingDuplicate.value = true;
    try {
        const res = await fetch(`/secretary/crm/check-duplicate?phone=${encodeURIComponent(phone)}`, {
            credentials: 'same-origin',
        });
        const data = await res.json();
        duplicateWarning.value = data.exists ? data.lead : null;
    } catch (e) {
        duplicateWarning.value = null;
    }
    checkingDuplicate.value = false;
}

function toggleService(id) {
    const idx = form.interested_services.indexOf(id);
    if (idx > -1) {
        form.interested_services.splice(idx, 1);
    } else {
        form.interested_services.push(id);
    }
}

/* ---------- Service search filter ---------- */
const serviceSearch = ref('');
const filteredServices = computed(() => {
    if (!props.services?.length) return [];
    if (!serviceSearch.value.trim()) return props.services;
    const q = serviceSearch.value.trim().toLowerCase();
    return props.services.filter(s =>
        (s.name_en || '').toLowerCase().includes(q) ||
        (s.name_ar || '').includes(q)
    );
});

const nationalityOptions = [
    { value: 'Emirati', ar: 'إماراتي', en: 'Emirati' },
    { value: 'Egyptian', ar: 'مصري', en: 'Egyptian' },
    { value: 'Saudi', ar: 'سعودي', en: 'Saudi' },
    { value: 'Kuwaiti', ar: 'كويتي', en: 'Kuwaiti' },
    { value: 'Qatari', ar: 'قطري', en: 'Qatari' },
    { value: 'Bahraini', ar: 'بحريني', en: 'Bahraini' },
    { value: 'Omani', ar: 'عماني', en: 'Omani' },
    { value: 'Jordanian', ar: 'أردني', en: 'Jordanian' },
    { value: 'Lebanese', ar: 'لبناني', en: 'Lebanese' },
    { value: 'Syrian', ar: 'سوري', en: 'Syrian' },
    { value: 'Iraqi', ar: 'عراقي', en: 'Iraqi' },
    { value: 'Palestinian', ar: 'فلسطيني', en: 'Palestinian' },
    { value: 'Yemeni', ar: 'يمني', en: 'Yemeni' },
    { value: 'Libyan', ar: 'ليبي', en: 'Libyan' },
    { value: 'Tunisian', ar: 'تونسي', en: 'Tunisian' },
    { value: 'Algerian', ar: 'جزائري', en: 'Algerian' },
    { value: 'Moroccan', ar: 'مغربي', en: 'Moroccan' },
    { value: 'Sudanese', ar: 'سوداني', en: 'Sudanese' },
    { value: 'Somali', ar: 'صومالي', en: 'Somali' },
    { value: 'Turkish', ar: 'تركي', en: 'Turkish' },
    { value: 'Iranian', ar: 'إيراني', en: 'Iranian' },
    { value: 'Pakistani', ar: 'باكستاني', en: 'Pakistani' },
    { value: 'Indian', ar: 'هندي', en: 'Indian' },
    { value: 'Bangladeshi', ar: 'بنغلاديشي', en: 'Bangladeshi' },
    { value: 'Sri Lankan', ar: 'سريلانكي', en: 'Sri Lankan' },
    { value: 'Afghan', ar: 'أفغاني', en: 'Afghan' },
    { value: 'Filipino', ar: 'فلبيني', en: 'Filipino' },
    { value: 'Indonesian', ar: 'إندونيسي', en: 'Indonesian' },
    { value: 'Malaysian', ar: 'ماليزي', en: 'Malaysian' },
    { value: 'Chinese', ar: 'صيني', en: 'Chinese' },
    { value: 'Japanese', ar: 'ياباني', en: 'Japanese' },
    { value: 'Korean', ar: 'كوري', en: 'Korean' },
    { value: 'Thai', ar: 'تايلاندي', en: 'Thai' },
    { value: 'Ethiopian', ar: 'إثيوبي', en: 'Ethiopian' },
    { value: 'Nigerian', ar: 'نيجيري', en: 'Nigerian' },
    { value: 'Kenyan', ar: 'كيني', en: 'Kenyan' },
    { value: 'South African', ar: 'جنوب أفريقي', en: 'South African' },
    { value: 'American', ar: 'أمريكي', en: 'American' },
    { value: 'Canadian', ar: 'كندي', en: 'Canadian' },
    { value: 'British', ar: 'بريطاني', en: 'British' },
    { value: 'French', ar: 'فرنسي', en: 'French' },
    { value: 'German', ar: 'ألماني', en: 'German' },
    { value: 'Italian', ar: 'إيطالي', en: 'Italian' },
    { value: 'Spanish', ar: 'إسباني', en: 'Spanish' },
    { value: 'Russian', ar: 'روسي', en: 'Russian' },
    { value: 'Australian', ar: 'أسترالي', en: 'Australian' },
    { value: 'Other', ar: 'أخرى', en: 'Other' },
];

const citiesByNationality = {
    Emirati: [
        { value: 'Abu Dhabi', ar: 'أبوظبي', en: 'Abu Dhabi' },
        { value: 'Dubai', ar: 'دبي', en: 'Dubai' },
        { value: 'Sharjah', ar: 'الشارقة', en: 'Sharjah' },
        { value: 'Ajman', ar: 'عجمان', en: 'Ajman' },
        { value: 'Ras Al Khaimah', ar: 'رأس الخيمة', en: 'Ras Al Khaimah' },
        { value: 'Fujairah', ar: 'الفجيرة', en: 'Fujairah' },
        { value: 'Umm Al Quwain', ar: 'أم القيوين', en: 'Umm Al Quwain' },
        { value: 'Al Ain', ar: 'العين', en: 'Al Ain' },
    ],
    Egyptian: [
        { value: 'Cairo', ar: 'القاهرة', en: 'Cairo' },
        { value: 'Giza', ar: 'الجيزة', en: 'Giza' },
        { value: 'Alexandria', ar: 'الإسكندرية', en: 'Alexandria' },
        { value: 'Sharm El Sheikh', ar: 'شرم الشيخ', en: 'Sharm El Sheikh' },
        { value: 'Hurghada', ar: 'الغردقة', en: 'Hurghada' },
        { value: 'Luxor', ar: 'الأقصر', en: 'Luxor' },
        { value: 'Aswan', ar: 'أسوان', en: 'Aswan' },
        { value: 'Mansoura', ar: 'المنصورة', en: 'Mansoura' },
        { value: 'Tanta', ar: 'طنطا', en: 'Tanta' },
        { value: 'Zagazig', ar: 'الزقازيق', en: 'Zagazig' },
        { value: 'Ismailia', ar: 'الإسماعيلية', en: 'Ismailia' },
        { value: 'Suez', ar: 'السويس', en: 'Suez' },
        { value: 'Port Said', ar: 'بورسعيد', en: 'Port Said' },
        { value: 'Damietta', ar: 'دمياط', en: 'Damietta' },
        { value: 'Fayoum', ar: 'الفيوم', en: 'Fayoum' },
        { value: 'Beni Suef', ar: 'بني سويف', en: 'Beni Suef' },
        { value: 'Minya', ar: 'المنيا', en: 'Minya' },
        { value: 'Asyut', ar: 'أسيوط', en: 'Asyut' },
        { value: 'Sohag', ar: 'سوهاج', en: 'Sohag' },
        { value: 'Qena', ar: 'قنا', en: 'Qena' },
        { value: 'Kafr El Sheikh', ar: 'كفر الشيخ', en: 'Kafr El Sheikh' },
        { value: 'Gharbia', ar: 'الغربية', en: 'Gharbia' },
        { value: 'Monufia', ar: 'المنوفية', en: 'Monufia' },
        { value: 'Beheira', ar: 'البحيرة', en: 'Beheira' },
        { value: 'Qalyubia', ar: 'القليوبية', en: 'Qalyubia' },
        { value: 'Sharqia', ar: 'الشرقية', en: 'Sharqia' },
        { value: 'Dakahlia', ar: 'الدقهلية', en: 'Dakahlia' },
        { value: 'New Valley', ar: 'الوادي الجديد', en: 'New Valley' },
        { value: 'Red Sea', ar: 'البحر الأحمر', en: 'Red Sea' },
        { value: 'Matruh', ar: 'مطروح', en: 'Matruh' },
        { value: 'North Sinai', ar: 'شمال سيناء', en: 'North Sinai' },
        { value: 'South Sinai', ar: 'جنوب سيناء', en: 'South Sinai' },
    ],
    Saudi: [
        { value: 'Riyadh', ar: 'الرياض', en: 'Riyadh' },
        { value: 'Jeddah', ar: 'جدة', en: 'Jeddah' },
        { value: 'Mecca', ar: 'مكة المكرمة', en: 'Mecca' },
        { value: 'Medina', ar: 'المدينة المنورة', en: 'Medina' },
        { value: 'Dammam', ar: 'الدمام', en: 'Dammam' },
        { value: 'Khobar', ar: 'الخبر', en: 'Khobar' },
        { value: 'Tabuk', ar: 'تبوك', en: 'Tabuk' },
        { value: 'Abha', ar: 'أبها', en: 'Abha' },
    ],
    Jordanian: [
        { value: 'Amman', ar: 'عمّان', en: 'Amman' },
        { value: 'Irbid', ar: 'إربد', en: 'Irbid' },
        { value: 'Zarqa', ar: 'الزرقاء', en: 'Zarqa' },
        { value: 'Aqaba', ar: 'العقبة', en: 'Aqaba' },
    ],
    Lebanese: [
        { value: 'Beirut', ar: 'بيروت', en: 'Beirut' },
        { value: 'Tripoli', ar: 'طرابلس', en: 'Tripoli' },
        { value: 'Sidon', ar: 'صيدا', en: 'Sidon' },
    ],
    Syrian: [
        { value: 'Damascus', ar: 'دمشق', en: 'Damascus' },
        { value: 'Aleppo', ar: 'حلب', en: 'Aleppo' },
        { value: 'Homs', ar: 'حمص', en: 'Homs' },
        { value: 'Latakia', ar: 'اللاذقية', en: 'Latakia' },
    ],
    Iraqi: [
        { value: 'Baghdad', ar: 'بغداد', en: 'Baghdad' },
        { value: 'Basra', ar: 'البصرة', en: 'Basra' },
        { value: 'Erbil', ar: 'أربيل', en: 'Erbil' },
        { value: 'Mosul', ar: 'الموصل', en: 'Mosul' },
    ],
    Palestinian: [
        { value: 'Ramallah', ar: 'رام الله', en: 'Ramallah' },
        { value: 'Gaza', ar: 'غزة', en: 'Gaza' },
        { value: 'Nablus', ar: 'نابلس', en: 'Nablus' },
        { value: 'Hebron', ar: 'الخليل', en: 'Hebron' },
    ],
    Kuwaiti: [
        { value: 'Kuwait City', ar: 'مدينة الكويت', en: 'Kuwait City' },
        { value: 'Hawalli', ar: 'حولي', en: 'Hawalli' },
        { value: 'Salmiya', ar: 'السالمية', en: 'Salmiya' },
    ],
    Qatari: [
        { value: 'Doha', ar: 'الدوحة', en: 'Doha' },
        { value: 'Al Wakrah', ar: 'الوكرة', en: 'Al Wakrah' },
    ],
    Bahraini: [
        { value: 'Manama', ar: 'المنامة', en: 'Manama' },
        { value: 'Muharraq', ar: 'المحرق', en: 'Muharraq' },
    ],
    Omani: [
        { value: 'Muscat', ar: 'مسقط', en: 'Muscat' },
        { value: 'Salalah', ar: 'صلالة', en: 'Salalah' },
        { value: 'Sohar', ar: 'صحار', en: 'Sohar' },
    ],
    Indian: [
        { value: 'Mumbai', ar: 'مومباي', en: 'Mumbai' },
        { value: 'Delhi', ar: 'دلهي', en: 'Delhi' },
        { value: 'Bangalore', ar: 'بنغالور', en: 'Bangalore' },
        { value: 'Kerala', ar: 'كيرالا', en: 'Kerala' },
        { value: 'Hyderabad', ar: 'حيدر أباد', en: 'Hyderabad' },
        { value: 'Chennai', ar: 'تشيناي', en: 'Chennai' },
    ],
    Pakistani: [
        { value: 'Karachi', ar: 'كراتشي', en: 'Karachi' },
        { value: 'Lahore', ar: 'لاهور', en: 'Lahore' },
        { value: 'Islamabad', ar: 'إسلام آباد', en: 'Islamabad' },
    ],
    Filipino: [
        { value: 'Manila', ar: 'مانيلا', en: 'Manila' },
        { value: 'Cebu', ar: 'سيبو', en: 'Cebu' },
    ],
};

/* UAE cities as default fallback */
const defaultCities = [
    { value: 'Abu Dhabi', ar: 'أبوظبي', en: 'Abu Dhabi' },
    { value: 'Dubai', ar: 'دبي', en: 'Dubai' },
    { value: 'Sharjah', ar: 'الشارقة', en: 'Sharjah' },
    { value: 'Ajman', ar: 'عجمان', en: 'Ajman' },
    { value: 'Ras Al Khaimah', ar: 'رأس الخيمة', en: 'Ras Al Khaimah' },
    { value: 'Fujairah', ar: 'الفجيرة', en: 'Fujairah' },
    { value: 'Umm Al Quwain', ar: 'أم القيوين', en: 'Umm Al Quwain' },
    { value: 'Al Ain', ar: 'العين', en: 'Al Ain' },
];

const cityOptions = computed(() => {
    const nat = form.nationality;
    const list = (nat && citiesByNationality[nat]) ? citiesByNationality[nat] : defaultCities;
    return [...list, { value: 'Other', ar: 'أخرى', en: 'Other' }];
});

/* ---------- Searchable Select Logic ---------- */
const natOpen = ref(false);
const natSearch = ref('');
const natRef = ref(null);
const cityOpen = ref(false);
const citySearch = ref('');
const cityRef = ref(null);

const filteredNationalities = computed(() => {
    if (!natSearch.value.trim()) return nationalityOptions;
    const q = natSearch.value.trim().toLowerCase();
    return nationalityOptions.filter(n =>
        n.ar.includes(q) || n.en.toLowerCase().includes(q) || n.value.toLowerCase().includes(q)
    );
});

const filteredCities = computed(() => {
    const list = cityOptions.value;
    if (!citySearch.value.trim()) return list;
    const q = citySearch.value.trim().toLowerCase();
    return list.filter(c =>
        c.ar.includes(q) || c.en.toLowerCase().includes(q) || c.value.toLowerCase().includes(q)
    );
});

function selectNationality(val) {
    form.nationality = val;
    natOpen.value = false;
    natSearch.value = '';
    // Reset city when nationality changes (cities might differ)
    if (val !== form.nationality) {
        form.city = '';
        citySearch.value = '';
    }
}

function selectCity(val) {
    form.city = val;
    cityOpen.value = false;
    citySearch.value = '';
}

function getNatLabel(val) {
    const n = nationalityOptions.find(o => o.value === val);
    return n ? (isRtl.value ? n.ar : n.en) : '';
}

function getCityLabel(val) {
    const c = cityOptions.value.find(o => o.value === val);
    return c ? (isRtl.value ? c.ar : c.en) : '';
}

/* Close dropdowns on outside click */
function handleOutsideClick(e) {
    if (natRef.value && !natRef.value.contains(e.target)) natOpen.value = false;
    if (cityRef.value && !cityRef.value.contains(e.target)) cityOpen.value = false;
}
onMounted(() => document.addEventListener('mousedown', handleOutsideClick));
onBeforeUnmount(() => document.removeEventListener('mousedown', handleOutsideClick));

/* Reset city when nationality changes */
watch(() => form.nationality, (newVal, oldVal) => {
    if (newVal !== oldVal) {
        form.city = '';
        citySearch.value = '';
    }
});

/* ---------- Source icon helper ---------- */
function sourceIcon(icon) {
    const map = {
        globe: 'M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9',
        phone: 'M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z',
        whatsapp: 'M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z',
        facebook: 'M18 2h-3a5 5 0 00-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 011-1h3z',
        instagram: 'M16 4H8a4 4 0 00-4 4v8a4 4 0 004 4h8a4 4 0 004-4V8a4 4 0 00-4-4zm-4 11a3 3 0 110-6 3 3 0 010 6zm4.5-7.5a1 1 0 110-2 1 1 0 010 2z',
        tiktok: 'M9 12a4 4 0 104 4V4a5 5 0 005 5',
        google: 'M21.35 11.1h-9.18v2.73h5.51c-.24 1.27-.97 2.34-2.06 3.06v2.54h3.33c1.94-1.79 3.06-4.42 3.06-7.53 0-.52-.05-1.02-.14-1.5zM12.17 21c2.78 0 5.11-.92 6.81-2.5l-3.33-2.54c-.92.62-2.1.98-3.48.98-2.68 0-4.95-1.81-5.76-4.24H3.01v2.63A10.17 10.17 0 0012.17 21z',
        walk: 'M13 7a2 2 0 100-4 2 2 0 000 4zm-1 14l-2-7-3 1.5V21h-2v-7l3-1.5L7 9l5-1 4 3v3h2v2h-4v5h-2z',
        users: 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z',
        snapchat: 'M12 2C6.477 2 2 6.477 2 12s4.477 10 10 10 10-4.477 10-10S17.523 2 12 2zm0 18c-4.418 0-8-3.582-8-8s3.582-8 8-8 8 3.582 8 8-3.582 8-8 8z',
        more: 'M5 12h.01M12 12h.01M19 12h.01M6 12a1 1 0 11-2 0 1 1 0 012 0zm7 0a1 1 0 11-2 0 1 1 0 012 0zm7 0a1 1 0 11-2 0 1 1 0 012 0z',
    };
    return map[icon] || map.more;
}

const priorityOptions = [
    { value: 1, label: { en: 'Hot', ar: '\u0633\u0627\u062E\u0646' }, icon: 'hot', color: 'bg-red-100 text-red-700 border-red-300 ring-red-400' },
    { value: 2, label: { en: 'Warm', ar: '\u062F\u0627\u0641\u0626' }, icon: 'warm', color: 'bg-amber-100 text-amber-700 border-amber-300 ring-amber-400' },
    { value: 3, label: { en: 'Cold', ar: '\u0628\u0627\u0631\u062F' }, icon: 'cold', color: 'bg-blue-100 text-blue-700 border-blue-300 ring-blue-400' },
];

/* ---------- Form progress indicator ---------- */
const formSections = [
    { key: 'contact', en: 'Contact', ar: '\u0627\u0644\u0627\u062A\u0635\u0627\u0644', check: () => !!form.full_name && !!form.phone },
    { key: 'personal', en: 'Personal', ar: '\u0627\u0644\u0634\u062E\u0635\u064A\u0629', check: () => !!form.gender || !!form.date_of_birth || !!form.city },
    { key: 'details', en: 'Details', ar: '\u0627\u0644\u062A\u0641\u0627\u0635\u064A\u0644', check: () => !!form.lead_source_id || !!form.priority },
    { key: 'notes', en: 'Notes', ar: '\u0645\u0644\u0627\u062D\u0638\u0627\u062A', check: () => !!form.notes },
];

const formProgress = computed(() => {
    const filled = formSections.filter(s => s.check()).length;
    return Math.round((filled / formSections.length) * 100);
});

/* ---------- Email validation ---------- */
const emailValidation = computed(() => {
    const email = form.email.trim();
    if (!email) return null;
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/;
    if (!emailRegex.test(email)) return { valid: false, en: 'Invalid email format', ar: '\u0635\u064A\u063A\u0629 \u0628\u0631\u064A\u062F \u063A\u064A\u0631 \u0635\u062D\u064A\u062D\u0629' };
    const domain = email.split('@')[1]?.toLowerCase();
    const disposable = ['tempmail.com', 'throwaway.email', 'guerrillamail.com', 'mailinator.com', 'yopmail.com'];
    if (disposable.includes(domain)) return { valid: false, en: 'Disposable email not allowed', ar: '\u0628\u0631\u064A\u062F \u0645\u0624\u0642\u062A \u063A\u064A\u0631 \u0645\u0633\u0645\u0648\u062D' };
    return { valid: true, en: 'Valid email', ar: '\u0628\u0631\u064A\u062F \u0635\u062D\u064A\u062D' };
});

/* ---------- Phone validation ---------- */
const phoneValidation = computed(() => {
    const phone = form.phone.trim();
    if (!phone) return null;
    const clean = phone.replace(/[\s\-\(\)]/g, '');
    if (!/^\+?\d{7,15}$/.test(clean)) return { valid: false, en: 'Invalid phone format', ar: '\u0635\u064A\u063A\u0629 \u0647\u0627\u062A\u0641 \u063A\u064A\u0631 \u0635\u062D\u064A\u062D\u0629' };
    if (clean.startsWith('+971') && clean.length !== 13) return { valid: false, en: 'UAE number should be +971XXXXXXXXX', ar: '\u0631\u0642\u0645 \u0627\u0644\u0625\u0645\u0627\u0631\u0627\u062A \u064A\u062C\u0628 \u0623\u0646 \u064A\u0643\u0648\u0646 +971XXXXXXXXX' };
    if (clean.startsWith('+966') && clean.length !== 13) return { valid: false, en: 'KSA number should be +966XXXXXXXXX', ar: '\u0631\u0642\u0645 \u0627\u0644\u0633\u0639\u0648\u062F\u064A\u0629 \u064A\u062C\u0628 \u0623\u0646 \u064A\u0643\u0648\u0646 +966XXXXXXXXX' };
    return { valid: true, en: 'Valid phone number', ar: '\u0631\u0642\u0645 \u0647\u0627\u062A\u0641 \u0635\u062D\u064A\u062D' };
});
</script>

<template>
<SecretaryLayout :title="isEdit ? (isRtl ? '\u062A\u0639\u062F\u064A\u0644 \u0627\u0644\u0639\u0645\u064A\u0644' : 'Edit Lead') : (isRtl ? '\u0639\u0645\u064A\u0644 \u062C\u062F\u064A\u062F' : 'New Lead')">
<div class="min-h-screen bg-gradient-to-br from-slate-50 via-teal-50/30 to-slate-50 p-4 md:p-6">

    <!-- Breadcrumb -->
    <nav :class="['flex items-center gap-2 text-sm mb-6 transition-all duration-700', mounted ? 'opacity-100 translate-y-0' : 'opacity-0 -translate-y-3']"
         :style="{ direction: isRtl ? 'rtl' : 'ltr' }">
        <Link href="/secretary/crm" class="text-teal-600 hover:text-teal-700 transition-colors">
            {{ isRtl ? '\u0644\u0648\u062D\u0629 CRM' : 'CRM Dashboard' }}
        </Link>
        <span class="text-slate-300">&rsaquo;</span>
        <Link href="/secretary/crm/leads" class="text-teal-600 hover:text-teal-700 transition-colors">
            {{ isRtl ? '\u0627\u0644\u0639\u0645\u0644\u0627\u0621' : 'Leads' }}
        </Link>
        <span class="text-slate-300">&rsaquo;</span>
        <span class="text-slate-500">{{ isEdit ? (isRtl ? '\u062A\u0639\u062F\u064A\u0644' : 'Edit') : (isRtl ? '\u062C\u062F\u064A\u062F' : 'New') }}</span>
    </nav>

    <!-- Hero Header -->
    <div :class="['relative overflow-hidden rounded-2xl bg-gradient-to-r from-teal-600 via-teal-500 to-emerald-500 p-6 md:p-8 mb-8 shadow-xl transition-all duration-700', mounted ? 'opacity-100 translate-y-0' : 'opacity-0 -translate-y-4']"
         :style="{ transitionTimingFunction: 'cubic-bezier(0.16, 1, 0.3, 1)', transitionDelay: '50ms' }">
        <div class="absolute inset-0 opacity-10">
            <div class="absolute -top-10 -right-10 w-40 h-40 bg-white rounded-full"></div>
            <div class="absolute -bottom-8 -left-8 w-32 h-32 bg-white rounded-full"></div>
        </div>
        <div class="relative flex items-center gap-4" :style="{ direction: isRtl ? 'rtl' : 'ltr' }">
            <div class="w-14 h-14 bg-white/20 backdrop-blur rounded-xl flex items-center justify-center">
                <svg v-if="isEdit" class="w-7 h-7 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l2.651 2.651M19.513 7.138L8.768 17.883a2 2 0 01-.87.513l-3.898 1.3 1.3-3.898a2 2 0 01.513-.87L16.558 4.183a1.879 1.879 0 012.955 2.955z"/></svg>
                <svg v-else class="w-7 h-7 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
            </div>
            <div>
                <h1 class="text-2xl font-bold text-white">
                    {{ isEdit ? (isRtl ? '\u062A\u0639\u062F\u064A\u0644 \u0628\u064A\u0627\u0646\u0627\u062A \u0627\u0644\u0639\u0645\u064A\u0644' : 'Edit Lead Details') : (isRtl ? '\u0625\u0636\u0627\u0641\u0629 \u0639\u0645\u064A\u0644 \u0645\u062D\u062A\u0645\u0644 \u062C\u062F\u064A\u062F' : 'Add New Lead') }}
                </h1>
                <p class="text-teal-100 mt-1 text-sm">
                    {{ isEdit ? (isRtl ? '\u062A\u062D\u062F\u064A\u062B \u0645\u0639\u0644\u0648\u0645\u0627\u062A \u0627\u0644\u0639\u0645\u064A\u0644 \u0627\u0644\u0645\u062D\u062A\u0645\u0644' : 'Update the lead information below') : (isRtl ? '\u0623\u062F\u062E\u0644 \u0628\u064A\u0627\u0646\u0627\u062A \u0627\u0644\u0639\u0645\u064A\u0644 \u0627\u0644\u0645\u062D\u062A\u0645\u0644 \u0627\u0644\u062C\u062F\u064A\u062F' : 'Fill in the new lead details below') }}
                </p>
            </div>
        </div>
    </div>

    <!-- Form Progress Indicator -->
    <div :class="['bg-white rounded-2xl shadow-sm border border-slate-100 p-5 mb-6 transition-all duration-700', mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4']"
         :style="{ transitionTimingFunction: 'cubic-bezier(0.16, 1, 0.3, 1)', transitionDelay: '80ms', direction: isRtl ? 'rtl' : 'ltr' }">
        <div class="flex items-center justify-between mb-3">
            <div class="flex items-center gap-2">
                <svg class="w-4 h-4 text-teal-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                <span class="text-sm font-semibold text-slate-700">{{ isRtl ? '\u0627\u0643\u062A\u0645\u0627\u0644 \u0627\u0644\u0646\u0645\u0648\u0630\u062C' : 'Form Completion' }}</span>
            </div>
            <span :class="['text-sm font-bold tabular-nums', formProgress >= 75 ? 'text-emerald-600' : formProgress >= 50 ? 'text-teal-600' : 'text-slate-400']">{{ formProgress }}%</span>
        </div>
        <div class="h-2 bg-slate-100 rounded-full overflow-hidden mb-3">
            <div class="h-full rounded-full transition-all duration-700 ease-out"
                 :style="{ width: formProgress + '%', background: formProgress >= 75 ? 'linear-gradient(90deg, #10b981, #059669)' : formProgress >= 50 ? 'linear-gradient(90deg, #0d9488, #14b8a6)' : 'linear-gradient(90deg, #94a3b8, #cbd5e1)' }"></div>
        </div>
        <div class="flex items-center gap-1">
            <div v-for="(section, idx) in formSections" :key="section.key"
                 class="flex items-center gap-1">
                <div :class="['flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-medium transition-all duration-300',
                     section.check() ? 'bg-teal-50 text-teal-700' : 'bg-slate-50 text-slate-400']">
                    <svg v-if="section.check()" class="w-3.5 h-3.5 text-teal-500" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                    <div v-else class="w-3.5 h-3.5 rounded-full border-2 border-slate-300"></div>
                    {{ isRtl ? section.ar : section.en }}
                </div>
                <svg v-if="idx < formSections.length - 1" class="w-3 h-3 text-slate-300 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" :d="isRtl ? 'M15 19l-7-7 7-7' : 'M9 5l7 7-7 7'"/></svg>
            </div>
        </div>
    </div>

    <form @submit.prevent="submit" :style="{ direction: isRtl ? 'rtl' : 'ltr' }">

        <!-- Section 1: Contact Info -->
        <div :class="['bg-white rounded-2xl shadow-sm border border-slate-100 p-6 mb-6 transition-all duration-700', mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-6']"
             :style="{ transitionTimingFunction: 'cubic-bezier(0.16, 1, 0.3, 1)', transitionDelay: '100ms' }">
            <div class="flex items-center gap-3 mb-5">
                <div class="w-10 h-10 bg-teal-100 rounded-xl flex items-center justify-center">
                    <svg class="w-5 h-5 text-teal-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                </div>
                <h2 class="text-lg font-bold text-slate-800">{{ isRtl ? '\u0645\u0639\u0644\u0648\u0645\u0627\u062A \u0627\u0644\u0627\u062A\u0635\u0627\u0644' : 'Contact Information' }}</h2>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <!-- Full Name -->
                <div>
                    <label class="block text-sm font-semibold text-slate-600 mb-1.5">
                        {{ isRtl ? '\u0627\u0644\u0627\u0633\u0645 \u0627\u0644\u0643\u0627\u0645\u0644' : 'Full Name' }} <span class="text-red-500">*</span>
                    </label>
                    <input v-model="form.full_name" type="text"
                           :class="['w-full rounded-xl border px-4 py-3 text-sm transition-all duration-200 focus:ring-2 focus:ring-teal-400/40 focus:border-teal-400 outline-none', form.errors.full_name ? 'border-red-300 bg-red-50' : 'border-slate-200 bg-slate-50/50 hover:border-slate-300']"
                           :placeholder="isRtl ? '\u0623\u062F\u062E\u0644 \u0627\u0633\u0645 \u0627\u0644\u0639\u0645\u064A\u0644' : 'Enter client name'" />
                    <p v-if="form.errors.full_name" class="mt-1 text-xs text-red-500">{{ form.errors.full_name }}</p>
                </div>
                <!-- Phone -->
                <div>
                    <label class="block text-sm font-semibold text-slate-600 mb-1.5">
                        {{ isRtl ? '\u0631\u0642\u0645 \u0627\u0644\u0647\u0627\u062A\u0641' : 'Phone Number' }} <span class="text-red-500">*</span>
                    </label>
                    <input v-model="form.phone" type="tel" dir="ltr" @blur="checkDuplicate"
                           :class="['w-full rounded-xl border px-4 py-3 text-sm transition-all duration-200 focus:ring-2 focus:ring-teal-400/40 focus:border-teal-400 outline-none', form.errors.phone ? 'border-red-300 bg-red-50' : 'border-slate-200 bg-slate-50/50 hover:border-slate-300']"
                           placeholder="+971 XX XXX XXXX" />
                    <p v-if="form.errors.phone" class="mt-1 text-xs text-red-500">{{ form.errors.phone }}</p>
                    <!-- Phone Validation Hint -->
                    <div v-if="phoneValidation && !form.errors.phone && !checkingDuplicate" class="mt-1.5 flex items-center gap-1.5">
                        <svg v-if="phoneValidation.valid" class="w-3.5 h-3.5 text-emerald-500" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                        <svg v-else class="w-3.5 h-3.5 text-amber-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>
                        <span :class="['text-xs', phoneValidation.valid ? 'text-emerald-600' : 'text-amber-600']">{{ isRtl ? phoneValidation.ar : phoneValidation.en }}</span>
                    </div>
                    <div v-if="checkingDuplicate" class="mt-1.5 flex items-center gap-1.5">
                        <svg class="w-3.5 h-3.5 text-slate-400 animate-spin" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                        <span class="text-xs text-slate-400">{{ isRtl ? '\u062C\u0627\u0631\u064A \u0627\u0644\u062A\u062D\u0642\u0642...' : 'Checking...' }}</span>
                    </div>
                    <!-- Duplicate Warning -->
                    <div v-if="duplicateWarning" class="mt-2 bg-amber-50 border border-amber-200 rounded-lg p-3 flex items-start gap-2">
                        <svg class="w-5 h-5 text-amber-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>
                        <div class="text-sm">
                            <p class="font-medium text-amber-800">{{ isRtl ? 'تحذير: هذا الرقم موجود مسبقاً!' : 'Warning: This phone already exists!' }}</p>
                            <p class="text-amber-600 mt-0.5">{{ duplicateWarning.full_name }} - {{ duplicateWarning.status }}</p>
                            <Link v-if="duplicateWarning.is_mine" :href="`/secretary/crm/leads/${duplicateWarning.id}`" class="text-teal-600 hover:underline text-xs mt-1 inline-block">
                                {{ isRtl ? 'عرض العميل' : 'View Lead' }}
                            </Link>
                        </div>
                    </div>
                </div>
                <!-- Phone 2 -->
                <div>
                    <label class="block text-sm font-semibold text-slate-600 mb-1.5">
                        {{ isRtl ? '\u0631\u0642\u0645 \u0647\u0627\u062A\u0641 \u0628\u062F\u064A\u0644' : 'Alternative Phone' }}
                    </label>
                    <input v-model="form.phone2" type="tel" dir="ltr"
                           class="w-full rounded-xl border border-slate-200 bg-slate-50/50 hover:border-slate-300 px-4 py-3 text-sm transition-all duration-200 focus:ring-2 focus:ring-teal-400/40 focus:border-teal-400 outline-none"
                           placeholder="+971 XX XXX XXXX" />
                    <p v-if="form.errors.phone2" class="mt-1 text-xs text-red-500">{{ form.errors.phone2 }}</p>
                </div>
                <!-- Email -->
                <div>
                    <label class="block text-sm font-semibold text-slate-600 mb-1.5">
                        {{ isRtl ? '\u0627\u0644\u0628\u0631\u064A\u062F \u0627\u0644\u0625\u0644\u0643\u062A\u0631\u0648\u0646\u064A' : 'Email' }}
                    </label>
                    <div class="relative">
                        <input v-model="form.email" type="email" dir="ltr"
                               :class="['w-full rounded-xl border bg-slate-50/50 hover:border-slate-300 px-4 py-3 text-sm transition-all duration-200 focus:ring-2 outline-none',
                                   emailValidation && !emailValidation.valid
                                       ? 'border-red-300 focus:ring-red-400/40 focus:border-red-400'
                                       : emailValidation && emailValidation.valid
                                           ? 'border-emerald-300 focus:ring-emerald-400/40 focus:border-emerald-400'
                                           : 'border-slate-200 focus:ring-teal-400/40 focus:border-teal-400']"
                               placeholder="email@example.com" />
                        <!-- Validation icon -->
                        <div v-if="emailValidation" :class="['absolute top-1/2 -translate-y-1/2', isRtl ? 'left-3' : 'right-3']">
                            <svg v-if="emailValidation.valid" class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                            <svg v-else class="w-4 h-4 text-red-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/></svg>
                        </div>
                    </div>
                    <p v-if="emailValidation && !emailValidation.valid" class="mt-1 text-xs text-red-500">{{ isRtl ? emailValidation.ar : emailValidation.en }}</p>
                    <p v-else-if="form.errors.email" class="mt-1 text-xs text-red-500">{{ form.errors.email }}</p>
                </div>
            </div>
        </div>

        <!-- Section 2: Personal Info -->
        <div :class="['relative z-20 bg-white rounded-2xl shadow-sm border border-slate-100 p-6 mb-6 transition-all duration-700', mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-6']"
             :style="{ transitionTimingFunction: 'cubic-bezier(0.16, 1, 0.3, 1)', transitionDelay: '200ms' }">
            <div class="flex items-center gap-3 mb-5">
                <div class="w-10 h-10 bg-purple-100 rounded-xl flex items-center justify-center">
                    <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                </div>
                <h2 class="text-lg font-bold text-slate-800">{{ isRtl ? '\u0627\u0644\u0645\u0639\u0644\u0648\u0645\u0627\u062A \u0627\u0644\u0634\u062E\u0635\u064A\u0629' : 'Personal Information' }}</h2>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <!-- Gender -->
                <div>
                    <label class="block text-sm font-semibold text-slate-600 mb-1.5">{{ isRtl ? '\u0627\u0644\u062C\u0646\u0633' : 'Gender' }}</label>
                    <div class="flex gap-3">
                        <button type="button" @click="form.gender = 'male'"
                                :class="['flex-1 py-3 rounded-xl border text-sm font-medium transition-all duration-200', form.gender === 'male' ? 'bg-teal-50 border-teal-400 text-teal-700 ring-2 ring-teal-400/30' : 'border-slate-200 text-slate-500 hover:border-slate-300']">
                            {{ isRtl ? '\u0630\u0643\u0631' : 'Male' }}
                        </button>
                        <button type="button" @click="form.gender = 'female'"
                                :class="['flex-1 py-3 rounded-xl border text-sm font-medium transition-all duration-200', form.gender === 'female' ? 'bg-pink-50 border-pink-400 text-pink-700 ring-2 ring-pink-400/30' : 'border-slate-200 text-slate-500 hover:border-slate-300']">
                            {{ isRtl ? '\u0623\u0646\u062B\u0649' : 'Female' }}
                        </button>
                    </div>
                </div>
                <!-- Date of Birth -->
                <div>
                    <label class="block text-sm font-semibold text-slate-600 mb-1.5">{{ isRtl ? '\u062A\u0627\u0631\u064A\u062E \u0627\u0644\u0645\u064A\u0644\u0627\u062F' : 'Date of Birth' }}</label>
                    <input v-model="form.date_of_birth" type="date" dir="ltr"
                           class="w-full rounded-xl border border-slate-200 bg-slate-50/50 hover:border-slate-300 px-4 py-3 text-sm transition-all duration-200 focus:ring-2 focus:ring-teal-400/40 focus:border-teal-400 outline-none" />
                </div>
                <!-- Nationality (Searchable) -->
                <div ref="natRef" class="relative">
                    <label class="block text-sm font-semibold text-slate-600 mb-1.5">{{ isRtl ? 'الجنسية' : 'Nationality' }}</label>
                    <button type="button" @click="natOpen = !natOpen; if (natOpen) nextTick(() => { const el = natRef?.querySelector?.('input'); if(el) el.focus(); })"
                            :class="['w-full rounded-xl border bg-slate-50/50 hover:border-slate-300 px-4 py-3 text-sm transition-all duration-200 focus:ring-2 focus:ring-teal-400/40 focus:border-teal-400 outline-none flex items-center justify-between',
                                natOpen ? 'border-teal-400 ring-2 ring-teal-400/30 bg-white' : 'border-slate-200']">
                        <span :class="form.nationality ? 'text-slate-800' : 'text-slate-400'">
                            {{ form.nationality ? getNatLabel(form.nationality) : (isRtl ? 'اختر الجنسية' : 'Select Nationality') }}
                        </span>
                        <svg :class="['w-4 h-4 text-slate-400 transition-transform duration-200', natOpen ? 'rotate-180' : '']" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <!-- Dropdown -->
                    <Transition enter-active-class="transition duration-200 ease-out" enter-from-class="opacity-0 -translate-y-2 scale-95" enter-to-class="opacity-100 translate-y-0 scale-100"
                                leave-active-class="transition duration-150 ease-in" leave-from-class="opacity-100 translate-y-0 scale-100" leave-to-class="opacity-0 -translate-y-2 scale-95">
                        <div v-if="natOpen" class="absolute z-50 mt-1.5 w-full bg-white rounded-xl border border-slate-200 shadow-xl shadow-slate-200/50 overflow-hidden">
                            <div class="p-2 border-b border-slate-100">
                                <div class="relative">
                                    <svg class="w-4 h-4 text-slate-400 absolute top-1/2 -translate-y-1/2 pointer-events-none" :class="isRtl ? 'right-3' : 'left-3'" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path stroke-linecap="round" d="m21 21-4.35-4.35"/></svg>
                                    <input v-model="natSearch" type="text" :placeholder="isRtl ? 'ابحث عن الجنسية...' : 'Search nationality...'"
                                           class="w-full rounded-lg border border-slate-200 text-sm py-2.5 outline-none focus:border-teal-400 focus:ring-1 focus:ring-teal-400/30"
                                           :class="isRtl ? 'pr-9 pl-3' : 'pl-9 pr-3'" />
                                </div>
                            </div>
                            <div class="max-h-52 overflow-y-auto overscroll-contain">
                                <button v-for="n in filteredNationalities" :key="n.value" type="button"
                                        @click="selectNationality(n.value)"
                                        :class="['w-full text-start px-4 py-2.5 text-sm transition-colors duration-100 flex items-center justify-between',
                                            form.nationality === n.value ? 'bg-teal-50 text-teal-700 font-medium' : 'text-slate-600 hover:bg-slate-50']">
                                    <span>{{ isRtl ? n.ar : n.en }}</span>
                                    <svg v-if="form.nationality === n.value" class="w-4 h-4 text-teal-500 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                </button>
                                <div v-if="filteredNationalities.length === 0" class="px-4 py-6 text-center text-xs text-slate-400">
                                    {{ isRtl ? 'لا توجد نتائج' : 'No results found' }}
                                </div>
                            </div>
                        </div>
                    </Transition>
                </div>
                <!-- City / Governorate (Searchable, Dynamic) -->
                <div ref="cityRef" class="relative">
                    <label class="block text-sm font-semibold text-slate-600 mb-1.5">
                        {{ form.nationality === 'Egyptian' ? (isRtl ? 'المحافظة' : 'Governorate') : (isRtl ? 'المدينة' : 'City') }}
                    </label>
                    <button type="button" @click="cityOpen = !cityOpen; if (cityOpen) nextTick(() => { const el = cityRef?.querySelector?.('input'); if(el) el.focus(); })"
                            :class="['w-full rounded-xl border bg-slate-50/50 hover:border-slate-300 px-4 py-3 text-sm transition-all duration-200 focus:ring-2 focus:ring-teal-400/40 focus:border-teal-400 outline-none flex items-center justify-between',
                                cityOpen ? 'border-teal-400 ring-2 ring-teal-400/30 bg-white' : 'border-slate-200']">
                        <span :class="form.city ? 'text-slate-800' : 'text-slate-400'">
                            {{ form.city ? getCityLabel(form.city) : (form.nationality === 'Egyptian' ? (isRtl ? 'اختر المحافظة' : 'Select Governorate') : (isRtl ? 'اختر المدينة' : 'Select City')) }}
                        </span>
                        <svg :class="['w-4 h-4 text-slate-400 transition-transform duration-200', cityOpen ? 'rotate-180' : '']" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <!-- Hint: nationality-linked -->
                    <p v-if="form.nationality && citiesByNationality[form.nationality]" class="mt-1 text-[10px] text-teal-500 flex items-center gap-1">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        {{ isRtl ? ('معروض: ' + (form.nationality === 'Egyptian' ? 'محافظات مصر' : 'مدن ' + getNatLabel(form.nationality))) : ('Showing: ' + getNatLabel(form.nationality) + (form.nationality === 'Egyptian' ? ' governorates' : ' cities')) }}
                    </p>
                    <!-- Dropdown -->
                    <Transition enter-active-class="transition duration-200 ease-out" enter-from-class="opacity-0 -translate-y-2 scale-95" enter-to-class="opacity-100 translate-y-0 scale-100"
                                leave-active-class="transition duration-150 ease-in" leave-from-class="opacity-100 translate-y-0 scale-100" leave-to-class="opacity-0 -translate-y-2 scale-95">
                        <div v-if="cityOpen" class="absolute z-50 mt-1.5 w-full bg-white rounded-xl border border-slate-200 shadow-xl shadow-slate-200/50 overflow-hidden">
                            <div class="p-2 border-b border-slate-100">
                                <div class="relative">
                                    <svg class="w-4 h-4 text-slate-400 absolute top-1/2 -translate-y-1/2 pointer-events-none" :class="isRtl ? 'right-3' : 'left-3'" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path stroke-linecap="round" d="m21 21-4.35-4.35"/></svg>
                                    <input v-model="citySearch" type="text"
                                           :placeholder="form.nationality === 'Egyptian' ? (isRtl ? 'ابحث عن المحافظة...' : 'Search governorate...') : (isRtl ? 'ابحث عن المدينة...' : 'Search city...')"
                                           class="w-full rounded-lg border border-slate-200 text-sm py-2.5 outline-none focus:border-teal-400 focus:ring-1 focus:ring-teal-400/30"
                                           :class="isRtl ? 'pr-9 pl-3' : 'pl-9 pr-3'" />
                                </div>
                            </div>
                            <div class="max-h-52 overflow-y-auto overscroll-contain">
                                <button v-for="c in filteredCities" :key="c.value" type="button"
                                        @click="selectCity(c.value)"
                                        :class="['w-full text-start px-4 py-2.5 text-sm transition-colors duration-100 flex items-center justify-between',
                                            form.city === c.value ? 'bg-teal-50 text-teal-700 font-medium' : 'text-slate-600 hover:bg-slate-50']">
                                    <span>{{ isRtl ? c.ar : c.en }}</span>
                                    <svg v-if="form.city === c.value" class="w-4 h-4 text-teal-500 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                </button>
                                <div v-if="filteredCities.length === 0" class="px-4 py-6 text-center text-xs text-slate-400">
                                    {{ isRtl ? 'لا توجد نتائج' : 'No results found' }}
                                </div>
                            </div>
                        </div>
                    </Transition>
                </div>
            </div>
        </div>

        <!-- Section 3: Lead Details -->
        <div :class="['bg-white rounded-2xl shadow-sm border border-slate-100 p-6 mb-6 transition-all duration-700', mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-6']"
             :style="{ transitionTimingFunction: 'cubic-bezier(0.16, 1, 0.3, 1)', transitionDelay: '300ms' }">
            <div class="flex items-center gap-3 mb-5">
                <div class="w-10 h-10 bg-amber-100 rounded-xl flex items-center justify-center">
                    <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                </div>
                <h2 class="text-lg font-bold text-slate-800">{{ isRtl ? '\u062A\u0641\u0627\u0635\u064A\u0644 \u0627\u0644\u0639\u0645\u064A\u0644 \u0627\u0644\u0645\u062D\u062A\u0645\u0644' : 'Lead Details' }}</h2>
            </div>
            <!-- Source - Visual Grid -->
            <div class="mb-5">
                <label class="block text-sm font-semibold text-slate-600 mb-2.5">{{ isRtl ? 'مصدر العميل' : 'Lead Source' }}</label>
                <div v-if="sources && sources.length > 0" class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-6 gap-2">
                    <button v-for="s in sources" :key="s.id" type="button"
                            @click="form.lead_source_id = form.lead_source_id === s.id ? '' : s.id"
                            :class="['relative flex flex-col items-center gap-1.5 py-3 px-2 rounded-xl border-2 text-xs font-medium transition-all duration-200',
                                form.lead_source_id === s.id
                                    ? 'border-teal-400 bg-teal-50 text-teal-700 ring-2 ring-teal-400/30 shadow-sm scale-[1.03]'
                                    : 'border-slate-200 text-slate-500 hover:border-slate-300 hover:bg-slate-50']">
                        <div class="w-8 h-8 rounded-lg flex items-center justify-center transition-all duration-200"
                             :style="{ backgroundColor: form.lead_source_id === s.id ? s.color + '20' : '#f1f5f9' }">
                            <svg class="w-4 h-4" :style="{ color: form.lead_source_id === s.id ? s.color : '#94a3b8' }"
                                 fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" :d="sourceIcon(s.icon)" />
                            </svg>
                        </div>
                        <span class="text-[10px] leading-tight text-center">{{ isRtl ? s.name_ar : s.name_en }}</span>
                        <div v-if="form.lead_source_id === s.id" class="absolute -top-1.5 -right-1.5 w-5 h-5 bg-teal-500 rounded-full flex items-center justify-center shadow-sm">
                            <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                        </div>
                    </button>
                </div>
                <div v-else class="bg-amber-50 border border-amber-200 rounded-xl p-4 text-sm text-amber-700 flex items-center gap-2">
                    <svg class="w-5 h-5 text-amber-500 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>
                    {{ isRtl ? 'لا توجد مصادر عملاء. يرجى تشغيل LeadSourceSeeder.' : 'No lead sources available. Please run LeadSourceSeeder.' }}
                </div>
                <p v-if="form.errors.lead_source_id" class="mt-1.5 text-xs text-red-500">{{ form.errors.lead_source_id }}</p>
            </div>

            <!-- Campaign - Card Style -->
            <div class="mb-5">
                <label class="block text-sm font-semibold text-slate-600 mb-2.5">{{ isRtl ? 'الحملة' : 'Campaign' }}</label>
                <div v-if="campaigns && campaigns.length > 0" class="grid grid-cols-1 md:grid-cols-2 gap-2.5">
                    <button v-for="c in campaigns" :key="c.id" type="button"
                            @click="form.campaign_id = form.campaign_id === c.id ? '' : c.id"
                            :class="['relative flex items-center gap-3 py-3.5 px-4 rounded-xl border-2 text-sm transition-all duration-200 text-start',
                                form.campaign_id === c.id
                                    ? 'border-teal-400 bg-teal-50 text-teal-700 ring-2 ring-teal-400/30 shadow-sm'
                                    : 'border-slate-200 text-slate-600 hover:border-slate-300 hover:bg-slate-50']">
                        <div :class="['w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0 transition-all duration-200',
                                form.campaign_id === c.id ? 'bg-teal-500 text-white' : 'bg-slate-100 text-slate-400']">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/></svg>
                        </div>
                        <span class="font-medium leading-snug">{{ c.name }}</span>
                        <div v-if="form.campaign_id === c.id" class="absolute -top-1.5 -right-1.5 w-5 h-5 bg-teal-500 rounded-full flex items-center justify-center shadow-sm">
                            <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                        </div>
                    </button>
                </div>
                <div v-else class="bg-slate-50 border border-slate-200 rounded-xl p-4 text-sm text-slate-500 flex items-center gap-2">
                    <svg class="w-5 h-5 text-slate-400 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/></svg>
                    {{ isRtl ? 'لا توجد حملات نشطة حالياً' : 'No active campaigns available' }}
                </div>
                <p v-if="form.errors.campaign_id" class="mt-1.5 text-xs text-red-500">{{ form.errors.campaign_id }}</p>
            </div>

            <!-- Priority -->
            <div class="mb-5">
                <label class="block text-sm font-semibold text-slate-600 mb-2">{{ isRtl ? '\u0627\u0644\u0623\u0648\u0644\u0648\u064A\u0629' : 'Priority' }} <span class="text-red-500">*</span></label>
                <div class="flex gap-3">
                    <button v-for="p in priorityOptions" :key="p.value" type="button"
                            @click="form.priority = p.value"
                            :class="['flex-1 py-3 px-4 rounded-xl border-2 text-sm font-semibold transition-all duration-300', form.priority === p.value ? p.color + ' ring-2 shadow-sm scale-[1.02]' : 'border-slate-200 text-slate-400 hover:border-slate-300']">
                        <svg v-if="p.icon === 'hot'" class="w-5 h-5 mx-auto" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17.657 18.657A8 8 0 016.343 7.343S7 9 9 10c0-2 .5-5 2.986-7C14 5 16.09 5.777 17.656 7.343A7.975 7.975 0 0120 13a7.975 7.975 0 01-2.343 5.657z"/><path stroke-linecap="round" stroke-linejoin="round" d="M9.879 16.121A3 3 0 1012.015 11L11 14H9c0 .768.293 1.536.879 2.121z"/></svg>
                        <svg v-else-if="p.icon === 'warm'" class="w-5 h-5 mx-auto" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                        <svg v-else class="w-5 h-5 mx-auto" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
                        <span class="block mt-0.5">{{ isRtl ? p.label.ar : p.label.en }}</span>
                    </button>
                </div>
            </div>

            <!-- Interested Services -->
            <div v-if="services && services.length > 0">
                <div class="flex items-center justify-between mb-2">
                    <label class="text-sm font-semibold text-slate-600">{{ isRtl ? '\u0627\u0644\u062E\u062F\u0645\u0627\u062A \u0627\u0644\u0645\u0647\u062A\u0645 \u0628\u0647\u0627' : 'Interested Services' }}</label>
                    <span v-if="form.interested_services.length > 0" class="text-[10px] font-bold text-teal-600 bg-teal-50 px-2 py-0.5 rounded-full">
                        {{ form.interested_services.length }} {{ isRtl ? '\u0645\u062E\u062A\u0627\u0631\u0629' : 'selected' }}
                    </span>
                </div>
                <!-- Search filter (shows when 6+ services) -->
                <div v-if="services.length >= 6" class="relative mb-2.5">
                    <svg class="w-3.5 h-3.5 text-slate-400 absolute top-1/2 -translate-y-1/2 pointer-events-none" :class="isRtl ? 'right-3' : 'left-3'" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path stroke-linecap="round" d="m21 21-4.35-4.35"/></svg>
                    <input v-model="serviceSearch" type="text"
                        :placeholder="isRtl ? '\u0628\u062D\u062B \u0641\u064A \u0627\u0644\u062E\u062F\u0645\u0627\u062A...' : 'Search services...'"
                        class="w-full rounded-xl border border-slate-200 bg-slate-50/50 text-xs py-2.5 transition-all duration-200 focus:ring-2 focus:ring-teal-400/40 focus:border-teal-400 outline-none"
                        :class="isRtl ? 'pr-9 pl-3' : 'pl-9 pr-3'" />
                </div>
                <div class="grid grid-cols-2 md:grid-cols-3 gap-2" :class="services.length >= 12 ? 'max-h-48 overflow-y-auto rounded-xl' : ''">
                    <button v-for="svc in filteredServices" :key="svc.id" type="button"
                            @click="toggleService(svc.id)"
                            :class="['py-2.5 px-3 rounded-xl border text-xs font-medium transition-all duration-200', form.interested_services.includes(svc.id) ? 'bg-teal-50 border-teal-400 text-teal-700 ring-1 ring-teal-300' : 'border-slate-200 text-slate-500 hover:border-slate-300 hover:bg-slate-50']">
                        <svg v-if="form.interested_services.includes(svc.id)" class="inline-block w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                        {{ isRtl ? svc.name_ar : svc.name_en }}
                    </button>
                </div>
                <p v-if="serviceSearch && filteredServices.length === 0" class="text-xs text-slate-400 text-center py-3">
                    {{ isRtl ? '\u0644\u0627 \u062A\u0648\u062C\u062F \u0646\u062A\u0627\u0626\u062C' : 'No matching services' }}
                </p>
            </div>
        </div>

        <!-- Section 4: Notes -->
        <div :class="['bg-white rounded-2xl shadow-sm border border-slate-100 p-6 mb-6 transition-all duration-700', mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-6']"
             :style="{ transitionTimingFunction: 'cubic-bezier(0.16, 1, 0.3, 1)', transitionDelay: '400ms' }">
            <div class="flex items-center gap-3 mb-5">
                <div class="w-10 h-10 bg-sky-100 rounded-xl flex items-center justify-center">
                    <svg class="w-5 h-5 text-sky-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                </div>
                <h2 class="text-lg font-bold text-slate-800">{{ isRtl ? '\u0645\u0644\u0627\u062D\u0638\u0627\u062A' : 'Notes' }}</h2>
            </div>
            <textarea v-model="form.notes" rows="4"
                      class="w-full rounded-xl border border-slate-200 bg-slate-50/50 hover:border-slate-300 px-4 py-3 text-sm transition-all duration-200 focus:ring-2 focus:ring-teal-400/40 focus:border-teal-400 outline-none resize-none"
                      :placeholder="isRtl ? '\u0623\u0636\u0641 \u0645\u0644\u0627\u062D\u0638\u0627\u062A \u062D\u0648\u0644 \u0627\u0644\u0639\u0645\u064A\u0644 \u0627\u0644\u0645\u062D\u062A\u0645\u0644...' : 'Add notes about this lead...'"></textarea>
        </div>

        <!-- Action Buttons -->
        <div :class="['flex items-center justify-between bg-white rounded-2xl shadow-sm border border-slate-100 p-5 transition-all duration-700', mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-6']"
             :style="{ transitionTimingFunction: 'cubic-bezier(0.16, 1, 0.3, 1)', transitionDelay: '500ms' }">
            <Link href="/secretary/crm/leads"
                  class="px-6 py-3 rounded-xl border border-slate-200 text-slate-600 text-sm font-medium hover:bg-slate-50 transition-all duration-200">
                {{ isRtl ? '\u0625\u0644\u063A\u0627\u0621' : 'Cancel' }}
            </Link>
            <button type="submit" :disabled="form.processing"
                    :class="['px-8 py-3 rounded-xl text-white text-sm font-semibold shadow-lg transition-all duration-300 flex items-center gap-2', form.processing ? 'bg-teal-400 cursor-not-allowed' : 'bg-gradient-to-r from-teal-600 to-emerald-500 hover:shadow-xl hover:scale-[1.02] active:scale-[0.98]']">
                <svg v-if="form.processing" class="animate-spin h-4 w-4" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"/>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                </svg>
                <span v-if="!form.processing">{{ isEdit ? (isRtl ? '\u062D\u0641\u0638 \u0627\u0644\u062A\u0639\u062F\u064A\u0644\u0627\u062A' : 'Save Changes') : (isRtl ? '\u0625\u0646\u0634\u0627\u0621 \u0627\u0644\u0639\u0645\u064A\u0644' : 'Create Lead') }}</span>
                <span v-else>{{ isRtl ? '\u062C\u0627\u0631\u064A \u0627\u0644\u062D\u0641\u0638...' : 'Saving...' }}</span>
            </button>
        </div>

        <!-- Global Error -->
        <div v-if="Object.keys(form.errors).length > 0"
             class="mt-4 bg-red-50 border border-red-200 rounded-xl p-4 text-sm text-red-600">
            <p class="font-semibold mb-1">{{ isRtl ? '\u064A\u0631\u062C\u0649 \u062A\u0635\u062D\u064A\u062D \u0627\u0644\u0623\u062E\u0637\u0627\u0621 \u0627\u0644\u062A\u0627\u0644\u064A\u0629:' : 'Please fix the following errors:' }}</p>
            <ul class="list-disc list-inside space-y-1">
                <li v-for="(err, field) in form.errors" :key="field">{{ err }}</li>
            </ul>
        </div>
    </form>

</div>
</SecretaryLayout>
</template>
