<template>
    <div class="min-h-screen bg-slate-950 text-slate-50 selection:bg-emerald-500/30">
        <RouterView v-slot="{ Component, route }">
            <component :is="resolveLayout(route.meta.layout)">
                <Suspense>
                    <component :is="Component" />
                </Suspense>
            </component>
        </RouterView>

        <NotificationCenter />
    </div>
</template>

<script setup>
import { RouterView } from 'vue-router';
import { markRaw } from 'vue';
import NotificationCenter from '@/components/feedback/NotificationCenter.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import AuthLayout from '@/layouts/AuthLayout.vue';
import AdminLayout from '@/layouts/AdminLayout.vue';

const layouts = {
    app: markRaw(AppLayout),
    auth: markRaw(AuthLayout),
    admin: markRaw(AdminLayout),
};

function resolveLayout(layout) {
    return layouts[layout] ?? layouts.app;
}
</script>
