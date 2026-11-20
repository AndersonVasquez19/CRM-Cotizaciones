<script setup lang="ts">
import { ArrowLeft } from 'lucide-vue-next'
import type { Quote } from '~/types'

async function onSaved(quote: Quote) {
  await navigateTo(`/quotes/${quote.id}`)
}
</script>

<template>
  <div class="space-y-6">
    <div class="flex items-center gap-3">
      <NuxtLink to="/quotes" class="p-2 rounded-lg border border-slate-200 bg-white hover:bg-slate-50">
        <ArrowLeft class="w-4 h-4 text-slate-600" />
      </NuxtLink>
      <div>
        <h2 class="text-2xl font-bold text-slate-900 tracking-tight">Nueva Cotización Técnica</h2>
        <p class="text-sm text-slate-500">Constructor de partidas, secciones y cálculo de rentabilidad.</p>
      </div>
    </div>

    <QuoteBuilder @saved="onSaved" />
  </div>
</template>
