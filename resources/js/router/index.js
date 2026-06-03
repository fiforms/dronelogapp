import { createRouter, createWebHistory } from 'vue-router';
import { useAuthStore } from '../stores/auth';

const routes = [
    { path: '/login',                  component: () => import('../views/LoginView.vue'),              meta: { public: true } },
    { path: '/',                       component: () => import('../views/DashboardView.vue'),           meta: { auth: true } },
    { path: '/flights',                component: () => import('../views/FlightListView.vue'),          meta: { auth: true } },
    { path: '/flights/start',          component: () => import('../views/FlightStartView.vue'),         meta: { auth: true } },
    { path: '/flights/:id/active',     component: () => import('../views/FlightActiveView.vue'),        meta: { auth: true } },
    { path: '/flights/:id/end',        component: () => import('../views/FlightEndView.vue'),           meta: { auth: true } },
    { path: '/flights/:id',            component: () => import('../views/FlightDetailView.vue'),        meta: { auth: true } },
    { path: '/drones',                 component: () => import('../views/DronesView.vue'),              meta: { auth: true } },
    { path: '/batteries',              component: () => import('../views/BatteriesView.vue'),           meta: { auth: true } },
    { path: '/accessories',            component: () => import('../views/AccessoriesView.vue'),         meta: { auth: true } },
    { path: '/checklists',             component: () => import('../views/ChecklistTemplatesView.vue'),  meta: { auth: true } },
    { path: '/:pathMatch(.*)*',        redirect: '/' },
];

const router = createRouter({
    history: createWebHistory(),
    routes,
    scrollBehavior: () => ({ top: 0 }),
});

router.beforeEach(async (to) => {
    const auth = useAuthStore();

    if (!auth.user && !auth.loading) {
        await auth.fetchUser();
    }

    if (to.meta.auth && !auth.isAuthenticated) {
        return { path: '/login' };
    }

    if (to.path === '/login' && auth.isAuthenticated) {
        return { path: '/' };
    }
});

export default router;
