<template>
    <div class="mx-auto max-w-5xl space-y-8">
        <header class="rounded-3xl border border-white/5 bg-white/5 p-6 backdrop-blur">
            <h1 class="text-xl font-semibold text-white">Consulta inteligente de medicamentos</h1>
            <p class="mt-1 text-sm text-slate-400">
                Pesquise por um fármaco ou princípio ativo para obter dados estruturados da bula.
            </p>
            <form class="mt-6 flex flex-col gap-3 sm:flex-row" @submit.prevent="handleSearch">
                <div class="relative flex-1">
                    <MagnifyingGlassIcon
                        class="pointer-events-none absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-500"
                    />
                    <input
                        v-model="query"
                        type="search"
                        placeholder="Exemplo: dipirona, amoxicilina..."
                        class="h-12 w-full rounded-xl border border-white/10 bg-white/5 pl-10 pr-4 text-sm text-white placeholder:text-slate-500 focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-400/40"
                    />
                </div>
                <button
                    type="submit"
                    class="inline-flex items-center justify-center gap-2 rounded-xl bg-emerald-500 px-4 py-3 text-sm font-semibold text-emerald-900 transition hover:bg-emerald-400 disabled:cursor-not-allowed disabled:opacity-70"
                    :disabled="loading"
                >
                    <SparklesIcon class="h-4 w-4" />
                    Consultar
                </button>
                <label class="flex items-center gap-2 text-xs text-slate-400">
                    <input v-model="forceRefresh" type="checkbox" class="h-4 w-4 rounded border-white/20 bg-white/10" />
                    Forçar atualização
                </label>
            </form>
        </header>

        <div v-if="loading" class="space-y-4">
            <SkeletonCard />
            <SkeletonCard />
        </div>

        <template v-else>
            <article v-if="medication" class="space-y-6 rounded-3xl border border-white/5 bg-white/5 p-6 backdrop-blur">
                <header class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <p class="text-xs uppercase tracking-[0.3em] text-emerald-400">Resultados</p>
                        <h2 class="text-2xl font-semibold text-white">{{ medication.name }}</h2>
                        <p class="text-xs text-slate-400">
                            Última atualização em {{ formatDate(medication.fetched_at) }}
                            <span v-if="fromCache" class="ml-2 rounded-full bg-white/5 px-2 py-0.5 text-[0.65rem] uppercase text-slate-500">
                                Cache
                            </span>
                        </p>
                    </div>
                    <button
                        type="button"
                        class="inline-flex items-center gap-2 rounded-xl border border-white/10 bg-white/5 px-4 py-2 text-xs font-semibold text-slate-200 transition hover:border-emerald-400/40 hover:text-emerald-200"
                        @click="exportJson"
                    >
                        <ArrowDownTrayIcon class="h-4 w-4" />
                        Exportar JSON
                    </button>
                </header>

                <div class="grid gap-6 md:grid-cols-2">
                    <InfoBlock title="Resumo humanizado">
                        {{ medication.human_summary ?? 'Não informado.' }}
                    </InfoBlock>
                    <InfoBlock title="Posologia">
                        {{ medication.posology ?? 'Não informado.' }}
                    </InfoBlock>
                    <InfoBlock title="Indicações">
                        {{ medication.indications ?? 'Não informado.' }}
                    </InfoBlock>
                    <InfoBlock title="Contraindicações">
                        {{ medication.contraindications ?? 'Não informado.' }}
                    </InfoBlock>
                    <InfoBlock title="Interações e alertas" class="md:col-span-2">
                        {{ medication.interaction_alerts ?? 'Não informado.' }}
                    </InfoBlock>
                </div>

                <section>
                    <h3 class="text-sm font-semibold text-white">Composição e meia-vida</h3>
                    <div v-if="medication.composition?.length" class="mt-3 overflow-hidden rounded-2xl border border-white/5">
                        <table class="min-w-full divide-y divide-white/5 text-sm">
                            <thead class="bg-white/5 text-left text-xs uppercase tracking-widest text-slate-400">
                                <tr>
                                    <th class="px-4 py-3">Componente</th>
                                    <th class="px-4 py-3">Forma</th>
                                    <th class="px-4 py-3">Força</th>
                                    <th class="px-4 py-3">Meia-vida</th>
                                    <th class="px-4 py-3">Observações</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-white/5 text-slate-200">
                                <tr v-for="item in medication.composition" :key="item.component" class="bg-white/[0.02]">
                                    <td class="px-4 py-3 font-semibold">{{ item.component }}</td>
                                    <td class="px-4 py-3 text-slate-400">{{ item.dosage_form ?? '—' }}</td>
                                    <td class="px-4 py-3 text-slate-400">{{ item.strength ?? '—' }}</td>
                                    <td class="px-4 py-3 text-slate-400">{{ item.half_life_hours ?? '—' }}</td>
                                    <td class="px-4 py-3 text-slate-400">{{ item.notes ?? '—' }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <EmptyState v-else icon="BeakerIcon" title="Sem composição estruturada">
                        Ainda não há informações detalhadas da composição para este medicamento.
                    </EmptyState>
                </section>

                <div class="rounded-2xl border border-emerald-500/40 bg-emerald-500/10 p-4 text-xs text-emerald-200">
                    <p class="font-semibold">Aviso importante</p>
                    <p class="mt-1">
                        {{ medication.disclaimer ?? defaultDisclaimer }}
                    </p>
                </div>
            </article>

            <EmptyState v-else icon="MagnifyingGlassIcon" title="Pesquise por um medicamento">
                Digite o nome comercial ou princípio ativo para localizar a bula interpretada pela IA.
            </EmptyState>
        </template>
    </div>
</template>

<script setup>
import {
    ArrowDownTrayIcon,
    BeakerIcon,
    MagnifyingGlassIcon,
    SparklesIcon,
} from '@heroicons/vue/24/outline';
import { onMounted, ref } from 'vue';
import { useRoute } from 'vue-router';
import { apiClient } from '@/bootstrap';
import EmptyState from '@/components/feedback/EmptyState.vue';
import SkeletonCard from '@/components/feedback/SkeletonCard.vue';
import InfoBlock from '@/components/medications/InfoBlock.vue';

const route = useRoute();
const query = ref(route.query.q ?? '');
const medication = ref(null);
const loading = ref(false);
const fromCache = ref(false);
const forceRefresh = ref(false);
const defaultDisclaimer =
    'Esta informação tem caráter educativo. Consulte sempre um profissional de saúde antes de iniciar, alterar ou interromper tratamentos.';

onMounted(() => {
    if (query.value) {
        handleSearch();
    }
});

async function handleSearch() {
    if (!query.value) return;
    loading.value = true;
    medication.value = null;
    try {
        const { data } = await apiClient.post('/medications/query', {
            query: query.value,
            force_refresh: forceRefresh.value,
        });
        medication.value = data.medication;
        fromCache.value = data.from_cache;
    } catch (error) {
        medication.value = null;
    } finally {
        loading.value = false;
    }
}

function exportJson() {
    if (!medication.value) return;
    const blob = new Blob([JSON.stringify(medication.value, null, 2)], { type: 'application/json' });
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = `${medication.value.slug}.json`;
    link.click();
    URL.revokeObjectURL(url);
}

function formatDate(value) {
    if (!value) return '—';
    return new Intl.DateTimeFormat('pt-BR', { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(value));
}
</script>
