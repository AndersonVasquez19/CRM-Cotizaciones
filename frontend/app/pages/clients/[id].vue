<script setup lang="ts">
import { ArrowLeft, Plus, Trash2, MapPin, Users, FileText } from 'lucide-vue-next'
import type { Client } from '~/types'

const route = useRoute()
const api = useApi()
const { formatMoney, formatDate, quoteStatusMeta } = useFormatters()

const client = ref<Client | null>(null)
const activeTab = ref<'contacts' | 'sites' | 'quotes'>('contacts')

const contactForm = reactive({
  full_name: '',
  position: '',
  email: '',
  phone: '',
  is_primary: false,
})

const siteForm = reactive({
  site_name: '',
  address: '',
  district_city: '',
  reference: '',
})

async function loadClient() {
  const res = await api.get<{ data: Client }>(`/clients/${route.params.id}`)
  client.value = res.data
}

async function addContact() {
  if (!client.value || !contactForm.full_name) return
  await api.post(`/clients/${client.value.id}/contacts`, contactForm)
  Object.assign(contactForm, { full_name: '', position: '', email: '', phone: '', is_primary: false })
  await loadClient()
}

async function deleteContact(contactId: number) {
  if (!client.value) return
  await api.del(`/clients/${client.value.id}/contacts/${contactId}`)
  await loadClient()
}

async function addSite() {
  if (!client.value || !siteForm.site_name || !siteForm.address) return
  await api.post(`/clients/${client.value.id}/sites`, siteForm)
  Object.assign(siteForm, { site_name: '', address: '', district_city: '', reference: '' })
  await loadClient()
}

async function deleteSite(siteId: number) {
  if (!client.value) return
  await api.del(`/clients/${client.value.id}/sites/${siteId}`)
  await loadClient()
}

onMounted(loadClient)
</script>

<template>
  <div v-if="client" class="space-y-6">
    <div class="flex items-center gap-3">
      <NuxtLink to="/clients" class="p-2 rounded-lg border border-slate-200 bg-white hover:bg-slate-50">
        <ArrowLeft class="w-4 h-4 text-slate-600" />
      </NuxtLink>
      <div>
        <div class="text-xs font-mono font-semibold text-cyan-700">
          {{ client.document_type }} {{ client.document_number }}
        </div>
        <h2 class="text-2xl font-bold text-slate-900">{{ client.business_name }}</h2>
      </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 bg-white p-5 rounded-[6px] border border-slate-200 text-sm">
      <div>
        <span class="text-xs text-slate-400">Sector</span>
        <div class="font-medium text-slate-900">{{ client.sector || '—' }}</div>
      </div>
      <div>
        <span class="text-xs text-slate-400">Dirección Fiscal</span>
        <div class="font-medium text-slate-900">{{ client.fiscal_address || '—' }}</div>
      </div>
      <div>
        <span class="text-xs text-slate-400">Correo / Teléfono</span>
        <div class="font-medium text-slate-900">{{ client.email || '—' }} · {{ client.phone || '—' }}</div>
      </div>
    </div>

    <!-- Tabs -->
    <div class="border-b border-slate-200 flex gap-6">
      <button
        type="button"
        class="pb-3 text-sm font-semibold border-b-2 flex items-center gap-2 transition-colors"
        :class="activeTab === 'contacts' ? 'border-cyan-600 text-cyan-700' : 'border-transparent text-slate-500'"
        @click="activeTab = 'contacts'"
      >
        <Users class="w-4 h-4" /> Contactos ({{ client.contacts?.length || 0 }})
      </button>
      <button
        type="button"
        class="pb-3 text-sm font-semibold border-b-2 flex items-center gap-2 transition-colors"
        :class="activeTab === 'sites' ? 'border-cyan-600 text-cyan-700' : 'border-transparent text-slate-500'"
        @click="activeTab = 'sites'"
      >
        <MapPin class="w-4 h-4" /> Sedes de Instalación ({{ client.sites?.length || 0 }})
      </button>
      <button
        type="button"
        class="pb-3 text-sm font-semibold border-b-2 flex items-center gap-2 transition-colors"
        :class="activeTab === 'quotes' ? 'border-cyan-600 text-cyan-700' : 'border-transparent text-slate-500'"
        @click="activeTab = 'quotes'"
      >
        <FileText class="w-4 h-4" /> Cotizaciones ({{ client.quotes?.length || 0 }})
      </button>
    </div>

    <!-- Tab Contactos -->
    <div v-if="activeTab === 'contacts'" class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <div class="lg:col-span-2 bg-white rounded-[6px] border border-slate-200 p-4 space-y-3">
        <div
          v-for="contact in client.contacts || []"
          :key="contact.id"
          class="flex items-center justify-between p-3 rounded-lg border border-slate-100 bg-slate-50"
        >
          <div>
            <div class="font-semibold text-sm text-slate-900">
              {{ contact.full_name }}
              <span v-if="contact.is_primary" class="ml-2 px-2 py-0.5 text-[10px] rounded-full bg-indigo-100 text-cyan-800">
                Principal
              </span>
            </div>
            <div class="text-xs text-slate-500">
              {{ contact.position || 'Sin cargo' }} · {{ contact.email || '—' }} · {{ contact.phone || '—' }}
            </div>
          </div>
          <button
            type="button"
            class="p-1.5 text-slate-400 hover:text-rose-600"
            @click="deleteContact(contact.id)"
          >
            <Trash2 class="w-4 h-4" />
          </button>
        </div>
      </div>

      <form class="bg-white rounded-[6px] border border-slate-200 p-4 space-y-3 h-fit" @submit.prevent="addContact">
        <h4 class="text-sm font-bold text-slate-900">Agregar Contacto</h4>
        <input v-model="contactForm.full_name" type="text" required placeholder="Nombre completo *" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
        <input v-model="contactForm.position" type="text" placeholder="Cargo (ej. Jefe de TI)" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
        <input v-model="contactForm.email" type="email" placeholder="Correo electrónico" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
        <input v-model="contactForm.phone" type="text" placeholder="Teléfono" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
        <label class="flex items-center gap-2 text-xs text-slate-600">
          <input v-model="contactForm.is_primary" type="checkbox"> Contacto principal
        </label>
        <button type="submit" class="w-full py-2 rounded-lg bg-slate-900 text-white text-xs font-semibold flex items-center justify-center gap-1">
          <Plus class="w-3.5 h-3.5" /> Añadir Contacto
        </button>
      </form>
    </div>

    <!-- Tab Sedes -->
    <div v-if="activeTab === 'sites'" class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <div class="lg:col-span-2 bg-white rounded-[6px] border border-slate-200 p-4 space-y-3">
        <div
          v-for="site in client.sites || []"
          :key="site.id"
          class="flex items-center justify-between p-3 rounded-lg border border-slate-100 bg-slate-50"
        >
          <div>
            <div class="font-semibold text-sm text-slate-900">{{ site.site_name }}</div>
            <div class="text-xs text-slate-500">{{ site.address }} ({{ site.district_city || '—' }})</div>
          </div>
          <button type="button" class="p-1.5 text-slate-400 hover:text-rose-600" @click="deleteSite(site.id)">
            <Trash2 class="w-4 h-4" />
          </button>
        </div>
      </div>

      <form class="bg-white rounded-[6px] border border-slate-200 p-4 space-y-3 h-fit" @submit.prevent="addSite">
        <h4 class="text-sm font-bold text-slate-900">Agregar Sede de Instalación</h4>
        <input v-model="siteForm.site_name" type="text" required placeholder="Nombre sede *" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
        <input v-model="siteForm.address" type="text" required placeholder="Dirección *" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
        <input v-model="siteForm.district_city" type="text" placeholder="Distrito / Ciudad" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
        <input v-model="siteForm.reference" type="text" placeholder="Referencia" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
        <button type="submit" class="w-full py-2 rounded-lg bg-slate-900 text-white text-xs font-semibold flex items-center justify-center gap-1">
          <Plus class="w-3.5 h-3.5" /> Añadir Sede
        </button>
      </form>
    </div>

    <!-- Tab Cotizaciones -->
    <div v-if="activeTab === 'quotes'" class="bg-white rounded-[6px] border border-slate-200 overflow-x-auto">
      <table class="w-full text-sm">
        <thead class="bg-slate-50 border-b border-slate-200">
          <tr class="text-left text-xs font-semibold uppercase text-slate-500">
            <th class="px-4 py-3">Número</th>
            <th class="px-4 py-3">Fecha</th>
            <th class="px-4 py-3">Estado</th>
            <th class="px-4 py-3 text-right">Total</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <tr v-for="q in client.quotes || []" :key="q.id" class="hover:bg-slate-50">
            <td class="px-4 py-3 font-semibold text-cyan-700">
              <NuxtLink :to="`/quotes/${q.id}`">{{ q.quote_number }} v{{ q.version }}</NuxtLink>
            </td>
            <td class="px-4 py-3 text-slate-600">{{ formatDate(q.issue_date) }}</td>
            <td class="px-4 py-3">
              <span class="px-2 py-0.5 text-xs font-semibold rounded-full border" :class="quoteStatusMeta[q.status]?.badgeClass">
                {{ quoteStatusMeta[q.status]?.label }}
              </span>
            </td>
            <td class="px-4 py-3 text-right font-semibold text-slate-900">{{ formatMoney(q.total, q.currency) }}</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>
