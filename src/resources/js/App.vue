<script setup>
import { computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import api from './services/api';

const route = useRoute();
const router = useRouter();

const authenticated = computed(() => {
    route.fullPath;

    return Boolean(
        sessionStorage.getItem('auth_token')
    );
});

const showNavigation = computed(() => {
    return authenticated.value && route.name !== 'login';
});

async function logout() {
    try {
        await api.post('/logout');
    } finally {
        sessionStorage.removeItem('auth_token');
        sessionStorage.removeItem('auth_user');

        await router.replace('/login');
    }
}
</script>

<template>
    <div class="application">
        <header
            v-if="showNavigation"
            class="topbar"
        >
            <RouterLink
                class="logo"
                to="/clients"
            >
                Amar Assist
            </RouterLink>

            <nav aria-label="Navegação principal">
                <RouterLink to="/clients">
                    Clientes
                </RouterLink>

                <RouterLink to="/charges">
                    Cobranças
                </RouterLink>
            </nav>

            <button
                class="logout"
                type="button"
                @click="logout"
            >
                Sair
            </button>
        </header>

        <RouterView />
    </div>
</template>

<style>
* {
    box-sizing: border-box;
}

html {
    color: #0f172a;
    background: #f8fafc;
    font-family: Arial, sans-serif;
}

body {
    margin: 0;
}

button,
input,
select {
    font: inherit;
}

.topbar {
    min-height: 64px;
    display: flex;
    align-items: center;
    gap: 32px;
    padding: 0 32px;
    background: #0f172a;
    color: #fff;
}

.logo {
    color: #fff;
    font-size: 20px;
    font-weight: 700;
    text-decoration: none;
}

nav {
    display: flex;
    flex: 1;
    gap: 8px;
}

nav a {
    padding: 10px 14px;
    border-radius: 8px;
    color: #cbd5e1;
    text-decoration: none;
}

nav a.router-link-active {
    background: #1e293b;
    color: #fff;
}

.logout {
    padding: 9px 14px;
    border: 1px solid #475569;
    border-radius: 8px;
    background: transparent;
    color: #fff;
    cursor: pointer;
}

@media (max-width: 640px) {
    .topbar {
        flex-wrap: wrap;
        gap: 8px;
        padding: 12px 16px;
    }

    nav {
        order: 3;
        width: 100%;
    }
}
</style>