<template>
    <div class="space-y-6">
        <header>
            <h1 class="text-2xl font-semibold text-white">Configurações gerais</h1>
            <p class="mt-1 text-sm text-slate-400">
                Ajuste integrações (OpenAI, Redis, notificações) e parâmetros do aplicativo web e mobile.
            </p>
        </header>

        <div class="grid gap-4 lg:grid-cols-2">
            <section class="rounded-3xl border border-white/5 bg-white/5 p-6 backdrop-blur">
                <h2 class="text-sm uppercase tracking-[0.3em] text-emerald-400">Chaves e integrações</h2>
                <p class="mt-3 text-xs text-slate-400">
                    A tela administrativa permitirá atualizar as variáveis OPENAI_* diretamente via painel. A implementação no backend
                    ainda não está disponível; até lá, mantenha as chaves no arquivo .env.
                </p>
            </section>

            <section class="rounded-3xl border border-white/5 bg-white/5 p-6 backdrop-blur">
                <h2 class="text-sm uppercase tracking-[0.3em] text-emerald-400">Notificações e alertas</h2>
                <p class="mt-3 text-xs text-slate-400">
                    Futuramente configure gatilhos de e-mail/SMS/WhatsApp utilizando o telefone dos usuários já armazenado no cadastro.
                </p>
            </section>
        </div>

        <EmptyState icon="Cog6ToothIcon" title="Funcionalidades administrativas em construção">
            Assim que definirmos os endpoints de gerenciamento, esta página passa a controlar parâmetros críticos como limites de tokens,
            envio de alertas e auditoria de dados semicriticos.
        </EmptyState>
    </div>
</template>

<script setup>
import EmptyState from '@/components/feedback/EmptyState.vue';
import { Cog6ToothIcon } from '@heroicons/vue/24/outline';
</script>
