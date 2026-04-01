<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    columns: {
        type: Array,
        required: true,
        // Each column: { key: String, label: String, sortable: Boolean }
    },
    rows: {
        type: Array,
        required: true,
    },
    pagination: {
        type: Object,
        default: null,
    },
});

defineEmits(['delete']);
</script>

<template>
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th
                            v-for="col in columns"
                            :key="col.key"
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                        >
                            {{ col.label }}
                        </th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Actions
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <tr v-for="(row, index) in rows" :key="row.id || index" class="hover:bg-gray-50">
                        <td v-for="col in columns" :key="col.key" class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                            <slot :name="`cell-${col.key}`" :row="row" :value="row[col.key]">
                                {{ row[col.key] }}
                            </slot>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <slot name="actions" :row="row" />
                        </td>
                    </tr>
                    <tr v-if="rows.length === 0">
                        <td :colspan="columns.length + 1" class="px-6 py-8 text-center text-gray-500">
                            No records found.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div v-if="pagination && pagination.links && pagination.links.length > 3" class="bg-white px-4 py-3 flex items-center justify-between border-t border-gray-200 sm:px-6">
            <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                <div>
                    <p class="text-sm text-gray-700">
                        Showing <span class="font-medium">{{ pagination.from }}</span>
                        to <span class="font-medium">{{ pagination.to }}</span>
                        of <span class="font-medium">{{ pagination.total }}</span> results
                    </p>
                </div>
                <div>
                    <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px">
                        <Link
                            v-for="(link, i) in pagination.links"
                            :key="i"
                            :href="link.url || '#'"
                            v-html="link.label"
                            class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50"
                            :class="{
                                'bg-gold-primary text-white border-gold-primary': link.active,
                                'opacity-50 cursor-not-allowed': !link.url,
                            }"
                            preserve-scroll
                        />
                    </nav>
                </div>
            </div>
        </div>
    </div>
</template>
