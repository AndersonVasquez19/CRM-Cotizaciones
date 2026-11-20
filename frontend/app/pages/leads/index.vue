<script setup lang="ts">
import {
  Kanban,
  List,
  Plus,
  Wrench,
  X,
  Calendar,
  Building2,
} from 'lucide-vue-next'
import type { Client, Lead, LeadStage, PaginatedResponse, ServiceLine, SiteSurvey } from '~/types'

const api = useApi()
const { formatMoney, formatDateTime, leadStageMeta, serviceLineLabels } = useFormatters()

const viewMode = ref<'kanban' | 'table'>('kanban')
const leads = ref<Lead[]>([])
const clients = ref<Client[]>([])
const loading = ref(false)

const stages: Array<{ key: LeadStage; label: string }> = [
  { key: 'nuevo', label: 'Nuevo' },
  { key: 'visita_tecnica', label: 'Visita Técnica' },
  { key: 'cotizado', label: 'Cotizado' },
  { key: 'negociacion', label: 'Negociación' },
  { key: 'ganado', label: 'Ganado' },
  { key: 'perdido', label: 'Perdido' },
]

// New Lead Modal
const showCreateModal = ref(false)
const createForm = reactive({
  client_id: '' as number | '',
  title: '',
  service_line: 'cctv' as ServiceLine,
  source: 'web',
  estimated_amount: 0,
  expected_close_date: '',
  notes: '',
})
const savingLead = ref(false)

// Lead Detail & Technical Visit Drawer
const selectedLead = ref<Lead | null>(null)
const surveys = ref<SiteSurvey[]>([])
const surveyFiles = ref<FileList | null>(null)
const surveyForm = reactive({
  cable_meters: 0,
  conduit_type: 'Tubería EMT 3/4"',
  max_height_meters: 3.5,
  power_requirements: '',
  observations: '',
})
const savingSurvey = ref(false)

const visitForm = reactive({
  activity_type: 'visita_tecnica' as 'visita_tecnica' | 'llamada' | 'correo' | 'reunion' | 'nota',
  subject: '',
  description: '',
  scheduled_at: '',
})
const savingActivity = ref(false)

async function loadData() {
  loading.value = true
  try {
    const [leadsRes, clientsRes] = await Promise.all([
      api.get<PaginatedResponse<Lead>>('/leads', { per_page: 100 }),
      api.get<PaginatedResponse<Client>>('/clients', { per_page: 100 }),
    ])
    leads.value = leadsRes.data
    clients.value = clientsRes.data
  } finally {
    loading.value = false
  }
}

function leadsByStage(stage: LeadStage): Lead[] {
  return leads.value.filter((l) => l.stage === stage)
}

async function moveStage(lead: Lead, newStage: LeadStage) {
  if (lead.stage === newStage) return
  const res = await api.patch<{ data: Lead }>(`/leads/${lead.id}/stage`, {
    stage: newStage,
  })
  const idx = leads.value.findIndex((l) => l.id === lead.id)
  if (idx !== -1) leads.value[idx] = res.data
  if (selectedLead.value?.id === lead.id) {
    selectedLead.value = res.data
  }
}

async function openDetail(lead: Lead) {
  const [res, srvRes] = await Promise.all([
    api.get<{ data: Lead }>(`/leads/${lead.id}`),
    api.get<{ data: SiteSurvey[] }>(`/leads/${lead.id}/surveys`),
  ])
  selectedLead.value = res.data
  surveys.value = srvRes.data
}

async function submitSurvey() {
  if (!selectedLead.value) return
  savingSurvey.value = true
  try {
    const fd = new FormData()
    fd.append('cable_meters', String(surveyForm.cable_meters))
    fd.append('conduit_type', surveyForm.conduit_type)
    fd.append('max_height_meters', String(surveyForm.max_height_meters))
    fd.append('power_requirements', surveyForm.power_requirements)
    fd.append('observations', surveyForm.observations)
    if (surveyFiles.value) {
      for (let i = 0; i < surveyFiles.value.length; i++) {
        const f = surveyFiles.value.item(i)
        if (f) fd.append('photos[]', f)
      }
    }
    await api.post(`/leads/${selectedLead.value.id}/surveys`, fd)
    surveyForm.cable_meters = 0
    surveyForm.power_requirements = ''
    surveyForm.observations = ''
    surveyFiles.value = null
    await openDetail(selectedLead.value)
  } finally {
    savingSurvey.value = false
  }
}

async function createLead() {
  if (!createForm.client_id || !createForm.title) return
  savingLead.value = true
  try {
    await api.post('/leads', createForm)
    showCreateModal.value = false
    Object.assign(createForm, {
      client_id: '',
      title: '',
      service_line: 'cctv',
      source: 'web',
      estimated_amount: 0,
      expected_close_date: '',
      notes: '',
    })
    await loadData()
  } finally {
    savingLead.value = false
  }
}

async function submitActivity() {
  if (!selectedLead.value || !visitForm.subject) return
  savingActivity.value = true
  try {
    await api.post(`/leads/${selectedLead.value.id}/activities`, {
      ...visitForm,
      scheduled_at: visitForm.scheduled_at || null,
    })
    visitForm.subject = ''
    visitForm.description = ''
    visitForm.scheduled_at = ''
    await openDetail(selectedLead.value)
  } finally {
    savingActivity.value = false
  }
}

onMounted(loadData)
</script>

<template>
  <div class="space-y-5">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
      <div>
        <h2 class="text-2xl font-bold text-slate-900 tracking-tight">Pipeline CRM</h2>
        <p class="text-sm text-slate-500">Gestiona oportunidades comerciales y levantamientos de visitas técnicas.</p>
      </div>

      <div class="flex items-center gap-2">
        <div class="inline-flex rounded-lg border border-slate-200 bg-white p-1">
          <button
            type="button"
            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-md text-xs font-semibold transition-colors"
            :class="viewMode === 'kanban' ? 'bg-slate-900 text-white' : 'text-slate-600 hover:bg-slate-50'"
            @click="viewMode = 'kanban'"
          >
            <Kanban class="w-3.5 h-3.5" /> Kanban
          </button>
          <button
            type="button"
            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-md text-xs font-semibold transition-colors"
            :class="viewMode === 'table' ? 'bg-slate-900 text-white' : 'text-slate-600 hover:bg-slate-50'"
            @click="viewMode = 'table'"
          >
            <List class="w-3.5 h-3.5" /> Tabla
          </button>
        </div>

        <button
          type="button"
          class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-lg bg-cyan-600 hover:bg-cyan-700 text-white text-xs font-semibold"
          @click="showCreateModal = true"
        >
          <Plus class="w-4 h-4" /> Nueva Oportunidad
        </button>
      </div>
    </div>

    <!-- Kanban Board -->
    <div v-if="viewMode === 'kanban'" class="flex gap-4 overflow-x-auto pb-4">
      <div
        v-for="col in stages"
        :key="col.key"
        class="w-72 shrink-0 bg-slate-100/80 border border-slate-200 rounded-[6px] p-3 flex flex-col max-h-[75vh]"
      >
        <div class="flex items-center justify-between px-1 pb-2.5 mb-2 border-b border-slate-200">
          <span class="text-xs font-bold uppercase tracking-wider text-slate-700">
            {{ col.label }}
          </span>
          <span class="px-2 py-0.5 text-xs font-bold rounded-full bg-white text-slate-700 border border-slate-200">
            {{ leadsByStage(col.key).length }}
          </span>
        </div>

        <div class="space-y-2.5 overflow-y-auto pr-1">
          <div
            v-for="lead in leadsByStage(col.key)"
            :key="lead.id"
            class="bg-white rounded-lg border border-slate-200 p-3 hover:border-cyan-300 transition-colors cursor-pointer"
            @click="openDetail(lead)"
          >
            <div class="flex items-center justify-between gap-2 mb-1">
              <span class="text-[11px] font-bold text-cyan-700">{{ lead.code }}</span>
              <span class="text-[11px] font-medium text-slate-500">
                {{ serviceLineLabels[lead.service_line] }}
              </span>
            </div>
            <h4 class="text-sm font-semibold text-slate-900 leading-snug mb-1.5">
              {{ lead.title }}
            </h4>
            <div class="flex items-center gap-1.5 text-xs text-slate-500 mb-2.5">
              <Building2 class="w-3.5 h-3.5 shrink-0" />
              <span class="truncate">{{ lead.client?.business_name }}</span>
            </div>
            <div class="flex items-center justify-between pt-2 border-t border-slate-100">
              <span class="text-xs font-bold text-slate-900">
                {{ formatMoney(lead.estimated_amount) }}
              </span>
              <select
                :value="lead.stage"
                class="text-[11px] border border-slate-200 rounded px-1.5 py-0.5 bg-slate-50 text-slate-700"
                @click.stop
                @change="moveStage(lead, ($event.target as HTMLSelectElement).value as LeadStage)"
              >
                <option v-for="s in stages" :key="s.key" :value="s.key">
                  {{ s.label }}
                </option>
              </select>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Table View -->
    <div v-else class="tq-panel overflow-x-auto">
      <table class="w-full text-sm">
        <thead class="bg-slate-50 border-b border-slate-200">
          <tr class="text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
            <th class="px-4 py-3">Código</th>
            <th class="px-4 py-3">Oportunidad</th>
            <th class="px-4 py-3">Cliente</th>
            <th class="px-4 py-3">Línea</th>
            <th class="px-4 py-3">Etapa</th>
            <th class="px-4 py-3 text-right">Monto Est.</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <tr
            v-for="lead in leads"
            :key="lead.id"
            class="hover:bg-slate-50 cursor-pointer"
            @click="openDetail(lead)"
          >
            <td class="px-4 py-3 font-bold text-cyan-700">{{ lead.code }}</td>
            <td class="px-4 py-3 font-medium text-slate-900">{{ lead.title }}</td>
            <td class="px-4 py-3 text-slate-600">{{ lead.client?.business_name }}</td>
            <td class="px-4 py-3 text-slate-600">{{ serviceLineLabels[lead.service_line] }}</td>
            <td class="px-4 py-3">
              <span
                class="inline-flex px-2 py-0.5 text-xs font-semibold rounded-full border"
                :class="leadStageMeta[lead.stage]?.badgeClass"
              >
                {{ leadStageMeta[lead.stage]?.label }}
              </span>
            </td>
            <td class="px-4 py-3 text-right font-semibold text-slate-900">
              {{ formatMoney(lead.estimated_amount) }}
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Modal Nueva Oportunidad -->
    <div
      v-if="showCreateModal"
      class="fixed inset-0 z-50 bg-slate-900/60 flex items-center justify-center p-4"
    >
      <div class="bg-white rounded-[6px] max-w-lg w-full p-6 shadow-xl space-y-4">
        <div class="flex items-center justify-between">
          <h3 class="text-lg font-bold text-slate-900">Nueva Oportunidad Comercial</h3>
          <button type="button" class="text-slate-400 hover:text-slate-600" @click="showCreateModal = false">
            <X class="w-5 h-5" />
          </button>
        </div>

        <form class="space-y-3.5" @submit.prevent="createLead">
          <div>
            <label class="block text-xs font-semibold text-slate-600 mb-1">Cliente *</label>
            <select
              v-model="createForm.client_id"
              required
              class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"
            >
              <option value="">Seleccionar cliente...</option>
              <option v-for="c in clients" :key="c.id" :value="c.id">
                {{ c.business_name }} ({{ c.document_number }})
              </option>
            </select>
          </div>

          <div>
            <label class="block text-xs font-semibold text-slate-600 mb-1">Título del Proyecto *</label>
            <input
              v-model="createForm.title"
              type="text"
              required
              placeholder="Ej. Implementación CCTV 16 Cámaras Planta Sur"
              class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"
            >
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-xs font-semibold text-slate-600 mb-1">Línea de Servicio</label>
              <select
                v-model="createForm.service_line"
                class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"
              >
                <option value="cctv">CCTV / Videovigilancia</option>
                <option value="cableado_estructurado">Cableado Estructurado</option>
                <option value="redes_wifi">Redes / Wi-Fi Corporativo</option>
                <option value="mantenimiento">Mantenimiento</option>
                <option value="integral">Proyecto Integral</option>
              </select>
            </div>
            <div>
              <label class="block text-xs font-semibold text-slate-600 mb-1">Monto Estimado (S/)</label>
              <input
                v-model.number="createForm.estimated_amount"
                type="number"
                min="0"
                step="0.01"
                class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"
              >
            </div>
          </div>

          <div>
            <label class="block text-xs font-semibold text-slate-600 mb-1">Notas Iniciales</label>
            <textarea
              v-model="createForm.notes"
              rows="2"
              class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"
            />
          </div>

          <div class="flex justify-end gap-2 pt-2">
            <button
              type="button"
              class="px-4 py-2 rounded-lg border border-slate-200 text-xs font-semibold text-slate-700"
              @click="showCreateModal = false"
            >
              Cancelar
            </button>
            <button
              type="submit"
              :disabled="savingLead"
              class="px-4 py-2 rounded-lg bg-cyan-600 hover:bg-cyan-700 text-white text-xs font-semibold"
            >
              {{ savingLead ? 'Guardando...' : 'Crear Oportunidad' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Drawer Detalle de Lead & Visitas Técnicas -->
    <div
      v-if="selectedLead"
      class="fixed inset-0 z-50 bg-slate-900/60 flex justify-end"
      @click.self="selectedLead = null"
    >
      <div class="w-full max-w-xl bg-white h-full overflow-y-auto p-6 shadow-2xl space-y-6">
        <div class="flex items-start justify-between gap-4 border-b border-slate-200 pb-4">
          <div>
            <span class="text-xs font-bold text-cyan-700">{{ selectedLead.code }}</span>
            <h3 class="text-xl font-bold text-slate-900 mt-0.5">{{ selectedLead.title }}</h3>
            <p class="text-sm text-slate-500 mt-0.5">{{ selectedLead.client?.business_name }}</p>
          </div>
          <button type="button" class="text-slate-400 hover:text-slate-600" @click="selectedLead = null">
            <X class="w-5 h-5" />
          </button>
        </div>

        <div class="grid grid-cols-2 gap-3 bg-slate-50 p-4 rounded-[6px] border border-slate-200 text-sm">
          <div>
            <div class="text-xs text-slate-500">Etapa Actual</div>
            <select
              :value="selectedLead.stage"
              class="mt-1 w-full rounded-lg border border-slate-300 px-2.5 py-1.5 text-xs font-semibold bg-white"
              @change="moveStage(selectedLead, ($event.target as HTMLSelectElement).value as LeadStage)"
            >
              <option v-for="s in stages" :key="s.key" :value="s.key">{{ s.label }}</option>
            </select>
          </div>
          <div>
            <div class="text-xs text-slate-500">Monto Estimado</div>
            <div class="mt-1.5 font-bold text-slate-900">{{ formatMoney(selectedLead.estimated_amount) }}</div>
          </div>
        </div>

        <!-- Levantamiento Técnico Estructurado (Site Survey) -->
        <div class="border border-cyan-200 bg-cyan-50/30 rounded-[6px] p-4 space-y-3">
          <div class="flex items-center gap-2 text-sm font-bold text-indigo-900">
            <Wrench class="w-4 h-4 text-cyan-700" />
            Site Survey (Levantamiento Técnico con Fotos)
          </div>

          <form class="space-y-2.5" @submit.prevent="submitSurvey">
            <div class="grid grid-cols-3 gap-2">
              <div>
                <label class="block text-[11px] font-semibold text-slate-600 mb-1">Metraje Cable (m)</label>
                <input v-model.number="surveyForm.cable_meters" type="number" step="0.5" min="0" required class="w-full rounded-lg border border-slate-300 px-2.5 py-1.5 text-xs bg-white">
              </div>
              <div>
                <label class="block text-[11px] font-semibold text-slate-600 mb-1">Canalización</label>
                <select v-model="surveyForm.conduit_type" class="w-full rounded-lg border border-slate-300 px-2 py-1.5 text-xs bg-white">
                  <option value='Tubería EMT 3/4"'>Tubería EMT 3/4"</option>
                  <option value="Tubería PVC SAP">Tubería PVC SAP</option>
                  <option value="Bandeja Portacables">Bandeja Portacables</option>
                  <option value="Canaleta Ranurada">Canaleta Ranurada</option>
                </select>
              </div>
              <div>
                <label class="block text-[11px] font-semibold text-slate-600 mb-1">Altura Máx (m)</label>
                <input v-model.number="surveyForm.max_height_meters" type="number" step="0.1" min="0" class="w-full rounded-lg border border-slate-300 px-2.5 py-1.5 text-xs bg-white">
              </div>
            </div>
            <input v-model="surveyForm.power_requirements" type="text" placeholder="Requerimientos eléctricos (UPS, toma estabilizada)" class="w-full rounded-lg border border-slate-300 px-3 py-1.5 text-xs bg-white">
            <textarea v-model="surveyForm.observations" rows="2" placeholder="Observaciones técnicas del sitio..." class="w-full rounded-lg border border-slate-300 px-3 py-1.5 text-xs bg-white" />
            <div>
              <label class="block text-[11px] font-semibold text-slate-600 mb-1">Fotos de Evidencia (hasta 6)</label>
              <input type="file" multiple accept="image/*" class="text-xs" @change="surveyFiles = ($event.target as HTMLInputElement).files">
            </div>
            <div class="flex justify-end">
              <button type="submit" :disabled="savingSurvey" class="px-3.5 py-1.5 rounded-lg bg-cyan-600 hover:bg-cyan-700 text-white text-xs font-semibold">
                {{ savingSurvey ? 'Subiendo...' : 'Guardar Site Survey' }}
              </button>
            </div>
          </form>

          <div v-if="surveys.length" class="space-y-2 pt-2 border-t border-cyan-200">
            <div v-for="srv in surveys" :key="srv.id" class="bg-white rounded-lg p-3 border border-indigo-100 text-xs space-y-1">
              <div class="font-bold text-slate-900">
                {{ srv.cable_meters }}m · {{ srv.conduit_type }} · Altura {{ srv.max_height_meters }}m
              </div>
              <div v-if="srv.observations" class="text-slate-600">{{ srv.observations }}</div>
              <div v-if="srv.photos?.length" class="flex gap-2 flex-wrap pt-1">
                <a v-for="ph in srv.photos" :key="ph.id" :href="ph.url" target="_blank" class="block">
                  <img :src="ph.url" :alt="ph.caption || 'Foto'" class="w-16 h-16 object-cover rounded border border-slate-200">
                </a>
              </div>
            </div>
          </div>
        </div>

        <!-- Formulario Visita Técnica / Actividad -->
        <div class="border border-slate-200 rounded-[6px] p-4 space-y-3">
          <div class="flex items-center gap-2 text-sm font-bold text-slate-900">
            <Wrench class="w-4 h-4 text-cyan-700" />
            Registrar Visita Técnica o Actividad
          </div>

          <form class="space-y-3" @submit.prevent="submitActivity">
            <div class="grid grid-cols-2 gap-3">
              <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1">Tipo</label>
                <select
                  v-model="visitForm.activity_type"
                  class="w-full rounded-lg border border-slate-300 px-2.5 py-1.5 text-xs"
                >
                  <option value="visita_tecnica">Visita Técnica</option>
                  <option value="llamada">Llamada</option>
                  <option value="reunion">Reunión</option>
                  <option value="correo">Correo</option>
                  <option value="nota">Nota</option>
                </select>
              </div>
              <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1">Fecha Programada</label>
                <input
                  v-model="visitForm.scheduled_at"
                  type="datetime-local"
                  class="w-full rounded-lg border border-slate-300 px-2.5 py-1.5 text-xs"
                >
              </div>
            </div>

            <div>
              <label class="block text-xs font-semibold text-slate-600 mb-1">Asunto *</label>
              <input
                v-model="visitForm.subject"
                type="text"
                required
                placeholder="Ej. Levantamiento de metraje y puntos de red"
                class="w-full rounded-lg border border-slate-300 px-3 py-1.5 text-sm"
              >
            </div>

            <div>
              <label class="block text-xs font-semibold text-slate-600 mb-1">
                Observaciones de Canalización / Metraje
              </label>
              <textarea
                v-model="visitForm.description"
                rows="3"
                placeholder="Detalle metraje de cable UTP, altura de montaje, ductos existentes, ubicación de gabinete..."
                class="w-full rounded-lg border border-slate-300 px-3 py-1.5 text-sm"
              />
            </div>

            <div class="flex justify-end">
              <button
                type="submit"
                :disabled="savingActivity"
                class="px-3.5 py-2 rounded-lg bg-slate-900 hover:bg-slate-800 text-white text-xs font-semibold"
              >
                {{ savingActivity ? 'Registrando...' : 'Guardar en Bitácora' }}
              </button>
            </div>
          </form>
        </div>

        <!-- Bitácora -->
        <div class="space-y-3">
          <h4 class="text-sm font-bold text-slate-900">Bitácora de Actividades</h4>
          <div
            v-for="act in selectedLead.activities || []"
            :key="act.id"
            class="border border-slate-200 rounded-lg p-3 bg-slate-50/50 space-y-1"
          >
            <div class="flex items-center justify-between text-xs">
              <span class="px-2 py-0.5 rounded bg-cyan-50 text-cyan-800 font-semibold uppercase">
                {{ act.activity_type.replace('_', ' ') }}
              </span>
              <span class="text-slate-400 flex items-center gap-1">
                <Calendar class="w-3 h-3" /> {{ formatDateTime(act.created_at) }}
              </span>
            </div>
            <div class="text-sm font-semibold text-slate-900">{{ act.subject }}</div>
            <p v-if="act.description" class="text-xs text-slate-600 whitespace-pre-line">
              {{ act.description }}
            </p>
            <div class="text-[11px] text-slate-400">Por: {{ act.user?.name || 'Sistema' }}</div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
