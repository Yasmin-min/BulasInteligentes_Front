<template>
    <div class="mx-auto max-w-4xl space-y-8">
        <header class="flex flex-col gap-2">
            <h1 class="text-2xl font-semibold text-white">Alergias monitoradas</h1>
            <p class="text-sm text-slate-400">
                As interações fornecidas pela IA utilizam esta lista para alertas imediatos.
            </p>
        </header>

        <form class="rounded-3xl border border-white/5 bg-white/5 p-6 backdrop-blur" @submit.prevent="submit">
            <div class="grid gap-4 md:grid-cols-2">
                <div class="md:col-span-2">
                    <label class="mb-2 block text-sm font-medium text-slate-200" for="allergen">Substância ou medicamento</label>
                    <input
                        id="allergen"
                        v-model="form.allergen"
                        type="text"
                        placeholder="Penicilina, dipirona, nimesulida..."
                        required
                        class="w-full rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm text-white placeholder:text-slate-500 focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-400/40"
                    />
                </div>

                <div>
                    <label class="mb-2 block text-sm font-medium text-slate-200" for="reaction">Reação principal</label>
                    <input
                        id="reaction"
                        v-model="form.reaction"
                        type="text"
                        placeholder="Anafilaxia, urticária, dispneia..."
                        class="w-full rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm text-white placeholder:text-slate-500 focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-400/40"
                    />
                </div>

                <div>
                    <label class="mb-2 block text-sm font-medium text-slate-200" for="severity">Severidade</label>
                    <select
                        id="severity"
                        v-model="form.severity"
                        class="w-full rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm text-white focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-400/40"
                    >
                        <option value="">Selecione</option>
                        <option value="mild">Leve</option>
                        <option value="moderate">Moderada</option>
                        <option value="severe">Grave</option>
                        <option value="critical">Crítica</option>
                    </select>
                </div>

                <div class="md:col-span-2">
                    <label class="mb-2 block text-sm font-medium text-slate-200" for="notes">Observações</label>
                    <textarea
                        id="notes"
                        v-model="form.notes"
                        rows="2"
                        placeholder="Contexto adicional para a reação..."
                        class="w-full rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm text-white placeholder:text-slate-500 focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-400/40"
                    ></textarea>
                </div>
            </div>

            <div class="mt-4 flex items-center gap-3">
                <button
                    type="submit"
                    class="inline-flex items-center gap-2 rounded-xl bg-emerald-500 px-4 py-2 text-sm font-semibold text-emerald-900 transition hover:bg-emerald-400 disabled:cursor-not-allowed disabled:opacity-60"
                    :disabled="submitting"
                >
                    <component :is="editing ? PencilSquareIcon : PlusIcon" class="h-4 w-4" />
                    {{ editing ? 'Atualizar alergia' : 'Adicionar alergia' }}
                </button>
                <button
                    v-if="editing"
                    type="button"
                    class="text-xs text-slate-400 hover:text-slate-200"
                    @click="resetForm"
                >
                    Cancelar edição
                </button>
            </div>
        </form>

        <section class="space-y-3">
            <div v-if="loading" class="grid gap-3 md:grid-cols-2">
                <SkeletonCard v-for="i in 4" :key="i" />
            </div>

            <div v-else-if="allergies.length" class="grid gap-3 md:grid-cols-2">
                <article
                    v-for="allergy in allergies"
                    :key="allergy.id"
                    class="rounded-3xl border border-white/5 bg-white/5 p-4 text-sm text-slate-200 backdrop-blur transition hover:border-emerald-400/40"
                >
                    <header class="flex items-start justify-between">
                        <div>
                            <p class="text-xs uppercase tracking-widest text-emerald-400">Substância</p>
                            <h2 class="text-lg font-semibold text-white">{{ allergy.allergen }}</h2>
                        </div>
                        <span
                            v-if="allergy.severity"
                            :class="['rounded-full px-3 py-1 text-xs font-semibold uppercase', severityTone(allergy.severity)]"
                        >
                            {{ translateSeverity(allergy.severity) }}
                        </span>
                    </header>
                    <p class="mt-3 text-xs text-slate-400">
                        Reação: {{ allergy.reaction ?? 'Não informada' }}
                    </p>
                    <p v-if="allergy.notes" class="mt-2 text-xs text-slate-400">Notas: {{ allergy.notes }}</p>

                    <footer class="mt-4 flex items-center gap-3 text-xs">
                        <button
                            class="inline-flex items-center gap-1 text-emerald-300 hover:text-emerald-200"
                            @click="edit(allergy)"
                        >
                            <PencilSquareIcon class="h-4 w-4" />
                            Editar
                        </button>
                        <button
                            class="inline-flex items-center gap-1 text-rose-300 hover:text-rose-200"
                            @click="destroy(allergy)"
                        >
                            <TrashIcon class="h-4 w-4" />
                            Remover
                        </button>
                    </footer>
                </article>
            </div>

            <EmptyState v-else icon="ShieldExclamationIcon" title="Nenhuma alergia cadastrada">
                Registre substâncias para receber alertas personalizados em tempo real durante as consultas.
            </EmptyState>
        </section>
    </div>
</template>

<script setup>
import { reactive, ref, onMounted } from 'vue';
import { apiClient } from '@/bootstrap';
import { useUiStore } from '@/stores/ui';
import { PencilSquareIcon, PlusIcon, TrashIcon } from '@heroicons/vue/24/outline';
import EmptyState from '@/components/feedback/EmptyState.vue';
import SkeletonCard from '@/components/feedback/SkeletonCard.vue';

const ui = useUiStore();
const allergies = ref([]);
const loading = ref(true);
const submitting = ref(false);

const editing = ref(null);
const form = reactive({
    allergen: '',
    reaction: '',
    severity: '',
    notes: '',
});

onMounted(loadAllergies);

async function loadAllergies() {
    loading.value = true;
    try {
        const { data } = await apiClient.get('/profile/allergies');
        allergies.value = data.data ?? [];
    } finally {
        loading.value = false;
    }
}

function resetForm() {
    editing.value = null;
    form.allergen = '';
    form.reaction = '';
    form.severity = '';
    form.notes = '';
}

function edit(allergy) {
    editing.value = allergy;
    form.allergen = allergy.allergen;
    form.reaction = allergy.reaction ?? '';
    form.severity = allergy.severity ?? '';
    form.notes = allergy.notes ?? '';
}

async function submit() {
    submitting.value = true;
    try {
        if (editing.value) {
            const { data } = await apiClient.put(`/profile/allergies/${editing.value.id}`, form);
            allergies.value = allergies.value.map((item) => (item.id === editing.value.id ? data.data : item));
            ui.notify({ title: 'Alergia atualizada', message: 'Os alertas serão ajustados imediatamente.', tone: 'success' });
        } else {
            const { data } = await apiClient.post('/profile/allergies', form);
            allergies.value.unshift(data.data);
            ui.notify({ title: 'Alergia adicionada', message: 'A IA considerará esta nova informação.', tone: 'success' });
        }
        resetForm();
    } catch (error) {
        const message = error.response?.data?.message ?? 'Não foi possível salvar.';
        ui.notify({ title: 'Erro ao salvar', message, tone: 'error' });
    } finally {
        submitting.value = false;
    }
}

async function destroy(allergy) {
    if (!confirm(`Remover ${allergy.allergen}?`)) return;
    try {
        await apiClient.delete(`/profile/allergies/${allergy.id}`);
        allergies.value = allergies.value.filter((item) => item.id !== allergy.id);
        ui.notify({ title: 'Alergia removida', message: 'Alertas deixarão de considerar esta substância.', tone: 'success' });
    } catch (error) {
        ui.notify({ title: 'Erro ao remover', message: 'Tente novamente mais tarde.', tone: 'error' });
    }
}

function translateSeverity(severity) {
    switch (severity) {
        case 'mild':
            return 'Leve';
        case 'moderate':
            return 'Moderada';
        case 'severe':
            return 'Grave';
        case 'critical':
            return 'Crítica';
        default:
            return 'Não classificada';
    }
}

function severityTone(severity) {
    switch (severity) {
        case 'mild':
            return 'bg-emerald-500/10 text-emerald-200';
        case 'moderate':
            return 'bg-amber-500/10 text-amber-200';
        case 'severe':
            return 'bg-orange-500/10 text-orange-200';
        case 'critical':
            return 'bg-rose-500/10 text-rose-200';
        default:
            return 'bg-white/5 text-slate-200';
    }
}
</script>
