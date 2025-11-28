<template>
    <div class="space-y-8">
        <header class="flex flex-col gap-2 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <h1 class="text-2xl font-semibold text-white">Receitas médicas digitalizadas</h1>
                <p class="text-sm text-slate-400">
                    Envie a foto ou PDF da prescrição para que o sistema interprete horários, dosagens e medicamentos.
                </p>
            </div>
            <label
                class="flex cursor-pointer flex-col items-center justify-center gap-2 rounded-3xl border border-dashed border-emerald-400/40 bg-emerald-500/5 px-6 py-5 text-center text-xs text-emerald-200 transition hover:bg-emerald-500/10"
            >
                <ArrowUpTrayIcon class="h-5 w-5" />
                <span>Selecionar arquivo</span>
                <input hidden type="file" accept="image/*,.pdf" @change="handleFile" />
            </label>
        </header>

        <div v-if="uploading" class="rounded-3xl border border-white/5 bg-white/5 p-6 text-sm text-slate-300">
            <p class="font-semibold text-emerald-200">Processando receita...</p>
            <p class="mt-1 text-xs text-slate-400">Isso pode levar alguns segundos. Você será notificado quando finalizar.</p>
            <div class="mt-4 h-1 w-full overflow-hidden rounded-full bg-white/5">
                <div class="h-full w-1/2 animate-pulse rounded-full bg-emerald-500/60"></div>
            </div>
        </div>

        <div v-if="loading" class="grid gap-3 md:grid-cols-2">
            <SkeletonCard v-for="i in 4" :key="i" />
        </div>

        <div v-else-if="uploads.length" class="grid gap-4 md:grid-cols-2">
            <article
                v-for="upload in uploads"
                :key="upload.id"
                class="rounded-3xl border border-white/5 bg-white/5 p-5 text-sm text-slate-200 backdrop-blur transition hover:border-emerald-400/40"
            >
                <header class="flex items-center justify-between text-xs text-slate-400">
                    <p class="font-semibold text-white">Receita {{ formatDate(upload.created_at) }}</p>
                    <StatusPill :status="upload.status" />
                </header>
                <p class="mt-2 text-xs text-slate-400">
                    Arquivo original: {{ upload.original_name ?? 'imagem capturada' }}
                </p>
                <p v-if="upload.failure_reason" class="mt-2 text-xs text-rose-200">
                    Erro: {{ upload.failure_reason }}
                </p>
                <div v-if="upload.parsed_payload?.length" class="mt-4 space-y-2 rounded-2xl border border-white/5 bg-white/5 p-3 text-xs">
                    <p class="text-emerald-300">Medicamentos identificados:</p>
                    <ul class="space-y-1">
                        <li v-for="item in upload.parsed_payload" :key="item.medication_name" class="flex items-center gap-2">
                            <span class="h-1.5 w-1.5 rounded-full bg-emerald-400"></span>
                            <span>{{ item.medication_name }} – {{ item.dosage ?? 'dose não informada' }}</span>
                        </li>
                    </ul>
                </div>
                <p v-if="upload.extracted_text" class="mt-4 line-clamp-3 text-xs text-slate-400">
                    "{{ upload.extracted_text }}"
                </p>

                <div
                    v-if="canGeneratePlan(upload)"
                    class="mt-4 space-y-3 rounded-2xl border border-emerald-500/30 bg-emerald-500/5 p-3 text-xs text-emerald-100"
                >
                    <p class="font-semibold">Gerar plano de tratamento</p>
                    <p class="text-emerald-200/80">
                        Confirme quando deseja iniciar. A IA usará a receita e Google Vision para montar horários evitando conflitos
                        entre medicamentos.
                    </p>
                    <div class="flex flex-col gap-2 md:flex-row md:items-center">
                        <label class="text-[0.7rem] text-emerald-100">
                            Início do tratamento
                            <input
                                v-model="planStart[upload.id]"
                                type="datetime-local"
                                class="mt-1 w-full rounded-xl border border-emerald-500/40 bg-black/20 px-3 py-2 text-xs text-white focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-400/40"
                            />
                        </label>
                        <button
                            type="button"
                            class="inline-flex items-center justify-center gap-2 rounded-xl bg-emerald-500 px-4 py-2 text-xs font-semibold text-emerald-900 transition hover:bg-emerald-400 disabled:cursor-not-allowed disabled:opacity-60"
                            :disabled="planLoading[upload.id] || !planStart[upload.id]"
                            @click="generatePlan(upload)"
                        >
                            <ArrowPathIcon v-if="planLoading[upload.id]" class="h-4 w-4 animate-spin" />
                            <span v-else>Gerar plano</span>
                        </button>
                    </div>
                </div>

                <div class="mt-3 flex items-center justify-end gap-3 text-[0.7rem] text-slate-400">
                    <button
                        type="button"
                        class="inline-flex items-center gap-2 rounded-lg px-3 py-2 transition hover:bg-white/10"
                        :disabled="removing[upload.id]"
                        @click="removeUpload(upload)"
                    >
                        <ArrowPathIcon v-if="removing[upload.id]" class="h-3.5 w-3.5 animate-spin" />
                        <TrashIcon v-else class="h-3.5 w-3.5" />
                        <span>{{ removing[upload.id] ? 'Removendo...' : 'Remover tentativa' }}</span>
                    </button>
                </div>
            </article>
        </div>

        <EmptyState v-else icon="DocumentArrowUpIcon" title="Nenhuma receita enviada">
            Envie sua primeira prescrição para que possamos interpretar e gerar um plano automaticamente.
        </EmptyState>
    </div>
</template>

<script setup>
import { onBeforeUnmount, onMounted, ref } from 'vue';
import { useRouter } from 'vue-router';
import { apiClient } from '@/bootstrap';
import { useUiStore } from '@/stores/ui';
import { ArrowPathIcon, ArrowUpTrayIcon, TrashIcon } from '@heroicons/vue/24/outline';
import SkeletonCard from '@/components/feedback/SkeletonCard.vue';
import EmptyState from '@/components/feedback/EmptyState.vue';
import StatusPill from '@/components/prescriptions/StatusPill.vue';

const ui = useUiStore();
const router = useRouter();
const uploads = ref([]);
const loading = ref(true);
const uploading = ref(false);
const pollingHandle = ref(null);
const lastStatuses = ref(new Map());
const planStart = ref({});
const planLoading = ref({});
const removing = ref({});

onMounted(loadUploads);
onMounted(() => {
    startPolling();
});

onBeforeUnmount(() => {
    stopPolling();
});

async function loadUploads(options = { showLoader: true }) {
    if (options.showLoader) {
        loading.value = true;
    }
    try {
        const { data } = await apiClient.get('/prescriptions/uploads');
        const list = data.data ?? [];
        detectStatusChanges(list);
        uploads.value = list;
        const hasPending = list.some((upload) => ['pending', 'processing'].includes(upload.status));
        if (!hasPending) {
            stopPolling();
        }
    } finally {
        if (options.showLoader) {
            loading.value = false;
        }
    }
}

async function handleFile(event) {
    const file = event.target.files?.[0];
    if (!file) return;

    const formData = new FormData();
    formData.append('file', file);

    uploading.value = true;

    try {
        const { data } = await apiClient.post('/prescriptions/uploads', formData, {
            headers: { 'Content-Type': 'multipart/form-data' },
        });
        detectStatusChanges([data.data], true);
        uploads.value.unshift(data.data);
        ui.notify({ title: 'Receita enviada', message: 'Processamento iniciado.', tone: 'success' });
        startPolling();
    } catch (error) {
        const message = error.response?.data?.message ?? 'Não foi possível enviar.';
        ui.notify({ title: 'Erro no envio', message, tone: 'error' });
    } finally {
        uploading.value = false;
        event.target.value = '';
    }
}

function formatDate(value) {
    if (!value) return '—';
    return new Intl.DateTimeFormat('pt-BR', { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(value));
}

function startPolling() {
    stopPolling();
    pollingHandle.value = window.setInterval(async () => {
        const hasPending = uploads.value.some((upload) => ['pending', 'processing'].includes(upload.status));
        if (hasPending) {
            await loadUploads({ showLoader: false });
        } else {
            stopPolling();
        }
    }, 5000);
}

function stopPolling() {
    if (pollingHandle.value) {
        clearInterval(pollingHandle.value);
        pollingHandle.value = null;
    }
}

function detectStatusChanges(list, skipInitial = false) {
    const previous = new Map(lastStatuses.value);
    const nextMap = skipInitial ? new Map(previous) : new Map();

    list.forEach((item) => {
        const prevStatus = previous.get(item.id);
        if (prevStatus && prevStatus !== item.status) {
            notifyStatusChange(item);
        } else if (!prevStatus && !skipInitial) {
            // primeira carga, apenas registra
        }

        nextMap.set(item.id, item.status);
    });

    lastStatuses.value = nextMap;
}

function notifyStatusChange(upload) {
    const terminalStatuses = {
        parsed: {
            title: 'Receita processada',
            message: 'A prescrição foi interpretada com sucesso.',
            tone: 'success',
        },
        text_extracted: {
            title: 'Texto extraído',
            message: 'Revisar manualmente as informações antes de criar o plano.',
            tone: 'info',
        },
        manual_review: {
            title: 'Revisão manual necessária',
            message: 'Não conseguimos interpretar a receita automaticamente.',
            tone: 'warning',
        },
        failed: {
            title: 'Falha no processamento',
            message: upload.failure_reason ?? 'Ocorreu um erro ao processar a receita.',
            tone: 'error',
        },
    };

    const feedback = terminalStatuses[upload.status];
    if (feedback) {
        ui.notify(feedback);
    }
}

function canGeneratePlan(upload) {
    return ['parsed', 'text_extracted'].includes(upload.status);
}

async function generatePlan(upload) {
    if (!canGeneratePlan(upload)) return;

    const startAt = planStart.value[upload.id];

    if (!startAt) {
        ui.notify({ title: 'Informe o início', message: 'Defina a data/hora de início do tratamento.', tone: 'warning' });
        return;
    }

    planLoading.value = { ...planLoading.value, [upload.id]: true };

    try {
        const { data } = await apiClient.post(`/prescriptions/uploads/${upload.id}/plan`, {
            start_at: startAt,
        });

        const plan = data.data ?? data;
        ui.notify({ title: 'Plano criado', message: 'Geramos o plano com base na receita.', tone: 'success' });
        if (plan?.id) {
            router.push({ name: 'plans.detail', params: { id: plan.id } });
        }
    } catch (error) {
        const message = error.response?.data?.message ?? 'Não foi possível gerar o plano agora.';
        ui.notify({ title: 'Erro ao gerar plano', message, tone: 'error' });
    } finally {
        planLoading.value = { ...planLoading.value, [upload.id]: false };
    }
}

async function removeUpload(upload) {
    const confirmed = window.confirm('Remover esta tentativa de leitura?');
    if (!confirmed) return;

    removing.value = { ...removing.value, [upload.id]: true };
    try {
        await apiClient.delete(`/prescriptions/uploads/${upload.id}`);
        uploads.value = uploads.value.filter((item) => item.id !== upload.id);
        ui.notify({ title: 'Removido', message: 'Tentativa de leitura apagada.', tone: 'success' });
    } catch (error) {
        const message = error.response?.data?.message ?? 'Não foi possível remover agora.';
        ui.notify({ title: 'Erro ao remover', message, tone: 'error' });
    } finally {
        removing.value = { ...removing.value, [upload.id]: false };
    }
}
</script>
