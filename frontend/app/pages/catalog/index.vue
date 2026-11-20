<script setup lang="ts">
import { Search, Plus, Pencil, X } from 'lucide-vue-next'
import type {
  CatalogCategory,
  CatalogItem,
  CatalogItemType,
  MeasurementUnit,
  PaginatedResponse,
} from '~/types'

const api = useApi()
const { formatMoney } = useFormatters()

const items = ref<CatalogItem[]>([])
const categories = ref<CatalogCategory[]>([])
const search = ref('')
const filterType = ref<string>('')
const showModal = ref(false)
const editingId = ref<number | null>(null)
const saving = ref(false)

const form = reactive({
  category_id: '' as number | '',
  sku: '',
  name: '',
  description: '',
  item_type: 'equipo' as CatalogItemType,
  unit: 'UND' as MeasurementUnit,
  unit_cost: 0,
  sale_price: 0,
  is_active: true,
})

function marginPercent(item: CatalogItem): number {
  const cost = Number(item.unit_cost)
  const price = Number(item.sale_price)
  if (price <= 0) return 0
  return Math.round(((price - cost) / price) * 1000) / 10
}

async function loadData() {
  const [itemsRes, catsRes] = await Promise.all([
    api.get<PaginatedResponse<CatalogItem>>('/catalog-items', {
      search: search.value || undefined,
      item_type: filterType.value || undefined,
      per_page: 100,
    }),
    api.get<{ data: CatalogCategory[] }>('/catalog-categories'),
  ])
  items.value = itemsRes.data
  categories.value = catsRes.data
}

function openCreate() {
  editingId.value = null
  Object.assign(form, {
    category_id: categories.value[0]?.id || '',
    sku: '',
    name: '',
    description: '',
    item_type: 'equipo',
    unit: 'UND',
    unit_cost: 0,
    sale_price: 0,
    is_active: true,
  })
  showModal.value = true
}

function openEdit(item: CatalogItem) {
  editingId.value = item.id
  Object.assign(form, {
    category_id: item.category_id,
    sku: item.sku,
    name: item.name,
    description: item.description || '',
    item_type: item.item_type,
    unit: item.unit,
    unit_cost: Number(item.unit_cost),
    sale_price: Number(item.sale_price),
    is_active: item.is_active,
  })
  showModal.value = true
}

async function saveItem() {
  saving.value = true
  try {
    if (editingId.value) {
      await api.put(`/catalog-items/${editingId.value}`, form)
    } else {
      await api.post('/catalog-items', form)
    }
    showModal.value = false
    await loadData()
  } finally {
    saving.value = false
  }
}

watchDebounced([search, filterType], loadData, { debounce: 250 })
onMounted(loadData)
</script>

<template>
  <div class="space-y-5">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
      <div>
        <h2 class="text-2xl font-bold text-slate-900 tracking-tight">Catálogo Técnico</h2>
        <p class="text-sm text-slate-500">Equipos, materiales, servicios de instalación y márgenes de rentabilidad.</p>
      </div>
      <button
        type="button"
        class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-lg bg-cyan-600 hover:bg-cyan-700 text-white text-xs font-semibold"
        @click="openCreate"
      >
        <Plus class="w-4 h-4" /> Nuevo Ítem
      </button>
    </div>

    <div class="bg-white rounded-[6px] border border-slate-200 p-4 flex flex-wrap gap-3 items-center justify-between">
      <div class="relative flex-1 min-w-[220px] max-w-md">
        <Search class="w-4 h-4 text-slate-400 absolute left-3 top-2.5" />
        <input
          v-model="search"
          type="text"
          placeholder="Buscar por SKU o nombre..."
          class="w-full pl-9 pr-3 py-2 rounded-lg border border-slate-300 text-sm"
        >
      </div>

      <div class="flex flex-wrap gap-1.5">
        <button
          v-for="opt in [
            { value: '', label: 'Todos' },
            { value: 'equipo', label: 'Equipo' },
            { value: 'material', label: 'Material' },
            { value: 'servicio', label: 'Servicio' },
            { value: 'mantenimiento', label: 'Mantenimiento' },
          ]"
          :key="opt.value"
          type="button"
          class="px-3 py-1.5 rounded-lg text-xs font-semibold border transition-colors"
          :class="filterType === opt.value ? 'bg-slate-900 text-white border-slate-900' : 'bg-white text-slate-600 border-slate-200 hover:bg-slate-50'"
          @click="filterType = opt.value"
        >
          {{ opt.label }}
        </button>
      </div>
    </div>

    <div class="tq-panel overflow-x-auto">
      <table class="w-full text-sm">
        <thead class="bg-slate-50 border-b border-slate-200">
          <tr class="text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
            <th class="px-4 py-3">SKU</th>
            <th class="px-4 py-3">Descripción</th>
            <th class="px-4 py-3">Tipo</th>
            <th class="px-4 py-3 text-center">Und.</th>
            <th class="px-4 py-3 text-right">Costo</th>
            <th class="px-4 py-3 text-right">Precio Venta</th>
            <th class="px-4 py-3 text-right">Margen</th>
            <th class="px-4 py-3 text-right">Editar</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <tr v-for="item in items" :key="item.id" class="hover:bg-slate-50">
            <td class="px-4 py-3 font-mono text-xs font-bold text-cyan-700">{{ item.sku }}</td>
            <td class="px-4 py-3">
              <div class="font-semibold text-slate-900">{{ item.name }}</div>
              <div class="text-xs text-slate-400">{{ item.category?.name }}</div>
            </td>
            <td class="px-4 py-3 capitalize text-slate-600">{{ item.item_type }}</td>
            <td class="px-4 py-3 text-center font-mono text-xs">{{ item.unit }}</td>
            <td class="px-4 py-3 text-right text-slate-600">{{ formatMoney(item.unit_cost) }}</td>
            <td class="px-4 py-3 text-right font-semibold text-slate-900">{{ formatMoney(item.sale_price) }}</td>
            <td class="px-4 py-3 text-right">
              <span
                class="px-2 py-0.5 rounded-full text-xs font-bold"
                :class="marginPercent(item) >= 30 ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700'"
              >
                {{ marginPercent(item) }}%
              </span>
            </td>
            <td class="px-4 py-3 text-right">
              <button
                type="button"
                class="p-1.5 rounded-lg text-slate-500 hover:text-cyan-700 hover:bg-cyan-50"
                @click="openEdit(item)"
              >
                <Pencil class="w-4 h-4" />
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Modal CRUD -->
    <div v-if="showModal" class="fixed inset-0 z-50 bg-slate-900/60 flex items-center justify-center p-4">
      <div class="bg-white rounded-[6px] max-w-lg w-full p-6 shadow-xl space-y-4">
        <div class="flex items-center justify-between">
          <h3 class="text-lg font-bold text-slate-900">
            {{ editingId ? 'Editar Ítem' : 'Nuevo Ítem de Catálogo' }}
          </h3>
          <button type="button" class="text-slate-400 hover:text-slate-600" @click="showModal = false">
            <X class="w-5 h-5" />
          </button>
        </div>

        <form class="space-y-3" @submit.prevent="saveItem">
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-xs font-semibold text-slate-600 mb-1">Categoría *</label>
              <select v-model="form.category_id" required class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
              </select>
            </div>
            <div>
              <label class="block text-xs font-semibold text-slate-600 mb-1">SKU *</label>
              <input v-model="form.sku" type="text" required class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
            </div>
          </div>

          <div>
            <label class="block text-xs font-semibold text-slate-600 mb-1">Nombre *</label>
            <input v-model="form.name" type="text" required class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-xs font-semibold text-slate-600 mb-1">Tipo</label>
              <select v-model="form.item_type" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                <option value="equipo">Equipo</option>
                <option value="material">Material</option>
                <option value="servicio">Servicio</option>
                <option value="mantenimiento">Mantenimiento</option>
              </select>
            </div>
            <div>
              <label class="block text-xs font-semibold text-slate-600 mb-1">Unidad</label>
              <select v-model="form.unit" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                <option value="UND">UND</option>
                <option value="MTR">MTR</option>
                <option value="PTO">PTO</option>
                <option value="CJA">CJA</option>
                <option value="GLB">GLB</option>
                <option value="HRS">HRS</option>
              </select>
            </div>
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-xs font-semibold text-slate-600 mb-1">Costo Unitario *</label>
              <input v-model.number="form.unit_cost" type="number" step="0.01" min="0" required class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
            </div>
            <div>
              <label class="block text-xs font-semibold text-slate-600 mb-1">Precio Venta *</label>
              <input v-model.number="form.sale_price" type="number" step="0.01" min="0" required class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
            </div>
          </div>

          <div class="flex justify-end gap-2 pt-2">
            <button type="button" class="px-4 py-2 rounded-lg border border-slate-200 text-xs font-semibold text-slate-700" @click="showModal = false">
              Cancelar
            </button>
            <button type="submit" :disabled="saving" class="px-4 py-2 rounded-lg bg-cyan-600 hover:bg-cyan-700 text-white text-xs font-semibold">
              {{ saving ? 'Guardando...' : 'Guardar Ítem' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>
