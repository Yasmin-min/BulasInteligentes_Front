<template>
    <div class="flex items-center justify-between">
        <div>
            <p class="text-xs uppercase tracking-[0.3em] text-emerald-400">{{ title }}</p>
            <p v-if="subtitle" class="text-sm text-slate-400">{{ subtitle }}</p>
        </div>
        <slot name="action" />
    </div>
</template>

<script setup>
defineProps({
    title: {
        type: String,
        required: true,
    },
    subtitle: {
        type: String,
        default: null,
    },
});
</script>
