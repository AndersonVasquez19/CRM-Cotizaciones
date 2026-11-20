<script setup lang="ts">
import { Plus, Trash2, Search, Calculator, Save, Layers } from 'lucide-vue-next'
import type {
  CatalogItem,
  Client,
  CompanyProfile,
  CurrencyCode,
  Lead,
  MeasurementUnit,
  PaginatedResponse,
  Quote,
  QuoteItem,
  QuoteTemplate,
} from '~/types'

const props = defineProps<{
  initialQuote?: Quote | null
}>()

const emit = defineEmits<{
  saved: [quote: Quote]
}>()

const api = useApi()
const auth = useAuthStore()
const { formatMoney } = useFormatters()

const clients = ref<Client[]>([])
const companies = ref<CompanyProfile[]>([])
const selectedClient = ref<Client | null>(null)
const leads = ref<Lead[]>([])
const catalogResults = ref<CatalogItem[]>([])
const templates = ref<QuoteTemplate[]>([])
const selectedTemplateId = ref<number | ''>('')
const catalogSearch = ref('')
const activeSection = ref('1. Equipamiento CCTV')
const newSectionName = ref('')
const saving = ref(false)
const errorMsg = ref('')

const canViewInternalCosts = computed(() => auth.user?.role !== 'tecnico')

const form = reactive({
  client_id: (props.initialQuote?.client_id ?? '') as number | '',
  company_profile_id: (props.initialQuote?.company_profile_id ?? '') as number | '',
  contact_id: (props.initialQuote?.contact_id ?? '') as number | '',
  site_id: (props.initialQuote?.site_id ?? '') as number | '',
  lead_id: (props.initialQuote?.lead_id ?? '') as number | '',
  currency: (props.initialQuote?.currency ?? 'PEN') as CurrencyCode,
  exchange_rate: Number(props.initialQuote?.exchange_rate ?? 1),
  issue_date: props.initialQuote?.issue_date?.slice(0, 10) || new Date().toISOString().slice(0, 10),
  valid_until:
    props.initialQuote?.valid_until?.slice(0, 10) ||
    new Date(Date.now() + 15 * 86400000).toISOString().slice(0, 10),
  discount_amount: Number(props.initialQuote?.discount_amount ?? 0),
  payment_terms: props.initialQuote?.payment_terms ?? '50% adelanto, 50% contra entrega',
  delivery_time: props.initialQuote?.delivery_time ?? '7 días hábiles',
  warranty_terms: props.initialQuote?.warranty_terms ?? '12 meses por defectos de fabricación e instalación',
  technical_notes: props.initialQuote?.technical_notes ?? '',
})

const items = ref<QuoteItem[]>(
  props.initialQuote?.items?.length
    ? props.initialQuote.items.map((it) => ({
        id: it.id,
        catalog_item_id: it.catalog_item_id,
        section_name: it.section_name,
        description: it.description,
        unit: it.unit,
        quantity: Number(it.quantity),
        unit_cost: Number(it.unit_cost),
        unit_price: Number(it.unit_price),
        discount_percent: Number(it.discount_percent),
      }))
    : [],
)

const sections = computed(() => {
  const set = new Set<string>(['1. Equipamiento CCTV', '2. Cableado y Materiales', '3. Servicios de Instalación'])
  items.value.forEach((it) => set.add(it.section_name))
  if (activeSection.value) set.add(activeSection.value)
  return Array.from(set)
})

function lineTotal(it: QuoteItem): number {
  const gross = Number(it.quantity || 0) * Number(it.unit_price || 0)
  return Math.round(gross * (1 - Number(it.discount_percent || 0) / 100) * 100) / 100
}

const totals = computed(() => {
  const subtotal = Math.round(items.value.reduce((acc, it) => acc + lineTotal(it), 0) * 100) / 100
  const discount = Math.min(Math.max(Number(form.discount_amount || 0), 0), subtotal)
  const netSubtotal = Math.round((subtotal - discount) * 100) / 100
  const tax = Math.round(netSubtotal * 0.18 * 100) / 100
  const total = Math.round((netSubtotal + tax) * 100) / 100
  const totalCost = Math.round(
    items.value.reduce((acc, it) => acc + Number(it.quantity || 0) * Number(it.unit_cost || 0), 0) * 100,
  ) / 100
  const marginAmount = Math.round((netSubtotal - totalCost) * 100) / 100
  const marginPercent = netSubtotal > 0 ? Math.round((marginAmount / netSubtotal) * 10000) / 100 : 0
  return { subtotal, discount, netSubtotal, tax, total, totalCost, marginAmount, marginPercent }
})

async function loadInitialData() {
  const [clientsRes, leadsRes, catRes, tplRes, compRes] = await Promise.all([
    api.get<PaginatedResponse<Client>>('/clients', { per_page: 100 }),
    api.get<PaginatedResponse<Lead>>('/leads', { per_page: 100 }),
    api.get<PaginatedResponse<CatalogItem>>('/catalog-items', { per_page: 50 }),
    api.get<{ data: QuoteTemplate[] }>('/quote-templates'),
    api.get<{ data: CompanyProfile[] }>('/company-profiles'),
  ])
  clients.value = clientsRes.data
  leads.value = leadsRes.data
  catalogResults.value = catRes.data
  templates.value = tplRes.data
  companies.value = compRes.data
  if (!form.company_profile_id && companies.value.length) {
    form.company_profile_id = (companies.value.find((c) => c.is_default) || companies.value[0])?.id || ''
  }

  if (form.client_id) {
    await fetchClientDetail(Number(form.client_id))
  }
}

function applyTemplate() {
  const tpl = templates.value.find((t) => t.id === Number(selectedTemplateId.value))
  if (!tpl?.items?.length) return
  for (const it of tpl.items) {
    items.value.push({
      catalog_item_id: it.catalog_item_id ?? null,
      section_name: it.section_name,
      description: it.description,
      unit: it.unit,
      quantity: Number(it.quantity),
      unit_cost: Number(it.unit_cost),
      unit_price: Number(it.unit_price),
      discount_percent: 0,
    })
  }
  selectedTemplateId.value = ''
}

async function fetchClientDetail(clientId: number) {
  const res = await api.get<{ data: Client }>(`/clients/${clientId}`)
  selectedClient.value = res.data
}

watch(
  () => form.client_id,
  async (newVal) => {
    if (newVal) {
      await fetchClientDetail(Number(newVal))
      if (!props.initialQuote) {
        form.contact_id = selectedClient.value?.contacts?.find((c) => c.is_primary)?.id || ''
        form.site_id = selectedClient.value?.sites?.[0]?.id || ''
      }
    } else {
      selectedClient.value = null
      form.contact_id = ''
      form.site_id = ''
    }
  },
)

watchDebounced(
  catalogSearch,
  async () => {
    const res = await api.get<PaginatedResponse<CatalogItem>>('/catalog-items', {
      search: catalogSearch.value || undefined,
      per_page: 20,
    })
    catalogResults.value = res.data
  },
  { debounce: 250 },
)

function addCatalogItem(cat: CatalogItem) {
  items.value.push({
    catalog_item_id: cat.id,
    section_name: activeSection.value,
    description: cat.name,
    unit: cat.unit,
    quantity: 1,
    unit_cost: Number(cat.unit_cost),
    unit_price: Number(cat.sale_price),
    discount_percent: 0,
  })
}

function addFreeItem() {
  items.value.push({
    catalog_item_id: null,
    section_name: activeSection.value,
    description: 'Partida técnica personalizada',
    unit: 'UND' as MeasurementUnit,
    quantity: 1,
    unit_cost: 0,
    unit_price: 0,
    discount_percent: 0,
  })
}

function addSection() {
  const name = newSectionName.value.trim()
  if (!name) return
  activeSection.value = name
  newSectionName.value = ''
}

function removeItem(idx: number) {
  items.value.splice(idx, 1)
}

async function submitQuote() {
  errorMsg.value = ''
  if (!form.client_id) {
    errorMsg.value = 'Debes seleccionar un cliente.'
    return
  }
  if (!items.value.length) {
    errorMsg.value = 'Agrega al menos una partida a la cotización.'
    return
  }

  saving.value = true
  try {
    const payload = {
      client_id: Number(form.client_id),
      company_profile_id: form.company_profile_id ? Number(form.company_profile_id) : null,
      contact_id: form.contact_id ? Number(form.contact_id) : null,
      site_id: form.site_id ? Number(form.site_id) : null,
      lead_id: form.lead_id ? Number(form.lead_id) : null,
      currency: form.currency,
      exchange_rate: form.exchange_rate,
      issue_date: form.issue_date,
      valid_until: form.valid_until,
      discount_amount: form.discount_amount,
      payment_terms: form.payment_terms,
      delivery_time: form.delivery_time,
      warranty_terms: form.warranty_terms,
      technical_notes: form.technical_notes,
      items: items.value.map((it, idx) => ({
        ...it,
        sort_order: idx,
      })),
    }

    const res = props.initialQuote
      ? await api.put<{ data: Quote }>(`/quotes/${props.initialQuote.id}`, payload)
      : await api.post<{ data: Quote }>('/quotes', payload)

    emit('saved', res.data)
  } catch (err: any) {
    errorMsg.value = err?.message || 'No se pudo guardar la cotización.'
  } finally {
    saving.value = false
  }
}

onMounted(loadInitialData)
</script>

<template>
  <div class="space-y-6">
    <div v-if="errorMsg" class="p-3 rounded-lg bg-rose-50 border border-rose-200 text-rose-700 text-sm">
      {{ errorMsg }}
    </div>

    <!-- Cabecera Cliente / Sede / Lead -->
    <div class="bg-white rounded-[6px] border border-slate-200 p-5 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
      <div>
        <label class="block text-xs font-semibold text-slate-600 mb-1">Cliente *</label>
        <select v-model="form.client_id" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
          <option value="">Seleccionar cliente...</option>
          <option v-for="c in clients" :key="c.id" :value="c.id">{{ c.business_name }}</option>
        </select>
      </div>

      <div>
        <label class="block text-xs font-semibold text-slate-600 mb-1">Contacto</label>
        <select v-model="form.contact_id" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
          <option value="">Sin contacto específico</option>
          <option v-for="ct in selectedClient?.contacts || []" :key="ct.id" :value="ct.id">
            {{ ct.full_name }}
          </option>
        </select>
      </div>

      <div>
        <label class="block text-xs font-semibold text-slate-600 mb-1">Sede de Instalación</label>
        <select v-model="form.site_id" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
          <option value="">Dirección fiscal</option>
          <option v-for="st in selectedClient?.sites || []" :key="st.id" :value="st.id">
            {{ st.site_name }}
          </option>
        </select>
      </div>

      <div>
        <label class="block text-xs font-semibold text-slate-600 mb-1">Oportunidad (Lead)</label>
        <select v-model="form.lead_id" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
          <option value="">Opcional</option>
          <option v-for="l in leads" :key="l.id" :value="l.id">
            {{ l.code }} - {{ l.title }}
          </option>
        </select>
      </div>

      <div>
        <label class="block text-xs font-semibold text-slate-600 mb-1">Moneda</label>
        <select v-model="form.currency" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
          <option value="PEN">Soles (PEN)</option>
          <option value="USD">Dólares (USD)</option>
        </select>
      </div>

      <div>
        <label class="block text-xs font-semibold text-slate-600 mb-1">Emisión</label>
        <input v-model="form.issue_date" type="date" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
      </div>

      <div>
        <label class="block text-xs font-semibold text-slate-600 mb-1">Válida hasta</label>
        <input v-model="form.valid_until" type="date" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
      </div>

      <div>
        <label class="block text-xs font-semibold text-slate-600 mb-1">Empresa Emisora (Multi-Empresa)</label>
        <select v-model="form.company_profile_id" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
          <option v-for="comp in companies" :key="comp.id" :value="comp.id">{{ comp.name }} ({{ comp.ruc }})</option>
        </select>
      </div>

      <div>
        <label class="block text-xs font-semibold text-slate-600 mb-1">Descuento Global</label>
        <input v-model.number="form.discount_amount" type="number" min="0" step="0.01" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
      </div>
    </div>

    <!-- Buscador Rápido de Catálogo + Sección Activa -->
    <div class="bg-white rounded-[6px] border border-slate-200 p-5 space-y-4">
      <div class="flex flex-wrap items-end gap-3 justify-between">
        <div class="flex flex-wrap items-end gap-3">
          <div>
            <label class="block text-xs font-semibold text-slate-600 mb-1">Sección Activa</label>
            <select v-model="activeSection" class="rounded-lg border border-slate-300 px-3 py-2 text-sm font-medium">
              <option v-for="sec in sections" :key="sec" :value="sec">{{ sec }}</option>
            </select>
          </div>
          <div class="flex items-center gap-1.5">
            <input
              v-model="newSectionName"
              type="text"
              placeholder="Nueva sección..."
              class="rounded-lg border border-slate-300 px-3 py-2 text-sm"
            >
            <button
              type="button"
              class="px-3 py-2 rounded-lg bg-slate-100 hover:bg-slate-200 text-xs font-semibold text-slate-700"
              @click="addSection"
            >
              + Sección
            </button>
          </div>
        </div>

        <div class="flex flex-wrap items-center gap-2">
          <div class="flex items-center gap-1.5">
            <Layers class="w-4 h-4 text-cyan-700" />
            <select v-model="selectedTemplateId" class="rounded-lg border border-slate-300 px-3 py-2 text-xs font-medium">
              <option value="">Insertar Kit / Plantilla BOM...</option>
              <option v-for="tpl in templates" :key="tpl.id" :value="tpl.id">
                {{ tpl.name }} ({{ tpl.items_count ?? tpl.items?.length ?? 0 }} ítems)
              </option>
            </select>
            <button
              v-if="selectedTemplateId"
              type="button"
              class="px-3 py-2 rounded-lg bg-cyan-600 text-white text-xs font-semibold"
              @click="applyTemplate"
            >
              Insertar
            </button>
          </div>

          <button
            type="button"
            class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg border border-cyan-200 bg-cyan-50 hover:bg-cyan-100 text-cyan-800 text-xs font-semibold"
            @click="addFreeItem"
          >
            <Plus class="w-3.5 h-3.5" /> Añadir Partida Libre
          </button>
        </div>
      </div>

      <div class="relative">
        <Search class="w-4 h-4 text-slate-400 absolute left-3 top-2.5" />
        <input
          v-model="catalogSearch"
          type="text"
          placeholder="Buscar equipo, cable o servicio en el catálogo para agregar..."
          class="w-full pl-9 pr-3 py-2 rounded-lg border border-slate-300 text-sm"
        >
      </div>

      <div class="flex gap-2 overflow-x-auto pb-1">
        <button
          v-for="cat in catalogResults.slice(0, 8)"
          :key="cat.id"
          type="button"
          class="shrink-0 text-left px-3 py-2 rounded-lg border border-slate-200 hover:border-indigo-400 hover:bg-cyan-50/40 transition-colors"
          @click="addCatalogItem(cat)"
        >
          <div class="text-[11px] font-mono font-bold text-cyan-700">{{ cat.sku }}</div>
          <div class="text-xs font-semibold text-slate-900 max-w-[180px] truncate">{{ cat.name }}</div>
          <div class="text-[11px] text-slate-500">{{ formatMoney(cat.sale_price, form.currency) }} / {{ cat.unit }}</div>
        </button>
      </div>
    </div>

    <!-- Tabla de Partidas -->
    <div class="tq-panel overflow-x-auto">
      <table class="w-full text-sm">
        <thead class="bg-slate-900 text-white">
          <tr class="text-left text-xs uppercase tracking-wider">
            <th class="px-3 py-2.5">Sección</th>
            <th class="px-3 py-2.5">Descripción</th>
            <th class="px-3 py-2.5 w-20">Und.</th>
            <th class="px-3 py-2.5 w-24 text-right">Cant.</th>
            <th class="px-3 py-2.5 w-28 text-right">P. Unit.</th>
            <th class="px-3 py-2.5 w-20 text-right">Desc.%</th>
            <th class="px-3 py-2.5 w-28 text-right">Importe</th>
            <th class="px-3 py-2.5 w-12"></th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <tr v-for="(it, idx) in items" :key="idx" class="hover:bg-slate-50/70">
            <td class="px-3 py-2">
              <input v-model="it.section_name" type="text" class="w-full rounded border border-slate-200 px-2 py-1 text-xs">
            </td>
            <td class="px-3 py-2">
              <input v-model="it.description" type="text" class="w-full rounded border border-slate-200 px-2 py-1 text-xs font-medium">
            </td>
            <td class="px-3 py-2">
              <select v-model="it.unit" class="w-full rounded border border-slate-200 px-1.5 py-1 text-xs">
                <option value="UND">UND</option>
                <option value="MTR">MTR</option>
                <option value="PTO">PTO</option>
                <option value="CJA">CJA</option>
                <option value="GLB">GLB</option>
                <option value="HRS">HRS</option>
              </select>
            </td>
            <td class="px-3 py-2">
              <input v-model.number="it.quantity" type="number" min="0.01" step="0.01" class="w-full rounded border border-slate-200 px-2 py-1 text-xs text-right">
            </td>
            <td class="px-3 py-2">
              <input v-model.number="it.unit_price" type="number" min="0" step="0.01" class="w-full rounded border border-slate-200 px-2 py-1 text-xs text-right">
            </td>
            <td class="px-3 py-2">
              <input v-model.number="it.discount_percent" type="number" min="0" max="100" step="0.5" class="w-full rounded border border-slate-200 px-2 py-1 text-xs text-right">
            </td>
            <td class="px-3 py-2 text-right font-bold text-slate-900 whitespace-nowrap">
              {{ formatMoney(lineTotal(it), form.currency) }}
            </td>
            <td class="px-3 py-2 text-center">
              <button type="button" class="text-slate-400 hover:text-rose-600" @click="removeItem(idx)">
                <Trash2 class="w-4 h-4" />
              </button>
            </td>
          </tr>
          <tr v-if="!items.length">
            <td colspan="8" class="py-8 text-center text-slate-400 text-sm">
              Selecciona ítems del catálogo o añade una partida libre para comenzar.
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Condiciones + Totales + Panel de Rentabilidad Interna -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
      <div class="lg:col-span-7 bg-white rounded-[6px] border border-slate-200 p-5 space-y-3">
        <h4 class="text-sm font-bold text-slate-900">Condiciones Comerciales y Técnicas</h4>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
          <div>
            <label class="block text-xs font-semibold text-slate-600 mb-1">Forma de Pago</label>
            <input v-model="form.payment_terms" type="text" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
          </div>
          <div>
            <label class="block text-xs font-semibold text-slate-600 mb-1">Tiempo de Entrega</label>
            <input v-model="form.delivery_time" type="text" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
          </div>
        </div>
        <div>
          <label class="block text-xs font-semibold text-slate-600 mb-1">Garantía</label>
          <input v-model="form.warranty_terms" type="text" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
        </div>
        <div>
          <label class="block text-xs font-semibold text-slate-600 mb-1">Notas Técnicas</label>
          <textarea v-model="form.technical_notes" rows="2" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" />
        </div>
      </div>

      <div class="lg:col-span-5 space-y-4">
        <div class="bg-white rounded-[6px] border border-slate-200 p-5 space-y-2 text-sm">
          <div class="flex justify-between text-slate-600">
            <span>Subtotal</span>
            <span class="font-semibold text-slate-900">{{ formatMoney(totals.subtotal, form.currency) }}</span>
          </div>
          <div v-if="totals.discount > 0" class="flex justify-between text-rose-600">
            <span>Descuento Global</span>
            <span>- {{ formatMoney(totals.discount, form.currency) }}</span>
          </div>
          <div class="flex justify-between text-slate-600">
            <span>IGV (18%)</span>
            <span class="font-semibold text-slate-900">{{ formatMoney(totals.tax, form.currency) }}</span>
          </div>
          <div class="flex justify-between pt-2 border-t border-slate-200 text-lg font-bold text-cyan-800">
            <span>TOTAL</span>
            <span>{{ formatMoney(totals.total, form.currency) }}</span>
          </div>
        </div>

        <!-- Panel de Rentabilidad Interna -->
        <div v-if="canViewInternalCosts" class="bg-slate-900 text-slate-100 rounded-[6px] p-4 space-y-2 text-xs">
          <div class="flex items-center gap-1.5 font-bold uppercase tracking-wider text-indigo-300">
            <Calculator class="w-4 h-4" /> Rentabilidad Interna (Solo Comercial)
          </div>
          <div class="flex justify-between">
            <span class="text-slate-400">Costo Total Estimado:</span>
            <span class="font-semibold">{{ formatMoney(totals.totalCost, form.currency) }}</span>
          </div>
          <div class="flex justify-between">
            <span class="text-slate-400">Venta Neta (sin IGV):</span>
            <span class="font-semibold">{{ formatMoney(totals.netSubtotal, form.currency) }}</span>
          </div>
          <div class="flex justify-between pt-1.5 border-t border-slate-800 text-sm font-bold">
            <span>Margen Bruto:</span>
            <span :class="totals.marginPercent >= 25 ? 'text-emerald-400' : 'text-amber-400'">
              {{ formatMoney(totals.marginAmount, form.currency) }} ({{ totals.marginPercent }}%)
            </span>
          </div>
        </div>

        <button
          type="button"
          :disabled="saving"
          class="w-full py-3 rounded-[6px] bg-cyan-600 hover:bg-cyan-700 disabled:opacity-60 text-white font-semibold text-sm flex items-center justify-center gap-2 shadow"
          @click="submitQuote"
        >
          <Save class="w-4 h-4" />
          {{ saving ? 'Guardando Cotización...' : (initialQuote ? 'Actualizar Cotización' : 'Guardar Cotización') }}
        </button>
      </div>
    </div>
  </div>
</template>
