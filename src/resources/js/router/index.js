import {
    createRouter,
    createWebHistory,
} from 'vue-router';

import LoginView from '../views/LoginView.vue';
import ClientsView from '../views/ClientsView.vue';
import ChargesView from '../views/ChargesView.vue';

const router = createRouter({
    history: createWebHistory(),
    routes: [
        {
            path: '/',
            redirect: '/clients',
        },
        {
            path: '/login',
            name: 'login',
            component: LoginView,
            meta: {
                guestOnly: true,
            },
        },
        {
            path: '/clients',
            name: 'clients',
            component: ClientsView,
            meta: {
                requiresAuth: true,
            },
        },
        {
            path: '/charges',
            name: 'charges',
            component: ChargesView,
            meta: {
                requiresAuth: true,
            },
        },
    ],
});

router.beforeEach((to) => {
    const authenticated = Boolean(
        sessionStorage.getItem('auth_token')
    );

    if (to.meta.requiresAuth && ! authenticated) {
        return {
            name: 'login',
            query: {
                redirect: to.fullPath,
            },
        };
    }

    if (to.meta.guestOnly && authenticated) {
        return {
            name: 'clients',
        };
    }

    return true;
});

export default router;