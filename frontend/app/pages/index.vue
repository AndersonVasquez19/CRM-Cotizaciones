<script setup lang="ts">
import {
  FileDown,
  Pencil,
  RefreshCw,
  ArrowUpRight,
} from 'lucide-vue-next'
import type { DashboardMetrics } from '~/types'

const api = useApi()
const { formatMoney, formatDate, quoteStatusMeta, leadStageMeta } = useFormatters()

const metrics = ref<DashboardMetrics | null>(null)
const loading = ref(true)
const downloadingId = ref<number | null>(null)

async function loadMetrics() {
  loading.value = true
  try {
    metrics.value = await api.get<DashboardMetrics>('/dashboard/metrics')
  } finally {
    loading.value = false
  }
}

const maxStageAmount = computed(() => {
  if (!metrics.value) return 1
  return Math.max(...metrics.value.pipeline_by_stage.map((s) => s.total_amount), 1)
})

async function handlePdf(id: number, quoteNumber: string, version: number) {
  downloadingId.value = id
  try {
    await api.downloadPdf(`/quotes/${id}/pdf`, `${quoteNumber}-v${version}.pdf`)
  } finally {
    downloadingId.value = null
  }
}

onMounted(loadMetrics)
</script>

<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
      <div>
        <h1 class="text-2xl font-bold text-[var(--tq-ink)] tracking-tight">Dashboard Ejecutivo</h1>
        <p class="text-sm text-[var(--tq-ink-muted)] mt-0.5">
          Resumen comercial en tiempo real de oportunidades, cotizaciones y cuentas activas.
        </p>
      </div>
      <button
        type="button"
        class="tq-btn-secondary text-xs py-2 px-3.5"
        @click="loadMetrics"
      >
        <RefreshCw class="w-3.5 h-3.5" :class="{ 'animate-spin': loading }" />
        Actualizar datos
      </button>
    </div>

    <!-- Continuous Telemetry KPI Strip (No generic icon cards) -->
    <div class="tq-panel grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 divide-y sm:divide-y-0 sm:divide-x divide-[var(--tq-border)] overflow-hidden">
      <div class="p-5">
        <div class="flex items-center justify-between">
          <span class="text-xs font-semibold text-[var(--tq-ink-muted)]">Cotizado del Mes</span>
          <span class="w-2 h-2 rounded-full bg-cyan-600" />
        </div>
        <div v-if="loading" class="h-8 w-32 bg-slate-200 animate-pulse rounded mt-2.5" />
        <div v-else class="mt-2 text-2xl font-bold text-[var(--tq-ink)] tq-num">
          {{ formatMoney(metrics?.kpis.monthly_quoted_total ?? 0) }}
        </div>
        <div class="mt-1 text-xs text-[var(--tq-ink-muted)]">Total emitido en el mes actual</div>
      </div>

      <div class="p-5">
        <div class="flex items-center justify-between">
          <span class="text-xs font-semibold text-[var(--tq-ink-muted)]">Monto Aprobado</span>
          <span class="w-2 h-2 rounded-full bg-emerald-600" />
        </div>
        <div v-if="loading" class="h-8 w-32 bg-slate-200 animate-pulse rounded mt-2.5" />
        <div v-else class="mt-2 text-2xl font-bold text-emerald-700 tq-num">
          {{ formatMoney(metrics?.kpis.monthly_approved_total ?? 0) }}
        </div>
        <div class="mt-1 text-xs text-[var(--tq-ink-muted)]">Cotizaciones aprobadas del mes</div>
      </div>

      <div class="p-5">
        <div class="flex items-center justify-between">
          <span class="text-xs font-semibold text-[var(--tq-ink-muted)]">Conversión Leads</span>
          <span class="w-2 h-2 rounded-full bg-amber-500" />
        </div>
        <div v-if="loading" class="h-8 w-20 bg-slate-200 animate-pulse rounded mt-2.5" />
        <div v-else class="mt-2 text-2xl font-bold text-[var(--tq-ink)] tq-num">
          {{ metrics?.kpis.lead_conversion_rate ?? 0 }}%
        </div>
        <div class="mt-1 text-xs text-[var(--tq-ink-muted)] tq-num">
          {{ metrics?.kpis.won_leads ?? 0 }} ganados de {{ metrics?.kpis.total_leads ?? 0 }} oportunidades
        </div>
      </div>

      <div class="p-5">
        <div class="flex items-center justify-between">
          <span class="text-xs font-semibold text-[var(--tq-ink-muted)]">Clientes Activos</span>
          <span class="w-2 h-2 rounded-full bg-slate-700" />
        </div>
        <div v-if="loading" class="h-8 w-16 bg-slate-200 animate-pulse rounded mt-2.5" />
        <div v-else class="mt-2 text-2xl font-bold text-[var(--tq-ink)] tq-num">
          {{ metrics?.kpis.active_clients ?? 0 }}
        </div>
        <div class="mt-1 text-xs text-[var(--tq-ink-muted)]">Cuentas corporativas activas</div>
      </div>
    </div>

    <!-- Main Analytical Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
      <!-- Embudo Comercial -->
      <section class="lg:col-span-5 tq-panel overflow-hidden flex flex-col">
        <div class="tq-panel-header">
          <h2 class="text-sm font-bold text-[var(--tq-ink)]">Embudo Comercial por Etapa</h2>
          <NuxtLink to="/leads" class="inline-flex items-center gap-1 text-xs font-semibold text-cyan-700 hover:text-cyan-900">
            Ver Kanban
            <ArrowUpRight class="w-3.5 h-3.5" />
          </NuxtLink>
        </div>

        <div class="p-5 space-y-4 flex-1">
          <template v-if="loading">
            <div v-for="i in 5" :key="i" class="space-y-1.5">
              <div class="h-3.5 w-full bg-slate-100 animate-pulse rounded" />
              <div class="h-2 w-3/4 bg-slate-100 animate-pulse rounded" />
            </div>
          </template>
          <template v-else>
            <div v-for="item in metrics?.pipeline_by_stage || []" :key="item.stage">
              <div class="flex items-center justify-between text-xs mb-1.5">
                <span class="font-semibold text-[var(--tq-ink)]">
                  {{ item.label }}
                  <span class="tq-code text-[11px] text-[var(--tq-ink-muted)] font-normal ml-1">({{ item.count }})</span>
                </span>
                <span class="font-semibold text-[var(--tq-ink)] tq-num">{{ formatMoney(item.total_amount) }}</span>
              </div>
              <div class="w-full h-2 bg-slate-100 rounded-[2px] overflow-hidden">
                <div
                  class="h-full transition-all duration-300"
                  :class="leadStageMeta[item.stage]?.barClass || 'bg-cyan-600'"
                  :style="{ width: `${Math.max((item.total_amount / maxStageAmount) * 100, item.count > 0 ? 6 : 0)}%` }"
                />
              </div>
            </div>
          </template>
        </div>
      </section>

      <!-- Últimas Cotizaciones -->
      <section class="lg:col-span-7 tq-panel overflow-hidden flex flex-col">
        <div class="tq-panel-header">
          <h2 class="text-sm font-bold text-[var(--tq-ink)]">Últimas Cotizaciones</h2>
          <NuxtLink to="/quotes" class="inline-flex items-center gap-1 text-xs font-semibold text-cyan-700 hover:text-cyan-900">
            Ver todas
            <ArrowUpRight class="w-3.5 h-3.5" />
          </NuxtLink>
        </div>

        <div class="overflow-x-auto flex-1">
          <table class="tq-table">
            <thead>
              <tr>
                <th>Código</th>
                <th>Cliente</th>
                <th>Estado</th>
                <th class="text-right">Total</th>
                <th class="text-right">Acciones</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="q in metrics?.recent_quotes || []" :key="q.id">
                <td class="whitespace-nowrap">
                  <span class="tq-code font-semibold text-cyan-800">{{ q.quote_number }}</span>
                  <span class="tq-code ml-1.5 px-1.5 py-0.5 text-[10px] rounded bg-slate-100 text-slate-600 border border-slate-200">
                    v{{ q.version }}
                  </span>
                </td>
                <td>
                  <div class="font-medium text-[var(--tq-ink)] truncate max-w-[190px]">{{ q.client?.business_name || '—' }}</div>
                  <div class="text-[11px] text-[var(--tq-ink-muted)]">{{ formatDate(q.issue_date) }}</div>
                </td>
                <td>
                  <span
                    class="inline-flex px-2 py-0.5 text-xs font-semibold rounded-[4px] border"
                    :class="quoteStatusMeta[q.status]?.badgeClass"
                  >
                    {{ quoteStatusMeta[q.status]?.label || q.status }}
                  </span>
                </td>
                <td class="text-right font-semibold text-[var(--tq-ink)] whitespace-nowrap tq-num">
                  {{ formatMoney(q.total, q.currency) }}
                </td>
                <td class="text-right whitespace-nowrap">
                  <div class="inline-flex items-center gap-1">
                    <NuxtLink
                      :to="`/quotes/${q.id}`"
                      title="Editar / Ver detalle"
                      class="p-1.5 rounded-[4px] text-slate-500 hover:text-cyan-700 hover:bg-cyan-50 transition-colors"
                    >
                      <Pencil class="w-4 h-4" />
                    </NuxtLink>
                    <button
                      type="button"
                      title="Descargar PDF"
                      :disabled="downloadingId === q.id"
                      class="p-1.5 rounded-[4px] text-slate-500 hover:text-emerald-700 hover:bg-emerald-50 disabled:opacity-50 transition-colors"
                      @click="handlePdf(q.id, q.quote_number, q.version)"
                    >
                      <FileDown class="w-4 h-4" />
                    </button>
                  </div>
                </td>
              </tr>
              <tr v-if="!loading && !(metrics?.recent_quotes?.length)">
                <td colspan="5" class="py-10 text-center text-[var(--tq-ink-muted)] text-sm">
                  No hay cotizaciones registradas aún. Crea la primera desde el botón superior.
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>
    </div>
  </div>
</template>
