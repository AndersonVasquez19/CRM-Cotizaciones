<script setup lang="ts">
import { Plus, Pencil, X, Shield } from 'lucide-vue-next'
import type { User, UserRole } from '~/types'

const api = useApi()
const users = ref<User[]>([])
const showModal = ref(false)
const editingId = ref<number | null>(null)
const saving = ref(false)
const errorMsg = ref('')

const form = reactive({
  name: '',
  email: '',
  password: '',
  role: 'comercial' as UserRole,
  phone: '',
  is_active: true,
})

async function loadUsers() {
  const res = await api.get<{ data: User[] }>('/users')
  users.value = res.data
}

function openCreate() {
  editingId.value = null
  errorMsg.value = ''
  Object.assign(form, { name: '', email: '', password: '', role: 'comercial', phone: '', is_active: true })
  showModal.value = true
}

function openEdit(u: User) {
  editingId.value = u.id
  errorMsg.value = ''
  Object.assign(form, {
    name: u.name,
    email: u.email,
    password: '',
    role: u.role,
    phone: u.phone || '',
    is_active: u.is_active,
  })
  showModal.value = true
}

async function saveUser() {
  saving.value = true
  errorMsg.value = ''
  try {
    if (editingId.value) {
      await api.put(`/users/${editingId.value}`, form)
    } else {
      await api.post('/users', form)
    }
    showModal.value = false
    await loadUsers()
  } catch (err: any) {
    errorMsg.value = err?.message || 'Error al guardar usuario.'
  } finally {
    saving.value = false
  }
}

onMounted(loadUsers)
</script>

<template>
  <div class="space-y-5">
    <div class="flex items-center justify-between">
      <div>
        <h2 class="text-2xl font-bold text-slate-900 tracking-tight">Equipo y Roles (RBAC)</h2>
        <p class="text-sm text-slate-500">Administra accesos, roles comerciales, técnicos y supervisores.</p>
      </div>
      <button
        type="button"
        class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-lg bg-cyan-600 hover:bg-cyan-700 text-white text-xs font-semibold"
        @click="openCreate"
      >
        <Plus class="w-4 h-4" /> Nuevo Usuario
      </button>
    </div>

    <div class="tq-panel overflow-x-auto">
      <table class="w-full text-sm">
        <thead class="bg-slate-50 border-b border-slate-200">
          <tr class="text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
            <th class="px-4 py-3">Nombre</th>
            <th class="px-4 py-3">Correo</th>
            <th class="px-4 py-3">Rol</th>
            <th class="px-4 py-3">Estado</th>
            <th class="px-4 py-3 text-right">Editar</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <tr v-for="u in users" :key="u.id" class="hover:bg-slate-50">
            <td class="px-4 py-3 font-semibold text-slate-900">{{ u.name }}</td>
            <td class="px-4 py-3 text-slate-600">{{ u.email }}</td>
            <td class="px-4 py-3">
              <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-cyan-50 text-cyan-800 border border-cyan-200">
                <Shield class="w-3 h-3" /> {{ u.role_label || u.role }}
              </span>
            </td>
            <td class="px-4 py-3">
              <span
                class="px-2 py-0.5 rounded-full text-xs font-semibold"
                :class="u.is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-500'"
              >
                {{ u.is_active ? 'Activo' : 'Inactivo' }}
              </span>
            </td>
            <td class="px-4 py-3 text-right">
              <button type="button" class="p-1.5 rounded-lg text-slate-500 hover:text-cyan-700 hover:bg-cyan-50" @click="openEdit(u)">
                <Pencil class="w-4 h-4" />
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div v-if="showModal" class="fixed inset-0 z-50 bg-slate-900/60 flex items-center justify-center p-4">
      <div class="bg-white rounded-[6px] max-w-md w-full p-6 shadow-xl space-y-4">
        <div class="flex items-center justify-between">
          <h3 class="text-lg font-bold text-slate-900">{{ editingId ? 'Editar Usuario' : 'Nuevo Usuario' }}</h3>
          <button type="button" class="text-slate-400 hover:text-slate-600" @click="showModal = false">
            <X class="w-5 h-5" />
          </button>
        </div>

        <div v-if="errorMsg" class="p-3 rounded-lg bg-rose-50 text-rose-700 text-xs">{{ errorMsg }}</div>

        <form class="space-y-3" @submit.prevent="saveUser">
          <div>
            <label class="block text-xs font-semibold text-slate-600 mb-1">Nombre completo *</label>
            <input v-model="form.name" type="text" required class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
          </div>
          <div>
            <label class="block text-xs font-semibold text-slate-600 mb-1">Correo *</label>
            <input v-model="form.email" type="email" required class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
          </div>
          <div>
            <label class="block text-xs font-semibold text-slate-600 mb-1">
              Contraseña {{ editingId ? '(opcional)' : '*' }}
            </label>
            <input v-model="form.password" type="password" :required="!editingId" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
          </div>
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-xs font-semibold text-slate-600 mb-1">Rol</label>
              <select v-model="form.role" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                <option value="admin">Administrador</option>
                <option value="supervisor">Supervisor</option>
                <option value="comercial">Comercial</option>
                <option value="tecnico">Técnico</option>
              </select>
            </div>
            <div>
              <label class="block text-xs font-semibold text-slate-600 mb-1">Teléfono</label>
              <input v-model="form.phone" type="text" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
            </div>
          </div>
          <label class="flex items-center gap-2 text-xs text-slate-700">
            <input v-model="form.is_active" type="checkbox"> Cuenta activa
          </label>
          <div class="flex justify-end gap-2 pt-2">
            <button type="button" class="px-4 py-2 rounded-lg border border-slate-200 text-xs font-semibold" @click="showModal = false">Cancelar</button>
            <button type="submit" :disabled="saving" class="px-4 py-2 rounded-lg bg-cyan-600 text-white text-xs font-semibold">
              {{ saving ? 'Guardando...' : 'Guardar' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>
