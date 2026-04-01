<script setup>
import { computed } from 'vue';
import { Link, useForm, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const page = usePage();
const locale = computed(() => page.props.locale || 'ar');
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const props = defineProps({
    role: Object,
    availablePermissions: Object,
});

const form = useForm({
    display_name_en: props.role.display_name_en,
    display_name_ar: props.role.display_name_ar,
    permissions: [...props.role.permissions],
    _method: 'PUT',
});

const isSystem = props.role.is_system;

// Standard actions order
const actionLabels = {
    view: 'View',
    create: 'Create',
    update: 'Update',
    delete: 'Delete',
    export: 'Export',
    edit_services: 'Edit Services',
};

// Get all unique actions
const allActions = computed(() => {
    const actions = new Set();
    Object.values(props.availablePermissions).forEach(module => {
        module.actions.forEach(action => actions.add(action));
    });
    return Array.from(actions);
});

function isChecked(module, action) {
    if (isSystem) return true; // System roles have all perms
    return form.permissions.includes(`${module}.${action}`);
}

function togglePermission(module, action) {
    if (isSystem) return;
    const perm = `${module}.${action}`;
    const index = form.permissions.indexOf(perm);
    if (index > -1) {
        form.permissions.splice(index, 1);
    } else {
        form.permissions.push(perm);
    }
}

function toggleModuleAll(module) {
    if (isSystem) return;
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

function isModuleAllChecked(module) {
    if (isSystem) return true;
    const moduleConfig = props.availablePermissions[module];
    return moduleConfig.actions.every(action => form.permissions.includes(`${module}.${action}`));
}

function toggleActionAll(action) {
    if (isSystem) return;
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

function isActionAllChecked(action) {
    if (isSystem) return true;
    const modules = Object.keys(props.availablePermissions).filter(
        module => props.availablePermissions[module].actions.includes(action)
    );
    return modules.every(module => form.permissions.includes(`${module}.${action}`));
}

function toggleAll() {
    if (isSystem) return;
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
    if (isSystem) return true;
    let total = 0;
    Object.values(props.availablePermissions).forEach(config => {
        total += config.actions.length;
    });
    return form.permissions.length === total;
});

function submit() {
    form.post(`/admin/roles/${props.role.id}`);
}
</script>

<template>
    <AdminLayout :title="isSystem ? $t('a_view_role') : $t('a_edit_role')">
        <div class="space-y-6">
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-bold text-gray-800">{{ isSystem ? $t('a_view_role') : $t('a_edit_role') }}</h1>
                <Link href="/admin/roles" class="text-sm text-gray-500 hover:text-gray-700">{{ $t('a_back_to_roles') }}</Link>
            </div>

            <!-- System role notice -->
            <div v-if="isSystem" class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                <div class="flex items-center space-x-2 rtl:space-x-reverse">
                    <svg class="w-5 h-5 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z" />
                    </svg>
                    <span class="text-sm font-medium text-yellow-800">{{ $t('a_system_role_notice') }}</span>
                </div>
            </div>

            <form @submit.prevent="submit">
                <!-- Basic Info -->
                <div class="bg-white rounded-lg shadow-sm p-6 space-y-5 mb-6">
                    <h2 class="text-lg font-semibold text-gray-700 border-b pb-3">{{ $t('a_role_information') }}</h2>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('a_slug') }}</label>
                        <input
                            :value="role.name"
                            type="text"
                            disabled
                            class="w-full px-4 py-2 border border-gray-200 rounded-lg text-sm font-mono bg-gray-100 text-gray-500"
                        />
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('a_display_name_en') }}</label>
                            <input
                                v-model="form.display_name_en"
                                type="text"
                                :disabled="isSystem"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-yellow-200 focus:border-transparent"
                                :class="{ 'bg-gray-100 text-gray-500': isSystem }"
                            />
                            <p v-if="form.errors.display_name_en" class="mt-1 text-sm text-red-600">{{ form.errors.display_name_en }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('a_display_name_ar') }}</label>
                            <input
                                v-model="form.display_name_ar"
                                type="text"
                                dir="rtl"
                                :disabled="isSystem"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-yellow-200 focus:border-transparent"
                                :class="{ 'bg-gray-100 text-gray-500': isSystem }"
                            />
                            <p v-if="form.errors.display_name_ar" class="mt-1 text-sm text-red-600">{{ form.errors.display_name_ar }}</p>
                        </div>
                    </div>
                </div>

                <!-- Permission Matrix -->
                <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
                    <div class="flex items-center justify-between border-b pb-3 mb-4">
                        <h2 class="text-lg font-semibold text-gray-700">{{ $t('a_permissions') }}</h2>
                        <button
                            v-if="!isSystem"
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
                                                :disabled="isSystem"
                                                class="h-3.5 w-3.5 rounded border-gray-300 text-yellow-600 focus:ring-yellow-500"
                                                :class="{ 'opacity-60': isSystem }"
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
                                            :disabled="isSystem"
                                            class="h-4 w-4 rounded border-gray-300 text-yellow-600 focus:ring-yellow-500"
                                            :class="{ 'opacity-60': isSystem }"
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
                                            :disabled="isSystem"
                                            class="h-4 w-4 rounded border-gray-300 text-yellow-600 focus:ring-yellow-500"
                                            :class="{ 'opacity-60': isSystem }"
                                        />
                                        <span v-else class="text-gray-300">—</span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div v-if="!isSystem" class="mt-3 text-xs text-gray-400">
                        Selected: {{ form.permissions.length }} permission{{ form.permissions.length !== 1 ? 's' : '' }}
                    </div>
                </div>

                <!-- Submit -->
                <div v-if="!isSystem" class="flex space-x-3 rtl:space-x-reverse">
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="py-2.5 px-6 rounded-lg text-white font-medium text-sm transition disabled:opacity-50"
                        style="background-color: #C4A265;"
                    >
                        {{ form.processing ? $t('a_saving') : $t('a_update_role') }}
                    </button>
                    <Link href="/admin/roles" class="px-4 py-2.5 rounded-lg bg-gray-200 text-gray-700 text-sm font-medium hover:bg-gray-300 transition">
                        {{ $t('a_cancel') }}
                    </Link>
                </div>
            </form>
        </div>
    </AdminLayout>
</template>
