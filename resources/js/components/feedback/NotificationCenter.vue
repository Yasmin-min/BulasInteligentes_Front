<template>
    <div
        class="pointer-events-none fixed inset-x-0 top-0 z-[200] flex flex-col items-center gap-2 p-4 sm:items-end sm:p-6"
        aria-live="polite"
        aria-atomic="true"
    >
        <TransitionGroup name="toast" tag="div" class="flex w-full flex-col items-center gap-2 sm:w-auto sm:items-end">
            <article
                v-for="notice in notices"
                :key="notice.id"
                class="pointer-events-auto w-full max-w-sm rounded-xl border border-white/10 bg-slate-900/90 p-4 shadow-lg shadow-emerald-500/10 backdrop-blur"
                :class="toneClass(notice.tone)"
            >
                <div class="flex items-start gap-3">
                    <div class="mt-1">
                        <component :is="iconFor(notice.tone)" class="h-5 w-5" />
                    </div>
                    <div class="flex-1">
                        <p class="text-sm font-semibold text-white">{{ notice.title }}</p>
                        <p class="mt-1 text-sm text-slate-200/80">{{ notice.message }}</p>
                    </div>
                    <button
                        type="button"
                        class="mt-1 inline-flex h-6 w-6 items-center justify-center rounded-full bg-white/10 text-slate-200 transition hover:bg-white/20"
                        @click="dismiss(notice.id)"
                        aria-label="Fechar notificação"
                    >
                        <XMarkIcon class="h-4 w-4" />
                    </button>
                </div>
            </article>
        </TransitionGroup>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import { useUiStore } from '@/stores/ui';
import {
    CheckCircleIcon,
    ExclamationTriangleIcon,
    InformationCircleIcon,
    XCircleIcon,
    XMarkIcon,
} from '@heroicons/vue/24/outline';

const ui = useUiStore();
const notices = computed(() => ui.notifications);

function dismiss(id) {
    ui.dismiss(id);
}

function toneClass(tone) {
    switch (tone) {
        case 'success':
            return 'border-emerald-500/30';
        case 'warning':
            return 'border-amber-500/30';
        case 'error':
            return 'border-rose-500/30';
        default:
            return 'border-slate-500/20';
    }
}

function iconFor(tone) {
    switch (tone) {
        case 'success':
            return CheckCircleIcon;
        case 'warning':
            return ExclamationTriangleIcon;
        case 'error':
            return XCircleIcon;
        default:
            return InformationCircleIcon;
    }
}
</script>

<style scoped>
.toast-move,
.toast-enter-active,
.toast-leave-active {
    transition: all 0.25s ease;
}
.toast-enter-from,
.toast-leave-to {
    opacity: 0;
    transform: translateY(-10px) scale(0.98);
}
.toast-leave-active {
    position: absolute;
}
</style>
