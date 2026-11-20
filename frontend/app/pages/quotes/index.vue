<script setup lang="ts">
import { Plus, Copy, FileDown, Pencil, Search } from 'lucide-vue-next'
import type { PaginatedResponse, Quote } from '~/types'

const api = useApi()
const { formatMoney, formatDate, quoteStatusMeta } = useFormatters()

const quotes = ref<Quote[]>([])
const filterStatus = ref('')
const search = ref('')
const busyId = ref<number | null>(null)

async function loadQuotes() {
  const res = await api.get<PaginatedResponse<Quote>>('/quotes', {
    status: filterStatus.value || undefined,
    search: search.value || undefined,
    per_page: 50,
  })
  quotes.value = res.data
}

async function duplicateQuote(quote: Quote) {
  busyId.value = quote.id
  try {
    const res = await api.post<{ data: Quote }>(`/quotes/${quote.id}/duplicate`)
    await navigateTo(`/quotes/${res.data.id}`)
  } finally {
    busyId.value = null
  }
}

async function downloadPdf(quote: Quote) {
  busyId.value = quote.id
  try {
    await api.downloadPdf(`/quotes/${quote.id}/pdf`, `${quote.quote_number}-v${quote.version}.pdf`)
  } finally {
    busyId.value = null
  }
}

watchDebounced([filterStatus, search], loadQuotes, { debounce: 250 })
onMounted(loadQuotes)
</script>

<template>
  <div class="space-y-5">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
      <div>
        <h2 class="text-2xl font-bold text-slate-900 tracking-tight">Cotizaciones Técnicas</h2>
        <p class="text-sm text-slate-500">Historial, control de versiones (v1, v2) y emisión de PDF corporativo.</p>
      </div>
      <NuxtLink
        to="/quotes/create"
        class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-lg bg-cyan-600 hover:bg-cyan-700 text-white text-xs font-semibold"
      >
        <Plus class="w-4 h-4" /> Nueva Cotización
      </NuxtLink>
    </div>

    <div class="bg-white rounded-[6px] border border-slate-200 p-4 flex flex-wrap gap-3 items-center justify-between">
      <div class="relative flex-1 min-w-[220px] max-w-md">
        <Search class="w-4 h-4 text-slate-400 absolute left-3 top-2.5" />
        <input
          v-model="search"
          type="text"
          placeholder="Buscar por número COT o cliente..."
          class="w-full pl-9 pr-3 py-2 rounded-lg border border-slate-300 text-sm"
        >
      </div>

      <div class="flex flex-wrap gap-1.5">
        <button
          v-for="opt in [
            { value: '', label: 'Todas' },
            { value: 'borrador', label: 'Borrador' },
            { value: 'enviada', label: 'Enviada' },
            { value: 'aprobada', label: 'Aprobada' },
            { value: 'rechazada', label: 'Rechazada' },
          ]"
          :key="opt.value"
          type="button"
          class="px-3 py-1.5 rounded-lg text-xs font-semibold border transition-colors"
          :class="filterStatus === opt.value ? 'bg-slate-900 text-white border-slate-900' : 'bg-white text-slate-600 border-slate-200 hover:bg-slate-50'"
          @click="filterStatus = opt.value"
        >
          {{ opt.label }}
        </button>
      </div>
    </div>

    <div class="tq-panel overflow-x-auto">
      <table class="w-full text-sm">
        <thead class="bg-slate-50 border-b border-slate-200">
          <tr class="text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
            <th class="px-4 py-3">Número / Versión</th>
            <th class="px-4 py-3">Cliente</th>
            <th class="px-4 py-3">Emisión</th>
            <th class="px-4 py-3">Estado</th>
            <th class="px-4 py-3 text-right">Total</th>
            <th class="px-4 py-3 text-right">Acciones</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <tr v-for="q in quotes" :key="q.id" class="hover:bg-slate-50">
            <td class="px-4 py-3 font-bold text-slate-900 whitespace-nowrap">
              {{ q.quote_number }}
              <span class="ml-1.5 px-2 py-0.5 text-[11px] font-bold rounded bg-cyan-50 text-cyan-800 border border-cyan-200">
                v{{ q.version }}
              </span>
            </td>
            <td class="px-4 py-3">
              <div class="font-medium text-slate-900">{{ q.client?.business_name }}</div>
              <div v-if="q.lead" class="text-xs text-slate-400">{{ q.lead.code }} · {{ q.lead.title }}</div>
            </td>
            <td class="px-4 py-3 text-slate-600 whitespace-nowrap">{{ formatDate(q.issue_date) }}</td>
            <td class="px-4 py-3">
              <span
                class="inline-flex px-2.5 py-0.5 text-xs font-semibold rounded-full border"
                :class="quoteStatusMeta[q.status]?.badgeClass"
              >
                {{ quoteStatusMeta[q.status]?.label }}
              </span>
            </td>
            <td class="px-4 py-3 text-right font-bold text-slate-900 whitespace-nowrap">
              {{ formatMoney(q.total, q.currency) }}
            </td>
            <td class="px-4 py-3 text-right whitespace-nowrap">
              <div class="inline-flex items-center gap-1">
                <NuxtLink
                  :to="`/quotes/${q.id}`"
                  class="p-1.5 rounded-lg text-slate-500 hover:text-cyan-700 hover:bg-cyan-50"
                  title="Editar"
                >
                  <Pencil class="w-4 h-4" />
                </NuxtLink>
                <button
                  type="button"
                  :disabled="busyId === q.id"
                  class="p-1.5 rounded-lg text-slate-500 hover:text-amber-600 hover:bg-amber-50"
                  title="Duplicar Versión"
                  @click="duplicateQuote(q)"
                >
                  <Copy class="w-4 h-4" />
                </button>
                <button
                  type="button"
                  :disabled="busyId === q.id"
                  class="p-1.5 rounded-lg text-slate-500 hover:text-emerald-600 hover:bg-emerald-50"
                  title="Descargar PDF"
                  @click="downloadPdf(q)"
                >
                  <FileDown class="w-4 h-4" />
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>
