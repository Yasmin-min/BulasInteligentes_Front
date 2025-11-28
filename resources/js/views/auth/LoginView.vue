<template>
    <form class="space-y-6" @submit.prevent="handleSubmit">
        <div>
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
            <div class="mb-2 flex items-center justify-between text-sm font-medium text-slate-200">
                <label for="password">Senha</label>
                <button type="button" class="text-emerald-300 hover:text-emerald-200" @click="togglePassword">
                    {{ showPassword ? 'Ocultar' : 'Mostrar' }}
                </button>
            </div>
            <input
                id="password"
                v-model="form.password"
                :type="showPassword ? 'text' : 'password'"
                required
                autocomplete="current-password"
                class="w-full rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm text-white placeholder:text-slate-500 focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-400/40"
            />
        </div>

        <button
            type="submit"
            class="flex w-full items-center justify-center gap-2 rounded-xl bg-emerald-500 px-4 py-3 text-sm font-semibold text-emerald-950 transition hover:bg-emerald-400 disabled:cursor-not-allowed disabled:opacity-70"
            :disabled="auth.loading"
        >
            <ArrowRightIcon class="h-4 w-4" />
            Entrar
        </button>

        <p class="text-center text-xs text-slate-400">
            Não possui acesso?
            <RouterLink to="/register" class="font-semibold text-emerald-300 hover:text-emerald-200">crie sua conta</RouterLink>
        </p>
    </form>
</template>

<script setup>
import { reactive, ref } from 'vue';
import { ArrowRightIcon } from '@heroicons/vue/24/outline';
import { useAuthStore } from '@/stores/auth';
import { useUiStore } from '@/stores/ui';
import { useRoute, useRouter, RouterLink } from 'vue-router';

const auth = useAuthStore();
const ui = useUiStore();
const router = useRouter();
const route = useRoute();

const showPassword = ref(false);
const form = reactive({
    email: '',
    password: '',
    device_name: 'web-app',
});

function togglePassword() {
    showPassword.value = !showPassword.value;
}

async function handleSubmit() {
    try {
        await auth.login(form);
        const redirectTo = route.query.redirect ?? '/';
        router.replace(redirectTo);
    } catch (error) {
        const message = error.response?.data?.message ?? 'Não foi possível entrar. Verifique suas credenciais.';
        ui.notify({ title: 'Erro no login', message, tone: 'error' });
    }
}
</script>
