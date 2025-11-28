<template>
    <div class="flex min-h-screen items-center justify-center bg-slate-950 px-4 py-12 text-slate-100">
        <div class="w-full max-w-md rounded-3xl border border-white/10 bg-slate-900/70 p-8 shadow-lg shadow-emerald-500/10 backdrop-blur">
            <div class="mb-8 flex items-center justify-center gap-2 text-emerald-300">
                <span class="flex h-11 w-11 items-center justify-center rounded-2xl bg-emerald-500/20">
                    <BeakerIcon class="h-6 w-6" />
                </span>
                <div>
                    <p class="text-lg font-semibold">Bulas Inteligentes</p>
                    <p class="text-xs text-emerald-200/80">Autentique-se para continuar</p>
                </div>
            </div>
            <slot />
        </div>
    </div>
</template>

<script setup>
import { BeakerIcon } from '@heroicons/vue/24/outline';
</script>
