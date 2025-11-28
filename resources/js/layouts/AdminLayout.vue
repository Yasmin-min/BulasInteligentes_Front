<template>
    <div class="flex min-h-screen bg-slate-950 text-slate-100">
        <aside class="hidden w-72 flex-shrink-0 border-r border-white/5 bg-slate-950/90 px-4 py-6 backdrop-blur-xl xl:flex">
            <nav class="flex h-full w-full flex-col justify-between">
                <div class="space-y-4 text-sm font-medium">
                    <RouterLink to="/admin" class="flex items-center gap-3 px-2 text-lg font-semibold">
                        <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-500/20 text-emerald-400">
                            <BuildingOfficeIcon class="h-5 w-5" />
                        </span>
                        Console Administrativa
                    </RouterLink>

                    <div class="space-y-1 pt-2">
                        <NavLink to="/admin" icon="PresentationChartLineIcon" label="Visão geral" />
                        <NavLink to="/admin/users" icon="UsersIcon" label="Usuários" />
                        <NavLink to="/admin/medications" icon="BeakerIcon" label="Medicamentos" />
                        <NavLink to="/admin/settings" icon="Cog6ToothIcon" label="Configurações" />
                        <RouterLink
                            to="/"
                            class="mt-6 flex items-center gap-2 rounded-xl bg-white/5 px-3 py-2 text-xs font-semibold text-emerald-200 transition hover:bg-white/10"
                        >
                            <ArrowLeftIcon class="h-4 w-4" />
                            Voltar ao aplicativo
                        </RouterLink>
                    </div>
                </div>
            </nav>
        </aside>

        <div class="flex flex-1 flex-col">
            <header class="sticky top-0 z-50 border-b border-white/5 bg-slate-950/80 backdrop-blur">
                <div class="flex items-center justify-between px-4 py-4 xl:px-8">
                    <div>
                        <h1 class="text-lg font-semibold text-white">
                            {{ route.meta?.title ?? 'Administração' }}
                        </h1>
                        <p class="text-xs text-slate-400">
                            Monitore métricas, gerencie usuários e mantenha o catálogo sempre atualizado.
                        </p>
                    </div>
                    <button
                        class="inline-flex items-center gap-2 rounded-xl border border-white/10 bg-white/5 px-3 py-2 text-sm text-slate-300 transition hover:border-rose-400/40 hover:text-rose-200"
                        @click="auth.logout"
                    >
                        <ArrowRightOnRectangleIcon class="h-4 w-4" />
                        Sair
                    </button>
                </div>
            </header>

            <main class="flex-1 px-4 py-6 xl:px-8">
                <slot />
            </main>
        </div>
    </div>
</template>

<script setup>
import {
    ArrowLeftIcon,
    ArrowRightOnRectangleIcon,
    BeakerIcon,
    BuildingOfficeIcon,
    Cog6ToothIcon,
    PresentationChartLineIcon,
    UsersIcon,
} from '@heroicons/vue/24/outline';
import { RouterLink, useRoute } from 'vue-router';
import NavLink from '@/components/navigation/NavLink.vue';
import { useAuthStore } from '@/stores/auth';

const route = useRoute();
const auth = useAuthStore();
</script>
