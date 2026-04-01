<script setup>
import { ref, computed } from 'vue';
import UserAvatar from './UserAvatar.vue';

const props = defineProps({
    users: { type: Array, default: () => [] },
    show: { type: Boolean, default: false },
    accentColor: { type: String, default: '#C4A265' },
});

const emit = defineEmits(['close', 'select']);
const search = ref('');

const filteredUsers = computed(() => {
    if (!search.value.trim()) return props.users;
    const q = search.value.toLowerCase();
    return props.users.filter(u =>
        u.name.toLowerCase().includes(q) ||
        u.email.toLowerCase().includes(q) ||
        (u.role_display || '').toLowerCase().includes(q) ||
        (u.role || '').toLowerCase().includes(q)
    );
});

function selectUser(user) {
    emit('select', user);
    search.value = '';
}

function close() {
    emit('close');
    search.value = '';
}

// Role color
function getRoleColor(role) {
    const colors = {
        super_admin: '#C4A265',
        editor: '#6366f1',
        moderator: '#f59e0b',
        receptionist: '#ec4899',
        secretary: '#14b8a6',
        doctor: '#3b82f6',
        accountant: '#8b5cf6',
        webmaster: '#7C3AED',
    };
    return colors[role] || '#6b7280';
}
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition-opacity duration-200"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition-opacity duration-150"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center">
                <!-- Backdrop -->
                <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" @click="close"></div>

                <!-- Modal -->
                <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md mx-4 max-h-[80vh] flex flex-col overflow-hidden">
                    <!-- Header -->
                    <div class="p-5 border-b border-gray-100">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-bold text-gray-800">New Conversation</h3>
                            <button @click="close" class="w-8 h-8 rounded-lg flex items-center justify-center text-gray-400 hover:text-gray-600 hover:bg-gray-100 transition-colors">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                        <!-- Search -->
                        <div class="relative">
                            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                            <input
                                v-model="search"
                                type="text"
                                placeholder="Search by name, email, or role..."
                                class="w-full pl-9 pr-3 py-2.5 text-sm border border-gray-200 rounded-xl focus:outline-none focus:ring-1 focus:ring-gray-300 focus:border-gray-300 bg-gray-50 transition-all"
                                autofocus
                            />
                        </div>
                    </div>

                    <!-- User list -->
                    <div class="flex-1 overflow-y-auto">
                        <button
                            v-for="user in filteredUsers"
                            :key="user.id"
                            @click="selectUser(user)"
                            class="w-full flex items-center gap-3 px-5 py-3 hover:bg-gray-50 transition-colors text-left"
                        >
                            <UserAvatar
                                :name="user.name"
                                :is-online="user.is_online"
                                :accent-color="getRoleColor(user.role)"
                            />
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-semibold text-gray-800 truncate">{{ user.name }}</p>
                                <p class="text-[11px] text-gray-400 truncate">{{ user.email }}</p>
                            </div>
                            <span
                                class="text-[10px] font-medium px-2 py-0.5 rounded-full capitalize"
                                :style="{ backgroundColor: getRoleColor(user.role) + '15', color: getRoleColor(user.role) }"
                            >
                                {{ user.role_display || user.role }}
                            </span>
                        </button>

                        <div v-if="filteredUsers.length === 0" class="flex flex-col items-center justify-center py-12 px-4">
                            <p class="text-sm text-gray-500">No users found</p>
                        </div>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>
