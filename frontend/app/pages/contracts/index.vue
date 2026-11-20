<template>
  <div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
      <div>
        <h1 class="text-2xl font-bold text-slate-900">Contratos &amp; Soporte SLA</h1>
        <p class="text-sm text-slate-500">Pólizas de mantenimiento preventivo y mesa de ayuda técnica</p>
      </div>
      <div class="flex items-center gap-2">
        <button
          @click="openTicketModal()"
          class="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
        >
          <LifeBuoy class="h-4 w-4 text-cyan-700" />
          Nuevo Ticket SLA
        </button>
        <button
          @click="showContractModal = true"
          class="inline-flex items-center gap-2 rounded-lg bg-cyan-600 px-4 py-2 text-sm font-medium text-white hover:bg-cyan-700"
        >
          <Plus class="h-4 w-4" />
          Nuevo Contrato
        </button>
      </div>
    </div>

    <!-- Tabs -->
    <div class="border-b border-slate-200">
      <nav class="-mb-px flex space-x-8">
        <button
          @click="activeTab = 'contracts'"
          :class="[
            activeTab === 'contracts'
              ? 'border-cyan-600 text-cyan-700'
              : 'border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-700',
            'whitespace-nowrap border-b-2 px-1 py-3 text-sm font-medium'
          ]"
        >
          Contratos de Mantenimiento ({{ contracts.length }})
        </button>
        <button
          @click="activeTab = 'tickets'"
          :class="[
            activeTab === 'tickets'
              ? 'border-cyan-600 text-cyan-700'
              : 'border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-700',
            'whitespace-nowrap border-b-2 px-1 py-3 text-sm font-medium'
          ]"
        >
          Tickets SLA ({{ tickets.length }})
        </button>
      </nav>
    </div>

    <div v-if="loading" class="flex justify-center py-12">
      <div class="h-8 w-8 animate-spin rounded-full border-4 border-cyan-600 border-t-transparent"></div>
    </div>

    <!-- Contracts Tab -->
    <div v-else-if="activeTab === 'contracts'" class="overflow-hidden tq-panel">
      <table class="min-w-full divide-y divide-slate-200">
        <thead class="bg-slate-50">
          <tr>
            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Código</th>
            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Cliente / Servicio</th>
            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Frecuencia</th>
            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">SLA</th>
            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Vigencia</th>
            <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">Fee Mensual</th>
            <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-slate-500">Estado</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-200 bg-white">
          <tr v-for="c in contracts" :key="c.id" class="hover:bg-slate-50">
            <td class="whitespace-nowrap px-4 py-3 text-sm font-semibold text-cyan-700">{{ c.contract_number }}</td>
            <td class="px-4 py-3">
              <div class="text-sm font-medium text-slate-900">{{ c.title }}</div>
              <div class="text-xs text-slate-500">{{ c.client?.business_name }}</div>
            </td>
            <td class="whitespace-nowrap px-4 py-3 text-sm capitalize text-slate-700">{{ c.frequency }}</td>
            <td class="whitespace-nowrap px-4 py-3 text-sm text-slate-700">{{ c.sla_hours }}h</td>
            <td class="whitespace-nowrap px-4 py-3 text-xs text-slate-500">
              {{ formatDate(c.start_date) }} - {{ formatDate(c.end_date) }}
            </td>
            <td class="whitespace-nowrap px-4 py-3 text-right text-sm font-semibold text-slate-900">
              {{ formatMoney(c.monthly_fee, c.currency) }}
            </td>
            <td class="whitespace-nowrap px-4 py-3 text-center">
              <span
                class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium capitalize"
                :class="c.status === 'activo' ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-700'"
              >
                {{ c.status }}
              </span>
            </td>
          </tr>
          <tr v-if="contracts.length === 0">
            <td colspan="7" class="px-4 py-10 text-center text-sm text-slate-500">No hay contratos registrados.</td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Tickets Tab -->
    <div v-else class="overflow-hidden tq-panel">
      <table class="min-w-full divide-y divide-slate-200">
        <thead class="bg-slate-50">
          <tr>
            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Ticket</th>
            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Asunto / Cliente</th>
            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Prioridad</th>
            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Vencimiento SLA</th>
            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Estado</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-200 bg-white">
          <tr v-for="t in tickets" :key="t.id" class="hover:bg-slate-50">
            <td class="whitespace-nowrap px-4 py-3 text-sm font-semibold text-cyan-700">{{ t.ticket_number }}</td>
            <td class="px-4 py-3">
              <div class="text-sm font-medium text-slate-900">{{ t.subject }}</div>
              <div class="text-xs text-slate-500">
                {{ t.client?.business_name }}
                <span v-if="t.contract"> · {{ t.contract.contract_number }}</span>
              </div>
            </td>
            <td class="whitespace-nowrap px-4 py-3">
              <span
                class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold capitalize"
                :class="priorityClass(t.priority)"
              >
                {{ t.priority }}
              </span>
            </td>
            <td class="whitespace-nowrap px-4 py-3 text-xs text-slate-600">
              {{ t.due_at ? formatDateTime(t.due_at) : '-' }}
            </td>
            <td class="whitespace-nowrap px-4 py-3">
              <select
                :value="t.status"
                @change="updateTicketStatus(t, ($event.target as HTMLSelectElement).value as SlaTicket['status'])"
                class="rounded-lg border border-slate-300 bg-white px-2.5 py-1 text-xs font-medium text-slate-700"
              >
                <option value="abierto">Abierto</option>
                <option value="en_proceso">En Proceso</option>
                <option value="resuelto">Resuelto</option>
                <option value="cerrado">Cerrado</option>
              </select>
            </td>
          </tr>
          <tr v-if="tickets.length === 0">
            <td colspan="5" class="px-4 py-10 text-center text-sm text-slate-500">No hay tickets SLA registrados.</td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Modal Nuevo Contrato -->
    <div v-if="showContractModal" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4">
      <div class="w-full max-w-lg rounded-[6px] bg-white p-6 shadow-xl">
        <h3 class="text-lg font-semibold text-slate-900">Nuevo Contrato de Mantenimiento</h3>
        <form @submit.prevent="createContract" class="mt-4 space-y-4">
          <div>
            <label class="block text-xs font-medium text-slate-700">Cliente *</label>
            <select v-model="contractForm.client_id" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
              <option value="" disabled>Seleccionar cliente...</option>
              <option v-for="cl in clients" :key="cl.id" :value="cl.id">{{ cl.business_name }}</option>
            </select>
          </div>
          <div>
            <label class="block text-xs font-medium text-slate-700">Título del Servicio *</label>
            <input v-model="contractForm.title" required type="text" placeholder="Ej. Póliza Mantenimiento CCTV 24/7" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" />
          </div>
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-xs font-medium text-slate-700">Frecuencia</label>
              <select v-model="contractForm.frequency" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                <option value="mensual">Mensual</option>
                <option value="trimestral">Trimestral</option>
                <option value="semestral">Semestral</option>
                <option value="anual">Anual</option>
              </select>
            </div>
            <div>
              <label class="block text-xs font-medium text-slate-700">SLA (horas)</label>
              <input v-model.number="contractForm.sla_hours" required type="number" min="1" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" />
            </div>
          </div>
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-xs font-medium text-slate-700">Moneda</label>
              <select v-model="contractForm.currency" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                <option value="PEN">PEN (S/)</option>
                <option value="USD">USD ($)</option>
              </select>
            </div>
            <div>
              <label class="block text-xs font-medium text-slate-700">Fee Mensual *</label>
              <input v-model.number="contractForm.monthly_fee" required type="number" step="0.01" min="0" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" />
            </div>
          </div>
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-xs font-medium text-slate-700">Inicio *</label>
              <input v-model="contractForm.start_date" required type="date" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" />
            </div>
            <div>
              <label class="block text-xs font-medium text-slate-700">Fin *</label>
              <input v-model="contractForm.end_date" required type="date" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" />
            </div>
          </div>
          <div class="flex justify-end gap-2 pt-2">
            <button type="button" @click="showContractModal = false" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700">Cancelar</button>
            <button type="submit" :disabled="saving" class="rounded-lg bg-cyan-600 px-4 py-2 text-sm font-medium text-white hover:bg-cyan-700 disabled:opacity-50">
              {{ saving ? 'Guardando...' : 'Crear Contrato' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Modal Nuevo Ticket -->
    <div v-if="showTicketModal" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4">
      <div class="w-full max-w-lg rounded-[6px] bg-white p-6 shadow-xl">
        <h3 class="text-lg font-semibold text-slate-900">Nuevo Ticket de Soporte SLA</h3>
        <form @submit.prevent="createTicket" class="mt-4 space-y-4">
          <div>
            <label class="block text-xs font-medium text-slate-700">Cliente *</label>
            <select v-model="ticketForm.client_id" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
              <option value="" disabled>Seleccionar cliente...</option>
              <option v-for="cl in clients" :key="cl.id" :value="cl.id">{{ cl.business_name }}</option>
            </select>
          </div>
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-xs font-medium text-slate-700">Contrato (Opcional)</label>
              <select v-model="ticketForm.contract_id" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                <option :value="null">Sin contrato vinculado</option>
                <option v-for="c in contracts" :key="c.id" :value="c.id">{{ c.contract_number }} - {{ c.title }}</option>
              </select>
            </div>
            <div>
              <label class="block text-xs font-medium text-slate-700">Prioridad</label>
              <select v-model="ticketForm.priority" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                <option value="baja">Baja</option>
                <option value="media">Media</option>
                <option value="alta">Alta</option>
                <option value="critica">Crítica</option>
              </select>
            </div>
          </div>
          <div>
            <label class="block text-xs font-medium text-slate-700">Asunto *</label>
            <input v-model="ticketForm.subject" required type="text" placeholder="Ej. NVR sin grabación en sede principal" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" />
          </div>
          <div>
            <label class="block text-xs font-medium text-slate-700">Descripción</label>
            <textarea v-model="ticketForm.description" rows="3" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"></textarea>
          </div>
          <div class="flex justify-end gap-2 pt-2">
            <button type="button" @click="showTicketModal = false" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700">Cancelar</button>
            <button type="submit" :disabled="saving" class="rounded-lg bg-cyan-600 px-4 py-2 text-sm font-medium text-white hover:bg-cyan-700 disabled:opacity-50">
              {{ saving ? 'Registrando...' : 'Registrar Ticket' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { LifeBuoy, Plus } from 'lucide-vue-next'
import type { Client, MaintenanceContract, SlaTicket } from '~/types'

const api = useApi()
const { formatMoney, formatDate, formatDateTime } = useFormatters()

const activeTab = ref<'contracts' | 'tickets'>('contracts')
const loading = ref(true)
const saving = ref(false)
const contracts = ref<MaintenanceContract[]>([])
const tickets = ref<SlaTicket[]>([])
const clients = ref<Client[]>([])

const showContractModal = ref(false)
const showTicketModal = ref(false)

const today = new Date().toISOString().slice(0, 10)
const nextYear = new Date(Date.now() + 365 * 86400000).toISOString().slice(0, 10)

const contractForm = reactive({
  client_id: '' as number | '',
  title: '',
  frequency: 'mensual' as MaintenanceContract['frequency'],
  monthly_fee: 850,
  currency: 'PEN' as 'PEN' | 'USD',
  sla_hours: 8,
  start_date: today,
  end_date: nextYear,
})

const ticketForm = reactive({
  client_id: '' as number | '',
  contract_id: null as number | null,
  priority: 'media' as SlaTicket['priority'],
  subject: '',
  description: '',
})

function priorityClass(p: SlaTicket['priority']) {
  const map: Record<SlaTicket['priority'], string> = {
    baja: 'bg-slate-100 text-slate-700',
    media: 'bg-sky-100 text-sky-700',
    alta: 'bg-amber-100 text-amber-700',
    critica: 'bg-rose-100 text-rose-700',
  }
  return map[p]
}

async function fetchData() {
  loading.value = true
  try {
    const [cRes, tRes, clRes] = await Promise.all([
      api.get<{ data: MaintenanceContract[] }>('/maintenance-contracts'),
      api.get<{ data: SlaTicket[] }>('/sla-tickets'),
      api.get<{ data: Client[] }>('/clients?per_page=100'),
    ])
    contracts.value = cRes.data
    tickets.value = tRes.data
    clients.value = clRes.data
  } finally {
    loading.value = false
  }
}

function openTicketModal() {
  ticketForm.client_id = ''
  ticketForm.contract_id = null
  ticketForm.priority = 'media'
  ticketForm.subject = ''
  ticketForm.description = ''
  showTicketModal.value = true
}

async function createContract() {
  if (!contractForm.client_id) return
  saving.value = true
  try {
    await api.post('/maintenance-contracts', contractForm)
    showContractModal.value = false
    contractForm.title = ''
    await fetchData()
  } finally {
    saving.value = false
  }
}

async function createTicket() {
  if (!ticketForm.client_id) return
  saving.value = true
  try {
    await api.post('/sla-tickets', ticketForm)
    showTicketModal.value = false
    activeTab.value = 'tickets'
    await fetchData()
  } finally {
    saving.value = false
  }
}

async function updateTicketStatus(ticket: SlaTicket, status: SlaTicket['status']) {
  await api.put(`/sla-tickets/${ticket.id}`, { status })
  ticket.status = status
}

onMounted(fetchData)
</script>
