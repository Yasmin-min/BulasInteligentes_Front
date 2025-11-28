<template>
    <section :class="['space-y-2 rounded-2xl border border-white/5 bg-white/5 p-4 text-sm text-slate-300', $attrs.class]">
        <p class="text-xs uppercase tracking-widest text-emerald-400">{{ title }}</p>
        <div class="text-sm leading-relaxed text-slate-200">
            <slot />
        </div>
    </section>
</template>

<script setup>
defineProps({
    title: {
        type: String,
        required: true,
    },
});
</script>
