<template>
    <div class="space-y-8">
        <header class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-sm uppercase tracking-[0.3em] text-emerald-400">Visão geral</p>
                <h1 class="text-2xl font-semibold text-white">Olá, {{ auth.user?.name?.split(' ')[0] ?? 'usuário' }}!</h1>
                <p class="text-sm text-slate-400">Monitore seus planos de tratamento, consultas recentes e avisos importantes.</p>
            </div>
            <div class="flex items-center gap-3">
                <RouterLink
                    to="/plans/create"
                    class="inline-flex items-center gap-2 rounded-xl border border-emerald-500/30 bg-emerald-500/10 px-4 py-2 text-sm font-semibold text-emerald-300 transition hover:bg-emerald-500/20"
                >
                    <PlusIcon class="h-4 w-4" />
                    Novo plano
                </RouterLink>
                <RouterLink
                    to="/assistant"
                    class="inline-flex items-center gap-2 rounded-xl bg-emerald-500 px-4 py-2 text-sm font-semibold text-emerald-900 transition hover:bg-emerald-400"
                >
                    <ChatBubbleLeftRightIcon class="h-4 w-4" />
                    Falar com a IA
                </RouterLink>
            </div>
        </header>

        <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <SummaryCard
                title="Doses para hoje"
                :value="summary.todayDoses.length"
                tooltip="Próximas doses agendadas nas próximas 24h."
                tone="emerald"
            >
                <template #icon>
                    <ClockIcon class="h-6 w-6" />
                </template>
            </SummaryCard>
            <SummaryCard
                title="Planos ativos"
                :value="summary.activePlans"
                tooltip="Planos de tratamento em andamento."
                tone="cyan"
            >
                <template #icon>
                    <SparklesIcon class="h-6 w-6" />
                </template>
            </SummaryCard>
            <SummaryCard
                title="Alertas de alergia"
                :value="summary.allergies"
                tooltip="Alergias cadastradas e monitoradas."
                tone="amber"
            >
                <template #icon>
                    <ShieldExclamationIcon class="h-6 w-6" />
                </template>
            </SummaryCard>
            <SummaryCard
                title="Receitas aguardando"
                :value="summary.pendingPrescriptions"
                tooltip="Receitas enviadas e ainda não processadas."
                tone="rose"
            >
                <template #icon>
                    <DocumentArrowUpIcon class="h-6 w-6" />
                </template>
            </SummaryCard>
        </section>

        <section class="grid gap-6 lg:grid-cols-3">
            <div class="space-y-4 lg:col-span-2">
                <SectionHeader title="Próximas doses" subtitle="Mantenha seu tratamento em dia." />

                <div v-if="loading" class="grid gap-3 sm:grid-cols-2">
                    <SkeletonCard v-for="i in 4" :key="i" />
                </div>
                <ul v-else-if="summary.todayDoses.length" class="grid gap-3 sm:grid-cols-2">
                    <li
                        v-for="dose in summary.todayDoses"
                        :key="dose.id"
                        class="rounded-2xl border border-white/5 bg-white/5 p-4 backdrop-blur transition hover:border-emerald-400/40"
                    >
                        <p class="text-xs uppercase tracking-widest text-slate-400">
                            {{ formatDate(dose.scheduled_at) }}
                        </p>
                        <p class="mt-1 text-base font-semibold text-white">
                            {{ dose.item?.medication_name }}
                        </p>
                        <p class="text-sm text-slate-400">
                            {{ dose.item?.dosage ?? 'Dose orientada' }}
                        </p>
                        <div class="mt-4 flex items-center gap-3 text-xs text-emerald-300">
                            <span class="inline-flex items-center gap-1 rounded-full bg-emerald-500/10 px-3 py-1 font-semibold">
                                <ClockIcon class="h-4 w-4" />
                                {{ formatTime(dose.scheduled_at) }}
                            </span>
                            <RouterLink
                                :to="{ name: 'plans.detail', params: { id: dose.item?.treatment_plan_id } }"
                                class="text-emerald-200 hover:text-emerald-100"
                            >
                                Ver plano
                            </RouterLink>
                        </div>
                    </li>
                </ul>
                <EmptyState v-else icon="ClockIcon" title="Sem doses para hoje">
                    Você está em dia. Continue registrando cada dose para manter o acompanhamento preciso.
                </EmptyState>
            </div>

            <div class="space-y-4">
                <SectionHeader title="Tratamentos ativos" subtitle="Monitoramento das medicações do momento." />

                <div v-if="loading" class="space-y-3">
                    <SkeletonCard v-for="i in 3" :key="i" />
                </div>

                <ul v-else-if="medicationCourses.length" class="space-y-3">
                    <li
                        v-for="course in medicationCourses"
                        :key="course.id"
                        class="rounded-2xl border border-white/5 bg-white/5 p-4 backdrop-blur transition hover:border-emerald-400/40"
                    >
                        <p class="text-sm font-semibold text-white">{{ course.medication_name }}</p>
                        <p class="text-xs text-slate-400">
                            {{ course.dosage ?? 'Dose conforme bula' }} &middot;
                            {{ course.frequency ?? intervalLabel(course.interval_minutes) }}
                        </p>
                        <p class="mt-2 text-xs text-slate-500">
                            Desde {{ formatDate(course.start_at) }}
                            <span v-if="course.end_at"> &middot; até {{ formatDate(course.end_at) }}</span>
                        </p>
                    </li>
                </ul>
                <EmptyState v-else icon="HeartIcon" title="Nenhum tratamento em andamento">
                    Cadastre seus medicamentos em uso para que a IA considere possíveis interações.
                </EmptyState>

                <SectionHeader title="Alertas rápidos" />
                <ul class="space-y-2 text-xs text-slate-300">
                    <li>
                        <span class="font-semibold text-emerald-300">Proteção ativa:</span>
                        <span> cruzamos alergias e medicamentos consultados em tempo real.</span>
                    </li>
                    <li>
                        <span class="font-semibold text-emerald-300">Receitas digitalizadas:</span>
                        <span> acompanhe o status após enviar a foto.</span>
                    </li>
                    <li>
                        <span class="font-semibold text-emerald-300">Assistente seguro:</span>
                        <span> todo retorno reforça a consulta médica.</span>
                    </li>
                </ul>
            </div>
        </section>
    </div>
</template>

<script setup>
import {
    ChatBubbleLeftRightIcon,
    ClockIcon,
    DocumentArrowUpIcon,
    HeartIcon,
    PlusIcon,
    ShieldExclamationIcon,
    SparklesIcon,
} from '@heroicons/vue/24/outline';
import { onMounted, reactive, computed } from 'vue';
import { RouterLink } from 'vue-router';
import { apiClient } from '@/bootstrap';
import { useUiStore } from '@/stores/ui';
import { useAuthStore } from '@/stores/auth';
import SummaryCard from '@/components/dashboard/SummaryCard.vue';
import SectionHeader from '@/components/dashboard/SectionHeader.vue';
import EmptyState from '@/components/feedback/EmptyState.vue';
import SkeletonCard from '@/components/feedback/SkeletonCard.vue';

const auth = useAuthStore();
const ui = useUiStore();

const state = reactive({
    loading: true,
    plans: [],
    medicationCourses: [],
    allergies: [],
    prescriptions: [],
});

const loading = computed(() => state.loading);
const medicationCourses = computed(() => state.medicationCourses.slice(0, 3));

const summary = computed(() => ({
    todayDoses: upcomingDoses(state.plans),
    activePlans: state.plans.filter((plan) => plan.is_active).length,
    allergies: state.allergies.length,
    pendingPrescriptions: state.prescriptions.filter((item) => item.status === 'pending' || item.status === 'processing')
        .length,
}));

onMounted(async () => {
    try {
        state.loading = true;
        const [plansRes, medsRes, allergiesRes, prescriptionsRes] = await Promise.allSettled([
            withTimeout(apiClient.get('/treatment-plans')),
            withTimeout(apiClient.get('/profile/medications')),
            withTimeout(apiClient.get('/profile/allergies')),
            withTimeout(apiClient.get('/prescriptions/uploads')),
        ]);

        const errors = [];

        if (plansRes.status === 'fulfilled') {
            state.plans = plansRes.value.data.data ?? [];
        } else {
            errors.push('planos');
        }

        if (medsRes.status === 'fulfilled') {
            state.medicationCourses = medsRes.value.data.data ?? [];
        } else {
            errors.push('tratamentos ativos');
        }

        if (allergiesRes.status === 'fulfilled') {
            state.allergies = allergiesRes.value.data.data ?? [];
        } else {
            errors.push('alergias');
        }

        if (prescriptionsRes.status === 'fulfilled') {
            state.prescriptions = prescriptionsRes.value.data.data ?? [];
        } else {
            errors.push('receitas');
        }

        if (errors.length) {
            ui.notify({
                title: 'Dados parciais',
                message: `Não foi possível carregar ${errors.join(', ')} agora.`,
                tone: 'warning',
            });
        }
    } catch (error) {
        ui.notify({ title: 'Falha ao carregar dados', message: 'Verifique sua conexão e tente novamente.', tone: 'error' });
    } finally {
        state.loading = false;
    }
});

function upcomingDoses(plans) {
    const now = new Date();
    const tomorrow = new Date();
    tomorrow.setDate(now.getDate() + 1);

    return plans
        .flatMap((plan) => plan.items ?? [])
        .flatMap((item) => {
            const schedules = item.next_schedules ?? item.schedules ?? [];
            return schedules.map((schedule) => ({
                ...schedule,
                item,
            }));
        })
        .filter((schedule) => {
            if (!schedule.scheduled_at) return false;
            const scheduled = new Date(schedule.scheduled_at);
            return scheduled >= now && scheduled <= tomorrow;
        })
        .sort((a, b) => new Date(a.scheduled_at) - new Date(b.scheduled_at))
        .slice(0, 6);
}

function formatDate(value) {
    if (!value) return '—';
    return new Intl.DateTimeFormat('pt-BR', { day: '2-digit', month: 'short' }).format(new Date(value));
}

function formatTime(value) {
    if (!value) return '—';
    return new Intl.DateTimeFormat('pt-BR', { hour: '2-digit', minute: '2-digit' }).format(new Date(value));
}

function intervalLabel(minutes) {
    if (!minutes) return 'Intervalo não informado';
    const hours = Math.round(minutes / 60);
    return `a cada ${hours}h`;
}

function withTimeout(promise, ms = 8000) {
    const timeout = new Promise((_, reject) => {
        const id = setTimeout(() => {
            clearTimeout(id);
            reject(new Error('timeout'));
        }, ms);
    });

    return Promise.race([promise, timeout]);
}
</script>
