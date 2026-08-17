<script setup>
import { onMounted, reactive, ref } from 'vue';
import api from '../services/api';

const charges = ref([]);
const loading = ref(false);
const payingId = ref(null);
const error = ref('');
const currentPage = ref(1);
const lastPage = ref(1);

const filters = reactive({
    status: '',
    payment_method: '',
});

async function loadCharges(page = 1) {
    loading.value = true;
    error.value = '';

    try {
        const response = await api.get('/charges', {
            params: {
                page,
                status: filters.status || undefined,
                payment_method:
                    filters.payment_method || undefined,
            },
        });

        charges.value = response.data.data;
        currentPage.value = response.data.current_page;
        lastPage.value = response.data.last_page;
    } catch (exception) {
        error.value =
            exception.response?.data?.message
            ?? 'Não foi possível carregar as cobranças.';
    } finally {
        loading.value = false;
    }
}

async function markAsPaid(charge) {
    if (! window.confirm(
        `Confirmar o pagamento da cobrança #${charge.id}?`
    )) {
        return;
    }

    payingId.value = charge.id;
    error.value = '';

    try {
        await api.patch(`/charges/${charge.id}/pay`);

        await loadCharges(currentPage.value);
    } catch (exception) {
        error.value =
            exception.response?.data?.message
            ?? 'Não foi possível registrar o pagamento.';
    } finally {
        payingId.value = null;
    }
}

function clearFilters() {
    filters.status = '';
    filters.payment_method = '';

    loadCharges(1);
}

function formatMoney(value) {
    return new Intl.NumberFormat('pt-BR', {
        style: 'currency',
        currency: 'BRL',
    }).format(Number(value));
}

function formatDate(value) {
    const [year, month, day] = value
        .slice(0, 10)
        .split('-');

    return `${day}/${month}/${year}`;
}

function methodLabel(method) {
    return {
        boleto: 'Boleto',
        card: 'Cartão',
        pix: 'Pix',
    }[method] ?? method;
}

onMounted(() => loadCharges());
</script>

<template>
    <main class="page">
        <header class="page-header">
            <div>
                <h1>Cobranças</h1>
                <p>
                    Cobranças abertas e vencidas aparecem primeiro.
                </p>
            </div>
        </header>

        <form
            class="filters"
            @submit.prevent="loadCharges(1)"
        >
            <div>
                <label for="status">Situação</label>
                <select
                    id="status"
                    v-model="filters.status"
                >
                    <option value="">Todas</option>
                    <option value="open">Aberta</option>
                    <option value="paid">Paga</option>
                </select>
            </div>

            <div>
                <label for="method">Forma de pagamento</label>
                <select
                    id="method"
                    v-model="filters.payment_method"
                >
                    <option value="">Todas</option>
                    <option value="boleto">Boleto</option>
                    <option value="card">Cartão</option>
                    <option value="pix">Pix</option>
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
            Carregando cobranças...
        </p>

        <div
            v-else
            class="table-wrapper"
        >
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Cliente</th>
                        <th>Vencimento</th>
                        <th>Forma</th>
                        <th>Valor</th>
                        <th>Multa</th>
                        <th>Total</th>
                        <th>Situação</th>
                        <th>Ação</th>
                    </tr>
                </thead>

                <tbody>
                    <tr
                        v-for="charge in charges"
                        :key="charge.id"
                        :class="{ overdue: charge.is_overdue }"
                    >
                        <td>#{{ charge.id }}</td>
                        <td>
                            {{ charge.contract.client.name }}
                        </td>
                        <td>{{ formatDate(charge.due_date) }}</td>
                        <td>
                            {{ methodLabel(charge.payment_method) }}
                        </td>
                        <td>{{ formatMoney(charge.amount) }}</td>
                        <td>
                            {{ formatMoney(charge.penalty_amount) }}
                        </td>
                        <td>
                            <strong>
                                {{ formatMoney(charge.total_amount) }}
                            </strong>
                        </td>
                        <td>
                            <span
                                v-if="charge.is_overdue"
                                class="status late"
                            >
                                Em atraso
                            </span>

                            <span
                                v-else
                                class="status"
                                :class="charge.status"
                            >
                                {{
                                    charge.status === 'paid'
                                        ? 'Paga'
                                        : 'Aberta'
                                }}
                            </span>
                        </td>
                        <td>
                            <button
                                v-if="charge.status === 'open'"
                                class="pay"
                                type="button"
                                :disabled="payingId === charge.id"
                                @click="markAsPaid(charge)"
                            >
                                {{
                                    payingId === charge.id
                                        ? 'Salvando...'
                                        : 'Marcar como paga'
                                }}
                            </button>

                            <span v-else>—</span>
                        </td>
                    </tr>

                    <tr v-if="charges.length === 0">
                        <td
                            colspan="9"
                            class="empty"
                        >
                            Nenhuma cobrança encontrada.
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
                @click="loadCharges(currentPage - 1)"
            >
                Anterior
            </button>

            <span>
                Página {{ currentPage }} de {{ lastPage }}
            </span>

            <button
                type="button"
                :disabled="currentPage === lastPage"
                @click="loadCharges(currentPage + 1)"
            >
                Próxima
            </button>
        </footer>
    </main>
</template>

<style scoped>
.page {
    max-width: 1380px;
    margin: 0 auto;
    padding: 32px 24px;
}

h1 {
    margin: 0 0 8px;
}

p {
    margin: 0;
    color: #64748b;
}

.filters {
    display: flex;
    align-items: end;
    gap: 16px;
    margin: 28px 0;
    padding: 20px;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    background: #fff;
}

.filters > div:not(.actions) {
    min-width: 220px;
    display: grid;
    gap: 6px;
}

label {
    color: #334155;
    font-size: 14px;
    font-weight: 700;
}

select {
    min-height: 42px;
    padding: 8px 10px;
    border: 1px solid #cbd5e1;
    border-radius: 8px;
    background: #fff;
}

.actions {
    display: flex;
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
    padding: 13px 14px;
    border-bottom: 1px solid #e2e8f0;
    text-align: left;
    white-space: nowrap;
}

th {
    background: #f8fafc;
    color: #475569;
    font-size: 12px;
    text-transform: uppercase;
}

tr.overdue {
    background: #fff7ed;
}

.status {
    display: inline-block;
    padding: 5px 9px;
    border-radius: 999px;
    font-size: 13px;
    font-weight: 700;
}

.status.open {
    background: #dbeafe;
    color: #1d4ed8;
}

.status.paid {
    background: #dcfce7;
    color: #166534;
}

.status.late {
    background: #ffedd5;
    color: #c2410c;
}

.pay {
    border: 0;
    background: #16a34a;
    color: #fff;
}

.message,
.empty {
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

@media (max-width: 700px) {
    .filters {
        align-items: stretch;
        flex-direction: column;
    }

    .filters > div:not(.actions) {
        min-width: 0;
    }
}
</style>