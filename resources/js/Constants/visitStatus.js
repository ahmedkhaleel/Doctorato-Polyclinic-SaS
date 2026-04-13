/**
 * Shared Visit Status Configuration
 * Used across Queue, Visits, and other doctor portal pages
 */

export const STATUS_KEYS = ['waiting', 'in_progress', 'completed', 'cancelled'];

/**
 * Get full status config with all styling tokens
 * @param {boolean} isRtl
 * @returns {Object}
 */
export function getStatusConfig(isRtl = false) {
    return {
        waiting: {
            key: 'waiting',
            label: isRtl ? 'انتظار' : 'Waiting',
            labelFull: isRtl ? 'في الانتظار' : 'Waiting',
            bg: 'bg-amber-50',
            text: 'text-amber-700',
            border: 'border-amber-200',
            dot: 'bg-amber-400',
            stripe: 'border-amber-400',
            iconBg: 'bg-amber-100',
            gradient: 'from-amber-500 to-amber-600',
            hex: '#f59e0b',
        },
        in_progress: {
            key: 'in_progress',
            label: isRtl ? 'جاري' : 'In Progress',
            labelFull: isRtl ? 'قيد التنفيذ' : 'In Progress',
            bg: 'bg-blue-50',
            text: 'text-blue-700',
            border: 'border-blue-200',
            dot: 'bg-blue-500',
            stripe: 'border-blue-500',
            iconBg: 'bg-blue-100',
            gradient: 'from-blue-500 to-blue-600',
            hex: '#3b82f6',
        },
        completed: {
            key: 'completed',
            label: isRtl ? 'مكتمل' : 'Completed',
            labelFull: isRtl ? 'مكتمل' : 'Completed',
            bg: 'bg-emerald-50',
            text: 'text-emerald-700',
            border: 'border-emerald-200',
            dot: 'bg-emerald-500',
            stripe: 'border-emerald-500',
            iconBg: 'bg-emerald-100',
            gradient: 'from-emerald-500 to-emerald-600',
            hex: '#10b981',
        },
        cancelled: {
            key: 'cancelled',
            label: isRtl ? 'ملغي' : 'Cancelled',
            labelFull: isRtl ? 'ملغي' : 'Cancelled',
            bg: 'bg-gray-50',
            text: 'text-gray-500',
            border: 'border-gray-200',
            dot: 'bg-gray-400',
            stripe: 'border-gray-300',
            iconBg: 'bg-gray-100',
            gradient: 'from-gray-400 to-gray-500',
            hex: '#6b7280',
        },
    };
}

/**
 * Build SearchableSelect-compatible options array for status filter
 * @param {boolean} isRtl
 * @param {Object} opts - { includeAll: true, allLabel: '' }
 * @returns {Array}
 */
export function getStatusSelectOptions(isRtl = false, opts = {}) {
    const cfg = getStatusConfig(isRtl);
    const options = [];
    if (opts.includeAll !== false) {
        options.push({ value: '', label: opts.allLabel || (isRtl ? 'كل الحالات' : 'All Statuses') });
    }
    STATUS_KEYS.forEach(key => {
        options.push({ value: key, label: cfg[key].label, color: cfg[key].hex });
    });
    return options;
}
