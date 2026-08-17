<script setup>
import { reactive, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import api from '../services/api';

const route = useRoute();
const router = useRouter();

const form = reactive({
    email: '',
    password: '',
});

const loading = ref(false);
const error = ref('');

async function submit() {
    loading.value = true;
    error.value = '';

    try {
        const response = await api.post('/login', form);

        sessionStorage.setItem(
            'auth_token',
            response.data.token
        );

        sessionStorage.setItem(
            'auth_user',
            JSON.stringify(response.data.user)
        );

        const destination =
            typeof route.query.redirect === 'string'
                ? route.query.redirect
                : '/clients';

        await router.replace(destination);
    } catch (exception) {
        error.value =
            exception.response?.data?.message
            ?? 'Não foi possível realizar o login.';
    } finally {
        loading.value = false;
    }
}
</script>

<template>
    <main class="login-page">
        <section class="login-card">
            <header>
                <span class="brand">Amar Assist</span>
                <h1>Sistema de cobranças</h1>
                <p>Entre com sua conta administrativa.</p>
            </header>

            <form @submit.prevent="submit">
                <label for="email">E-mail</label>
                <input
                    id="email"
                    v-model.trim="form.email"
                    type="email"
                    autocomplete="username"
                    required
                >

                <label for="password">Senha</label>
                <input
                    id="password"
                    v-model="form.password"
                    type="password"
                    autocomplete="current-password"
                    minlength="8"
                    required
                >

                <p
                    v-if="error"
                    class="error"
                    role="alert"
                >
                    {{ error }}
                </p>

                <button
                    type="submit"
                    :disabled="loading"
                >
                    {{ loading ? 'Entrando...' : 'Entrar' }}
                </button>
            </form>
        </section>
    </main>
</template>

<style scoped>
.login-page {
    min-height: 100vh;
    display: grid;
    place-items: center;
    padding: 24px;
    background: #f1f5f9;
    font-family: Arial, sans-serif;
}

.login-card {
    width: min(100%, 420px);
    padding: 32px;
    border-radius: 16px;
    background: #fff;
    box-shadow: 0 20px 45px rgb(15 23 42 / 12%);
}

.brand {
    color: #2563eb;
    font-weight: 700;
}

h1 {
    margin-bottom: 8px;
    color: #0f172a;
}

p {
    color: #64748b;
}

form {
    display: grid;
    gap: 12px;
    margin-top: 28px;
}

label {
    color: #334155;
    font-weight: 600;
}

input {
    padding: 12px;
    border: 1px solid #cbd5e1;
    border-radius: 8px;
    font: inherit;
}

input:focus {
    border-color: #2563eb;
    outline: 3px solid rgb(37 99 235 / 15%);
}

button {
    margin-top: 12px;
    padding: 12px;
    border: 0;
    border-radius: 8px;
    background: #2563eb;
    color: #fff;
    font: inherit;
    font-weight: 700;
    cursor: pointer;
}

button:disabled {
    cursor: wait;
    opacity: 0.65;
}

.error {
    margin: 0;
    color: #b91c1c;
}
</style>