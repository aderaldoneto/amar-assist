<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import api from '../services/api';

const contracts = ref([]);
const clients = ref([]);
const loading = ref(false);
const loadingClients = ref(false);
const saving = ref(false);
const showCreateForm = ref(false);
const error = ref('');
const success = ref('');
const validationErrors = ref({});
const currentPage = ref(1);
const lastPage = ref(1);
const total = ref(0);

const contractForm = reactive({
    client_id: '',
    billing_day: 10,
});

const selectedClient = computed(() => {
    return clients.value.find(
        (client) => client.id === Number(contractForm.client_id)
    );
});

const contractType = computed(() => {
    if (! selectedClient.value) {
        return '';
    }

    return selectedClient.value.document.length === 11
        ? 'PF'
        : 'PJ';
});

async function loadContracts(page = 1) {
    loading.value = true;
    error.value = '';

    try {
        const response = await api.get('/contracts', {
            params: { page },
        });

        contracts.value = response.data.data;
        currentPage.value = response.data.current_page;
        lastPage.value = response.data.last_page;
        total.value = response.data.total;
    } catch (exception) {
        error.value =
            exception.response?.data?.message
            ?? 'Não foi possível carregar os contratos.';
    } finally {
        loading.value = false;
    }
}

async function loadClients() {
    loadingClients.value = true;

    try {
        const response = await api.get('/clients', {
            params: {
                status: 'active',
                per_page: 100,
            },
        });

        clients.value = response.data.data;
    } catch (exception) {
        error.value =
            exception.response?.data?.message
            ?? 'Não foi possível carregar os clientes.';
    } finally {
        loadingClients.value = false;
    }
}

function resetForm() {
    contractForm.client_id = '';
    contractForm.billing_day = 10;
    validationErrors.value = {};
}

function toggleCreateForm() {
    showCreateForm.value = ! showCreateForm.value;
    error.value = '';
    success.value = '';

    if (! showCreateForm.value) {
        resetForm();
    }
}

async function createContract() {
    saving.value = true;
    error.value = '';
    success.value = '';
    validationErrors.value = {};

    try {
        await api.post('/contracts', {
            client_id: contractForm.client_id,
            type: contractType.value,
            billing_day: contractForm.billing_day,
        });

        resetForm();
        showCreateForm.value = false;
        await Promise.all([
            loadContracts(1),
            loadClients(),
        ]);
        success.value = 'Contrato cadastrado com sucesso.';
    } catch (exception) {
        validationErrors.value =
            exception.response?.data?.errors ?? {};
        error.value =
            exception.response?.data?.message
            ?? 'Não foi possível cadastrar o contrato.';
    } finally {
        saving.value = false;
    }
}

function formatDocument(document) {
    if (document.length === 11) {
        return document.replace(
            /(\d{3})(\d{3})(\d{3})(\d{2})/,
            '$1.$2.$3-$4'
        );
    }

    return document.replace(
        /(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/,
        '$1.$2.$3/$4-$5'
    );
}

onMounted(() => {
    loadContracts();
    loadClients();
});
</script>

<template>
    <main class="page">
        <header class="page-header">
            <div>
                <h1>Contratos</h1>
                <p>Consulte contratos e associe um novo contrato a um cliente.</p>
            </div>

            <div class="header-actions">
                <span class="total">{{ total }} contrato(s)</span>
                <button class="primary" type="button" @click="toggleCreateForm">
                    {{ showCreateForm ? 'Cancelar cadastro' : 'Novo contrato' }}
                </button>
            </div>
        </header>

        <form
            v-if="showCreateForm"
            class="contract-form"
            @submit.prevent="createContract"
        >
            <div>
                <h2>Novo contrato</h2>
                <p>Selecione o cliente e informe o ciclo de vencimento.</p>
            </div>

            <div class="form-grid">
                <div class="field field-wide">
                    <label for="contract-client">Cliente</label>
                    <select
                        id="contract-client"
                        v-model="contractForm.client_id"
                        :disabled="loadingClients"
                        required
                    >
                        <option value="" disabled>
                            {{ loadingClients ? 'Carregando clientes...' : 'Selecione um cliente ativo' }}
                        </option>
                        <option
                            v-for="client in clients"
                            :key="client.id"
                            :value="client.id"
                        >
                            {{ client.name }} — {{ formatDocument(client.document) }}
                        </option>
                    </select>
                    <small v-if="validationErrors.client_id" class="field-error">
                        {{ validationErrors.client_id[0] }}
                    </small>
                    <small v-else-if="! loadingClients && clients.length === 0">
                        Cadastre um cliente ativo antes de criar um contrato.
                    </small>
                </div>

                <div class="field">
                    <label for="contract-type">Tipo</label>
                    <input
                        id="contract-type"
                        :value="contractType"
                        type="text"
                        placeholder="Definido pelo CPF/CNPJ"
                        readonly
                    >
                    <small v-if="validationErrors.type" class="field-error">
                        {{ validationErrors.type[0] }}
                    </small>
                </div>

                <div class="field">
                    <label for="billing-day">Dia do vencimento</label>
                    <input
                        id="billing-day"
                        v-model.number="contractForm.billing_day"
                        type="number"
                        min="1"
                        max="31"
                        required
                    >
                    <small v-if="validationErrors.billing_day" class="field-error">
                        {{ validationErrors.billing_day[0] }}
                    </small>
                </div>
            </div>

            <div class="form-actions">
                <button class="secondary" type="button" :disabled="saving" @click="toggleCreateForm">
                    Cancelar
                </button>
                <button class="primary" type="submit" :disabled="saving || clients.length === 0">
                    {{ saving ? 'Salvando...' : 'Salvar contrato' }}
                </button>
            </div>
        </form>

        <p v-if="error" class="message error" role="alert">{{ error }}</p>
        <p v-if="success" class="message success" role="status">{{ success }}</p>
        <p v-if="loading" class="message">Carregando contratos...</p>

        <div v-else class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Cliente</th>
                        <th>CPF/CNPJ</th>
                        <th>Tipo</th>
                        <th>Ciclo</th>
                        <th>Cobranças</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="contract in contracts" :key="contract.id">
                        <td>#{{ contract.id }}</td>
                        <td>{{ contract.client.name }}</td>
                        <td>{{ formatDocument(contract.client.document) }}</td>
                        <td><span class="type">{{ contract.type }}</span></td>
                        <td>Dia {{ contract.billing_day }}</td>
                        <td>{{ contract.charges_count }}</td>
                    </tr>
                    <tr v-if="contracts.length === 0">
                        <td colspan="6" class="empty">Nenhum contrato encontrado.</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <footer v-if="lastPage > 1" class="pagination">
            <button
                type="button"
                :disabled="currentPage === 1"
                @click="loadContracts(currentPage - 1)"
            >
                Anterior
            </button>
            <span>Página {{ currentPage }} de {{ lastPage }}</span>
            <button
                type="button"
                :disabled="currentPage === lastPage"
                @click="loadContracts(currentPage + 1)"
            >
                Próxima
            </button>
        </footer>
    </main>
</template>

<style scoped>
.page { max-width: 1180px; margin: 0 auto; padding: 32px 24px; }
.page-header, .header-actions { display: flex; justify-content: space-between; align-items: start; gap: 16px; }
h1, h2 { margin: 0 0 8px; }
p { margin: 0; color: #64748b; }
.header-actions { align-items: center; }
.total { padding: 8px 12px; border-radius: 999px; background: #dbeafe; color: #1d4ed8; font-weight: 700; }
.contract-form { margin: 28px 0; padding: 24px; border: 1px solid #bfdbfe; border-radius: 12px; background: #eff6ff; }
.form-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 16px; margin-top: 20px; }
.field { display: grid; gap: 6px; }
.field-wide { grid-column: 1 / -1; }
label { color: #334155; font-size: 14px; font-weight: 700; }
input, select { min-height: 42px; padding: 8px 10px; border: 1px solid #cbd5e1; border-radius: 8px; background: #fff; }
input[readonly] { background: #f8fafc; color: #475569; }
.field-error, .error { color: #b91c1c; }
.success { color: #166534; }
.form-actions { display: flex; justify-content: flex-end; gap: 8px; margin-top: 20px; }
button { min-height: 42px; padding: 8px 14px; border-radius: 8px; cursor: pointer; }
.primary { border: 0; background: #2563eb; color: #fff; }
.secondary, .pagination button { border: 1px solid #cbd5e1; background: #fff; color: #334155; }
button:disabled { cursor: not-allowed; opacity: .5; }
.message, .empty { padding: 32px; text-align: center; }
.table-wrapper { overflow-x: auto; margin-top: 28px; border: 1px solid #e2e8f0; border-radius: 12px; background: #fff; }
table { width: 100%; border-collapse: collapse; }
th, td { padding: 14px 16px; border-bottom: 1px solid #e2e8f0; text-align: left; }
th { background: #f8fafc; color: #475569; font-size: 12px; text-transform: uppercase; }
.type { display: inline-block; padding: 5px 9px; border-radius: 999px; background: #e0e7ff; color: #3730a3; font-weight: 700; }
.pagination { display: flex; justify-content: center; align-items: center; gap: 16px; margin-top: 20px; }
@media (max-width: 700px) {
    .page-header, .header-actions { align-items: stretch; flex-direction: column; }
    .form-grid { grid-template-columns: 1fr; }
}
</style>
