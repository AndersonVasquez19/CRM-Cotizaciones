<template>
  <div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
      <div>
        <h1 class="text-2xl font-bold text-slate-900">Reportes Gerenciales &amp; BI</h1>
        <p class="text-sm text-slate-500">Análisis comercial, rentabilidad, líneas de servicio y exportación CSV</p>
      </div>
      <div class="flex flex-wrap items-center gap-2">
        <input v-model="filters.from" type="date" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm" />
        <input v-model="filters.to" type="date" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm" />
        <button
          @click="fetchReport"
          class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-800"
        >
          Filtrar
        </button>
        <button
          @click="exportCsv"
          class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700"
        >
          <Download class="h-4 w-4" />
          Exportar CSV
        </button>
      </div>
    </div>

    <div v-if="loading" class="flex justify-center py-12">
      <div class="h-8 w-8 animate-spin rounded-full border-4 border-cyan-600 border-t-transparent"></div>
    </div>

    <template v-else-if="report">
      <!-- KPIs -->
      <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-5">
        <div class="rounded-[6px] border border-slate-200 bg-white p-4">
          <p class="text-xs font-medium text-slate-500">Total Cotizado</p>
          <p class="mt-1 text-xl font-bold text-slate-900">{{ formatMoney(report.kpis.total_quoted, 'PEN') }}</p>
        </div>
        <div class="rounded-[6px] border border-slate-200 bg-white p-4">
          <p class="text-xs font-medium text-slate-500">Total Aprobado</p>
          <p class="mt-1 text-xl font-bold text-emerald-600">{{ formatMoney(report.kpis.total_approved, 'PEN') }}</p>
        </div>
        <div class="rounded-[6px] border border-slate-200 bg-white p-4">
          <p class="text-xs font-medium text-slate-500">Margen Bruto Prom.</p>
          <p class="mt-1 text-xl font-bold text-cyan-700">{{ report.kpis.avg_margin_percent }}%</p>
        </div>
        <div class="rounded-[6px] border border-slate-200 bg-white p-4">
          <p class="text-xs font-medium text-slate-500">Proyectos Activos</p>
          <p class="mt-1 text-xl font-bold text-slate-900">{{ report.kpis.projects_active }}</p>
        </div>
        <div class="rounded-[6px] border border-slate-200 bg-white p-4">
          <p class="text-xs font-medium text-slate-500">Proyectos Completados</p>
          <p class="mt-1 text-xl font-bold text-slate-900">{{ report.kpis.projects_completed }}</p>
        </div>
      </div>

      <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <!-- Por línea de servicio -->
        <div class="rounded-[6px] border border-slate-200 bg-white p-5">
          <h2 class="text-base font-semibold text-slate-900">Pipeline por Línea de Servicio</h2>
          <div class="mt-4 space-y-3">
            <div v-for="item in report.by_service_line" :key="item.service_line" class="flex items-center justify-between border-b border-slate-100 pb-2">
              <div>
                <p class="text-sm font-medium capitalize text-slate-800">{{ item.service_line.replace('_', ' ') }}</p>
                <p class="text-xs text-slate-500">{{ item.count }} oportunidades</p>
              </div>
              <p class="text-sm font-semibold text-slate-900">{{ formatMoney(item.amount, 'PEN') }}</p>
            </div>
            <p v-if="report.by_service_line.length === 0" class="text-sm text-slate-500">Sin datos en este período.</p>
          </div>
        </div>

        <!-- Ranking de vendedores -->
        <div class="rounded-[6px] border border-slate-200 bg-white p-5">
          <h2 class="text-base font-semibold text-slate-900">Desempeño por Vendedor</h2>
          <div class="mt-4 overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
              <thead>
                <tr class="text-left text-xs uppercase text-slate-500">
                  <th class="py-2">Ejecutivo</th>
                  <th class="py-2 text-center">Cotiz.</th>
                  <th class="py-2 text-center">Aprob.</th>
                  <th class="py-2 text-right">Monto Aprobado</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100">
                <tr v-for="s in report.by_seller" :key="s.seller">
                  <td class="py-2.5 font-medium text-slate-900">{{ s.seller }}</td>
                  <td class="py-2.5 text-center text-slate-600">{{ s.quotes_count }}</td>
                  <td class="py-2.5 text-center font-semibold text-emerald-600">{{ s.approved_count }}</td>
                  <td class="py-2.5 text-right font-semibold text-slate-900">{{ formatMoney(s.approved_total, 'PEN') }}</td>
                </tr>
                <tr v-if="report.by_seller.length === 0">
                  <td colspan="4" class="py-4 text-center text-slate-500">Sin datos en este período.</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </template>
  </div>
</template>

<script setup lang="ts">
import { Download } from 'lucide-vue-next'

interface ReportSummary {
  period: { from: string; to: string }
  kpis: {
    total_quoted: number
    total_approved: number
    avg_margin_percent: number
    projects_active: number
    projects_completed: number
  }
  by_service_line: Array<{ service_line: string; count: number; amount: number }>
  by_seller: Array<{ seller: string; quotes_count: number; approved_count: number; approved_total: number }>
}

const api = useApi()
const { formatMoney } = useFormatters()

const loading = ref(true)
const report = ref<ReportSummary | null>(null)
const now = new Date()
const filters = reactive({
  from: new Date(now.getFullYear(), now.getMonth(), 1).toISOString().slice(0, 10),
  to: now.toISOString().slice(0, 10),
})

async function fetchReport() {
  loading.value = true
  try {
    report.value = await api.get<ReportSummary>(`/reports/summary?from=${filters.from}&to=${filters.to}`)
  } finally {
    loading.value = false
  }
}

async function exportCsv() {
  await api.downloadPdf('/reports/export', `reporte-cotizaciones-${filters.from}-${filters.to}.csv`)
}

onMounted(fetchReport)
</script>
