<script setup>
import { computed } from 'vue';

const props = defineProps({
    status: { type: String, required: true },
    type: { type: String, default: 'default' },
});

const colorMaps = {
    visit: {
        waiting: { classes: 'bg-yellow-100 text-amber-800', label: 'Waiting' },
        in_progress: { classes: 'bg-slate-100 text-[#1B365D]', label: 'In Progress' },
        completed: { classes: 'bg-emerald-100 text-emerald-800', label: 'Completed' },
        cancelled: { classes: 'bg-red-100 text-red-800', label: 'Cancelled' },
    },
    invoice: {
        paid: { classes: 'bg-emerald-100 text-emerald-800', label: 'Paid' },
        partial: { classes: 'bg-yellow-100 text-amber-800', label: 'Partial' },
        unpaid: { classes: 'bg-red-100 text-red-800', label: 'Unpaid' },
        cancelled: { classes: 'bg-gray-100 text-gray-800', label: 'Cancelled' },
    },
    package: {
        active: { classes: 'bg-emerald-100 text-emerald-800', label: 'Active' },
        completed: { classes: 'bg-slate-100 text-[#1B365D]', label: 'Completed' },
        cancelled: { classes: 'bg-red-100 text-red-800', label: 'Cancelled' },
    },
    leave: {
        pending: { classes: 'bg-yellow-100 text-amber-800', label: 'Pending' },
        approved: { classes: 'bg-emerald-100 text-emerald-800', label: 'Approved' },
        rejected: { classes: 'bg-red-100 text-red-800', label: 'Rejected' },
    },
    booking: {
        new: { classes: 'bg-slate-100 text-[#1B365D]', label: 'New' },
        contacted: { classes: 'bg-yellow-100 text-amber-800', label: 'Contacted' },
        confirmed: { classes: 'bg-emerald-100 text-emerald-800', label: 'Confirmed' },
        completed: { classes: 'bg-gray-100 text-gray-800', label: 'Completed' },
        cancelled: { classes: 'bg-red-100 text-red-800', label: 'Cancelled' },
    },
};

const defaultMap = {
    active: 'bg-emerald-100 text-emerald-800',
    inactive: 'bg-gray-100 text-gray-800',
    waiting: 'bg-yellow-100 text-amber-800',
    in_progress: 'bg-slate-100 text-[#1B365D]',
    completed: 'bg-emerald-100 text-emerald-800',
    cancelled: 'bg-red-100 text-red-800',
    paid: 'bg-emerald-100 text-emerald-800',
    partial: 'bg-yellow-100 text-amber-800',
    unpaid: 'bg-red-100 text-red-800',
    pending: 'bg-yellow-100 text-amber-800',
    approved: 'bg-emerald-100 text-emerald-800',
    rejected: 'bg-red-100 text-red-800',
    new: 'bg-slate-100 text-[#1B365D]',
    contacted: 'bg-yellow-100 text-amber-800',
    confirmed: 'bg-emerald-100 text-emerald-800',
    draft: 'bg-gray-100 text-gray-800',
    published: 'bg-emerald-100 text-emerald-800',
    scheduled: 'bg-slate-100 text-[#1B365D]',
    expired: 'bg-red-100 text-red-800',
    hidden: 'bg-gray-100 text-gray-800',
};

const config = computed(() => {
    const typeMap = colorMaps[props.type];
    if (typeMap && typeMap[props.status]) {
        return typeMap[props.status];
    }
    return {
        classes: defaultMap[props.status] || 'bg-gray-100 text-gray-800',
        label: null,
    };
});

const displayLabel = computed(() => {
    if (config.value.label) return config.value.label;
    return props.status.replace(/_/g, ' ');
});
</script>

<template>
    <span
        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold capitalize"
        :class="config.classes"
    >
        {{ displayLabel }}
    </span>
</template>
