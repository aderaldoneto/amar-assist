<script setup>
import { onMounted, reactive, ref } from 'vue';
import api from '../services/api';

const clients = ref([]);
const loading = ref(false);
const error = ref('');
const currentPage = ref(1);
const lastPage = ref(1);
const total = ref(0);

const filters = reactive({
    name: '',
    document: '',
    status: '',
});

async function loadClients(page = 1) {
    loading.value = true;
    error.value = '';

    try {
        const response = await api.get('/clients', {
            params: {
                page,
                name: filters.name || undefined,
                document: filters.document || undefined,
                status: filters.status || undefined,
            },
        });

        clients.value = response.data.data;
        currentPage.value = response.data.current_page;
        lastPage.value = response.data.last_page;
        total.value = response.data.total;
    } catch (exception) {
        error.value =
            exception.response?.data?.message
            ?? 'Não foi possível carregar os clientes.';
    } finally {
        loading.value = false;
    }
}

function search() {
    loadClients(1);
}

function clearFilters() {
    filters.name = '';
    filters.document = '';
    filters.status = '';

    loadClients(1);
}

function formatDocument(document) {
    if (document.length === 11) {
        return document.replace(
            /(\d{3})(\d{3})(\d{3})(\d{2})/,
            '$1.$2.$3-$4'
        );
    }

    if (document.length === 14) {
        return document.replace(
            /(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/,
            '$1.$2.$3/$4-$5'
        );
    }

    return document;
}

onMounted(() => loadClients());
</script>

<template>
    <main class="page">
        <header class="page-header">
            <div>
                <h1>Clientes</h1>
                <p>
                    Consulte clientes por nome, CPF/CNPJ
                    e situação.
                </p>
            </div>

            <span class="total">
                {{ total }} cliente(s)
            </span>
        </header>

        <form
            class="filters"
            @submit.prevent="search"
        >
            <div>
                <label for="name">Nome</label>
                <input
                    id="name"
                    v-model.trim="filters.name"
                    type="search"
                    placeholder="Nome do cliente"
                >
            </div>

            <div>
                <label for="document">CPF/CNPJ</label>
                <input
                    id="document"
                    v-model.trim="filters.document"
                    type="search"
                    placeholder="Somente números ou formatado"
                >
            </div>

            <div>
                <label for="status">Situação</label>
                <select
                    id="status"
                    v-model="filters.status"
                >
                    <option value="">Todas</option>
                    <option value="active">Ativo</option>
                    <option value="inactive">Inativo</option>
                </select>
            </div>

            <div class="actions">
                <button
                    class="primary"
                    type="submit"
                >
                    Consultar
                </button>

                <button
                    class="secondary"
                    type="button"
                    @click="clearFilters"
                >
                    Limpar
                </button>
            </div>
        </form>

        <p
            v-if="error"
            class="message error"
            role="alert"
        >
            {{ error }}
        </p>

        <p
            v-if="loading"
            class="message"
        >
            Carregando clientes...
        </p>

        <div
            v-else
            class="table-wrapper"
        >
            <table>
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>CPF/CNPJ</th>
                        <th>Contato</th>
                        <th>Contratos</th>
                        <th>Situação</th>
                    </tr>
                </thead>

                <tbody>
                    <tr
                        v-for="client in clients"
                        :key="client.id"
                    >
                        <td>{{ client.name }}</td>
                        <td>
                            {{ formatDocument(client.document) }}
                        </td>
                        <td>{{ client.contact }}</td>
                        <td>{{ client.contracts_count }}</td>
                        <td>
                            <span
                                class="status"
                                :class="client.status"
                            >
                                {{
                                    client.status === 'active'
                                        ? 'Ativo'
                                        : 'Inativo'
                                }}
                            </span>
                        </td>
                    </tr>

                    <tr v-if="clients.length === 0">
                        <td
                            colspan="5"
                            class="empty"
                        >
                            Nenhum cliente encontrado.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <footer
            v-if="lastPage > 1"
            class="pagination"
        >
            <button
                type="button"
                :disabled="currentPage === 1"
                @click="loadClients(currentPage - 1)"
            >
                Anterior
            </button>

            <span>
                Página {{ currentPage }} de {{ lastPage }}
            </span>

            <button
                type="button"
                :disabled="currentPage === lastPage"
                @click="loadClients(currentPage + 1)"
            >
                Próxima
            </button>
        </footer>
    </main>
</template>

<style scoped>
.page {
    max-width: 1180px;
    margin: 0 auto;
    padding: 32px 24px;
}

.page-header {
    display: flex;
    justify-content: space-between;
    align-items: start;
    gap: 24px;
}

h1 {
    margin: 0 0 8px;
}

p {
    margin: 0;
    color: #64748b;
}

.total {
    padding: 8px 12px;
    border-radius: 999px;
    background: #dbeafe;
    color: #1d4ed8;
    font-weight: 700;
}

.filters {
    display: grid;
    grid-template-columns: 2fr 1.5fr 1fr auto;
    gap: 16px;
    margin: 28px 0;
    padding: 20px;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    background: #fff;
}

.filters div:not(.actions) {
    display: grid;
    gap: 6px;
}

label {
    color: #334155;
    font-size: 14px;
    font-weight: 700;
}

input,
select {
    min-height: 42px;
    padding: 8px 10px;
    border: 1px solid #cbd5e1;
    border-radius: 8px;
    background: #fff;
}

.actions {
    display: flex;
    align-items: end;
    gap: 8px;
}

button {
    min-height: 42px;
    padding: 8px 14px;
    border-radius: 8px;
    cursor: pointer;
}

.primary {
    border: 0;
    background: #2563eb;
    color: #fff;
}

.secondary,
.pagination button {
    border: 1px solid #cbd5e1;
    background: #fff;
    color: #334155;
}

.table-wrapper {
    overflow-x: auto;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    background: #fff;
}

table {
    width: 100%;
    border-collapse: collapse;
}

th,
td {
    padding: 14px 16px;
    border-bottom: 1px solid #e2e8f0;
    text-align: left;
}

th {
    background: #f8fafc;
    color: #475569;
    font-size: 13px;
    text-transform: uppercase;
}

.status {
    display: inline-block;
    padding: 5px 9px;
    border-radius: 999px;
    font-size: 13px;
    font-weight: 700;
}

.status.active {
    background: #dcfce7;
    color: #166534;
}

.status.inactive {
    background: #fee2e2;
    color: #991b1b;
}

.empty,
.message {
    padding: 32px;
    text-align: center;
}

.error {
    color: #b91c1c;
}

.pagination {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 16px;
    margin-top: 20px;
}

button:disabled {
    cursor: not-allowed;
    opacity: 0.5;
}

@media (max-width: 800px) {
    .filters {
        grid-template-columns: 1fr;
    }

    .actions {
        align-items: center;
    }
}
</style>