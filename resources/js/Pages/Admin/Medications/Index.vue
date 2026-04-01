<script setup>
import { ref, watch, computed } from 'vue';
import { Link, router, useForm, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { usePermissions } from '@/Composables/usePermissions.js';
import { useLocale } from '@/Composables/useLocale.js';

const { can } = usePermissions();
const { t } = useLocale();

const page = usePage();
const locale = computed(() => page.props.locale || 'ar');
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const props = defineProps({
    medications: Object,
    filters: Object,
});

const search = ref(props.filters?.search || '');
let searchTimeout = null;

watch(search, () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        router.get('/admin/medications', {
            search: search.value || undefined,
        }, {
            preserveState: true,
            replace: true,
        });
    }, 400);
});

const showModal = ref(false);
const editingMedication = ref(null);

const form = useForm({
    name: '',
    category: '',
    default_dosage: '',
    default_frequency: '',
    default_duration: '',
    is_active: true,
});

function openCreate() {
    editingMedication.value = null;
    form.reset();
    form.clearErrors();
    showModal.value = true;
}

function openEdit(medication) {
    editingMedication.value = medication;
    form.name = medication.name || '';
    form.category = medication.category || '';
    form.default_dosage = medication.default_dosage || '';
    form.default_frequency = medication.default_frequency || '';
    form.default_duration = medication.default_duration || '';
    form.is_active = medication.is_active ?? true;
    form.clearErrors();
    showModal.value = true;
}

function closeModal() {
    showModal.value = false;
    editingMedication.value = null;
    form.reset();
}

function submit() {
    if (editingMedication.value) {
        form.put(`/admin/medications/${editingMedication.value.id}`, {
            onSuccess: () => closeModal(),
        });
    } else {
        form.post('/admin/medications', {
            onSuccess: () => closeModal(),
        });
    }
}

function deleteMedication(id) {
    if (window.confirm(t('a_confirm_delete_medication'))) {
        router.delete(`/admin/medications/${id}`);
    }
}
</script>

<template>
    <AdminLayout :title="$t('a_medications')">
        <div class="space-y-6">
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-bold text-gray-800">{{ $t('a_medications') }}</h1>
                <button
                    v-if="can('medications.create')"
                    @click="openCreate"
                    class="inline-flex items-center px-4 py-2 rounded-lg text-white text-sm font-medium transition"
                    style="background-color: #C4A265;"
                >
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    {{ $t('a_add_medication_btn') }}
                </button>
            </div>

            <div class="bg-white rounded-lg shadow-sm p-4">
                <input
                    v-model="search"
                    type="text"
                    :placeholder="$t('a_search_medications')"
                    class="w-full sm:w-64 px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-yellow-200 focus:border-transparent"
                />
            </div>

            <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 ltr:text-left rtl:text-right text-xs font-medium text-gray-500 uppercase tracking-wider">{{ $t('a_name') }}</th>
                                <th class="px-6 py-3 ltr:text-left rtl:text-right text-xs font-medium text-gray-500 uppercase tracking-wider">{{ $t('a_category') }}</th>
                                <th class="px-6 py-3 ltr:text-left rtl:text-right text-xs font-medium text-gray-500 uppercase tracking-wider">{{ $t('a_default_dosage') }}</th>
                                <th class="px-6 py-3 ltr:text-left rtl:text-right text-xs font-medium text-gray-500 uppercase tracking-wider">{{ $t('a_default_frequency') }}</th>
                                <th class="px-6 py-3 ltr:text-left rtl:text-right text-xs font-medium text-gray-500 uppercase tracking-wider">{{ $t('a_default_duration') }}</th>
                                <th class="px-6 py-3 ltr:text-left rtl:text-right text-xs font-medium text-gray-500 uppercase tracking-wider">{{ $t('a_status') }}</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">{{ $t('a_actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-for="medication in medications.data" :key="medication.id" class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ medication.name }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ medication.category || '-' }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ medication.default_dosage || '-' }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ medication.default_frequency || '-' }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ medication.default_duration || '-' }}</td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span
                                        :class="medication.is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'"
                                        class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full"
                                    >
                                        {{ medication.is_active ? $t('a_active') : $t('a_inactive') }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm space-x-3 rtl:space-x-reverse">
                                    <button v-if="can('medications.update')" @click="openEdit(medication)" class="font-medium text-blue-600 hover:underline">{{ $t('a_edit') }}</button>
                                    <button v-if="can('medications.delete')" @click="deleteMedication(medication.id)" class="font-medium text-red-600 hover:underline">{{ $t('a_delete') }}</button>
                                </td>
                            </tr>
                            <tr v-if="!medications.data || medications.data.length === 0">
                                <td colspan="7" class="px-6 py-8 text-center text-sm text-gray-500">{{ $t('a_no_medications_found') }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="medications.links && medications.links.length > 3" class="px-6 py-3 border-t border-gray-200 flex items-center justify-between">
                    <p class="text-sm text-gray-500">{{ $t('a_showing') }} {{ medications.from }} {{ $t('a_to') }} {{ medications.to }} {{ $t('a_of') }} {{ medications.total }} {{ $t('a_results') }}</p>
                    <nav class="flex space-x-1 rtl:space-x-reverse">
                        <template v-for="link in medications.links" :key="link.label">
                            <Link
                                v-if="link.url"
                                :href="link.url"
                                v-html="link.label"
                                class="px-3 py-1 text-sm rounded border transition"
                                :class="link.active ? 'text-white border-transparent' : 'text-gray-600 border-gray-300 hover:bg-gray-50'"
                                :style="link.active ? 'background-color: #C4A265;' : ''"
                                preserve-state
                            />
                            <span v-else v-html="link.label" class="px-3 py-1 text-sm text-gray-400" />
                        </template>
                    </nav>
                </div>
            </div>
        </div>

        <!-- Modal -->
        <Teleport to="body">
            <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center">
                <div class="fixed inset-0 bg-black bg-opacity-50" @click="closeModal"></div>
                <div class="bg-white rounded-lg shadow-xl z-10 w-full max-w-lg mx-4 p-6 space-y-4">
                    <h2 class="text-lg font-semibold text-gray-700 border-b pb-2">
                        {{ editingMedication ? $t('a_edit_medication') : $t('a_add_medication_btn') }}
                    </h2>
                    <form @submit.prevent="submit" class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('a_name') }} <span class="text-red-500">*</span></label>
                            <input v-model="form.name" type="text" class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-yellow-200 focus:border-transparent" />
                            <p v-if="form.errors.name" class="mt-1 text-sm text-red-600">{{ form.errors.name }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('a_category') }}</label>
                            <input v-model="form.category" type="text" class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-yellow-200 focus:border-transparent" />
                            <p v-if="form.errors.category" class="mt-1 text-sm text-red-600">{{ form.errors.category }}</p>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('a_default_dosage') }}</label>
                                <input v-model="form.default_dosage" type="text" class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-yellow-200 focus:border-transparent" />
                                <p v-if="form.errors.default_dosage" class="mt-1 text-sm text-red-600">{{ form.errors.default_dosage }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('a_default_frequency') }}</label>
                                <input v-model="form.default_frequency" type="text" class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-yellow-200 focus:border-transparent" />
                                <p v-if="form.errors.default_frequency" class="mt-1 text-sm text-red-600">{{ form.errors.default_frequency }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('a_default_duration') }}</label>
                                <input v-model="form.default_duration" type="text" class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-yellow-200 focus:border-transparent" />
                                <p v-if="form.errors.default_duration" class="mt-1 text-sm text-red-600">{{ form.errors.default_duration }}</p>
                            </div>
                        </div>
                        <div class="flex items-center space-x-2 rtl:space-x-reverse">
                            <input
                                id="is_active"
                                v-model="form.is_active"
                                type="checkbox"
                                class="rounded border-gray-300 text-yellow-600 focus:ring-yellow-200"
                            />
                            <label for="is_active" class="text-sm font-medium text-gray-700">{{ $t('a_active') }}</label>
                            <p v-if="form.errors.is_active" class="mt-1 text-sm text-red-600">{{ form.errors.is_active }}</p>
                        </div>
                        <div class="flex justify-end space-x-3 pt-2 rtl:space-x-reverse">
                            <button type="button" @click="closeModal" class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition">{{ $t('a_cancel') }}</button>
                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="px-4 py-2 rounded-lg text-white text-sm font-medium transition disabled:opacity-50"
                                style="background-color: #C4A265;"
                            >
                                {{ form.processing ? $t('a_saving') : (editingMedication ? $t('a_update') : $t('a_create')) }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>
    </AdminLayout>
</template>
