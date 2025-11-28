<template>
    <div class="space-y-6">
        <header class="flex flex-col gap-2 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <h1 class="text-2xl font-semibold text-white">Catálogo de medicamentos</h1>
                <p class="text-sm text-slate-400">
                    Gere insights sobre bulas armazenadas, realize curadoria manual e acompanhe medicamentos faltantes.
                </p>
            </div>
            <div class="flex gap-2">
                <button
                    type="button"
                    class="inline-flex items-center gap-2 rounded-xl border border-white/10 bg-white/5 px-4 py-2 text-xs text-slate-200 transition hover:border-emerald-400/40 hover:text-emerald-200"
                >
                    <ArrowDownTrayIcon class="h-4 w-4" />
                    Exportar catálogo
                </button>
                <button
                    type="button"
                    class="inline-flex items-center gap-2 rounded-xl bg-emerald-500 px-4 py-2 text-xs font-semibold text-emerald-900 transition hover:bg-emerald-400"
                >
                    <SparklesIcon class="h-4 w-4" />
                    Enviar à IA
                </button>
            </div>
        </header>

        <EmptyState icon="BeakerIcon" title="Integração pendente">
            Estruturei a interface para consumir os endpoints administrativos do catálogo assim que forem criados
            (lista de bulas, registros faltantes, aprovações). Atualmente você pode aproveitar a aba “Planos” e “Consulta”
            para validar o fluxo de dados.
        </EmptyState>
    </div>
</template>

<script setup>
import EmptyState from '@/components/feedback/EmptyState.vue';
import { ArrowDownTrayIcon, BeakerIcon, SparklesIcon } from '@heroicons/vue/24/outline';
</script>
