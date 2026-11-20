<script setup lang="ts">
import { ShieldCheck, FileDown, CheckCircle2, XCircle, Eraser } from 'lucide-vue-next'
import type { Quote } from '~/types'

definePageMeta({
  layout: false,
})

const route = useRoute()
const config = useRuntimeConfig()
const { formatMoney, formatDate } = useFormatters()

const quote = ref<Quote | null>(null)
const loading = ref(true)
const errorMsg = ref('')
const submitting = ref(false)
const decisionDone = ref(false)

const form = reactive({
  signer_name: '',
  signer_document: '',
  signer_email: '',
  comment: '',
})

const canvasRef = ref<HTMLCanvasElement | null>(null)
let isDrawing = false

const token = computed(() => String(route.params.token))
const pdfDownloadUrl = computed(() => `${config.public.apiBase.replace('/api/v1', '/api/public')}/quotes/${token.value}/pdf`)

async function loadPublicQuote() {
  loading.value = true
  try {
    const publicBase = config.public.apiBase.replace('/api/v1', '/api/public')
    const res = await ($fetch as <T>(u: string) => Promise<T>)<{ data: Quote }>(
      `${publicBase}/quotes/${token.value}`,
    )
    quote.value = res.data
    if (res.data.status === 'aprobada' || res.data.status === 'rechazada') {
      decisionDone.value = true
    }
  } catch {
    errorMsg.value = 'El enlace de cotización no es válido o ha expirado.'
  } finally {
    loading.value = false
  }
}

function getPos(e: MouseEvent | TouchEvent) {
  const canvas = canvasRef.value
  if (!canvas) return { x: 0, y: 0 }
  const rect = canvas.getBoundingClientRect()
  if ('touches' in e && e.touches[0]) {
    return { x: e.touches[0].clientX - rect.left, y: e.touches[0].clientY - rect.top }
  }
  const me = e as MouseEvent
  return { x: me.clientX - rect.left, y: me.clientY - rect.top }
}

function startDraw(e: MouseEvent | TouchEvent) {
  const ctx = canvasRef.value?.getContext('2d')
  if (!ctx) return
  isDrawing = true
  const { x, y } = getPos(e)
  ctx.beginPath()
  ctx.moveTo(x, y)
}

function moveDraw(e: MouseEvent | TouchEvent) {
  if (!isDrawing) return
  const ctx = canvasRef.value?.getContext('2d')
  if (!ctx) return
  const { x, y } = getPos(e)
  ctx.lineWidth = 2
  ctx.lineCap = 'round'
  ctx.strokeStyle = '#0f172a'
  ctx.lineTo(x, y)
  ctx.stroke()
}

function endDraw() {
  isDrawing = false
}

function clearCanvas() {
  const canvas = canvasRef.value
  const ctx = canvas?.getContext('2d')
  if (canvas && ctx) {
    ctx.clearRect(0, 0, canvas.width, canvas.height)
  }
}

async function submitDecision(decision: 'aprobada' | 'rechazada') {
  if (!form.signer_name) {
    errorMsg.value = 'Ingresa tu nombre completo para registrar la decisión.'
    return
  }
  errorMsg.value = ''
  submitting.value = true
  try {
    const signatureData = canvasRef.value ? canvasRef.value.toDataURL('image/png') : null
    const publicBase = config.public.apiBase.replace('/api/v1', '/api/public')
    await ($fetch as <T>(u: string, opts: any) => Promise<T>)(
      `${publicBase}/quotes/${token.value}/decision`,
      {
        method: 'POST',
        body: {
          decision,
          ...form,
          signature_data: signatureData,
        },
      },
    )
    await loadPublicQuote()
  } catch (err: any) {
    errorMsg.value = err?.data?.message || 'No se pudo registrar tu decisión.'
  } finally {
    submitting.value = false
  }
}

onMounted(loadPublicQuote)
</script>

<template>
  <div class="min-h-screen bg-[var(--tq-canvas)] py-8 px-4">
    <div class="max-w-4xl mx-auto space-y-5">
      <div class="bg-[#0B0F14] text-white rounded-[6px] border border-[#1E2733] p-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div class="flex items-center gap-3">
          <span class="w-2.5 h-2.5 rounded-sm bg-cyan-400 shadow-[0_0_10px_rgba(34,211,238,0.6)] shrink-0" />
          <div>
            <h1 class="text-base font-bold tracking-tight">TECHQUOTE INTEGRACIONES S.A.C.</h1>
            <p class="text-xs text-slate-400">Portal Seguro de Revisión y Aprobación de Cotizaciones</p>
          </div>
        </div>
        <a
          v-if="quote"
          :href="pdfDownloadUrl"
          target="_blank"
          class="tq-btn-secondary text-xs py-2 px-3.5"
        >
          <FileDown class="w-4 h-4 text-cyan-700" /> Descargar PDF Oficial
        </a>
      </div>

      <div v-if="loading" class="tq-panel p-10 text-center text-[var(--tq-ink-muted)] text-sm">
        Cargando propuesta técnica...
      </div>

      <div v-else-if="errorMsg && !quote" class="bg-rose-50 border border-rose-200 rounded-[6px] p-8 text-center text-rose-700 font-medium text-sm">
        {{ errorMsg }}
      </div>

      <template v-else-if="quote">
        <div class="tq-panel p-6 space-y-5">
          <div class="flex flex-wrap justify-between gap-4 border-b border-[var(--tq-border)] pb-4">
            <div>
              <h2 class="text-2xl font-bold text-[var(--tq-ink)] tq-code">{{ quote.quote_number }} <span class="text-base font-medium text-[var(--tq-ink-muted)]">v{{ quote.version }}</span></h2>
              <p class="text-sm text-[var(--tq-ink-muted)] mt-1">{{ quote.client?.business_name }} — {{ quote.client?.document_type }} {{ quote.client?.document_number }}</p>
            </div>
            <div class="text-right text-sm space-y-0.5">
              <div class="text-[var(--tq-ink-muted)]">Emisión: <strong class="text-[var(--tq-ink)] tq-num">{{ formatDate(quote.issue_date) }}</strong></div>
              <div class="text-[var(--tq-ink-muted)]">Válida hasta: <strong class="text-[var(--tq-ink)] tq-num">{{ formatDate(quote.valid_until) }}</strong></div>
              <div class="mt-1.5 inline-block px-2.5 py-0.5 rounded-[4px] text-xs font-semibold uppercase bg-cyan-50 text-cyan-800 border border-cyan-200">
                {{ quote.status }}
              </div>
            </div>
          </div>

          <div class="overflow-x-auto border border-[var(--tq-border)] rounded-[5px]">
            <table class="tq-table">
              <thead>
                <tr>
                  <th>Sección</th>
                  <th>Descripción</th>
                  <th class="text-center">Und.</th>
                  <th class="text-right">Cant.</th>
                  <th class="text-right">P. Unit.</th>
                  <th class="text-right">Importe</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="it in quote.items || []" :key="it.id">
                  <td class="text-xs font-semibold text-[var(--tq-ink-muted)]">{{ it.section_name }}</td>
                  <td class="text-[var(--tq-ink)]">{{ it.description }}</td>
                  <td class="text-center tq-code text-xs">{{ it.unit }}</td>
                  <td class="text-right tq-num">{{ it.quantity }}</td>
                  <td class="text-right tq-num">{{ formatMoney(it.unit_price, quote.currency) }}</td>
                  <td class="text-right font-semibold text-[var(--tq-ink)] tq-num">{{ formatMoney(it.line_total ?? 0, quote.currency) }}</td>
                </tr>
              </tbody>
            </table>
          </div>

          <div class="flex justify-end pt-2">
            <div class="w-72 space-y-1.5 text-sm tq-num">
              <div class="flex justify-between text-[var(--tq-ink-muted)]">
                <span>Subtotal</span>
                <span>{{ formatMoney(quote.subtotal, quote.currency) }}</span>
              </div>
              <div class="flex justify-between text-[var(--tq-ink-muted)]">
                <span>IGV (18%)</span>
                <span>{{ formatMoney(quote.tax_amount, quote.currency) }}</span>
              </div>
              <div class="flex justify-between text-lg font-bold text-cyan-800 pt-2 border-t border-[var(--tq-border)]">
                <span>TOTAL</span>
                <span>{{ formatMoney(quote.total, quote.currency) }}</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Firma Digital o Resumen de Decisión -->
        <div v-if="decisionDone && quote.latest_acceptance" class="bg-emerald-50 border border-emerald-200 rounded-[6px] p-6 space-y-2">
          <div class="text-sm font-bold text-emerald-800 uppercase">
            Decisión Registrada: {{ quote.latest_acceptance.decision }}
          </div>
          <p class="text-sm text-slate-700">
            Firmado por <strong>{{ quote.latest_acceptance.signer_name }}</strong>. Gracias por su confirmación.
          </p>
          <img
            v-if="quote.latest_acceptance.signature_data"
            :src="quote.latest_acceptance.signature_data"
            alt="Firma"
            class="h-20 bg-white border border-emerald-200 rounded-[4px] p-2"
          >
        </div>

        <div v-else class="tq-panel p-6 space-y-4">
          <h3 class="text-base font-bold text-[var(--tq-ink)]">Aprobación Electrónica y Firma Digital</h3>
          <div v-if="errorMsg" class="p-3 rounded-[4px] bg-rose-50 border border-rose-200 text-rose-700 text-xs">{{ errorMsg }}</div>

          <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <input v-model="form.signer_name" type="text" placeholder="Nombre completo del firmante *" class="tq-input">
            <input v-model="form.signer_document" type="text" placeholder="DNI / RUC" class="tq-input">
            <input v-model="form.signer_email" type="email" placeholder="Correo corporativo" class="tq-input">
          </div>

          <div>
            <div class="flex items-center justify-between mb-1.5">
              <label class="text-xs font-semibold text-[var(--tq-ink-muted)]">Firma Manuscrita Digital (mouse o pantalla táctil)</label>
              <button type="button" class="inline-flex items-center gap-1 text-xs text-[var(--tq-ink-muted)] hover:text-[var(--tq-ink)]" @click="clearCanvas">
                <Eraser class="w-3.5 h-3.5" /> Limpiar firma
              </button>
            </div>
            <canvas
              ref="canvasRef"
              width="600"
              height="150"
              class="w-full max-w-xl h-36 border border-dashed border-[var(--tq-border-strong)] rounded-[5px] bg-[var(--tq-surface-subtle)] touch-none cursor-crosshair"
              @mousedown="startDraw"
              @mousemove="moveDraw"
              @mouseup="endDraw"
              @mouseleave="endDraw"
              @touchstart.prevent="startDraw"
              @touchmove.prevent="moveDraw"
              @touchend="endDraw"
            />
          </div>

          <textarea v-model="form.comment" rows="2" placeholder="Observaciones o número de Orden de Compra (opcional)" class="tq-input" />

          <div class="flex flex-wrap gap-3">
            <button
              type="button"
              :disabled="submitting"
              class="inline-flex items-center gap-2 px-5 py-2.5 rounded-[5px] bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold transition-colors"
              @click="submitDecision('aprobada')"
            >
              <CheckCircle2 class="w-4 h-4" /> Aprobar y Firmar Cotización
            </button>
            <button
              type="button"
              :disabled="submitting"
              class="inline-flex items-center gap-2 px-5 py-2.5 rounded-[5px] border border-rose-200 bg-rose-50 hover:bg-rose-100 text-rose-700 text-sm font-semibold transition-colors"
              @click="submitDecision('rechazada')"
            >
              <XCircle class="w-4 h-4" /> Rechazar / Solicitar Revisión
            </button>
          </div>
        </div>
      </template>
    </div>
  </div>
</template>
