<script setup lang="ts">
import {
  ArrowLeft,
  Copy,
  FileDown,
  Send,
  CheckCircle,
  XCircle,
  Mail,
  Link2,
  ShieldAlert,
  Check,
} from 'lucide-vue-next'
import type { Quote, QuoteStatus } from '~/types'

const route = useRoute()
const api = useApi()
const auth = useAuthStore()
const { quoteStatusMeta, formatDateTime } = useFormatters()

const quote = ref<Quote | null>(null)
const busy = ref(false)
const feedbackMsg = ref('')
const copied = ref(false)

const canApproveMargin = computed(
  () => auth.user?.role === 'admin' || auth.user?.role === 'supervisor',
)

const portalUrl = computed(() => {
  if (!quote.value?.public_token || typeof window === 'undefined') return ''
  return `${window.location.origin}/p/quotes/${quote.value.public_token}`
})

async function loadQuote() {
  const res = await api.get<{ data: Quote }>(`/quotes/${route.params.id}`)
  quote.value = res.data
}

async function changeStatus(status: QuoteStatus) {
  if (!quote.value) return
  busy.value = true
  feedbackMsg.value = ''
  try {
    const res = await api.patch<{ data: Quote }>(`/quotes/${quote.value.id}/status`, { status })
    quote.value = res.data
    if (res.data.status === 'pendiente_aprobacion') {
      feedbackMsg.value =
        'Margen por debajo del umbral corporativo: la cotización quedó en Pendiente de Aprobación Gerencial.'
    }
  } finally {
    busy.value = false
  }
}

async function approveMargin() {
  if (!quote.value) return
  busy.value = true
  feedbackMsg.value = ''
  try {
    const res = await api.post<{ data: Quote }>(`/quotes/${quote.value.id}/approve-margin`, {
      notes: 'Aprobado desde panel gerencial',
    })
    quote.value = res.data
    feedbackMsg.value = 'Margen aprobado. Cotización marcada como Enviada.'
  } finally {
    busy.value = false
  }
}

async function sendEmail() {
  if (!quote.value) return
  busy.value = true
  feedbackMsg.value = ''
  try {
    const res = await api.post<{ message: string; data: Quote }>(`/quotes/${quote.value.id}/send-email`)
    quote.value = res.data
    feedbackMsg.value = res.message
  } catch (err: any) {
    feedbackMsg.value = err?.data?.message || err?.message || 'Error al enviar correo SMTP.'
  } finally {
    busy.value = false
  }
}

async function copyPortalLink() {
  if (!portalUrl.value) return
  await navigator.clipboard.writeText(portalUrl.value)
  copied.value = true
  setTimeout(() => (copied.value = false), 2000)
}

async function duplicateVersion() {
  if (!quote.value) return
  busy.value = true
  try {
    const res = await api.post<{ data: Quote }>(`/quotes/${quote.value.id}/duplicate`)
    await navigateTo(`/quotes/${res.data.id}`)
  } finally {
    busy.value = false
  }
}

async function downloadPdf() {
  if (!quote.value) return
  await api.downloadPdf(
    `/quotes/${quote.value.id}/pdf`,
    `${quote.value.quote_number}-v${quote.value.version}.pdf`,
  )
}

function onSaved(updated: Quote) {
  quote.value = updated
}

onMounted(loadQuote)
</script>

<template>
  <div v-if="quote" class="space-y-6">
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
      <div class="flex items-center gap-3">
        <NuxtLink to="/quotes" class="p-2 rounded-lg border border-slate-200 bg-white hover:bg-slate-50">
          <ArrowLeft class="w-4 h-4 text-slate-600" />
        </NuxtLink>
        <div>
          <div class="flex flex-wrap items-center gap-2">
            <h2 class="text-2xl font-bold text-slate-900">{{ quote.quote_number }}</h2>
            <span class="px-2 py-0.5 text-xs font-bold rounded bg-cyan-50 text-cyan-800 border border-cyan-200">
              v{{ quote.version }}
            </span>
            <span
              class="px-2.5 py-0.5 text-xs font-semibold rounded-full border"
              :class="quoteStatusMeta[quote.status]?.badgeClass"
            >
              {{ quoteStatusMeta[quote.status]?.label }}
            </span>
          </div>
          <p class="text-sm text-slate-500">{{ quote.client?.business_name }}</p>
        </div>
      </div>

      <div class="flex flex-wrap items-center gap-2">
        <button
          v-if="quote.public_token"
          type="button"
          class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg border border-cyan-200 bg-cyan-50 hover:bg-cyan-100 text-xs font-semibold text-cyan-800"
          @click="copyPortalLink"
        >
          <Check v-if="copied" class="w-4 h-4 text-emerald-600" />
          <Link2 v-else class="w-4 h-4" />
          {{ copied ? 'Enlace Copiado' : 'Portal Cliente' }}
        </button>

        <button
          type="button"
          :disabled="busy"
          class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-xs font-semibold text-slate-700"
          @click="sendEmail"
        >
          <Mail class="w-4 h-4 text-blue-600" /> Enviar Email
        </button>

        <button
          type="button"
          :disabled="busy"
          class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-xs font-semibold text-slate-700"
          @click="downloadPdf"
        >
          <FileDown class="w-4 h-4 text-emerald-600" /> PDF
        </button>

        <button
          type="button"
          :disabled="busy"
          class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-xs font-semibold text-slate-700"
          @click="duplicateVersion"
        >
          <Copy class="w-4 h-4 text-amber-600" /> Duplicar
        </button>

        <button
          v-if="quote.status === 'pendiente_aprobacion' && canApproveMargin"
          type="button"
          :disabled="busy"
          class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg bg-amber-600 hover:bg-amber-700 text-xs font-semibold text-white"
          @click="approveMargin"
        >
          <ShieldAlert class="w-3.5 h-3.5" /> Aprobar Margen
        </button>

        <button
          v-if="quote.status === 'borrador'"
          type="button"
          :disabled="busy"
          class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg bg-blue-600 hover:bg-blue-700 text-xs font-semibold text-white"
          @click="changeStatus('enviada')"
        >
          <Send class="w-3.5 h-3.5" /> Marcar Enviada
        </button>

        <button
          v-if="quote.status !== 'aprobada'"
          type="button"
          :disabled="busy"
          class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-xs font-semibold text-white"
          @click="changeStatus('aprobada')"
        >
          <CheckCircle class="w-3.5 h-3.5" /> Aprobar
        </button>

        <button
          v-if="quote.status !== 'rechazada'"
          type="button"
          :disabled="busy"
          class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg bg-rose-600 hover:bg-rose-700 text-xs font-semibold text-white"
          @click="changeStatus('rechazada')"
        >
          <XCircle class="w-3.5 h-3.5" /> Rechazar
        </button>
      </div>
    </div>

    <div
      v-if="feedbackMsg"
      class="p-3.5 rounded-[6px] bg-amber-50 border border-amber-200 text-amber-900 text-sm font-medium"
    >
      {{ feedbackMsg }}
    </div>

    <!-- Firma Digital recibida del Portal Cliente -->
    <div
      v-if="quote.latest_acceptance"
      class="p-4 rounded-[6px] bg-emerald-50 border border-emerald-200 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4"
    >
      <div>
        <div class="text-xs font-bold uppercase tracking-wider text-emerald-700">
          Decisión Registrada en Portal Cliente ({{ quote.latest_acceptance.decision }})
        </div>
        <div class="text-sm font-semibold text-slate-900 mt-0.5">
          Firmado por: {{ quote.latest_acceptance.signer_name }}
          <span v-if="quote.latest_acceptance.signer_document" class="text-slate-600 font-normal">
            (Doc: {{ quote.latest_acceptance.signer_document }})
          </span>
        </div>
        <div class="text-xs text-slate-500">
          Fecha: {{ formatDateTime(quote.latest_acceptance.accepted_at) }} · IP: {{ quote.latest_acceptance.ip_address || '—' }}
        </div>
      </div>
      <img
        v-if="quote.latest_acceptance.signature_data"
        :src="quote.latest_acceptance.signature_data"
        alt="Firma del cliente"
        class="h-16 bg-white border border-emerald-200 rounded-lg p-1"
      >
    </div>

    <QuoteBuilder :key="quote.id" :initial-quote="quote" @saved="onSaved" />

    <!-- Auditoría de Estados -->
    <div v-if="quote.status_logs?.length" class="bg-white rounded-[6px] border border-slate-200 p-5 space-y-3">
      <h4 class="text-sm font-bold text-slate-900">Historial de Estados</h4>
      <div class="space-y-2">
        <div
          v-for="log in quote.status_logs"
          :key="log.id"
          class="flex items-center justify-between text-xs p-2.5 rounded-lg bg-slate-50 border border-slate-100"
        >
          <div>
            <span class="font-semibold text-slate-900">{{ log.user?.name || 'Sistema' }}</span>
            cambió a <span class="font-bold uppercase text-cyan-700">{{ log.to_status }}</span>
            <span v-if="log.comment" class="text-slate-500"> — {{ log.comment }}</span>
          </div>
          <span class="text-slate-400">{{ formatDateTime(log.created_at) }}</span>
        </div>
      </div>
    </div>
  </div>
</template>
