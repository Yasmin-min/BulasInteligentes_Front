<template>
    <div class="space-y-6">
        <header class="flex flex-col gap-2 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <h1 class="text-2xl font-semibold text-white">Gerenciamento de usuários</h1>
                <p class="text-sm text-slate-400">
                    Central que permitirá acompanhar acessos, perfis e permissões administrativas.
                </p>
            </div>
            <button
                type="button"
                class="inline-flex items-center gap-2 rounded-xl border border-white/10 bg-white/5 px-4 py-2 text-xs text-slate-200 transition hover:border-emerald-400/40 hover:text-emerald-200"
            >
                <UserPlusIcon class="h-4 w-4" />
                Convidar usuário
            </button>
        </header>

        <EmptyState icon="UsersIcon" title="Em breve">
            A API ainda não expõe endpoints administrativos de usuários. Esta tela foi estruturada para receber a listagem,
            filtros e ações (suspender, promover a admin) assim que forem disponibilizados.
        </EmptyState>
    </div>
</template>

<script setup>
import EmptyState from '@/components/feedback/EmptyState.vue';
import { UserPlusIcon, UsersIcon } from '@heroicons/vue/24/outline';
</script>
