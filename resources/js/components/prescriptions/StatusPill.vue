<template>
    <span :class="['inline-flex items-center gap-2 rounded-full px-3 py-1 text-[0.65rem] font-semibold uppercase', toneClass]">
        <span class="h-2 w-2 rounded-full" :class="dotClass"></span>
        {{ label }}
    </span>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    status: {
        type: String,
        default: 'pending',
    },
});

const map = {
    pending: { label: 'Aguardando', tone: 'bg-amber-500/10 text-amber-200', dot: 'bg-amber-300' },
    processing: { label: 'Processando', tone: 'bg-cyan-500/10 text-cyan-200', dot: 'bg-cyan-300 animate-pulse' },
    parsed: { label: 'Concluído', tone: 'bg-emerald-500/10 text-emerald-200', dot: 'bg-emerald-300' },
    text_extracted: { label: 'Texto extraído', tone: 'bg-blue-500/10 text-blue-200', dot: 'bg-blue-300' },
    manual_review: { label: 'Revisão manual', tone: 'bg-white/10 text-slate-200', dot: 'bg-white' },
    failed: { label: 'Falha', tone: 'bg-rose-500/10 text-rose-200', dot: 'bg-rose-300' },
};

const toneClass = computed(() => map[props.status]?.tone ?? 'bg-white/10 text-slate-200');
const dotClass = computed(() => map[props.status]?.dot ?? 'bg-white');
const label = computed(() => map[props.status]?.label ?? props.status);
</script>
