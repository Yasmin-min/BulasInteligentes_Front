<template>
    <div class="flex min-h-screen flex-col items-center justify-center bg-slate-950 text-center text-slate-200">
        <SparklesIcon class="mb-6 h-12 w-12 text-emerald-300" />
        <h1 class="text-3xl font-semibold text-white">Página não encontrada</h1>
        <p class="mt-2 max-w-md text-sm text-slate-400">
            Verifique a URL digitada ou retorne ao painel para continuar utilizando o Bulas Inteligentes.
        </p>
        <RouterLink
            to="/"
            class="mt-6 inline-flex items-center gap-2 rounded-xl bg-emerald-500 px-4 py-2 text-sm font-semibold text-emerald-900 transition hover:bg-emerald-400"
        >
            <ArrowLeftIcon class="h-4 w-4" />
            Voltar ao painel
        </RouterLink>
    </div>
</template>

<script setup>
import { RouterLink } from 'vue-router';
import { ArrowLeftIcon, SparklesIcon } from '@heroicons/vue/24/outline';
</script>
