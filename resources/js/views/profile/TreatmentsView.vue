<template>
    <div class="mx-auto max-w-5xl space-y-8">
        <header class="flex flex-col gap-2 md:flex-row md:items-end md:justify-between">
            <div>
                <h1 class="text-2xl font-semibold text-white">Tratamentos em andamento</h1>
                <p class="text-sm text-slate-400">
                    Registre medicamentos que você já está tomando para que a IA avalie interações automaticamente.
                </p>
            </div>
            <button
                type="button"
                class="inline-flex items-center gap-2 rounded-xl bg-emerald-500 px-4 py-2 text-sm font-semibold text-emerald-900 transition hover:bg-emerald-400"
                @click="openModal()"
            >
                <PlusIcon class="h-4 w-4" />
                Novo tratamento
            </button>
        </header>

        <div v-if="loading" class="grid gap-3 md:grid-cols-2">
            <SkeletonCard v-for="i in 4" :key="i" />
        </div>

        <div v-else-if="courses.length" class="grid gap-6 md:grid-cols-2">
            <article
                v-for="course in courses"
                :key="course.id"
                class="rounded-3xl border border-white/5 bg-white/5 p-5 text-sm text-slate-200 backdrop-blur transition hover:border-emerald-400/40"
            >
                <header class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-xs uppercase tracking-widest text-emerald-400">Medicamento</p>
                        <h2 class="text-lg font-semibold text-white">{{ course.medication_name }}</h2>
                        <p class="text-xs text-slate-400">
                            {{ course.dosage ?? 'Dose conforme prescrição' }} &middot;
                            {{ course.frequency ?? intervalLabel(course.interval_minutes) }}
                        </p>
                    </div>
                    <span
                        class="rounded-full px-3 py-1 text-xs font-semibold uppercase"
                        :class="course.is_active ? 'bg-emerald-500/10 text-emerald-200' : 'bg-white/5 text-slate-400'"
                    >
                        {{ course.is_active ? 'Ativo' : 'Arquivado' }}
                    </span>
                </header>
                <p class="mt-3 text-xs text-slate-400">
                    Prescrito por {{ course.prescribed_by ?? 'Profissional não informado' }} para
                    {{ course.diagnosis ?? 'diagnóstico não informado' }}
                </p>
                <p class="mt-2 text-xs text-slate-500">
                    {{ formatDate(course.start_at) }} – {{ course.end_at ? formatDate(course.end_at) : 'sem data de término' }}
                </p>
                <footer class="mt-4 flex items-center gap-3 text-xs">
                    <button class="inline-flex items-center gap-1 text-emerald-300 hover:text-emerald-200" @click="openModal(course)">
                        <PencilSquareIcon class="h-4 w-4" />
                        Editar
                    </button>
                    <button
                        class="inline-flex items-center gap-1 text-rose-300 hover:text-rose-200"
                        @click="remove(course)"
                    >
                        <TrashIcon class="h-4 w-4" />
                        Remover
                    </button>
                </footer>
            </article>
        </div>

        <EmptyState v-else icon="HeartIcon" title="Nenhum tratamento cadastrado">
            Cadastre os medicamentos prescritos para conferir interações e manter a agenda atualizada.
        </EmptyState>

        <TransitionRoot :show="modalOpen" as="template">
            <Dialog as="div" class="relative z-[200]" @close="closeModal">
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

                <div class="fixed inset-0 overflow-y-auto">
                    <div class="flex min-h-full items-center justify-center p-4">
                        <TransitionChild
                            as="template"
                            enter="duration-150 ease-out"
                            enter-from="scale-95 opacity-0"
                            enter-to="scale-100 opacity-100"
                            leave="duration-100 ease-in"
                            leave-from="scale-100 opacity-100"
                            leave-to="scale-95 opacity-0"
                        >
                            <DialogPanel class="w-full max-w-2xl rounded-3xl border border-white/10 bg-slate-950/90 p-6 backdrop-blur">
                                <DialogTitle class="text-lg font-semibold text-white">
                                    {{ editingCourse ? 'Editar tratamento' : 'Novo tratamento' }}
                                </DialogTitle>
                                <p class="mt-1 text-xs text-slate-400">
                                    Estas informações alimentam o assistente e o cálculo automático de doses.
                                </p>

                                <form class="mt-6 space-y-4" @submit.prevent="persist">
                                    <div class="grid gap-4 md:grid-cols-2">
                                        <div class="md:col-span-2">
                                            <label class="mb-2 block text-sm font-medium text-slate-200" for="medication_name">
                                                Medicamento
                                            </label>
                                            <input
                                                id="medication_name"
                                                v-model="form.medication_name"
                                                required
                                                placeholder="Dipirona, Amoxicilina..."
                                                class="w-full rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm text-white placeholder:text-slate-500 focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-400/40"
                                            />
                                        </div>

                                        <div>
                                            <label class="mb-2 block text-sm font-medium text-slate-200" for="dosage">Dosagem</label>
                                            <input
                                                id="dosage"
                                                v-model="form.dosage"
                                                placeholder="500 mg, 30 gotas..."
                                                class="w-full rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm text-white placeholder:text-slate-500 focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-400/40"
                                            />
                                        </div>

                                        <div>
                                            <label class="mb-2 block text-sm font-medium text-slate-200" for="frequency">Frequência</label>
                                            <input
                                                id="frequency"
                                                v-model="form.frequency"
                                                placeholder="2x ao dia"
                                                class="w-full rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm text-white placeholder:text-slate-500 focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-400/40"
                                            />
                                        </div>

                                        <div>
                                            <label class="mb-2 block text-sm font-medium text-slate-200" for="interval_minutes">Intervalo (min)</label>
                                            <input
                                                id="interval_minutes"
                                                v-model.number="form.interval_minutes"
                                                type="number"
                                                min="30"
                                                step="30"
                                                placeholder="480"
                                                class="w-full rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm text-white placeholder:text-slate-500 focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-400/40"
                                            />
                                        </div>

                                        <div>
                                            <label class="mb-2 block text-sm font-medium text-slate-200" for="start_at">Início</label>
                                            <input
                                                id="start_at"
                                                v-model="form.start_at"
                                                type="date"
                                                class="w-full rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm text-white placeholder:text-slate-500 focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-400/40"
                                            />
                                        </div>

                                        <div>
                                            <label class="mb-2 block text-sm font-medium text-slate-200" for="end_at">Término</label>
                                            <input
                                                id="end_at"
                                                v-model="form.end_at"
                                                type="date"
                                                class="w-full rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm text-white placeholder:text-slate-500 focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-400/40"
                                            />
                                        </div>

                                        <div>
                                            <label class="mb-2 block text-sm font-medium text-slate-200" for="prescribed_by">
                                                Profissional
                                            </label>
                                            <input
                                                id="prescribed_by"
                                                v-model="form.prescribed_by"
                                                placeholder="Dr(a). Nome"
                                                class="w-full rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm text-white placeholder:text-slate-500 focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-400/40"
                                            />
                                        </div>

                                        <div>
                                            <label class="mb-2 block text-sm font-medium text-slate-200" for="diagnosis">Diagnóstico</label>
                                            <input
                                                id="diagnosis"
                                                v-model="form.diagnosis"
                                                placeholder="Motivo do tratamento"
                                                class="w-full rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm text-white placeholder:text-slate-500 focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-400/40"
                                            />
                                        </div>

                                        <div class="md:col-span-2">
                                            <label class="mb-2 block text-sm font-medium text-slate-200" for="notes">Notas</label>
                                            <textarea
                                                id="notes"
                                                v-model="form.notes"
                                                rows="3"
                                                class="w-full rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm text-white placeholder:text-slate-500 focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-400/40"
                                            ></textarea>
                                        </div>
                                    </div>

                                    <div class="flex items-center justify-between">
                                        <label class="flex items-center gap-2 text-xs text-slate-400">
                                            <input v-model="form.is_active" type="checkbox" class="h-4 w-4 rounded border-white/20 bg-white/10" />
                                            Manter ativo
                                        </label>
                                        <div class="flex items-center gap-2">
                                            <button
                                                type="button"
                                                class="rounded-xl px-4 py-2 text-xs text-slate-400 hover:text-slate-200"
                                                @click="closeModal"
                                            >
                                                Cancelar
                                            </button>
                                            <button
                                                type="submit"
                                                class="inline-flex items-center gap-2 rounded-xl bg-emerald-500 px-4 py-2 text-sm font-semibold text-emerald-900 transition hover:bg-emerald-400 disabled:cursor-not-allowed disabled:opacity-60"
                                                :disabled="saving"
                                            >
                                                <CheckIcon class="h-4 w-4" />
                                                Salvar
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </DialogPanel>
                        </TransitionChild>
                    </div>
                </div>
            </Dialog>
        </TransitionRoot>
    </div>
</template>

<script setup>
import { onMounted, reactive, ref } from 'vue';
import { apiClient } from '@/bootstrap';
import { useUiStore } from '@/stores/ui';
import {
    CheckIcon,
    HeartIcon,
    PencilSquareIcon,
    PlusIcon,
    TrashIcon,
} from '@heroicons/vue/24/outline';
import {
    Dialog,
    DialogPanel,
    DialogTitle,
    TransitionChild,
    TransitionRoot,
} from '@headlessui/vue';
import SkeletonCard from '@/components/feedback/SkeletonCard.vue';
import EmptyState from '@/components/feedback/EmptyState.vue';

const ui = useUiStore();
const courses = ref([]);
const loading = ref(true);
const saving = ref(false);
const modalOpen = ref(false);
const editingCourse = ref(null);

const form = reactive({
    medication_name: '',
    dosage: '',
    frequency: '',
    interval_minutes: null,
    start_at: '',
    end_at: '',
    prescribed_by: '',
    diagnosis: '',
    notes: '',
    is_active: true,
});

onMounted(fetchCourses);

async function fetchCourses() {
    loading.value = true;
    try {
        const { data } = await apiClient.get('/profile/medications');
        courses.value = data.data ?? [];
    } finally {
        loading.value = false;
    }
}

function openModal(course = null) {
    editingCourse.value = course;
    if (course) {
        Object.assign(form, {
            medication_name: course.medication_name,
            dosage: course.dosage ?? '',
            frequency: course.frequency ?? '',
            interval_minutes: course.interval_minutes ?? null,
            start_at: course.start_at?.slice(0, 10) ?? '',
            end_at: course.end_at?.slice(0, 10) ?? '',
            prescribed_by: course.prescribed_by ?? '',
            diagnosis: course.diagnosis ?? '',
            notes: course.notes ?? '',
            is_active: Boolean(course.is_active),
        });
    } else {
        Object.assign(form, {
            medication_name: '',
            dosage: '',
            frequency: '',
            interval_minutes: null,
            start_at: '',
            end_at: '',
            prescribed_by: '',
            diagnosis: '',
            notes: '',
            is_active: true,
        });
    }
    modalOpen.value = true;
}

function closeModal() {
    modalOpen.value = false;
}

async function persist() {
    saving.value = true;
    try {
        if (editingCourse.value) {
            const { data } = await apiClient.put(`/profile/medications/${editingCourse.value.id}`, form);
            courses.value = courses.value.map((course) =>
                course.id === editingCourse.value.id ? data.data : course,
            );
            ui.notify({ title: 'Tratamento atualizado', message: 'Informações sincronizadas.', tone: 'success' });
        } else {
            const { data } = await apiClient.post('/profile/medications', form);
            courses.value.unshift(data.data);
            ui.notify({ title: 'Tratamento adicionado', message: 'A IA avaliará interações automaticamente.', tone: 'success' });
        }
        closeModal();
    } catch (error) {
        ui.notify({ title: 'Erro ao salvar', message: 'Verifique os dados e tente novamente.', tone: 'error' });
    } finally {
        saving.value = false;
    }
}

async function remove(course) {
    if (!confirm(`Remover ${course.medication_name}?`)) return;
    try {
        await apiClient.delete(`/profile/medications/${course.id}`);
        courses.value = courses.value.filter((item) => item.id !== course.id);
        ui.notify({ title: 'Tratamento removido', message: 'Registro excluído.', tone: 'success' });
    } catch (error) {
        ui.notify({ title: 'Erro ao remover', message: 'Não foi possível excluir.', tone: 'error' });
    }
}

function intervalLabel(interval) {
    if (!interval) return 'Intervalo não informado';
    const hours = Math.round(interval / 60);
    return `a cada ${hours}h`;
}

function formatDate(value) {
    if (!value) return 'sem data';
    return new Intl.DateTimeFormat('pt-BR', { dateStyle: 'medium' }).format(new Date(value));
}
</script>
