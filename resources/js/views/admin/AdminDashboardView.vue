<template>
    <div class="space-y-8">
        <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <StatCard label="Usuários" :value="metrics.users" icon="UsersIcon" />
            <StatCard label="Planos ativos" :value="metrics.activePlans" icon="ClipboardDocumentCheckIcon" />
            <StatCard label="Consultas IA (24h)" :value="metrics.aiQueries" icon="SparklesIcon" />
            <StatCard label="Receitas pendentes" :value="metrics.pendingPrescriptions" icon="DocumentArrowUpIcon" tone="warning" />
        </section>

        <div class="grid gap-6 lg:grid-cols-2">
            <article class="rounded-3xl border border-white/5 bg-white/5 p-6 backdrop-blur">
                <h2 class="text-sm uppercase tracking-[0.3em] text-emerald-400">Monitoramento de API</h2>
                <dl class="mt-4 space-y-3 text-sm text-slate-200">
                    <div class="flex items-center justify-between rounded-2xl border border-white/5 bg-white/5 px-4 py-3">
                        <dt>Latência média IA</dt>
                        <dd>{{ metrics.aiLatency }} ms</dd>
                    </div>
                    <div class="flex items-center justify-between rounded-2xl border border-white/5 bg-white/5 px-4 py-3">
                        <dt>Tokens consumidos hoje</dt>
                        <dd>{{ metrics.aiTokens }}</dd>
                    </div>
                    <div class="flex items-center justify-between rounded-2xl border border-white/5 bg-white/5 px-4 py-3">
                        <dt>Consultas em cache</dt>
                        <dd>{{ metrics.cacheHits }}</dd>
                    </div>
                </dl>
            </article>

            <article class="rounded-3xl border border-white/5 bg-white/5 p-6 backdrop-blur">
                <h2 class="text-sm uppercase tracking-[0.3em] text-emerald-400">Ações rápidas</h2>
                <div class="mt-4 grid gap-3 text-sm">
                    <RouterLink
                        to="/admin/medications"
                        class="group flex items-center justify-between rounded-2xl border border-white/5 bg-white/5 px-4 py-3 text-slate-200 transition hover:border-emerald-400/40 hover:text-emerald-200"
                    >
                        Revisar medicamentos sem composição
                        <ArrowRightIcon class="h-4 w-4 transition group-hover:translate-x-1" />
                    </RouterLink>
                    <RouterLink
                        to="/admin/users"
                        class="group flex items-center justify-between rounded-2xl border border-white/5 bg-white/5 px-4 py-3 text-slate-200 transition hover:border-emerald-400/40 hover:text-emerald-200"
                    >
                        Gerenciar usuários pendentes
                        <ArrowRightIcon class="h-4 w-4 transition group-hover:translate-x-1" />
                    </RouterLink>
                    <RouterLink
                        to="/admin/settings"
                        class="group flex items-center justify-between rounded-2xl border border-white/5 bg-white/5 px-4 py-3 text-slate-200 transition hover:border-emerald-400/40 hover:text-emerald-200"
                    >
                        Configurar integrações externas
                        <ArrowRightIcon class="h-4 w-4 transition group-hover:translate-x-1" />
                    </RouterLink>
                </div>
            </article>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { RouterLink } from 'vue-router';
import { apiClient } from '@/bootstrap';
import StatCard from '@/components/admin/StatCard.vue';
import { ArrowRightIcon } from '@heroicons/vue/24/outline';

const metrics = ref({
    users: '—',
    activePlans: '—',
    aiQueries: '—',
    pendingPrescriptions: '—',
    aiLatency: '—',
    aiTokens: '—',
    cacheHits: '—',
});

onMounted(async () => {
    try {
        const [users, plans, prescriptions, queries] = await Promise.allSettled([
            apiClient.get('/admin/metrics/users'),
            apiClient.get('/treatment-plans'),
            apiClient.get('/prescriptions/uploads'),
            apiClient.get('/admin/metrics/assistant'),
        ]);

        if (users.status === 'fulfilled') {
            metrics.value.users = users.value.data.total ?? users.value.data.data?.length ?? '—';
        }
        if (plans.status === 'fulfilled') {
            const list = plans.value.data.data ?? [];
            metrics.value.activePlans = list.filter((plan) => plan.is_active).length;
        }
        if (prescriptions.status === 'fulfilled') {
            const list = prescriptions.value.data.data ?? [];
            metrics.value.pendingPrescriptions = list.filter((upload) =>
                ['pending', 'processing'].includes(upload.status),
            ).length;
        }
        if (queries.status === 'fulfilled') {
            const { data } = queries.value;
            metrics.value.aiQueries = data.queries_last_24h ?? '—';
            metrics.value.aiLatency = data.avg_latency_ms ?? '—';
            metrics.value.aiTokens = data.tokens_last_24h ?? '—';
            metrics.value.cacheHits = data.cache_hits_last_24h ?? '—';
        }
    } catch (error) {
        // Silent fallback
    }
});
</script>
