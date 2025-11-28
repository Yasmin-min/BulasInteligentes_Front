<template>
    <div class="mx-auto flex h-[calc(100vh-7rem)] max-w-5xl flex-col rounded-3xl border border-white/5 bg-white/5 backdrop-blur">
        <header class="border-b border-white/5 px-6 py-4">
            <h1 class="text-lg font-semibold text-white">Assistente farmacêutico</h1>
            <p class="text-xs text-slate-400">
                Toda resposta reforça que a avaliação médica é indispensável. A IA utiliza suas alergias, tratamentos
                e planos cadastrados para personalizar a orientação. Pode também responder perguntas rápidas, como como
                funciona, sempre de forma enxuta.
            </p>
        </header>

        <div ref="scrollContainer" class="flex-1 overflow-y-auto px-6 py-6">
            <div v-if="messages.length === 0" class="flex h-full flex-col items-center justify-center text-sm text-slate-400">
                <SparklesIcon class="mb-4 h-12 w-12 text-emerald-300" />
                Faça uma pergunta sobre seus medicamentos, por exemplo:
                <span class="mt-2 text-emerald-200">"Posso tomar dipirona com os antibióticos atuais?"</span>
            </div>
            <ul class="space-y-4">
                <li
                    v-for="message in messages"
                    :key="message.id"
                    :class="[
                        'rounded-3xl border border-white/5 px-5 py-4 text-sm leading-relaxed shadow-sm',
                        message.role === 'assistant'
                            ? 'bg-emerald-500/10 text-emerald-100'
                            : 'ml-auto max-w-lg bg-white/10 text-slate-100',
                    ]"
                >
                    <p class="mb-2 text-xs uppercase tracking-widest" :class="message.role === 'assistant' ? 'text-emerald-300' : 'text-slate-300'">
                        {{ message.role === 'assistant' ? 'Assistente' : 'Você' }}
                    </p>
                    <p v-html="message.content" />
                    <p v-if="message.metadata?.medication" class="mt-3 text-xs text-emerald-200/80">
                        Contexto: {{ message.metadata.medication.name }}
                    </p>
                </li>
            </ul>
            <div v-if="loading" class="mt-4 flex items-center gap-2 text-xs text-emerald-200/80">
                <ArrowPathIcon class="h-4 w-4 animate-spin" />
                <span>Assistente pensando...</span>
            </div>
        </div>

        <footer class="border-t border-white/5 px-6 py-4">
            <form class="flex flex-col gap-3 sm:flex-row" @submit.prevent="handleSubmit">
                <div class="flex-1">
                    <label class="sr-only" for="question">Pergunta</label>
                    <textarea
                        id="question"
                        v-model="question"
                        rows="2"
                        :aria-busy="loading"
                        placeholder="Descreva sua dúvida ou pergunte como o assistente funciona. Mencione o medicamento, se houver."
                        class="min-h-[80px] w-full resize-y rounded-2xl border border-white/10 bg-white/5 px-4 py-3 text-sm text-white placeholder:text-slate-500 focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-400/40 sm:min-h-0 sm:resize-none"
                    ></textarea>
                </div>
                <div class="flex flex-col gap-3 sm:w-48">
                    <label class="text-xs text-slate-400">
                        <span class="mb-1 block">Medicamento em foco</span>
                        <input
                            v-model="medicationContext"
                            type="text"
                            placeholder="Opcional"
                            class="w-full rounded-2xl border border-white/10 bg-white/5 px-4 py-2 text-sm text-white placeholder:text-slate-500 focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-400/40"
                        />
                    </label>
                    <label class="flex items-center gap-2 text-xs text-slate-400">
                        <input v-model="allowRecommendations" type="checkbox" class="h-4 w-4 rounded border-white/20 bg-white/10" />
                        Permitir recomendações gerais
                    </label>
                    <button
                        type="submit"
                        class="inline-flex items-center justify-center gap-2 rounded-2xl bg-emerald-500 px-4 py-3 text-sm font-semibold text-emerald-900 transition hover:bg-emerald-400 disabled:cursor-not-allowed disabled:opacity-70"
                        :disabled="!question || loading"
                    >
                        <PaperAirplaneIcon class="h-4 w-4" />
                        Enviar
                    </button>
                </div>
            </form>
            <p class="mt-3 text-[0.65rem] text-emerald-200/80">
                O retorno é educativo e não substitui diagnóstico, prescrição ou acompanhamento profissional.
            </p>
        </footer>
    </div>
</template>

<script setup>
import { ArrowPathIcon, PaperAirplaneIcon, SparklesIcon } from '@heroicons/vue/24/outline';
import { ref, onMounted, nextTick } from 'vue';
import { apiClient } from '@/bootstrap';
import { useUiStore } from '@/stores/ui';

const ui = useUiStore();

const messages = ref([]);
const question = ref('');
const medicationContext = ref('');
const allowRecommendations = ref(false);
const loading = ref(false);
const scrollContainer = ref(null);

function pushMessage(payload) {
    messages.value.push({
        id: crypto.randomUUID(),
        ...payload,
    });
    requestAnimationFrame(async () => {
        await nextTick();
        if (scrollContainer.value) {
            scrollContainer.value.scrollTop = scrollContainer.value.scrollHeight;
        }
    });
}

async function handleSubmit() {
    if (!question.value.trim()) return;
    const userMessage = question.value.trim();

    pushMessage({ role: 'user', content: userMessage });
    question.value = '';
    loading.value = true;

    try {
        const { data } = await apiClient.post('/assistant/query', {
            question: userMessage,
            medication_context: medicationContext.value || undefined,
            allow_recommendations: allowRecommendations.value,
        });

        pushMessage({
            role: 'assistant',
            content: sanitizeMarkdown(data.message),
            metadata: { medication: data.medication },
        });
    } catch (error) {
        const message = error.response?.data?.message ?? 'Não foi possível obter resposta agora.';
        ui.notify({ title: 'Assistente indisponível', message, tone: 'error' });
    } finally {
        loading.value = false;
    }
}

function sanitizeMarkdown(text) {
    if (!text) return '';

    return text
        .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
        .replace(/\*(.*?)\*/g, '<em>$1</em>')
        .replace(/`([^`]+)`/g, '<code class="rounded bg-black/40 px-1 py-0.5 text-xs">$1</code>')
        .replace(/\n/g, '<br />');
}

onMounted(() => {
    pushMessage({
        role: 'assistant',
        content:
            'Olá! Conte com o assistente para entender bulas, posologias e alertas. Informe sempre que possível o medicamento principal e descreva seus tratamentos em andamento.',
    });
});
</script>
