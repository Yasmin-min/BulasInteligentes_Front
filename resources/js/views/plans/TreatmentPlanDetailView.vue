<template>
    <div class="space-y-8">
        <header class="flex flex-col gap-2 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <RouterLink
                    to="/plans"
                    class="inline-flex items-center gap-2 text-xs text-slate-400 transition hover:text-emerald-200"
                >
                    <ArrowLeftIcon class="h-3 w-3" />
                    Voltar
                </RouterLink>
                <h1 class="mt-2 text-2xl font-semibold text-white">{{ plan?.title }}</h1>
                <p class="text-sm text-slate-400">
                    {{ plan?.instructions ?? 'Sem instruções adicionais.' }}
                </p>
            </div>
            <div class="flex items-center gap-3 text-xs text-slate-400">
                <span class="rounded-full bg-white/5 px-3 py-1">Início {{ formatDate(plan?.start_at) }}</span>
                <span class="rounded-full bg-white/5 px-3 py-1">
                    {{ plan?.end_at ? `Término ${formatDate(plan.end_at)}` : 'Sem término' }}
                </span>
                <span
                    class="rounded-full px-3 py-1 font-semibold uppercase"
                    :class="plan?.is_active ? 'bg-emerald-500/10 text-emerald-200' : 'bg-white/5 text-slate-400'"
                >
                    {{ plan?.is_active ? 'Ativo' : 'Arquivado' }}
                </span>
            </div>
            <div class="flex items-center gap-2">
                <button
                    type="button"
                    class="inline-flex items-center gap-2 rounded-xl border border-white/10 bg-white/5 px-4 py-2 text-xs font-semibold text-white transition hover:border-emerald-400 hover:text-emerald-200"
                    @click="openEdit()"
                >
                    <PencilSquareIcon class="h-4 w-4" />
                    Editar
                </button>
                <button
                    type="button"
                    class="inline-flex items-center gap-2 rounded-xl border border-rose-500/30 bg-rose-500/10 px-4 py-2 text-xs font-semibold text-rose-100 transition hover:bg-rose-500/20"
                    @click="confirmDelete()"
                >
                    <TrashIcon class="h-4 w-4" />
                    Remover
                </button>
            </div>
        </header>

        <div v-if="loading" class="grid gap-3 lg:grid-cols-3">
            <SkeletonCard v-for="i in 6" :key="i" />
        </div>

        <template v-else>
            <section class="grid gap-6 lg:grid-cols-3">
                <div class="space-y-3 lg:col-span-1">
                    <h2 class="text-sm uppercase tracking-[0.3em] text-emerald-400">Medicamentos</h2>
                    <article
                        v-for="item in plan?.items ?? []"
                        :key="item.id"
                        class="rounded-3xl border border-white/5 bg-white/5 p-4 text-sm text-slate-200 backdrop-blur"
                    >
                        <h3 class="text-base font-semibold text-white">{{ item.medication_name }}</h3>
                        <p class="text-xs text-slate-400">
                            {{ item.dosage ?? 'Dose orientada' }} &middot;
                            {{ intervalLabel(item.interval_minutes) }}
                        </p>
                        <p class="mt-2 text-xs text-slate-500">
                            Primeira dose: {{ formatDateTime(item.first_dose_at) }}
                        </p>
                    </article>
                </div>

                <div class="lg:col-span-2">
                    <div class="flex items-center justify-between">
                        <h2 class="text-sm uppercase tracking-[0.3em] text-emerald-400">Agenda completa</h2>
                        <span class="text-xs text-slate-400">Toque para registrar cada dose</span>
                    </div>
                    <div class="mt-3 space-y-3">
                        <div
                            v-for="item in plan?.items ?? []"
                            :key="item.id"
                            class="rounded-3xl border border-white/5 bg-white/5 p-4"
                        >
                            <div class="flex items-center justify-between text-xs text-slate-400">
                                <span class="font-semibold text-slate-200">{{ item.medication_name }}</span>
                                <span>{{ item.next_schedules?.length ?? 0 }} próximas doses</span>
                            </div>

                            <ul class="mt-3 grid gap-3 md:grid-cols-2">
                                <li
                                    v-for="schedule in item.schedules ?? []"
                                    :key="schedule.id"
                                    class="rounded-2xl border border-white/5 bg-white/5 p-4 text-xs text-slate-300 transition hover:border-emerald-400/40"
                                >
                                    <div class="flex items-center justify-between">
                                        <p class="font-semibold text-white">{{ formatDateTime(schedule.scheduled_at) }}</p>
                                        <StatusBadge :status="schedule.status" />
                                    </div>
                                    <p class="mt-2 text-slate-400">
                                        Desvio: {{ schedule.deviation_minutes ?? 0 }} min
                                    </p>
                                    <div class="mt-3 flex items-center gap-2">
                                        <button
                                            class="inline-flex flex-1 items-center justify-center gap-1 rounded-xl bg-emerald-500/10 px-3 py-2 text-xs font-semibold text-emerald-200 transition hover:bg-emerald-500/20"
                                            @click="record(schedule, 'taken')"
                                        >
                                            <CheckIcon class="h-4 w-4" />
                                            Tomada
                                        </button>
                                        <button
                                            class="inline-flex flex-1 items-center justify-center gap-1 rounded-xl bg-amber-500/10 px-3 py-2 text-xs font-semibold text-amber-200 transition hover:bg-amber-500/20"
                                            @click="record(schedule, 'skipped')"
                                        >
                                            <XMarkIcon class="h-4 w-4" />
                                            Pulada
                                        </button>
                                        <button
                                            class="inline-flex flex-1 items-center justify-center gap-1 rounded-xl bg-white/10 px-3 py-2 text-xs font-semibold text-slate-200 transition hover:bg-white/20"
                                            @click="openReschedule(schedule)"
                                        >
                                            <ClockIcon class="h-4 w-4" />
                                            Reagendar
                                        </button>
                                    </div>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </section>
        </template>

        <TransitionRoot :show="rescheduleModal.open" as="template">
            <Dialog as="div" class="relative z-[200]" @close="closeReschedule">
                <TransitionChild
                    as="template"
                    enter="duration-150 ease-out"
                    enter-from="opacity-0"
                    enter-to="opacity-100"
                    leave="duration-100 ease-in"
                    leave-from="opacity-100"
                    leave-to="opacity-0"
                >
                    <div class="fixed inset-0 bg-black/70" />
                </TransitionChild>
                <div class="fixed inset-0 flex items-center justify-center p-4">
                    <TransitionChild
                        as="template"
                        enter="duration-150 ease-out"
                        enter-from="scale-95 opacity-0"
                        enter-to="scale-100 opacity-100"
                        leave="duration-100 ease-in"
                        leave-from="scale-100 opacity-100"
                        leave-to="scale-95 opacity-0"
                    >
                        <DialogPanel class="w-full max-w-md rounded-3xl border border-white/10 bg-slate-950/95 p-6 backdrop-blur">
                            <DialogTitle class="text-lg font-semibold text-white">Reagendar dose</DialogTitle>
                            <p class="mt-1 text-xs text-slate-400">
                                Ajuste o horário apenas quando necessário. As próximas doses serão recalculadas automaticamente.
                            </p>
                            <form class="mt-4 space-y-3" @submit.prevent="confirmReschedule">
                                <label class="text-xs text-slate-400">
                                    Nova data e hora
                                    <input
                                        v-model="rescheduleModal.newDate"
                                        type="datetime-local"
                                        required
                                        class="mt-1 w-full rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm text-white focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-400/40"
                                    />
                                </label>
                                <label class="text-xs text-slate-400">
                                    Observações
                                    <textarea
                                        v-model="rescheduleModal.notes"
                                        rows="3"
                                        class="mt-1 w-full rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm text-white focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-400/40"
                                    ></textarea>
                                </label>
                                <div class="flex items-center justify-end gap-2">
                                    <button
                                        type="button"
                                        class="rounded-xl px-4 py-2 text-xs text-slate-400 hover:text-slate-200"
                                        @click="closeReschedule"
                                    >
                                        Cancelar
                                    </button>
                                    <button
                                        type="submit"
                                        class="inline-flex items-center gap-2 rounded-xl bg-emerald-500 px-4 py-2 text-sm font-semibold text-emerald-900 transition hover:bg-emerald-400"
                                    >
                                        <CheckIcon class="h-4 w-4" />
                                        Reagendar
                                    </button>
                                </div>
                            </form>
                        </DialogPanel>
                    </TransitionChild>
                </div>
            </Dialog>
        </TransitionRoot>

        <TransitionRoot :show="editModal.open" as="template">
            <Dialog as="div" class="relative z-[210]" @close="closeEdit">
                <TransitionChild
                    as="template"
                    enter="duration-150 ease-out"
                    enter-from="opacity-0"
                    enter-to="opacity-100"
                    leave="duration-100 ease-in"
                    leave-from="opacity-100"
                    leave-to="opacity-0"
                >
                    <div class="fixed inset-0 bg-black/70" />
                </TransitionChild>
                <div class="fixed inset-0 flex items-center justify-center p-4">
                    <TransitionChild
                        as="template"
                        enter="duration-150 ease-out"
                        enter-from="scale-95 opacity-0"
                        enter-to="scale-100 opacity-100"
                        leave="duration-100 ease-in"
                        leave-from="scale-100 opacity-100"
                        leave-to="scale-95 opacity-0"
                    >
                        <DialogPanel class="w-full max-w-lg rounded-3xl border border-white/10 bg-slate-950/95 p-6 backdrop-blur">
                            <DialogTitle class="text-lg font-semibold text-white">Editar plano</DialogTitle>
                            <form class="mt-4 space-y-3" @submit.prevent="saveEdit">
                                <label class="text-xs text-slate-400">
                                    Título
                                    <input
                                        v-model="editModal.title"
                                        type="text"
                                        class="mt-1 w-full rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm text-white focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-400/40"
                                    />
                                </label>
                                <label class="text-xs text-slate-400">
                                    Instruções
                                    <textarea
                                        v-model="editModal.instructions"
                                        rows="3"
                                        class="mt-1 w-full rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm text-white focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-400/40"
                                    ></textarea>
                                </label>
                                <div class="grid gap-3 sm:grid-cols-2">
                                    <label class="text-xs text-slate-400">
                                        Início
                                        <input
                                            v-model="editModal.start_at"
                                            type="date"
                                            class="mt-1 w-full rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm text-white focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-400/40"
                                        />
                                    </label>
                                    <label class="text-xs text-slate-400">
                                        Término
                                        <input
                                            v-model="editModal.end_at"
                                            type="date"
                                            class="mt-1 w-full rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm text-white focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-400/40"
                                        />
                                    </label>
                                </div>
                                <label class="flex items-center gap-2 text-xs text-slate-300">
                                    <input v-model="editModal.is_active" type="checkbox" class="h-4 w-4 rounded border-white/20 bg-white/5" />
                                    Plano ativo
                                </label>
                                <div class="flex items-center justify-end gap-2">
                                    <button
                                        type="button"
                                        class="rounded-xl px-4 py-2 text-xs text-slate-400 hover:text-slate-200"
                                        @click="closeEdit"
                                    >
                                        Cancelar
                                    </button>
                                    <button
                                        type="submit"
                                        class="inline-flex items-center gap-2 rounded-xl bg-emerald-500 px-4 py-2 text-sm font-semibold text-emerald-900 transition hover:bg-emerald-400"
                                    >
                                        <CheckIcon class="h-4 w-4" />
                                        Salvar alterações
                                    </button>
                                </div>
                            </form>
                        </DialogPanel>
                    </TransitionChild>
                </div>
            </Dialog>
        </TransitionRoot>
    </div>
</template>

<script setup>
import { onMounted, reactive, ref } from 'vue';
import { useRoute, useRouter, RouterLink } from 'vue-router';
import { apiClient } from '@/bootstrap';
import {
    ArrowLeftIcon,
    CheckIcon,
    ClockIcon,
    PencilSquareIcon,
    PlusIcon,
    TrashIcon,
    XMarkIcon,
} from '@heroicons/vue/24/outline';
import { Dialog, DialogPanel, DialogTitle, TransitionChild, TransitionRoot } from '@headlessui/vue';
import SkeletonCard from '@/components/feedback/SkeletonCard.vue';
import StatusBadge from '@/components/plans/StatusBadge.vue';
import { useUiStore } from '@/stores/ui';

const route = useRoute();
const router = useRouter();
const ui = useUiStore();

const plan = ref(null);
const loading = ref(true);

const rescheduleModal = reactive({
    open: false,
    schedule: null,
    newDate: '',
    notes: '',
});
const editModal = reactive({
    open: false,
    title: '',
    instructions: '',
    start_at: '',
    end_at: '',
    is_active: true,
});

onMounted(loadPlan);

async function loadPlan() {
    loading.value = true;
    try {
        const { data } = await apiClient.get(`/treatment-plans/${route.params.id}`);
        plan.value = data.data;
    } finally {
        loading.value = false;
    }
}

async function record(schedule, status) {
    try {
        const payload =
            status === 'taken'
                ? { status: 'taken', taken_at: new Date().toISOString() }
                : { status };
        const { data } = await apiClient.patch(`/treatment-plans/${plan.value.id}/schedules/${schedule.id}`, payload);
        updateSchedule(data.data);
        ui.notify({ title: 'Registro atualizado', message: 'Agenda recalculada.', tone: 'success' });
    } catch (error) {
        ui.notify({ title: 'Não foi possível registrar', message: 'Tente novamente.', tone: 'error' });
    }
}

function openReschedule(schedule) {
    rescheduleModal.schedule = schedule;
    rescheduleModal.newDate = schedule.scheduled_at ? schedule.scheduled_at.slice(0, 16) : '';
    rescheduleModal.notes = schedule.notes ?? '';
    rescheduleModal.open = true;
}

function closeReschedule() {
    rescheduleModal.open = false;
}

async function confirmReschedule() {
    if (!rescheduleModal.schedule) return;
    try {
        const { data } = await apiClient.patch(
            `/treatment-plans/${plan.value.id}/schedules/${rescheduleModal.schedule.id}`,
            {
                status: 'rescheduled',
                reschedule_to: new Date(rescheduleModal.newDate).toISOString(),
                notes: rescheduleModal.notes,
            },
        );
        updateSchedule(data.data);
        ui.notify({ title: 'Dose reagendada', message: 'Os próximos horários foram recalculados.', tone: 'success' });
    } catch (error) {
        ui.notify({ title: 'Erro ao reagendar', message: 'Verifique o horário informado.', tone: 'error' });
    } finally {
        closeReschedule();
    }
}

function updateSchedule(updated) {
    plan.value = {
        ...plan.value,
        items: plan.value.items.map((item) => ({
            ...item,
            schedules: item.schedules.map((schedule) => (schedule.id === updated.id ? updated : schedule)),
        })),
    };
}

function intervalLabel(interval) {
    if (!interval) return 'intervalo não informado';
    const hours = Math.round(interval / 60);
    return `a cada ${hours}h`;
}

function openEdit() {
    editModal.open = true;
    editModal.title = plan.value?.title ?? '';
    editModal.instructions = plan.value?.instructions ?? '';
    editModal.start_at = formatDateInput(plan.value?.start_at);
    editModal.end_at = formatDateInput(plan.value?.end_at);
    editModal.is_active = !!plan.value?.is_active;
}

function closeEdit() {
    editModal.open = false;
}

async function saveEdit() {
    if (!plan.value) return;
    try {
        const payload = {
            title: editModal.title || undefined,
            instructions: editModal.instructions || undefined,
            start_at: editModal.start_at || null,
            end_at: editModal.end_at || null,
            is_active: editModal.is_active,
        };
        const { data } = await apiClient.patch(`/treatment-plans/${plan.value.id}`, payload);
        plan.value = data.data ?? plan.value;
        ui.notify({ title: 'Plano atualizado', message: 'As informações foram salvas.', tone: 'success' });
        closeEdit();
    } catch (error) {
        const message = error.response?.data?.message ?? 'Não foi possível atualizar o plano.';
        ui.notify({ title: 'Erro ao salvar', message, tone: 'error' });
    }
}

function formatDateInput(value) {
    if (!value) return '';
    const date = new Date(value);
    if (Number.isNaN(date.getTime())) return '';
    const y = date.getFullYear();
    const m = String(date.getMonth() + 1).padStart(2, '0');
    const d = String(date.getDate()).padStart(2, '0');
    return `${y}-${m}-${d}`;
}

async function confirmDelete() {
    if (!plan.value) return;
    const ok = window.confirm('Remover este plano e sua agenda?');
    if (!ok) return;
    try {
        await apiClient.delete(`/treatment-plans/${plan.value.id}`);
        ui.notify({ title: 'Plano removido', message: 'O plano foi excluído.', tone: 'success' });
        router.push('/plans');
    } catch (error) {
        const message = error.response?.data?.message ?? 'Não foi possível remover o plano.';
        ui.notify({ title: 'Erro ao remover', message, tone: 'error' });
    }
}

function formatDate(value) {
    if (!value) return '—';
    return new Intl.DateTimeFormat('pt-BR', { dateStyle: 'medium' }).format(new Date(value));
}

function formatDateTime(value) {
    if (!value) return '—';
    return new Intl.DateTimeFormat('pt-BR', { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(value));
}
</script>
