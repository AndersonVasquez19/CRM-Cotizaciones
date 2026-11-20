<template>
  <div class="space-y-6">
    <div>
      <h1 class="text-2xl font-bold text-slate-900">Finanzas &amp; Control de Cobros</h1>
      <p class="text-sm text-slate-500">Control interno de hitos de pago, adelantos y saldos pendientes</p>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
      <div class="rounded-[6px] border border-slate-200 bg-white p-5">
        <p class="text-xs font-medium uppercase tracking-wider text-slate-500">Total Comprometido</p>
        <p class="mt-2 text-2xl font-bold text-slate-900">{{ formatMoney(summary.total_receivable, 'PEN') }}</p>
      </div>
      <div class="rounded-[6px] border border-slate-200 bg-white p-5">
        <p class="text-xs font-medium uppercase tracking-wider text-emerald-600">Total Cobrado</p>
        <p class="mt-2 text-2xl font-bold text-emerald-600">{{ formatMoney(summary.total_collected, 'PEN') }}</p>
      </div>
      <div class="rounded-[6px] border border-slate-200 bg-white p-5">
        <p class="text-xs font-medium uppercase tracking-wider text-amber-600">Saldo Pendiente</p>
        <p class="mt-2 text-2xl font-bold text-amber-600">{{ formatMoney(summary.total_pending, 'PEN') }}</p>
      </div>
    </div>

    <div v-if="loading" class="flex justify-center py-12">
      <div class="h-8 w-8 animate-spin rounded-full border-4 border-cyan-600 border-t-transparent"></div>
    </div>

    <div v-else class="overflow-hidden tq-panel">
      <table class="min-w-full divide-y divide-slate-200">
        <thead class="bg-slate-50">
          <tr>
            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Hito / Concepto</th>
            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Proyecto / Cotización</th>
            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Vencimiento</th>
            <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">Monto</th>
            <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">Cobrado</th>
            <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-slate-500">Estado</th>
            <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">Acción</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-200 bg-white">
          <tr v-for="m in milestones" :key="m.id" class="hover:bg-slate-50">
            <td class="px-4 py-3">
              <div class="text-sm font-medium text-slate-900">{{ m.title }}</div>
              <div class="text-xs text-slate-500">{{ m.client?.business_name }}</div>
            </td>
            <td class="whitespace-nowrap px-4 py-3 text-xs text-slate-600">
              <div v-if="m.project" class="font-medium text-cyan-700">{{ m.project.code }}</div>
              <div v-if="m.quote">{{ m.quote.quote_number }}</div>
            </td>
            <td class="whitespace-nowrap px-4 py-3 text-xs text-slate-600">
              {{ m.due_date ? formatDate(m.due_date) : '-' }}
            </td>
            <td class="whitespace-nowrap px-4 py-3 text-right text-sm font-semibold text-slate-900">
              {{ formatMoney(m.amount, m.currency) }}
            </td>
            <td class="whitespace-nowrap px-4 py-3 text-right text-sm font-medium text-emerald-600">
              {{ formatMoney(paidAmount(m), m.currency) }}
            </td>
            <td class="whitespace-nowrap px-4 py-3 text-center">
              <span
                class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium capitalize"
                :class="statusBadge(m.status)"
              >
                {{ m.status }}
              </span>
            </td>
            <td class="whitespace-nowrap px-4 py-3 text-right">
              <button
                v-if="m.status !== 'pagado'"
                @click="openPaymentModal(m)"
                class="rounded-lg bg-cyan-50 px-3 py-1.5 text-xs font-semibold text-cyan-800 hover:bg-cyan-100"
              >
                Registrar Abono
              </button>
              <span v-else class="text-xs text-slate-400">Liquidado</span>
            </td>
          </tr>
          <tr v-if="milestones.length === 0">
            <td colspan="7" class="px-4 py-10 text-center text-sm text-slate-500">
              No hay hitos de cobro registrados. Se generan automáticamente al aprobar cotizaciones o crear proyectos.
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Modal Registrar Abono -->
    <div v-if="selectedMilestone" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4">
      <div class="w-full max-w-md rounded-[6px] bg-white p-6 shadow-xl">
        <h3 class="text-lg font-semibold text-slate-900">Registrar Abono</h3>
        <p class="mt-1 text-xs text-slate-500">{{ selectedMilestone.title }} · {{ selectedMilestone.client?.business_name }}</p>
        <form @submit.prevent="submitPayment" class="mt-4 space-y-4">
          <div>
            <label class="block text-xs font-medium text-slate-700">Monto ({{ selectedMilestone.currency }}) *</label>
            <input v-model.number="paymentForm.amount" required type="number" step="0.01" min="0.01" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" />
          </div>
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-xs font-medium text-slate-700">Método de Pago</label>
              <select v-model="paymentForm.payment_method" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                <option value="transferencia">Transferencia</option>
                <option value="deposito">Depósito</option>
                <option value="cheque">Cheque</option>
                <option value="tarjeta">Tarjeta</option>
                <option value="efectivo">Efectivo</option>
              </select>
            </div>
            <div>
              <label class="block text-xs font-medium text-slate-700">Fecha de Pago *</label>
              <input v-model="paymentForm.paid_at" required type="date" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" />
            </div>
          </div>
          <div>
            <label class="block text-xs font-medium text-slate-700">Nro. Operación / Referencia</label>
            <input v-model="paymentForm.reference_number" type="text" placeholder="Ej. OP-994821" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" />
          </div>
          <div>
            <label class="block text-xs font-medium text-slate-700">Notas</label>
            <textarea v-model="paymentForm.notes" rows="2" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"></textarea>
          </div>
          <div class="flex justify-end gap-2 pt-2">
            <button type="button" @click="selectedMilestone = null" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700">Cancelar</button>
            <button type="submit" :disabled="saving" class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700 disabled:opacity-50">
              {{ saving ? 'Registrando...' : 'Confirmar Pago' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import type { InvoiceMilestone, Payment } from '~/types'

const api = useApi()
const { formatMoney, formatDate } = useFormatters()

const loading = ref(true)
const saving = ref(false)
const milestones = ref<InvoiceMilestone[]>([])
const summary = ref({
  total_receivable: 0,
  total_collected: 0,
  total_pending: 0,
})

const selectedMilestone = ref<InvoiceMilestone | null>(null)
const paymentForm = reactive({
  amount: 0,
  payment_method: 'transferencia' as Payment['payment_method'],
  reference_number: '',
  paid_at: new Date().toISOString().slice(0, 10),
  notes: '',
})

function paidAmount(m: InvoiceMilestone): number {
  return (m.payments || []).reduce((acc, p) => acc + Number(p.amount), 0)
}

function statusBadge(status: InvoiceMilestone['status']) {
  const map: Record<InvoiceMilestone['status'], string> = {
    pendiente: 'bg-amber-100 text-amber-700',
    parcial: 'bg-sky-100 text-sky-700',
    pagado: 'bg-emerald-100 text-emerald-700',
    vencido: 'bg-rose-100 text-rose-700',
  }
  return map[status]
}

async function fetchFinance() {
  loading.value = true
  try {
    const res = await api.get<{
      summary: { total_receivable: number; total_collected: number; total_pending: number }
      data: InvoiceMilestone[]
    }>('/finance/milestones')
    summary.value = res.summary
    milestones.value = res.data
  } finally {
    loading.value = false
  }
}

function openPaymentModal(m: InvoiceMilestone) {
  selectedMilestone.value = m
  const remaining = Math.max(0, Number(m.amount) - paidAmount(m))
  paymentForm.amount = Number(remaining.toFixed(2))
  paymentForm.reference_number = ''
  paymentForm.notes = ''
}

async function submitPayment() {
  if (!selectedMilestone.value) return
  saving.value = true
  try {
    await api.post(`/finance/milestones/${selectedMilestone.value.id}/payments`, paymentForm)
    selectedMilestone.value = null
    await fetchFinance()
  } finally {
    saving.value = false
  }
}

onMounted(fetchFinance)
</script>
