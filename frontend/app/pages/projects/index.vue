<script setup lang="ts">
import { Eye, FileDown } from 'lucide-vue-next'
import type { PaginatedResponse, Project } from '~/types'

const api = useApi()
const projects = ref<Project[]>([])
const filterStatus = ref('')

async function loadProjects() {
  const res = await api.get<PaginatedResponse<Project>>('/projects', {
    status: filterStatus.value || undefined,
  })
  projects.value = res.data
}

async function downloadConformity(p: Project) {
  await api.downloadPdf(`/projects/${p.id}/conformity-pdf`, `Acta-Conformidad-${p.code}.pdf`)
}

watch(filterStatus, loadProjects)
onMounted(loadProjects)
</script>

<template>
  <div class="space-y-5">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
      <div>
        <h2 class="text-2xl font-bold text-slate-900 tracking-tight">Proyectos y Órdenes de Trabajo</h2>
        <p class="text-sm text-slate-500">Seguimiento de instalaciones generadas automáticamente al aprobar cotizaciones.</p>
      </div>
      <div class="flex gap-1.5">
        <button
          v-for="opt in [
            { value: '', label: 'Todos' },
            { value: 'planificado', label: 'Planificado' },
            { value: 'en_ejecucion', label: 'En Ejecución' },
            { value: 'completado', label: 'Completado' },
          ]"
          :key="opt.value"
          type="button"
          class="px-3 py-1.5 rounded-lg text-xs font-semibold border"
          :class="filterStatus === opt.value ? 'bg-slate-900 text-white border-slate-900' : 'bg-white text-slate-600 border-slate-200'"
          @click="filterStatus = opt.value"
        >
          {{ opt.label }}
        </button>
      </div>
    </div>

    <div class="tq-panel overflow-x-auto">
      <table class="w-full text-sm">
        <thead class="bg-slate-50 border-b border-slate-200 text-xs uppercase text-slate-500">
          <tr class="text-left">
            <th class="px-4 py-3">Código</th>
            <th class="px-4 py-3">Proyecto / Cliente</th>
            <th class="px-4 py-3">Cotización</th>
            <th class="px-4 py-3">Estado</th>
            <th class="px-4 py-3 w-40">Avance</th>
            <th class="px-4 py-3 text-right">Acciones</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <tr v-for="p in projects" :key="p.id" class="hover:bg-slate-50">
            <td class="px-4 py-3 font-mono text-xs font-bold text-cyan-700">{{ p.code }}</td>
            <td class="px-4 py-3">
              <div class="font-semibold text-slate-900">{{ p.title }}</div>
              <div class="text-xs text-slate-400">{{ p.client?.business_name }}</div>
            </td>
            <td class="px-4 py-3 text-xs text-slate-600">{{ p.quote?.quote_number }} v{{ p.quote?.version }}</td>
            <td class="px-4 py-3 capitalize text-xs font-semibold">{{ p.status.replace('_', ' ') }}</td>
            <td class="px-4 py-3">
              <div class="flex items-center gap-2">
                <div class="flex-1 h-2 bg-slate-100 rounded-full overflow-hidden">
                  <div class="h-full bg-cyan-600" :style="{ width: `${p.progress_percent}%` }" />
                </div>
                <span class="text-xs font-bold text-slate-700">{{ p.progress_percent }}%</span>
              </div>
            </td>
            <td class="px-4 py-3 text-right whitespace-nowrap">
              <div class="inline-flex gap-1">
                <NuxtLink :to="`/projects/${p.id}`" class="p-1.5 rounded-lg text-slate-500 hover:text-cyan-700 hover:bg-cyan-50" title="Ver tareas y series">
                  <Eye class="w-4 h-4" />
                </NuxtLink>
                <button type="button" class="p-1.5 rounded-lg text-slate-500 hover:text-emerald-600 hover:bg-emerald-50" title="Acta de Conformidad PDF" @click="downloadConformity(p)">
                  <FileDown class="w-4 h-4" />
                </button>
              </div>
            </td>
          </tr>
          <tr v-if="!projects.length">
            <td colspan="6" class="py-8 text-center text-slate-400">No hay proyectos aún. Aprueba una cotización para generarlo automáticamente.</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>
