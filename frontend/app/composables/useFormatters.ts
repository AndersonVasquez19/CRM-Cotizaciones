import dayjs from 'dayjs'
import type { CurrencyCode, LeadStage, QuoteStatus, ServiceLine } from '~/types'

export function useFormatters() {
  function formatMoney(amount: number | string, currency: CurrencyCode = 'PEN'): string {
    const num = Number(amount || 0)
    const symbol = currency === 'USD' ? '$' : 'S/'
    return `${symbol} ${num.toLocaleString('en-US', {
      minimumFractionDigits: 2,
      maximumFractionDigits: 2,
    })}`
  }

  function formatDate(date?: string | null): string {
    if (!date) return '—'
    return dayjs(date).format('DD/MM/YYYY')
  }

  function formatDateTime(date?: string | null): string {
    if (!date) return '—'
    return dayjs(date).format('DD/MM/YYYY HH:mm')
  }

  const quoteStatusMeta: Record<QuoteStatus, { label: string; badgeClass: string }> = {
    borrador: { label: 'Borrador', badgeClass: 'bg-slate-100 text-slate-700 border-slate-200' },
    pendiente_aprobacion: { label: 'Pend. Aprobación', badgeClass: 'bg-amber-50 text-amber-800 border-amber-300' },
    enviada: { label: 'Enviada', badgeClass: 'bg-blue-50 text-blue-700 border-blue-200' },
    aprobada: { label: 'Aprobada', badgeClass: 'bg-emerald-50 text-emerald-700 border-emerald-200' },
    rechazada: { label: 'Rechazada', badgeClass: 'bg-rose-50 text-rose-700 border-rose-200' },
    vencida: { label: 'Vencida', badgeClass: 'bg-amber-50 text-amber-700 border-amber-200' },
  }

  const leadStageMeta: Record<LeadStage, { label: string; badgeClass: string; barClass: string }> = {
    nuevo: { label: 'Nuevo', badgeClass: 'bg-slate-100 text-slate-700 border-slate-200', barClass: 'bg-slate-500' },
    visita_tecnica: { label: 'Visita Técnica', badgeClass: 'bg-sky-50 text-sky-700 border-sky-200', barClass: 'bg-sky-500' },
    cotizado: { label: 'Cotizado', badgeClass: 'bg-cyan-50 text-cyan-800 border-cyan-200', barClass: 'bg-cyan-600' },
    negociacion: { label: 'Negociación', badgeClass: 'bg-amber-50 text-amber-700 border-amber-200', barClass: 'bg-amber-500' },
    ganado: { label: 'Ganado', badgeClass: 'bg-emerald-50 text-emerald-700 border-emerald-200', barClass: 'bg-emerald-600' },
    perdido: { label: 'Perdido', badgeClass: 'bg-rose-50 text-rose-700 border-rose-200', barClass: 'bg-rose-500' },
  }

  const serviceLineLabels: Record<ServiceLine, string> = {
    cctv: 'CCTV / Videovigilancia',
    cableado_estructurado: 'Cableado Estructurado',
    redes_wifi: 'Redes / Wi-Fi Corporativo',
    mantenimiento: 'Mantenimiento',
    integral: 'Proyecto Integral',
  }

  return {
    formatMoney,
    formatDate,
    formatDateTime,
    quoteStatusMeta,
    leadStageMeta,
    serviceLineLabels,
  }
}
