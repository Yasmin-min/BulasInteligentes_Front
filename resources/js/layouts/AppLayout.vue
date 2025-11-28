<template>
    <div class="flex min-h-screen bg-slate-950 text-slate-100">
        <aside
            class="hidden w-64 flex-shrink-0 border-r border-white/5 bg-slate-950/80 px-4 py-6 backdrop-blur-xl lg:flex"
        >
            <nav class="flex w-full flex-col gap-6 text-sm font-medium">
                <RouterLink to="/" class="flex items-center gap-3 px-2 text-lg font-semibold">
                    <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-emerald-500/20 text-emerald-400">
                        <BeakerIcon class="h-5 w-5" />
                    </span>
                    Bulas Inteligentes
                </RouterLink>

                <div class="space-y-1">
                    <p class="px-2 text-xs uppercase tracking-widest text-slate-400">Aplicativo</p>
                    <NavLink to="/" icon="HomeIcon" label="Painel" exact />
                    <NavLink to="/medications" icon="MagnifyingGlassIcon" label="Consultar medicamento" />
                    <NavLink to="/assistant" icon="ChatBubbleLeftRightIcon" label="Assistente" />
                    <NavLink to="/profile/allergies" icon="ShieldExclamationIcon" label="Alergias" />
                    <NavLink to="/profile/treatments" icon="HeartIcon" label="Tratamentos" />
                    <NavLink to="/plans" icon="ClockIcon" label="Planos de tratamento" />
                    <NavLink to="/prescriptions" icon="DocumentArrowUpIcon" label="Receitas" />
                </div>

                <div v-if="auth.isAdmin" class="space-y-1">
                    <p class="px-2 text-xs uppercase tracking-widest text-slate-400">Administração</p>
                    <NavLink to="/admin" icon="BuildingOfficeIcon" label="Dashboard" />
                    <NavLink to="/admin/users" icon="UsersIcon" label="Usuários" />
                    <NavLink to="/admin/medications" icon="BeakerIcon" label="Medicamentos" />
                    <NavLink to="/admin/settings" icon="AdjustmentsHorizontalIcon" label="Configurações" />
                </div>
            </nav>
        </aside>

        <div class="flex min-h-screen flex-1 flex-col">
            <header class="sticky top-0 z-40 border-b border-white/5 bg-slate-950/80 backdrop-blur">
                <div class="flex items-center gap-3 px-4 py-3 lg:px-8">
                    <button
                        class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-white/10 text-slate-300 transition hover:border-emerald-500/40 hover:text-emerald-300 lg:hidden"
                        @click="ui.toggleSidebar(true)"
                        aria-label="Abrir menu"
                    >
                        <Bars3Icon class="h-5 w-5" />
                    </button>

                    <div class="relative flex-1">
                        <MagnifyingGlassIcon class="pointer-events-none absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-500" />
                        <input
                            type="search"
                            class="h-11 w-full rounded-xl border border-white/10 bg-white/5 pl-11 pr-4 text-sm text-white placeholder:text-slate-500 focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-400/40"
                            placeholder="Busque por medicamentos, planos ou receitas..."
                            @keyup.enter="quickSearch"
                        />
                    </div>

                    <RouterLink
                        to="/assistant"
                        class="hidden items-center gap-2 rounded-xl border border-emerald-500/40 bg-emerald-500/10 px-4 py-2 text-sm font-semibold text-emerald-300 transition hover:bg-emerald-500/20 md:inline-flex"
                    >
                        <SparklesIcon class="h-4 w-4" />
                        Perguntar à IA
                    </RouterLink>

                    <Menu as="div" class="relative">
                        <MenuButton class="flex items-center gap-3 rounded-xl border border-white/10 bg-white/5 px-3 py-2 text-left hover:border-emerald-400/40">
                            <div class="flex h-10 w-10 items-center justify-center rounded-full bg-emerald-500/20 text-lg font-semibold text-emerald-300">
                                {{ avatarInitials }}
                            </div>
                            <div class="hidden text-sm md:block">
                                <p class="font-semibold">{{ auth.user?.name }}</p>
                                <p class="text-xs text-slate-400">{{ auth.user?.email }}</p>
                            </div>
                            <ChevronDownIcon class="ml-2 hidden h-4 w-4 text-slate-400 md:block" />
                        </MenuButton>
                        <Transition
                            enter-active-class="transition duration-150 ease-out"
                            enter-from-class="transform opacity-0 translate-y-1"
                            enter-to-class="transform opacity-100 translate-y-0"
                            leave-active-class="transition duration-100 ease-in"
                            leave-from-class="transform opacity-100 translate-y-0"
                            leave-to-class="transform opacity-0 translate-y-1"
                        >
                            <MenuItems
                                class="absolute right-0 mt-2 w-56 origin-top-right rounded-xl border border-white/10 bg-slate-900/90 p-2 text-sm shadow-lg shadow-emerald-500/10 focus:outline-none"
                            >
                                <MenuItem v-slot="{ active }">
                                    <RouterLink
                                        to="/profile/allergies"
                                        :class="[
                                            'flex items-center gap-2 rounded-lg px-3 py-2 transition',
                                            active ? 'bg-emerald-500/10 text-emerald-300' : 'text-slate-300',
                                        ]"
                                    >
                                        <ShieldExclamationIcon class="h-4 w-4" />
                                        Gerenciar alergias
                                    </RouterLink>
                                </MenuItem>
                                <MenuItem v-slot="{ active }">
                                    <RouterLink
                                        to="/profile/treatments"
                                        :class="[
                                            'flex items-center gap-2 rounded-lg px-3 py-2 transition',
                                            active ? 'bg-emerald-500/10 text-emerald-300' : 'text-slate-300',
                                        ]"
                                    >
                                        <HeartIcon class="h-4 w-4" />
                                        Tratamentos ativos
                                    </RouterLink>
                                </MenuItem>
                                <MenuItem v-slot="{ active }">
                                    <button
                                        type="button"
                                        :class="[
                                            'flex w-full items-center gap-2 rounded-lg px-3 py-2 transition',
                                            active ? 'bg-rose-500/10 text-rose-300' : 'text-slate-300',
                                        ]"
                                        @click="auth.logout"
                                    >
                                        <ArrowRightOnRectangleIcon class="h-4 w-4" />
                                        Encerrar sessão
                                    </button>
                                </MenuItem>
                            </MenuItems>
                        </Transition>
                    </Menu>
                </div>
            </header>

            <main class="flex-1 px-4 py-6 lg:px-8">
                <slot />
            </main>
        </div>

        <Transition name="drawer">
            <div
                v-if="ui.sidebarOpen"
                class="fixed inset-0 z-50 flex lg:hidden"
                role="dialog"
                aria-modal="true"
            >
                <div class="absolute inset-0 bg-black/60" @click="ui.toggleSidebar(false)"></div>
                <aside class="relative ml-auto flex w-72 flex-col border-l border-white/10 bg-slate-950/95 p-6 backdrop-blur">
                    <button
                        class="mb-6 inline-flex h-10 w-10 items-center justify-center rounded-xl border border-white/10 text-slate-300 transition hover:border-emerald-500/40 hover:text-emerald-300"
                        @click="ui.toggleSidebar(false)"
                        aria-label="Fechar menu"
                    >
                        <XMarkIcon class="h-5 w-5" />
                    </button>
                    <nav class="flex flex-1 flex-col gap-4 text-sm font-medium">
                        <NavLink to="/" icon="HomeIcon" label="Painel" exact />
                        <NavLink to="/medications" icon="MagnifyingGlassIcon" label="Consultar medicamento" />
                        <NavLink to="/assistant" icon="ChatBubbleLeftRightIcon" label="Assistente" />
                        <NavLink to="/profile/allergies" icon="ShieldExclamationIcon" label="Alergias" />
                        <NavLink to="/profile/treatments" icon="HeartIcon" label="Tratamentos" />
                        <NavLink to="/plans" icon="ClockIcon" label="Planos de tratamento" />
                        <NavLink to="/prescriptions" icon="DocumentArrowUpIcon" label="Receitas" />
                        <div v-if="auth.isAdmin" class="border-t border-white/10 pt-4">
                            <p class="mb-2 text-xs uppercase tracking-widest text-slate-500">Administração</p>
                            <NavLink to="/admin" icon="BuildingOfficeIcon" label="Dashboard" />
                            <NavLink to="/admin/users" icon="UsersIcon" label="Usuários" />
                            <NavLink to="/admin/medications" icon="BeakerIcon" label="Medicamentos" />
                            <NavLink to="/admin/settings" icon="AdjustmentsHorizontalIcon" label="Configurações" />
                        </div>
                    </nav>
                </aside>
            </div>
        </Transition>
    </div>
</template>

<script setup>
import {
    AdjustmentsHorizontalIcon,
    ArrowRightOnRectangleIcon,
    Bars3Icon,
    BeakerIcon,
    BuildingOfficeIcon,
    ChatBubbleLeftRightIcon,
    ChevronDownIcon,
    ClockIcon,
    DocumentArrowUpIcon,
    HeartIcon,
    HomeIcon,
    MagnifyingGlassIcon,
    ShieldExclamationIcon,
    SparklesIcon,
    UsersIcon,
    XMarkIcon,
} from '@heroicons/vue/24/outline';
import { Menu, MenuButton, MenuItem, MenuItems } from '@headlessui/vue';
import { computed } from 'vue';
import { RouterLink, useRouter } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import { useUiStore } from '@/stores/ui';
import NavLink from '@/components/navigation/NavLink.vue';

const auth = useAuthStore();
const ui = useUiStore();
const router = useRouter();

const avatarInitials = computed(() => {
    if (!auth.user?.name) return 'BI';
    return auth.user.name
        .split(' ')
        .slice(0, 2)
        .map((part) => part[0]?.toUpperCase())
        .join('');
});

function quickSearch(event) {
    const value = event.target.value.trim();
    if (!value) return;
    router.push({ name: 'medications.search', query: { q: value } });
    event.target.value = '';
}
</script>

<style scoped>
.drawer-enter-active,
.drawer-leave-active {
    transition: opacity 0.2s ease, transform 0.2s ease;
}

.drawer-enter-from,
.drawer-leave-to {
    opacity: 0;
    transform: translateX(10%);
}
</style>
