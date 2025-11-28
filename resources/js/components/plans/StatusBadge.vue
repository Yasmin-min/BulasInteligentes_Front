<template>
    <span :class="['inline-flex items-center gap-1 rounded-full px-3 py-1 text-[0.65rem] font-semibold uppercase', toneClass]">
        <component :is="iconComponent" class="h-3 w-3" />
        {{ label }}
    </span>
</template>

<script setup>
import { computed } from 'vue';
import { CheckIcon, ClockIcon, XMarkIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    status: {
        type: String,
        default: 'scheduled',
    },
});

const label = computed(() => {
    switch (props.status) {
        case 'taken':
            return 'Tomada';
        case 'skipped':
            return 'Pulada';
        case 'rescheduled':
            return 'Reagendada';
        default:
            return 'Agendada';
    }
});

const toneClass = computed(() => {
    switch (props.status) {
        case 'taken':
            return 'bg-emerald-500/10 text-emerald-200';
        case 'skipped':
            return 'bg-rose-500/10 text-rose-200';
        case 'rescheduled':
            return 'bg-amber-500/10 text-amber-200';
        default:
            return 'bg-white/5 text-slate-400';
    }
});

const iconComponent = computed(() => {
    switch (props.status) {
        case 'taken':
            return CheckIcon;
        case 'skipped':
            return XMarkIcon;
        default:
            return ClockIcon;
    }
});
</script>
