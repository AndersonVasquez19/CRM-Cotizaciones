<script setup lang="ts">
import { ArrowLeft, FileDown, CheckSquare, Square, Save } from 'lucide-vue-next'
import type { Project, ProjectMaterial } from '~/types'

const route = useRoute()
const api = useApi()
const project = ref<Project | null>(null)
const notes = ref('')

async function loadProject() {
  const res = await api.get<{ data: Project }>(`/projects/${route.params.id}`)
  project.value = res.data
  notes.value = res.data.conformity_notes || ''
}

async function toggleTask(taskId: number) {
  if (!project.value) return
  const res = await api.patch<{ data: Project }>(`/projects/${project.value.id}/tasks/${taskId}/toggle`)
  project.value = { ...project.value, ...res.data }
}

async function saveMaterial(mat: ProjectMaterial) {
  if (!project.value) return
  await api.patch(`/projects/${project.value.id}/materials/${mat.id}`, {
    used_qty: Number(mat.used_qty),
    serial_numbers: mat.serial_numbers,
  })
}

async function saveNotes() {
  if (!project.value) return
  await api.put(`/projects/${project.value.id}`, { conformity_notes: notes.value })
}

async function downloadConformity() {
  if (!project.value) return
  await api.downloadPdf(`/projects/${project.value.id}/conformity-pdf`, `Acta-Conformidad-${project.value.code}.pdf`)
}

onMounted(loadProject)
</script>

<template>
  <div v-if="project" class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
      <div class="flex items-center gap-3">
        <NuxtLink to="/projects" class="p-2 rounded-lg border border-slate-200 bg-white hover:bg-slate-50">
          <ArrowLeft class="w-4 h-4 text-slate-600" />
        </NuxtLink>
        <div>
          <div class="text-xs font-mono font-bold text-cyan-700">{{ project.code }} · Avance {{ project.progress_percent }}%</div>
          <h2 class="text-2xl font-bold text-slate-900">{{ project.title }}</h2>
          <p class="text-sm text-slate-500">{{ project.client?.business_name }}</p>
        </div>
      </div>

      <button type="button" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold" @click="downloadConformity">
        <FileDown class="w-4 h-4" /> Descargar Acta de Conformidad PDF
      </button>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <!-- Checklist de Tareas -->
      <div class="bg-white rounded-[6px] border border-slate-200 p-5 space-y-3">
        <h3 class="text-base font-bold text-slate-900">Tareas de Instalación</h3>
        <div class="space-y-2">
          <button
            v-for="task in project.tasks || []"
            :key="task.id"
            type="button"
            class="w-full text-left flex items-start gap-3 p-3 rounded-lg border border-slate-100 hover:bg-slate-50"
            @click="toggleTask(task.id)"
          >
            <CheckSquare v-if="task.is_completed" class="w-5 h-5 text-emerald-600 shrink-0 mt-0.5" />
            <Square v-else class="w-5 h-5 text-slate-400 shrink-0 mt-0.5" />
            <div>
              <div class="text-[11px] font-bold uppercase text-cyan-700">{{ task.section_name }}</div>
              <div class="text-sm font-medium" :class="task.is_completed ? 'line-through text-slate-400' : 'text-slate-900'">
                {{ task.title }}
              </div>
            </div>
          </button>
        </div>
      </div>

      <!-- Materiales y Series -->
      <div class="bg-white rounded-[6px] border border-slate-200 p-5 space-y-4">
        <h3 class="text-base font-bold text-slate-900">Equipos, Materiales y Números de Serie</h3>
        <div class="space-y-3">
          <div v-for="mat in project.materials || []" :key="mat.id" class="p-3 rounded-lg bg-slate-50 border border-slate-200 space-y-2 text-xs">
            <div class="font-bold text-slate-900">{{ mat.description }} ({{ mat.planned_qty }} {{ mat.unit }})</div>
            <div class="grid grid-cols-3 gap-2">
              <div>
                <label class="text-slate-500">Cant. Usada</label>
                <input v-model.number="mat.used_qty" type="number" step="0.1" class="w-full rounded border border-slate-300 px-2 py-1 bg-white">
              </div>
              <div class="col-span-2">
                <label class="text-slate-500">Números de Serie (S/N)</label>
                <input v-model="mat.serial_numbers" type="text" placeholder="Ej. SN-001, SN-002" class="w-full rounded border border-slate-300 px-2 py-1 bg-white">
              </div>
            </div>
            <div class="flex justify-end">
              <button type="button" class="inline-flex items-center gap-1 px-2.5 py-1 rounded bg-slate-900 text-white font-semibold" @click="saveMaterial(mat)">
                <Save class="w-3 h-3" /> Guardar
              </button>
            </div>
          </div>
        </div>

        <div class="pt-3 border-t border-slate-200 space-y-2">
          <label class="block text-xs font-bold text-slate-700">Observaciones para Acta de Conformidad</label>
          <textarea v-model="notes" rows="2" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" />
          <button type="button" class="px-3 py-1.5 rounded-lg bg-cyan-600 text-white text-xs font-semibold" @click="saveNotes">
            Guardar Observaciones
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
