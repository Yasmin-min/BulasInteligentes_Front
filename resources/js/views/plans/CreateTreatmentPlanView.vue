<template>
    <div class="mx-auto max-w-5xl space-y-8">
        <header class="flex flex-col gap-2">
            <RouterLink
                to="/plans"
                class="inline-flex items-center gap-2 text-xs text-slate-400 transition hover:text-emerald-200"
            >
                <ArrowLeftIcon class="h-3 w-3" />
                Voltar
            </RouterLink>
            <h1 class="text-2xl font-semibold text-white">Novo plano de tratamento</h1>
            <p class="text-sm text-slate-400">
                Estruture medicamentos, horários e duração ou deixe a IA montar um plano básico a partir de um resumo.
                Você pode ajustar doses individualmente após a criação.
            </p>
        </header>

        <form class="space-y-6" @submit.prevent="submit">
            <section class="rounded-3xl border border-white/5 bg-white/5 p-6 backdrop-blur">
                <h2 class="text-sm uppercase tracking-[0.3em] text-emerald-400">Informações gerais</h2>
                <div class="mt-4 grid gap-4 md:grid-cols-2">
                    <label class="text-xs text-slate-400 md:col-span-2">
                        Nome do plano
                        <input
                            v-model="form.title"
                            required
                            placeholder="Pós-operatório, Tratamento febril..."
                            class="mt-1 w-full rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm text-white placeholder:text-slate-500 focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-400/40"
                        />
                    </label>
                    <label class="text-xs text-slate-400 md:col-span-2">
                        Instruções gerais
                        <textarea
                            v-model="form.instructions"
                            rows="3"
                            placeholder="Recomendações adicionais do profissional."
                            class="mt-1 w-full rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm text-white placeholder:text-slate-500 focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-400/40"
                        ></textarea>
                    </label>
                    <label class="text-xs text-slate-400">
                        Início
                        <input
                            v-model="form.start_at"
                            type="date"
                            class="mt-1 w-full rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm text-white focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-400/40"
                        />
                    </label>
                    <label class="text-xs text-slate-400">
                        Término (opcional)
                        <input
                            v-model="form.end_at"
                            type="date"
                            class="mt-1 w-full rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm text-white focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-400/40"
                        />
                    </label>
                </div>

                <div class="mt-6 rounded-2xl border border-emerald-500/30 bg-emerald-500/5 p-4">
                    <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                        <div class="flex items-center gap-2 text-sm text-emerald-100">
                            <SparklesIcon class="h-4 w-4" />
                            <div>
                                <p class="font-semibold">Deixe a IA montar</p>
                                <p class="text-xs text-emerald-200/80">
                                    Informe um resumo simples (medicamento, dose, frequência, duração) e nós sugerimos os horários.
                                </p>
                            </div>
                        </div>
                        <button
                            type="button"
                            class="inline-flex items-center gap-2 rounded-xl bg-emerald-500 px-3 py-2 text-xs font-semibold text-emerald-900 transition hover:bg-emerald-400 disabled:cursor-not-allowed disabled:opacity-70"
                            :disabled="aiLoading || !aiSummary.trim()"
                            @click="generateWithAi"
                        >
                            <ArrowPathIcon v-if="aiLoading" class="h-4 w-4 animate-spin" />
                            <SparklesIcon v-else class="h-4 w-4" />
                            {{ aiLoading ? 'Gerando...' : 'Gerar plano' }}
                        </button>
                    </div>
                    <textarea
                        v-model="aiSummary"
                        rows="2"
                        placeholder="Ex.: Amoxicilina 500 mg, tomar 3x ao dia por 7 dias. Começar amanhã às 08:00."
                        class="mt-3 w-full rounded-xl border border-emerald-500/30 bg-black/10 px-4 py-3 text-sm text-white placeholder:text-emerald-200/70 focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-400/40"
                    ></textarea>
                    <p class="mt-2 text-[0.7rem] text-emerald-200/80">
                        Dica: escolha a data de início acima se já souber. Revise o plano sugerido antes de salvar.
                    </p>
                </div>
            </section>

            <section class="rounded-3xl border border-white/5 bg-white/5 p-6 backdrop-blur">
                <div class="flex items-center justify-between">
                    <h2 class="text-sm uppercase tracking-[0.3em] text-emerald-400">Medicamentos</h2>
                    <button
                        type="button"
                        class="inline-flex items-center gap-2 rounded-xl bg-white/10 px-3 py-2 text-xs text-slate-200 transition hover:bg-white/20"
                        @click="addItem"
                    >
                        <PlusIcon class="h-4 w-4" />
                        Adicionar medicamento
                    </button>
                </div>

                <div v-if="form.items.length" class="mt-4 space-y-6">
                    <article
                        v-for="(item, index) in form.items"
                        :key="item.id"
                        class="rounded-3xl border border-white/5 bg-white/5 p-4 text-sm text-slate-200 backdrop-blur"
                    >
                        <header class="flex items-start justify-between">
                            <div>
                                <p class="text-xs uppercase tracking-widest text-emerald-400">Medicamento {{ index + 1 }}</p>
                            </div>
                            <button
                                type="button"
                                class="inline-flex items-center gap-1 text-xs text-rose-300 hover:text-rose-200"
                                @click="removeItem(index)"
                            >
                                <TrashIcon class="h-4 w-4" />
                                Remover
                            </button>
                        </header>
                        <div class="mt-3 grid gap-3 md:grid-cols-2">
                            <label class="text-xs text-slate-400 md:col-span-2">
                                Nome
                                <input
                                    v-model="item.medication_name"
                                    required
                                    placeholder="Nome ou princípio ativo"
                                    class="mt-1 w-full rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm text-white placeholder:text-slate-500 focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-400/40"
                                />
                            </label>
                            <label class="text-xs text-slate-400">
                                Dosagem
                                <input
                                    v-model="item.dosage"
                                    placeholder="500 mg, 10 ml..."
                                    class="mt-1 w-full rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm text-white placeholder:text-slate-500 focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-400/40"
                                />
                            </label>
                            <label class="text-xs text-slate-400">
                                Via (oral, tópica...)
                                <input
                                    v-model="item.route"
                                    placeholder="Oral, tópica..."
                                    class="mt-1 w-full rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm text-white placeholder:text-slate-500 focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-400/40"
                                />
                            </label>
                            <label class="text-xs text-slate-400 md:col-span-2">
                                Instruções específicas
                                <textarea
                                    v-model="item.instructions"
                                    rows="2"
                                    class="mt-1 w-full rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm text-white placeholder:text-slate-500 focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-400/40"
                                ></textarea>
                            </label>
                            <label class="text-xs text-slate-400">
                                Intervalo (min)
                                <input
                                    v-model.number="item.interval_minutes"
                                    type="number"
                                    min="30"
                                    step="30"
                                    placeholder="480"
                                    class="mt-1 w-full rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm text-white placeholder:text-slate-500 focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-400/40"
                                />
                            </label>
                            <label class="text-xs text-slate-400">
                                Doses totais
                                <input
                                    v-model.number="item.total_doses"
                                    type="number"
                                    min="1"
                                    placeholder="10"
                                    class="mt-1 w-full rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm text-white placeholder:text-slate-500 focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-400/40"
                                />
                            </label>
                            <label class="text-xs text-slate-400">
                                Duração (dias)
                                <input
                                    v-model.number="item.duration_days"
                                    type="number"
                                    min="1"
                                    placeholder="7"
                                    class="mt-1 w-full rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm text-white placeholder:text-slate-500 focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-400/40"
                                />
                            </label>
                            <label class="text-xs text-slate-400">
                                Primeira dose
                                <input
                                    v-model="item.first_dose_at"
                                    type="datetime-local"
                                    class="mt-1 w-full rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm text-white focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-400/40"
                                />
                            </label>
                            <div class="md:col-span-2">
                                <label class="text-xs text-slate-400">
                                    Horários específicos (opcional)
                                    <input
                                        v-model="item.specific_times_raw"
                                        placeholder="08:00, 14:00, 20:00"
                                        class="mt-1 w-full rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm text-white placeholder:text-slate-500 focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-400/40"
                                    />
                                    <span class="mt-1 block text-[0.65rem] text-slate-500">
                                        Use formato 24h e separe por vírgulas. Se preencher, o intervalo será ignorado.
                                    </span>
                                </label>
                            </div>
                        </div>
                    </article>
                </div>
                <EmptyState v-else icon="BeakerIcon" title="Nenhum medicamento adicionado">
                    Inclua ao menos um medicamento para criar o plano.
                </EmptyState>
            </section>

            <div class="flex items-center justify-end gap-3">
                <RouterLink
                    to="/plans"
                    class="rounded-xl px-4 py-2 text-sm text-slate-400 transition hover:text-slate-200"
                >
                    Cancelar
                </RouterLink>
                <button
                    type="submit"
                    class="inline-flex items-center gap-2 rounded-xl bg-emerald-500 px-5 py-3 text-sm font-semibold text-emerald-900 transition hover:bg-emerald-400 disabled:cursor-not-allowed disabled:opacity-70"
                    :disabled="saving || aiLoading || form.items.length === 0"
                >
                    <CheckIcon class="h-4 w-4" />
                    Criar plano
                </button>
            </div>
        </form>
    </div>
</template>

<script setup>
import { reactive, ref } from 'vue';
import { RouterLink, useRouter } from 'vue-router';
import { apiClient } from '@/bootstrap';
import { useUiStore } from '@/stores/ui';
import { ArrowLeftIcon, ArrowPathIcon, CheckIcon, PlusIcon, SparklesIcon, TrashIcon } from '@heroicons/vue/24/outline';
import EmptyState from '@/components/feedback/EmptyState.vue';

const router = useRouter();
const ui = useUiStore();

const saving = ref(false);
const aiLoading = ref(false);
const aiSummary = ref('');
const form = reactive({
    title: '',
    instructions: '',
    start_at: '',
    end_at: '',
    items: [],
});

function addItem() {
    form.items.push({
        id: crypto.randomUUID(),
        medication_name: '',
        dosage: '',
        route: '',
        instructions: '',
        interval_minutes: null,
        total_doses: null,
        duration_days: null,
        first_dose_at: '',
        specific_times_raw: '',
    });
}

function removeItem(index) {
    form.items.splice(index, 1);
}

async function generateWithAi() {
    if (!aiSummary.value.trim()) {
        ui.notify({ title: 'Resumo necessário', message: 'Descreva brevemente o tratamento para gerar o plano.', tone: 'warning' });
        return;
    }

    aiLoading.value = true;
    try {
        const { data } = await apiClient.post('/treatment-plans/ai/suggest', {
            summary: aiSummary.value,
            start_at: form.start_at || null,
            title: form.title || null,
        });

        const draft = data.data ?? data;

        form.title = draft.title || form.title || 'Plano sugerido';
        form.instructions = draft.instructions || form.instructions;
        form.start_at = draft.start_at ? formatDateForInput(draft.start_at) : form.start_at;

        form.items = (draft.items || []).map((item) => ({
            id: crypto.randomUUID(),
            medication_name: item.medication_name || '',
            dosage: item.dosage || '',
            route: item.route || '',
            instructions: item.instructions || '',
            interval_minutes: toNumberOrNull(item.interval_minutes),
            total_doses: toNumberOrNull(item.total_doses),
            duration_days: toNumberOrNull(item.duration_days),
            first_dose_at: formatDateTimeLocal(item.first_dose_at),
            specific_times_raw: Array.isArray(item.specific_times) ? item.specific_times.join(', ') : '',
        }));

        if (!form.items.length) {
            ui.notify({ title: 'Sem sugestões', message: 'A IA não conseguiu sugerir doses com esse resumo.', tone: 'warning' });
        } else {
            ui.notify({ title: 'Plano sugerido', message: 'Revise os horários gerados automaticamente.', tone: 'success' });
        }
    } catch (error) {
        const message = error.response?.data?.message ?? 'Não foi possível gerar o plano agora.';
        ui.notify({ title: 'Falha na geração', message, tone: 'error' });
    } finally {
        aiLoading.value = false;
    }
}

async function submit() {
    if (!form.items.length) {
        ui.notify({ title: 'Plano incompleto', message: 'Adicione ao menos um medicamento.', tone: 'warning' });
        return;
    }
    saving.value = true;
    try {
        const payload = {
            ...form,
            items: form.items.map((item) => ({
                medication_name: item.medication_name,
                dosage: item.dosage,
                route: item.route,
                instructions: item.instructions,
                interval_minutes: item.interval_minutes,
                total_doses: item.total_doses,
                duration_days: item.duration_days,
                first_dose_at: item.first_dose_at || null,
                specific_times: parseSpecificTimes(item.specific_times_raw),
            })),
        };
        const { data } = await apiClient.post('/treatment-plans', payload);
        ui.notify({ title: 'Plano criado', message: 'Agenda gerada automaticamente.', tone: 'success' });
        router.replace({ name: 'plans.detail', params: { id: data.data.id } });
    } catch (error) {
        ui.notify({ title: 'Erro ao criar plano', message: 'Verifique as informações e tente novamente.', tone: 'error' });
    } finally {
        saving.value = false;
    }
}

function parseSpecificTimes(raw) {
    if (!raw) return null;
    return raw
        .split(',')
        .map((time) => time.trim())
        .filter((time) => time.match(/^([01]\d|2[0-3]):[0-5]\d$/));
}

function formatDateForInput(value) {
    if (!value) return '';
    const date = new Date(value);
    if (Number.isNaN(date.getTime())) return '';

    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');

    return `${year}-${month}-${day}`;
}

function formatDateTimeLocal(value) {
    if (!value) return '';
    const date = new Date(value);
    if (Number.isNaN(date.getTime())) return '';

    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    const hours = String(date.getHours()).padStart(2, '0');
    const minutes = String(date.getMinutes()).padStart(2, '0');

    return `${year}-${month}-${day}T${hours}:${minutes}`;
}

function toNumberOrNull(value) {
    if (value === null || value === undefined || value === '') return null;
    const num = Number(value);
    return Number.isFinite(num) ? num : null;
}
</script>
