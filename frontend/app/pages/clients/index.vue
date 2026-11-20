<script setup lang="ts">
import { Search, Plus, Eye, Pencil, X } from 'lucide-vue-next'
import type { Client, DocumentType, PaginatedResponse } from '~/types'

const api = useApi()
const clients = ref<Client[]>([])
const search = ref('')
const loading = ref(false)

const showModal = ref(false)
const editingId = ref<number | null>(null)
const saving = ref(false)
const formError = ref('')

const form = reactive({
  document_type: 'RUC' as DocumentType,
  document_number: '',
  business_name: '',
  trade_name: '',
  sector: '',
  fiscal_address: '',
  phone: '',
  email: '',
})

async function loadClients() {
  loading.value = true
  try {
    const res = await api.get<PaginatedResponse<Client>>('/clients', {
      search: search.value || undefined,
      per_page: 50,
    })
    clients.value = res.data
  } finally {
    loading.value = false
  }
}

function openCreate() {
  editingId.value = null
  formError.value = ''
  Object.assign(form, {
    document_type: 'RUC',
    document_number: '',
    business_name: '',
    trade_name: '',
    sector: '',
    fiscal_address: '',
    phone: '',
    email: '',
  })
  showModal.value = true
}

function openEdit(client: Client) {
  editingId.value = client.id
  formError.value = ''
  Object.assign(form, {
    document_type: client.document_type,
    document_number: client.document_number,
    business_name: client.business_name,
    trade_name: client.trade_name || '',
    sector: client.sector || '',
    fiscal_address: client.fiscal_address || '',
    phone: client.phone || '',
    email: client.email || '',
  })
  showModal.value = true
}

async function saveClient() {
  saving.value = true
  formError.value = ''
  try {
    if (editingId.value) {
      await api.put(`/clients/${editingId.value}`, form)
    } else {
      await api.post('/clients', form)
    }
    showModal.value = false
    await loadClients()
  } catch (err: any) {
    formError.value = err?.message || 'Error al guardar cliente.'
  } finally {
    saving.value = false
  }
}

watchDebounced(search, loadClients, { debounce: 300 })
onMounted(loadClients)
</script>

<template>
  <div class="space-y-5">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
      <div>
        <h2 class="text-2xl font-bold text-slate-900 tracking-tight">Clientes Corporativos</h2>
        <p class="text-sm text-slate-500">Directorio de empresas, contactos y sedes de instalación.</p>
      </div>
      <button
        type="button"
        class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-lg bg-cyan-600 hover:bg-cyan-700 text-white text-xs font-semibold"
        @click="openCreate"
      >
        <Plus class="w-4 h-4" /> Nuevo Cliente
      </button>
    </div>

    <div class="bg-white rounded-[6px] border border-slate-200 p-4">
      <div class="relative max-w-md">
        <Search class="w-4 h-4 text-slate-400 absolute left-3 top-2.5" />
        <input
          v-model="search"
          type="text"
          placeholder="Buscar por RUC, DNI o Razón Social..."
          class="w-full pl-9 pr-3 py-2 rounded-lg border border-slate-300 text-sm focus:outline-none focus:ring-2 focus:ring-cyan-600"
        >
      </div>
    </div>

    <div class="tq-panel overflow-x-auto">
      <table class="w-full text-sm">
        <thead class="bg-slate-50 border-b border-slate-200">
          <tr class="text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
            <th class="px-4 py-3">Documento</th>
            <th class="px-4 py-3">Razón Social</th>
            <th class="px-4 py-3">Sector</th>
            <th class="px-4 py-3">Contacto</th>
            <th class="px-4 py-3 text-center">Sedes</th>
            <th class="px-4 py-3 text-center">Cotiz.</th>
            <th class="px-4 py-3 text-right">Acciones</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <tr v-for="c in clients" :key="c.id" class="hover:bg-slate-50">
            <td class="px-4 py-3 font-mono text-xs font-semibold text-slate-700">
              {{ c.document_type }} {{ c.document_number }}
            </td>
            <td class="px-4 py-3">
              <div class="font-semibold text-slate-900">{{ c.business_name }}</div>
              <div v-if="c.trade_name" class="text-xs text-slate-400">{{ c.trade_name }}</div>
            </td>
            <td class="px-4 py-3 text-slate-600">{{ c.sector || '—' }}</td>
            <td class="px-4 py-3 text-xs text-slate-600">
              <div>{{ c.email || '—' }}</div>
              <div class="text-slate-400">{{ c.phone || '' }}</div>
            </td>
            <td class="px-4 py-3 text-center font-semibold text-slate-700">{{ c.sites_count ?? 0 }}</td>
            <td class="px-4 py-3 text-center font-semibold text-cyan-700">{{ c.quotes_count ?? 0 }}</td>
            <td class="px-4 py-3 text-right whitespace-nowrap">
              <div class="inline-flex items-center gap-1">
                <NuxtLink
                  :to="`/clients/${c.id}`"
                  class="p-1.5 rounded-lg text-slate-500 hover:text-cyan-700 hover:bg-cyan-50"
                  title="Ver detalle, contactos y sedes"
                >
                  <Eye class="w-4 h-4" />
                </NuxtLink>
                <button
                  type="button"
                  class="p-1.5 rounded-lg text-slate-500 hover:text-slate-900 hover:bg-slate-100"
                  title="Editar"
                  @click="openEdit(c)"
                >
                  <Pencil class="w-4 h-4" />
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Modal Cliente -->
    <div v-if="showModal" class="fixed inset-0 z-50 bg-slate-900/60 flex items-center justify-center p-4">
      <div class="bg-white rounded-[6px] max-w-lg w-full p-6 shadow-xl space-y-4">
        <div class="flex items-center justify-between">
          <h3 class="text-lg font-bold text-slate-900">
            {{ editingId ? 'Editar Cliente' : 'Nuevo Cliente' }}
          </h3>
          <button type="button" class="text-slate-400 hover:text-slate-600" @click="showModal = false">
            <X class="w-5 h-5" />
          </button>
        </div>

        <div v-if="formError" class="p-3 rounded-lg bg-rose-50 text-rose-700 text-xs">
          {{ formError }}
        </div>

        <form class="space-y-3" @submit.prevent="saveClient">
          <div class="grid grid-cols-3 gap-3">
            <div>
              <label class="block text-xs font-semibold text-slate-600 mb-1">Tipo Doc.</label>
              <select v-model="form.document_type" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                <option value="RUC">RUC</option>
                <option value="DNI">DNI</option>
                <option value="CE">CE</option>
                <option value="PASAPORTE">PASAPORTE</option>
              </select>
            </div>
            <div class="col-span-2">
              <label class="block text-xs font-semibold text-slate-600 mb-1">Número de Documento *</label>
              <input
                v-model="form.document_number"
                type="text"
                required
                maxlength="20"
                class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"
              >
            </div>
          </div>

          <div>
            <label class="block text-xs font-semibold text-slate-600 mb-1">Razón Social *</label>
            <input
              v-model="form.business_name"
              type="text"
              required
              class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"
            >
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-xs font-semibold text-slate-600 mb-1">Nombre Comercial</label>
              <input v-model="form.trade_name" type="text" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
            </div>
            <div>
              <label class="block text-xs font-semibold text-slate-600 mb-1">Sector</label>
              <input v-model="form.sector" type="text" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
            </div>
          </div>

          <div>
            <label class="block text-xs font-semibold text-slate-600 mb-1">Dirección Fiscal</label>
            <input v-model="form.fiscal_address" type="text" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-xs font-semibold text-slate-600 mb-1">Teléfono</label>
              <input v-model="form.phone" type="text" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
            </div>
            <div>
              <label class="block text-xs font-semibold text-slate-600 mb-1">Correo Corporativo</label>
              <input v-model="form.email" type="email" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
            </div>
          </div>

          <div class="flex justify-end gap-2 pt-2">
            <button
              type="button"
              class="px-4 py-2 rounded-lg border border-slate-200 text-xs font-semibold text-slate-700"
              @click="showModal = false"
            >
              Cancelar
            </button>
            <button
              type="submit"
              :disabled="saving"
              class="px-4 py-2 rounded-lg bg-cyan-600 hover:bg-cyan-700 text-white text-xs font-semibold"
            >
              {{ saving ? 'Guardando...' : 'Guardar Cliente' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>
