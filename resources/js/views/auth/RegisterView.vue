<template>
    <form class="space-y-6" @submit.prevent="handleSubmit">
        <div class="grid gap-4 sm:grid-cols-2">
            <div class="sm:col-span-2">
                <label class="mb-2 block text-sm font-medium text-slate-200" for="name">Nome completo</label>
                <input
                    id="name"
                    v-model="form.name"
                    type="text"
                    required
                    autocomplete="name"
                    class="w-full rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm text-white placeholder:text-slate-500 focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-400/40"
                />
            </div>

            <div class="sm:col-span-2">
                <label class="mb-2 block text-sm font-medium text-slate-200" for="email">E-mail</label>
                <input
                    id="email"
                    v-model="form.email"
                    type="email"
                    required
                    autocomplete="email"
                    class="w-full rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm text-white placeholder:text-slate-500 focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-400/40"
                />
            </div>

            <div>
                <label class="mb-2 block text-sm font-medium text-slate-200" for="phone">Celular (opcional)</label>
                <input
                    id="phone"
                    v-model="form.phone"
                    type="tel"
                    autocomplete="tel"
                    placeholder="+55 (00) 00000-0000"
                    class="w-full rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm text-white placeholder:text-slate-500 focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-400/40"
                />
            </div>

            <div>
                <label class="mb-2 block text-sm font-medium text-slate-200" for="device_name">Dispositivo</label>
                <input
                    id="device_name"
                    v-model="form.device_name"
                    type="text"
                    readonly
                    class="w-full cursor-not-allowed rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm text-slate-400"
                />
            </div>

            <div>
                <label class="mb-2 block text-sm font-medium text-slate-200" for="password">Senha</label>
                <input
                    id="password"
                    v-model="form.password"
                    type="password"
                    required
                    minlength="8"
                    autocomplete="new-password"
                    class="w-full rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm text-white placeholder:text-slate-500 focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-400/40"
                />
            </div>

            <div>
                <label class="mb-2 block text-sm font-medium text-slate-200" for="password_confirmation">Confirme a senha</label>
                <input
                    id="password_confirmation"
                    v-model="form.password_confirmation"
                    type="password"
                    required
                    minlength="8"
                    autocomplete="new-password"
                    class="w-full rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm text-white placeholder:text-slate-500 focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-400/40"
                />
            </div>
        </div>

        <button
            type="submit"
            class="flex w-full items-center justify-center gap-2 rounded-xl bg-emerald-500 px-4 py-3 text-sm font-semibold text-emerald-950 transition hover:bg-emerald-400 disabled:cursor-not-allowed disabled:opacity-70"
            :disabled="auth.loading"
        >
            <CheckIcon class="h-4 w-4" />
            Cadastrar
        </button>

        <p class="text-center text-xs text-slate-400">
            Já possui cadastro?
            <RouterLink to="/login" class="font-semibold text-emerald-300 hover:text-emerald-200">entrar</RouterLink>
        </p>
    </form>
</template>

<script setup>
import { reactive } from 'vue';
import { CheckIcon } from '@heroicons/vue/24/outline';
import { useAuthStore } from '@/stores/auth';
import { useUiStore } from '@/stores/ui';
import { useRouter, RouterLink } from 'vue-router';

const auth = useAuthStore();
const ui = useUiStore();
const router = useRouter();

const form = reactive({
    name: '',
    email: '',
    phone: '',
    password: '',
    password_confirmation: '',
    device_name: 'web-app',
});

async function handleSubmit() {
    try {
        await auth.register(form);
        router.replace('/');
    } catch (error) {
        const errors = error.response?.data?.errors;
        if (errors) {
            Object.values(errors).flat().forEach((message) => {
                ui.notify({ title: 'Não foi possível cadastrar', message, tone: 'error' });
            });
        } else {
            const message = error.response?.data?.message ?? 'Erro inesperado ao criar conta.';
            ui.notify({ title: 'Não foi possível cadastrar', message, tone: 'error' });
        }
    }
}
</script>
