<template>
    <div class="space-y-8">
        <header class="flex flex-col gap-2 md:flex-row md:items-end md:justify-between">
            <div>
                <h1 class="text-2xl font-semibold text-white">Planos de tratamento</h1>
                <p class="text-sm text-slate-400">
                    Cada plano organiza medicamentos, horários e registros de doses automaticamente.
                </p>
            </div>
            <RouterLink
                to="/plans/create"
                class="inline-flex items-center gap-2 rounded-xl bg-emerald-500 px-4 py-2 text-sm font-semibold text-emerald-900 transition hover:bg-emerald-400"
            >
                <PlusIcon class="h-4 w-4" />
                Criar plano
            </RouterLink>
        </header>

        <div v-if="loading" class="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
            <SkeletonCard v-for="i in 6" :key="i" />
        </div>

        <div v-else-if="plans.length" class="grid gap-6 md:grid-cols-2 xl:grid-cols-3">
            <article
                v-for="plan in plans"
                :key="plan.id"
                class="relative flex h-full flex-col overflow-hidden rounded-3xl border border-white/5 bg-gradient-to-br from-emerald-900/30 via-slate-900/30 to-slate-900/60 p-5 text-sm text-slate-200 shadow-lg shadow-emerald-900/20 ring-1 ring-white/5 transition hover:-translate-y-0.5 hover:ring-emerald-400/50"
            >
                <div class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-emerald-400 via-teal-300 to-emerald-500"></div>
                <div class="flex items-start justify-between gap-3">
                    <div class="space-y-1">
                        <p class="text-[0.7rem] uppercase tracking-[0.3em] text-emerald-300">Plano</p>
                        <h2 class="text-lg font-semibold text-white">{{ plan.title }}</h2>
                        <p class="text-xs text-slate-400">
                            {{ plan.items?.length ?? 0 }} medicamento(s) &middot; início {{ formatDate(plan.start_at) }}
                        </p>
                    </div>
                    <span
                        class="rounded-full px-3 py-1 text-[0.65rem] font-semibold uppercase"
                        :class="plan.is_active ? 'bg-emerald-500/15 text-emerald-200' : 'bg-white/5 text-slate-400'"
                    >
                        {{ plan.is_active ? 'Ativo' : 'Arquivado' }}
                    </span>
                </div>
                <p v-if="plan.instructions" class="mt-3 text-xs text-slate-300 line-clamp-3">
                    {{ plan.instructions }}
                </p>
                <div class="mt-4 space-y-3 rounded-2xl bg-white/5 p-3">
                    <p class="text-[0.65rem] uppercase tracking-widest text-emerald-300">Medicamentos</p>
                    <ul class="space-y-2 text-xs text-slate-300">
                        <li
                            v-for="item in plan.items?.slice(0, 3)"
                            :key="item.id"
                            class="flex items-center justify-between gap-3 rounded-xl border border-white/5 bg-white/5 px-3 py-2"
                        >
                            <div class="space-y-1">
                                <p class="text-sm font-semibold text-white">{{ item.medication_name }}</p>
                                <p class="text-[0.65rem] uppercase tracking-widest text-slate-400">
                                    {{ item.dosage ?? 'Dose orientada' }} • {{ intervalLabel(item.interval_minutes) }}
                                </p>
                            </div>
                            <div class="rounded-full bg-emerald-500/10 px-3 py-1 text-[0.65rem] text-emerald-200">
                                {{ (item.next_schedules?.length ?? 0) }} agendadas
                            </div>
                        </li>
                    </ul>
                </div>
                <div class="mt-auto pt-4 text-xs text-slate-300">
                    <p class="text-[0.7rem] uppercase tracking-widest text-emerald-300">Próxima dose</p>
                    <p v-if="nextDose(plan)" class="mt-1 flex items-center gap-2 text-sm font-semibold text-white">
                        <span class="inline-flex items-center rounded-full bg-emerald-500/15 px-2 py-1 text-[0.75rem] text-emerald-200">
                            {{ formatTime(nextDose(plan)?.scheduled_at) }}
                        </span>
                        {{ nextDose(plan)?.item?.medication_name ?? 'Dose' }}
                    </p>
                    <p v-else class="mt-1 text-slate-400">Sem próximas doses.</p>
                </div>
                <RouterLink
                    :to="{ name: 'plans.detail', params: { id: plan.id } }"
                    class="mt-4 inline-flex items-center gap-2 text-xs font-semibold text-emerald-300 transition hover:text-emerald-200"
                >
                    Ver detalhes
                    <ArrowRightIcon class="h-3 w-3" />
                </RouterLink>
            </article>
        </div>

        <EmptyState v-else icon="ClockIcon" title="Nenhum plano cadastrado">
            Organize seus medicamentos em planos para manter horários e doses sempre atualizados.
            <template #action>
                <RouterLink
                    to="/plans/create"
                    class="mt-4 inline-flex items-center gap-2 rounded-xl bg-emerald-500 px-4 py-2 text-sm font-semibold text-emerald-900 transition hover:bg-emerald-400"
                >
                    <PlusIcon class="h-4 w-4" />
                    Criar primeiro plano
                </RouterLink>
            </template>
        </EmptyState>
    </div>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import { apiClient } from '@/bootstrap';
import { RouterLink } from 'vue-router';
import { PlusIcon, ArrowRightIcon, ClockIcon } from '@heroicons/vue/24/outline';
import EmptyState from '@/components/feedback/EmptyState.vue';
import SkeletonCard from '@/components/feedback/SkeletonCard.vue';

const plans = ref([]);
const loading = ref(true);

onMounted(async () => {
    loading.value = true;
    try {
        const { data } = await apiClient.get('/treatment-plans');
        plans.value = data.data ?? [];
    } finally {
        loading.value = false;
    }
});

function formatDate(value) {
    if (!value) return 'sem data';
    return new Intl.DateTimeFormat('pt-BR', { dateStyle: 'medium' }).format(new Date(value));
}

function formatDateTime(value) {
    if (!value) return 'sem horário';
    return new Intl.DateTimeFormat('pt-BR', { day: '2-digit', month: 'short', hour: '2-digit', minute: '2-digit' }).format(
        new Date(value),
    );
}

function intervalLabel(interval) {
    if (!interval) return 'intervalo não informado';
    const hours = Math.round(interval / 60);
    return `a cada ${hours}h`;
}

function nextDose(plan) {
    const schedules = (plan.items ?? [])
        .flatMap((item) => {
            const list = item.next_schedules ?? item.schedules ?? [];
            return list.map((schedule) => ({ ...schedule, item }));
        });

    if (!schedules.length) return null;

    return schedules.sort((a, b) => new Date(a.scheduled_at) - new Date(b.scheduled_at))[0];
}

function formatTime(value) {
    if (!value) return 'sem horário';
    return new Intl.DateTimeFormat('pt-BR', { hour: '2-digit', minute: '2-digit' }).format(new Date(value));
}
</script>
