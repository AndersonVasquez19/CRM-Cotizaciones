<script setup lang="ts">
import { Plus, CheckCircle2, X } from 'lucide-vue-next'
import type { CatalogItem, PaginatedResponse, PurchaseOrder, Supplier, Warehouse, WarehouseStock } from '~/types'

const api = useApi()
const { formatMoney } = useFormatters()

const tab = ref<'stocks' | 'orders'>('stocks')
const stocks = ref<WarehouseStock[]>([])
const warehouses = ref<Warehouse[]>([])
const suppliers = ref<Supplier[]>([])
const orders = ref<PurchaseOrder[]>([])
const catalogItems = ref<CatalogItem[]>([])

const showPoModal = ref(false)
const poForm = reactive({
  supplier_id: '' as number | '',
  warehouse_id: '' as number | '',
  catalog_item_id: '' as number | '',
  quantity: 5,
  unit_cost: 0,
})

async function loadAll() {
  const [stkRes, whRes, supRes, poRes, catRes] = await Promise.all([
    api.get<{ data: WarehouseStock[] }>('/inventory/stocks'),
    api.get<{ data: Warehouse[] }>('/inventory/warehouses'),
    api.get<{ data: Supplier[] }>('/suppliers'),
    api.get<{ data: PurchaseOrder[] }>('/purchase-orders'),
    api.get<PaginatedResponse<CatalogItem>>('/catalog-items', { per_page: 100 }),
  ])
  stocks.value = stkRes.data
  warehouses.value = whRes.data
  suppliers.value = supRes.data
  orders.value = poRes.data
  catalogItems.value = catRes.data
  if (warehouses.value[0]) poForm.warehouse_id = warehouses.value[0].id
  if (suppliers.value[0]) poForm.supplier_id = suppliers.value[0].id
}

async function createPo() {
  if (!poForm.supplier_id || !poForm.warehouse_id || !poForm.catalog_item_id) return
  await api.post('/purchase-orders', {
    supplier_id: Number(poForm.supplier_id),
    warehouse_id: Number(poForm.warehouse_id),
    items: [{ catalog_item_id: Number(poForm.catalog_item_id), quantity: poForm.quantity, unit_cost: poForm.unit_cost }],
  })
  showPoModal.value = false
  await loadAll()
}

async function receivePo(id: number) {
  await api.post(`/purchase-orders/${id}/receive`)
  await loadAll()
}

onMounted(loadAll)
</script>

<template>
  <div class="space-y-5">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
      <div>
        <h2 class="text-2xl font-bold text-slate-900 tracking-tight">Almacén, Stock y Compras</h2>
        <p class="text-sm text-slate-500">Control de existencias, reservas de proyectos y órdenes de compra.</p>
      </div>
      <button type="button" class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-lg bg-cyan-600 hover:bg-cyan-700 text-white text-xs font-semibold" @click="showPoModal = true">
        <Plus class="w-4 h-4" /> Nueva Orden de Compra
      </button>
    </div>

    <div class="border-b border-slate-200 flex gap-6">
      <button type="button" class="pb-3 text-sm font-semibold border-b-2" :class="tab === 'stocks' ? 'border-cyan-600 text-cyan-700' : 'border-transparent text-slate-500'" @click="tab = 'stocks'">
        Stock en Almacén ({{ stocks.length }})
      </button>
      <button type="button" class="pb-3 text-sm font-semibold border-b-2" :class="tab === 'orders' ? 'border-cyan-600 text-cyan-700' : 'border-transparent text-slate-500'" @click="tab = 'orders'">
        Órdenes de Compra ({{ orders.length }})
      </button>
    </div>

    <div v-if="tab === 'stocks'" class="tq-panel overflow-x-auto">
      <table class="w-full text-sm">
        <thead class="bg-slate-50 border-b border-slate-200 text-xs uppercase text-slate-500">
          <tr class="text-left">
            <th class="px-4 py-3">SKU</th>
            <th class="px-4 py-3">Producto</th>
            <th class="px-4 py-3">Almacén</th>
            <th class="px-4 py-3 text-right">Stock Físico</th>
            <th class="px-4 py-3 text-right">Reservado</th>
            <th class="px-4 py-3 text-right">Disponible</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <tr v-for="s in stocks" :key="s.id">
            <td class="px-4 py-3 font-mono text-xs font-bold text-cyan-700">{{ s.catalog_item?.sku }}</td>
            <td class="px-4 py-3 font-medium text-slate-900">{{ s.catalog_item?.name }}</td>
            <td class="px-4 py-3 text-slate-600">{{ s.warehouse?.name }}</td>
            <td class="px-4 py-3 text-right font-semibold">{{ s.quantity }}</td>
            <td class="px-4 py-3 text-right text-amber-600 font-semibold">{{ s.reserved_quantity }}</td>
            <td class="px-4 py-3 text-right font-bold" :class="Number(s.quantity) - Number(s.reserved_quantity) > 0 ? 'text-emerald-600' : 'text-rose-600'">
              {{ Number(s.quantity) - Number(s.reserved_quantity) }}
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div v-else class="tq-panel overflow-x-auto">
      <table class="w-full text-sm">
        <thead class="bg-slate-50 border-b border-slate-200 text-xs uppercase text-slate-500">
          <tr class="text-left">
            <th class="px-4 py-3">Orden</th>
            <th class="px-4 py-3">Proveedor</th>
            <th class="px-4 py-3">Almacén</th>
            <th class="px-4 py-3">Estado</th>
            <th class="px-4 py-3 text-right">Total</th>
            <th class="px-4 py-3 text-right">Acción</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <tr v-for="o in orders" :key="o.id">
            <td class="px-4 py-3 font-mono font-bold text-slate-900">{{ o.po_number }}</td>
            <td class="px-4 py-3">{{ o.supplier?.business_name }}</td>
            <td class="px-4 py-3 text-slate-600">{{ o.warehouse?.name }}</td>
            <td class="px-4 py-3 capitalize font-semibold">{{ o.status }}</td>
            <td class="px-4 py-3 text-right font-bold">{{ formatMoney(o.total) }}</td>
            <td class="px-4 py-3 text-right">
              <button
                v-if="o.status !== 'recibida'"
                type="button"
                class="inline-flex items-center gap-1 px-3 py-1 rounded-lg bg-emerald-600 text-white text-xs font-semibold"
                @click="receivePo(o.id)"
              >
                <CheckCircle2 class="w-3.5 h-3.5" /> Recibir en Almacén
              </button>
              <span v-else class="text-xs text-emerald-700 font-semibold">Ingresada</span>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div v-if="showPoModal" class="fixed inset-0 z-50 bg-slate-900/60 flex items-center justify-center p-4">
      <div class="bg-white rounded-[6px] max-w-md w-full p-6 space-y-4">
        <div class="flex justify-between items-center">
          <h3 class="text-lg font-bold">Nueva Orden de Compra</h3>
          <button type="button" @click="showPoModal = false"><X class="w-5 h-5" /></button>
        </div>
        <form class="space-y-3" @submit.prevent="createPo">
          <select v-model="poForm.supplier_id" required class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
            <option v-for="sup in suppliers" :key="sup.id" :value="sup.id">{{ sup.business_name }}</option>
          </select>
          <select v-model="poForm.warehouse_id" required class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
            <option v-for="w in warehouses" :key="w.id" :value="w.id">{{ w.name }}</option>
          </select>
          <select v-model="poForm.catalog_item_id" required class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
            <option value="">Seleccionar producto...</option>
            <option v-for="c in catalogItems" :key="c.id" :value="c.id">{{ c.sku }} - {{ c.name }}</option>
          </select>
          <div class="grid grid-cols-2 gap-3">
            <input v-model.number="poForm.quantity" type="number" min="1" required placeholder="Cantidad" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
            <input v-model.number="poForm.unit_cost" type="number" min="0" step="0.01" required placeholder="Costo Unit." class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
          </div>
          <div class="flex justify-end gap-2">
            <button type="button" class="px-4 py-2 rounded-lg border text-xs font-semibold" @click="showPoModal = false">Cancelar</button>
            <button type="submit" class="px-4 py-2 rounded-lg bg-cyan-600 text-white text-xs font-semibold">Emitir Orden</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>
