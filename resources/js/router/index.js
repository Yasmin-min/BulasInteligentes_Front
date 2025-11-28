import { createRouter, createWebHistory } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import { useUiStore } from '@/stores/ui';

const routes = [
    {
        path: '/login',
        name: 'auth.login',
        component: () => import('@/views/auth/LoginView.vue'),
        meta: { layout: 'auth', guestOnly: true },
    },
    {
        path: '/register',
        name: 'auth.register',
        component: () => import('@/views/auth/RegisterView.vue'),
        meta: { layout: 'auth', guestOnly: true },
    },
    {
        path: '/',
        name: 'dashboard',
        component: () => import('@/views/dashboard/DashboardView.vue'),
        meta: { requiresAuth: true, layout: 'app' },
    },
    {
        path: '/medications',
        name: 'medications.search',
        component: () => import('@/views/medications/MedicationSearchView.vue'),
        meta: { requiresAuth: true, layout: 'app' },
    },
    {
        path: '/assistant',
        name: 'assistant.chat',
        component: () => import('@/views/assistant/AssistantChatView.vue'),
        meta: { requiresAuth: true, layout: 'app' },
    },
    {
        path: '/profile/allergies',
        name: 'profile.allergies',
        component: () => import('@/views/profile/AllergiesView.vue'),
        meta: { requiresAuth: true, layout: 'app' },
    },
    {
        path: '/profile/treatments',
        name: 'profile.treatments',
        component: () => import('@/views/profile/TreatmentsView.vue'),
        meta: { requiresAuth: true, layout: 'app' },
    },
    {
        path: '/plans',
        name: 'plans.list',
        component: () => import('@/views/plans/TreatmentPlansView.vue'),
        meta: { requiresAuth: true, layout: 'app' },
    },
    {
        path: '/plans/create',
        name: 'plans.create',
        component: () => import('@/views/plans/CreateTreatmentPlanView.vue'),
        meta: { requiresAuth: true, layout: 'app' },
    },
    {
        path: '/plans/:id',
        name: 'plans.detail',
        component: () => import('@/views/plans/TreatmentPlanDetailView.vue'),
        meta: { requiresAuth: true, layout: 'app' },
    },
    {
        path: '/prescriptions',
        name: 'prescriptions.uploads',
        component: () => import('@/views/prescriptions/PrescriptionUploadsView.vue'),
        meta: { requiresAuth: true, layout: 'app' },
    },
    {
        path: '/admin',
        name: 'admin.dashboard',
        component: () => import('@/views/admin/AdminDashboardView.vue'),
        meta: { requiresAuth: true, requiresAdmin: true, layout: 'admin' },
    },
    {
        path: '/admin/users',
        name: 'admin.users',
        component: () => import('@/views/admin/UsersView.vue'),
        meta: { requiresAuth: true, requiresAdmin: true, layout: 'admin' },
    },
    {
        path: '/admin/medications',
        name: 'admin.medications',
        component: () => import('@/views/admin/MedicationsAdminView.vue'),
        meta: { requiresAuth: true, requiresAdmin: true, layout: 'admin' },
    },
    {
        path: '/admin/settings',
        name: 'admin.settings',
        component: () => import('@/views/admin/SettingsView.vue'),
        meta: { requiresAuth: true, requiresAdmin: true, layout: 'admin' },
    },
    {
        path: '/:pathMatch(.*)*',
        name: 'not-found',
        component: () => import('@/views/errors/NotFoundView.vue'),
        meta: { layout: 'auth' },
    },
];

const router = createRouter({
    history: createWebHistory(),
    routes,
    scrollBehavior() {
        return { top: 0 };
    },
});

router.beforeEach(async (to, from, next) => {
    const auth = useAuthStore();

    if (!auth.user && auth.token && !auth.loading) {
        try {
            await auth.fetchUser();
        } catch (error) {
            // Handled in store
        }
    }

    if (to.meta.requiresAuth && !auth.isAuthenticated) {
        return next({
            name: 'auth.login',
            query: { redirect: to.fullPath },
        });
    }

    if (to.meta.guestOnly && auth.isAuthenticated) {
        return next({ name: 'dashboard' });
    }

    if (to.meta.requiresAdmin && !auth.isAdmin) {
        return next({ name: 'dashboard' });
    }

    return next();
});

router.afterEach(() => {
    const ui = useUiStore();
    ui.toggleSidebar(false);
});

export default router;
