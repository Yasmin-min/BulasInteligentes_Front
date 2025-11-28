<template>
    <div class="flex flex-col items-center justify-center rounded-3xl border border-dashed border-white/10 bg-white/5 p-8 text-center text-sm text-slate-400">
        <component :is="iconComponent" class="mb-3 h-10 w-10 text-emerald-300" />
        <p class="text-base font-semibold text-white">{{ title }}</p>
        <p class="mt-2 max-w-md text-sm text-slate-400">
            <slot />
        </p>
        <div v-if="$slots.action" class="mt-4">
            <slot name="action" />
        </div>
    </div>
</template>

<script setup>
import * as icons from '@heroicons/vue/24/outline';
import { computed } from 'vue';

const props = defineProps({
    icon: {
        type: String,
        default: 'FaceSmileIcon',
    },
    title: {
        type: String,
        required: true,
    },
});

const iconComponent = computed(() => icons[props.icon] ?? icons.FaceSmileIcon);
</script>
