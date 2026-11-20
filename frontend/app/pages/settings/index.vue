<script setup lang="ts">
import { Save, ShieldCheck, History } from 'lucide-vue-next'
import type { ActivityLog, PaginatedResponse } from '~/types'

const api = useApi()
const auth = useAuthStore()
const { formatDateTime } = useFormatters()

const isAdmin = computed(() => auth.user?.role === 'admin')
const saving = ref(false)
const savedMsg = ref('')
const logs = ref<ActivityLog[]>([])

const settings = reactive({
  min_margin_percent: '20',
  max_discount_percent: '10',
  company_name: '',
  company_ruc: '',
  company_address: '',
  company_email: '',
})

async function loadAll() {
  const [setRes, logRes] = await Promise.all([
    api.get<{ data: Record<string, string> }>('/settings'),
    api.get<PaginatedResponse<ActivityLog>>('/activity-logs'),
  ])
  Object.assign(settings, setRes.data)
  logs.value = logRes.data
}

async function saveSettings() {
  saving.value = true
  savedMsg.value = ''
  try {
    await api.put('/settings', { settings })
    savedMsg.value = 'Parámetros guardados correctamente.'
  } finally {
    saving.value = false
  }
}

onMounted(loadAll)
</script>

<template>
  <div class="space-y-6">
    <div>
      <h2 class="text-2xl font-bold text-slate-900 tracking-tight">Configuración y Auditoría</h2>
      <p class="text-sm text-slate-500">Reglas de aprobación de márgenes, datos corporativos y bitácora global.</p>
    </div>

    <div v-if="savedMsg" class="p-3 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm">
      {{ savedMsg }}
    </div>

    <form class="bg-white rounded-[6px] border border-slate-200 p-6 space-y-5" @submit.prevent="saveSettings">
      <div class="flex items-center gap-2 text-sm font-bold text-slate-900">
        <ShieldCheck class="w-4 h-4 text-cyan-700" /> Reglas Comerciales y Empresa
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label class="block text-xs font-semibold text-slate-600 mb-1">Margen Bruto Mínimo (%)</label>
          <input v-model="settings.min_margin_percent" :disabled="!isAdmin" type="number" step="0.5" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
          <p class="text-[11px] text-slate-400 mt-1">Cotizaciones debajo de este margen requieren aprobación gerencial.</p>
        </div>
        <div>
          <label class="block text-xs font-semibold text-slate-600 mb-1">Descuento Máximo Permitido (%)</label>
          <input v-model="settings.max_discount_percent" :disabled="!isAdmin" type="number" step="0.5" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
        </div>
        <div>
          <label class="block text-xs font-semibold text-slate-600 mb-1">Razón Social Integradora</label>
          <input v-model="settings.company_name" :disabled="!isAdmin" type="text" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
        </div>
        <div>
          <label class="block text-xs font-semibold text-slate-600 mb-1">RUC</label>
          <input v-model="settings.company_ruc" :disabled="!isAdmin" type="text" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
        </div>
        <div>
          <label class="block text-xs font-semibold text-slate-600 mb-1">Dirección Fiscal</label>
          <input v-model="settings.company_address" :disabled="!isAdmin" type="text" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
        </div>
        <div>
          <label class="block text-xs font-semibold text-slate-600 mb-1">Correo Remitente</label>
          <input v-model="settings.company_email" :disabled="!isAdmin" type="email" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
        </div>
      </div>

      <div v-if="isAdmin" class="flex justify-end">
        <button type="submit" :disabled="saving" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-cyan-600 hover:bg-cyan-700 text-white text-xs font-semibold">
          <Save class="w-4 h-4" /> {{ saving ? 'Guardando...' : 'Guardar Configuración' }}
        </button>
      </div>
    </form>

    <div class="bg-white rounded-[6px] border border-slate-200 p-6 space-y-4">
      <div class="flex items-center gap-2 text-sm font-bold text-slate-900">
        <History class="w-4 h-4 text-slate-600" /> Bitácora de Auditoría Reciente
      </div>
      <div class="overflow-x-auto">
        <table class="w-full text-xs">
          <thead class="bg-slate-50 border-b border-slate-200 text-slate-500 uppercase">
            <tr class="text-left">
              <th class="px-3 py-2">Fecha</th>
              <th class="px-3 py-2">Usuario</th>
              <th class="px-3 py-2">Acción</th>
              <th class="px-3 py-2">Entidad</th>
              <th class="px-3 py-2">IP</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr v-for="log in logs" :key="log.id">
              <td class="px-3 py-2 text-slate-500 whitespace-nowrap">{{ formatDateTime(log.created_at) }}</td>
              <td class="px-3 py-2 font-medium text-slate-900">{{ log.user?.name || 'Portal Público' }}</td>
              <td class="px-3 py-2 font-mono text-cyan-800">{{ log.action }}</td>
              <td class="px-3 py-2 text-slate-600">{{ log.subject_type }} #{{ log.subject_id }}</td>
              <td class="px-3 py-2 text-slate-400">{{ log.ip_address || '—' }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>
