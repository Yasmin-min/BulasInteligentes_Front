<template>
    <div class="rounded-3xl border border-white/5 bg-white/5 p-5 backdrop-blur transition hover:border-emerald-400/40">
        <div class="flex items-center justify-between text-xs text-slate-400">
            <p class="uppercase tracking-[0.3em]">{{ label }}</p>
            <component :is="iconComponent" class="h-5 w-5" :class="toneClass" />
        </div>
        <p class="mt-4 text-3xl font-semibold text-white">{{ value }}</p>
        <p v-if="$slots.default" class="mt-2 text-xs text-slate-400">
            <slot />
        </p>
    </div>
</template>

<script setup>
import * as icons from '@heroicons/vue/24/outline';
import { computed } from 'vue';

const props = defineProps({
    label: {
        type: String,
        required: true,
    },
    value: {
        type: [String, Number],
        default: '—',
    },
    icon: {
        type: String,
        default: 'ChartBarIcon',
    },
    tone: {
        type: String,
        default: 'primary',
    },
});

const palette = {
    primary: 'text-emerald-300',
    warning: 'text-amber-300',
    danger: 'text-rose-300',
    neutral: 'text-slate-300',
};

const iconComponent = computed(() => icons[props.icon] ?? icons.ChartBarIcon);
const toneClass = computed(() => palette[props.tone] ?? palette.primary);
</script>
