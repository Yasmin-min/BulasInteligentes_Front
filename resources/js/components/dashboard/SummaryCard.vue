<template>
    <div
        class="relative rounded-3xl border border-white/5 bg-white/5 p-5 backdrop-blur transition hover:border-emerald-400/40"
    >
        <div class="absolute -top-5 right-4">
            <div :class="['rounded-2xl p-3', toneClass]">
                <slot name="icon" />
            </div>
        </div>
        <p class="text-xs uppercase tracking-widest text-slate-400">
            {{ title }}
            <span v-if="tooltip" class="ml-1 text-[0.65rem] text-slate-500">({{ tooltip }})</span>
        </p>
        <p class="mt-4 text-3xl font-semibold text-white">{{ value }}</p>
        <div v-if="$slots.default" class="mt-3 text-xs text-slate-400">
            <slot />
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    title: {
        type: String,
        required: true,
    },
    value: {
        type: [String, Number],
        default: '—',
    },
    tooltip: {
        type: String,
        default: null,
    },
    tone: {
        type: String,
        default: 'emerald',
    },
});

const toneClass = computed(() => {
    switch (props.tone) {
        case 'cyan':
            return 'bg-cyan-500/15 text-cyan-300';
        case 'amber':
            return 'bg-amber-500/15 text-amber-300';
        case 'rose':
            return 'bg-rose-500/15 text-rose-300';
        default:
            return 'bg-emerald-500/15 text-emerald-300';
    }
});
</script>
