<script setup>
import { ref, computed } from 'vue';
import { Link, useForm, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const page = usePage();
const locale = computed(() => page.props.locale || 'ar');
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const props = defineProps({
    availablePermissions: Object,
});

const form = useForm({
    name: '',
    display_name_en: '',
    display_name_ar: '',
    permissions: [],
});

// Standard actions order
const actionLabels = {
    view: 'View',
    create: 'Create',
    update: 'Update',
    delete: 'Delete',
    export: 'Export',
    edit_services: 'Edit Services',
};

// Get all unique actions across all modules
const allActions = computed(() => {
    const actions = new Set();
    Object.values(props.availablePermissions).forEach(module => {
        module.actions.forEach(action => actions.add(action));
    });
    return Array.from(actions);
});

// Check if a specific permission is selected
function isChecked(module, action) {
    return form.permissions.includes(`${module}.${action}`);
}

// Toggle a specific permission
function togglePermission(module, action) {
    const perm = `${module}.${action}`;
    const index = form.permissions.indexOf(perm);
    if (index > -1) {
        form.permissions.splice(index, 1);
    } else {
        form.permissions.push(perm);
    }
}

// Toggle all permissions for a module (row)
function toggleModuleAll(module) {
    const moduleConfig = props.availablePermissions[module];
    const allChecked = moduleConfig.actions.every(action => form.permissions.includes(`${module}.${action}`));

    if (allChecked) {
        moduleConfig.actions.forEach(action => {
            const idx = form.permissions.indexOf(`${module}.${action}`);
            if (idx > -1) form.permissions.splice(idx, 1);
        });
    } else {
        moduleConfig.actions.forEach(action => {
            const perm = `${module}.${action}`;
            if (!form.permissions.includes(perm)) form.permissions.push(perm);
        });
    }
}

// Check if all actions for a module are selected
function isModuleAllChecked(module) {
    const moduleConfig = props.availablePermissions[module];
    return moduleConfig.actions.every(action => form.permissions.includes(`${module}.${action}`));
}

// Toggle all permissions for an action column
function toggleActionAll(action) {
    const modules = Object.keys(props.availablePermissions).filter(
        module => props.availablePermissions[module].actions.includes(action)
    );
    const allChecked = modules.every(module => form.permissions.includes(`${module}.${action}`));

    if (allChecked) {
        modules.forEach(module => {
            const idx = form.permissions.indexOf(`${module}.${action}`);
            if (idx > -1) form.permissions.splice(idx, 1);
        });
    } else {
        modules.forEach(module => {
            const perm = `${module}.${action}`;
            if (!form.permissions.includes(perm)) form.permissions.push(perm);
        });
    }
}

// Check if all modules for an action are selected
function isActionAllChecked(action) {
    const modules = Object.keys(props.availablePermissions).filter(
        module => props.availablePermissions[module].actions.includes(action)
    );
    return modules.every(module => form.permissions.includes(`${module}.${action}`));
}

// Select all / deselect all
function toggleAll() {
    const allPerms = [];
    Object.entries(props.availablePermissions).forEach(([module, config]) => {
        config.actions.forEach(action => allPerms.push(`${module}.${action}`));
    });

    if (form.permissions.length === allPerms.length) {
        form.permissions = [];
    } else {
        form.permissions = [...allPerms];
    }
}

const isAllChecked = computed(() => {
    let total = 0;
    Object.values(props.availablePermissions).forEach(config => {
        total += config.actions.length;
    });
    return form.permissions.length === total;
});

// Auto-generate slug from English name
function generateSlug() {
    form.name = form.display_name_en
        .toLowerCase()
        .replace(/[^a-z0-9\s]/g, '')
        .replace(/\s+/g, '_')
        .replace(/_+/g, '_')
        .replace(/^_|_$/g, '');
}

function submit() {
    form.post('/admin/roles');
}
</script>

<template>
    <AdminLayout :title="$t('a_add_role')">
        <div class="space-y-6">
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-bold text-gray-800">{{ $t('a_add_role') }}</h1>
                <Link href="/admin/roles" class="text-sm text-gray-500 hover:text-gray-700">{{ $t('a_back_to_roles') }}</Link>
            </div>

            <form @submit.prevent="submit">
                <!-- Basic Info -->
                <div class="bg-white rounded-lg shadow-sm p-6 space-y-5 mb-6">
                    <h2 class="text-lg font-semibold text-gray-700 border-b pb-3">{{ $t('a_role_information') }}</h2>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('a_display_name_en') }}</label>
                            <input
                                v-model="form.display_name_en"
                                @blur="generateSlug"
                                type="text"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-yellow-200 focus:border-transparent"
                                placeholder="e.g. Content Editor"
                            />
                            <p v-if="form.errors.display_name_en" class="mt-1 text-sm text-red-600">{{ form.errors.display_name_en }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('a_display_name_ar') }}</label>
                            <input
                                v-model="form.display_name_ar"
                                type="text"
                                dir="rtl"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-yellow-200 focus:border-transparent"
                                placeholder="مثال: محرر المحتوى"
                            />
                            <p v-if="form.errors.display_name_ar" class="mt-1 text-sm text-red-600">{{ form.errors.display_name_ar }}</p>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('a_slug') }}</label>
                        <input
                            v-model="form.name"
                            type="text"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm font-mono focus:ring-2 focus:ring-yellow-200 focus:border-transparent bg-gray-50"
                            placeholder="e.g. content_editor"
                        />
                        <p class="mt-1 text-xs text-gray-400">Lowercase letters, numbers, and underscores only. Auto-generated from English name.</p>
                        <p v-if="form.errors.name" class="mt-1 text-sm text-red-600">{{ form.errors.name }}</p>
                    </div>
                </div>

                <!-- Permission Matrix -->
                <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
                    <div class="flex items-center justify-between border-b pb-3 mb-4">
                        <h2 class="text-lg font-semibold text-gray-700">{{ $t('a_permissions') }}</h2>
                        <button
                            type="button"
                            @click="toggleAll"
                            class="text-sm font-medium px-3 py-1 rounded transition"
                            :class="isAllChecked ? 'bg-red-100 text-red-700 hover:bg-red-200' : 'bg-green-100 text-green-700 hover:bg-green-200'"
                        >
                            {{ isAllChecked ? $t('a_deselect_all') : $t('a_select_all') }}
                        </button>
                    </div>

                    <p v-if="form.errors.permissions" class="mb-3 text-sm text-red-600">{{ form.errors.permissions }}</p>

                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b">
                                    <th class="ltr:text-left rtl:text-right px-3 py-2 text-xs font-semibold text-gray-500 uppercase">{{ $t('a_module') }}</th>
                                    <th class="px-3 py-2 text-center text-xs font-semibold text-gray-500 uppercase">{{ $t('a_all') }}</th>
                                    <th
                                        v-for="action in allActions"
                                        :key="action"
                                        class="px-3 py-2 text-center"
                                    >
                                        <div class="flex flex-col items-center space-y-1">
                                            <span class="text-xs font-semibold text-gray-500 uppercase">{{ actionLabels[action] || action }}</span>
                                            <input
                                                type="checkbox"
                                                :checked="isActionAllChecked(action)"
                                                @change="toggleActionAll(action)"
                                                class="h-3.5 w-3.5 rounded border-gray-300 text-yellow-600 focus:ring-yellow-500"
                                            />
                                        </div>
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="(config, module) in availablePermissions"
                                    :key="module"
                                    class="border-b last:border-b-0 hover:bg-gray-50"
                                >
                                    <td class="px-3 py-3">
                                        <div class="font-medium text-gray-800">{{ config.label_en }}</div>
                                        <div class="text-xs text-gray-400" dir="rtl">{{ config.label_ar }}</div>
                                    </td>
                                    <td class="px-3 py-3 text-center">
                                        <input
                                            type="checkbox"
                                            :checked="isModuleAllChecked(module)"
                                            @change="toggleModuleAll(module)"
                                            class="h-4 w-4 rounded border-gray-300 text-yellow-600 focus:ring-yellow-500"
                                        />
                                    </td>
                                    <td
                                        v-for="action in allActions"
                                        :key="action"
                                        class="px-3 py-3 text-center"
                                    >
                                        <input
                                            v-if="config.actions.includes(action)"
                                            type="checkbox"
                                            :checked="isChecked(module, action)"
                                            @change="togglePermission(module, action)"
                                            class="h-4 w-4 rounded border-gray-300 text-yellow-600 focus:ring-yellow-500"
                                        />
                                        <span v-else class="text-gray-300">—</span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-3 text-xs text-gray-400">
                        Selected: {{ form.permissions.length }} permission{{ form.permissions.length !== 1 ? 's' : '' }}
                    </div>
                </div>

                <!-- Submit -->
                <div class="flex space-x-3 rtl:space-x-reverse">
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="py-2.5 px-6 rounded-lg text-white font-medium text-sm transition disabled:opacity-50"
                        style="background-color: #C4A265;"
                    >
                        {{ form.processing ? $t('a_creating') : $t('a_create_role') }}
                    </button>
                    <Link href="/admin/roles" class="px-4 py-2.5 rounded-lg bg-gray-200 text-gray-700 text-sm font-medium hover:bg-gray-300 transition">
                        {{ $t('a_cancel') }}
                    </Link>
                </div>
            </form>
        </div>
    </AdminLayout>
</template>
