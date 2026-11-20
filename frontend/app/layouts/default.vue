<script setup lang="ts">
import {
  LayoutDashboard,
  Kanban,
  Building2,
  Package,
  FileText,
  FolderKanban,
  Warehouse,
  Wrench,
  Wallet,
  BarChart3,
  Users,
  Settings,
  LogOut,
  Menu,
  X,
  Plus,
  Radio,
} from 'lucide-vue-next'

const auth = useAuthStore()
const route = useRoute()
const mobileOpen = ref(false)

interface NavGroup {
  label: string
  items: Array<{ to: string; label: string; icon: any }>
}

const navGroups = computed<NavGroup[]>(() => {
  const groups: NavGroup[] = [
    {
      label: 'Comercial',
      items: [
        { to: '/', label: 'Dashboard', icon: LayoutDashboard },
        { to: '/leads', label: 'Pipeline CRM', icon: Kanban },
        { to: '/clients', label: 'Clientes', icon: Building2 },
        { to: '/catalog', label: 'Catálogo Técnico', icon: Package },
        { to: '/quotes', label: 'Cotizaciones', icon: FileText },
      ],
    },
    {
      label: 'Ejecución & Campo',
      items: [
        { to: '/projects', label: 'Proyectos / OT', icon: FolderKanban },
        { to: '/inventory', label: 'Almacén & Compras', icon: Warehouse },
        { to: '/contracts', label: 'Contratos SLA', icon: Wrench },
      ],
    },
    {
      label: 'Control & Gerencia',
      items: [
        { to: '/finance', label: 'Cobranzas', icon: Wallet },
        { to: '/reports', label: 'Reportes BI', icon: BarChart3 },
      ],
    },
  ]

  if (auth.user?.role === 'admin') {
    groups[2]?.items.push(
      { to: '/users', label: 'Equipo y Roles', icon: Users },
      { to: '/settings', label: 'Configuración', icon: Settings },
    )
  } else if (auth.user?.role === 'supervisor') {
    groups[2]?.items.push({ to: '/settings', label: 'Auditoría y Reglas', icon: Settings })
  }

  return groups
})

const currentTitle = computed(() => {
  for (const group of navGroups.value) {
    for (const item of group.items) {
      if (isActive(item.to)) return item.label
    }
  }
  return 'Operaciones'
})

function isActive(path: string): boolean {
  if (path === '/') return route.path === '/'
  return route.path.startsWith(path)
}
</script>

<template>
  <div class="min-h-screen bg-[var(--tq-canvas)] text-[var(--tq-ink)] flex">
    <!-- Sidebar Desktop -->
    <aside class="hidden lg:flex lg:flex-col lg:w-64 bg-[var(--tq-rail)] text-slate-200 border-r border-[var(--tq-rail-border)] shrink-0">
      <div class="h-14 px-5 flex items-center justify-between border-b border-[var(--tq-rail-border)]">
        <NuxtLink to="/" class="flex items-center gap-2.5">
          <span class="w-2.5 h-2.5 rounded-sm bg-cyan-400 shadow-[0_0_8px_rgba(34,211,238,0.6)]" />
          <span class="font-bold text-[15px] tracking-tight text-white">TechQuote</span>
          <span class="tq-code text-[10px] px-1.5 py-0.5 rounded bg-[var(--tq-rail-elevated)] text-cyan-300 border border-[#243242]">
            CRM
          </span>
        </NuxtLink>
      </div>

      <nav class="flex-1 px-3 py-4 space-y-5 overflow-y-auto">
        <div v-for="group in navGroups" :key="group.label" class="space-y-1">
          <div class="px-3 pb-1 text-[11px] font-semibold text-slate-500">
            {{ group.label }}
          </div>
          <NuxtLink
            v-for="item in group.items"
            :key="item.to"
            :to="item.to"
            class="group flex items-center justify-between px-3 py-2 rounded-[5px] text-[13px] font-medium transition-colors"
            :class="
              isActive(item.to)
                ? 'bg-[var(--tq-rail-elevated)] text-white border border-[#263545]'
                : 'text-slate-400 hover:text-slate-100 hover:bg-[#111720] border border-transparent'
            "
          >
            <span class="flex items-center gap-2.5 min-w-0">
              <component
                :is="item.icon"
                class="w-4 h-4 shrink-0 transition-colors"
                :class="isActive(item.to) ? 'text-cyan-400' : 'text-slate-500 group-hover:text-slate-300'"
              />
              <span class="truncate">{{ item.label }}</span>
            </span>
            <span
              v-if="isActive(item.to)"
              class="w-1.5 h-1.5 rounded-full bg-cyan-400 shrink-0"
            />
          </NuxtLink>
        </div>
      </nav>

      <div class="p-3.5 border-t border-[var(--tq-rail-border)] bg-[#090C11]">
        <div class="flex items-center justify-between gap-2">
          <div class="min-w-0">
            <div class="text-[13px] font-semibold text-white truncate">
              {{ auth.user?.name || 'Usuario' }}
            </div>
            <div class="text-[11px] text-slate-400 truncate flex items-center gap-1.5 mt-0.5">
              <span class="w-1.5 h-1.5 rounded-full bg-emerald-400" />
              <span>{{ auth.user?.role_label || auth.user?.role }}</span>
            </div>
          </div>
          <button
            type="button"
            title="Cerrar sesión"
            class="p-2 rounded-[5px] text-slate-400 hover:text-white hover:bg-[var(--tq-rail-elevated)] transition-colors"
            @click="auth.logout()"
          >
            <LogOut class="w-4 h-4" />
          </button>
        </div>
      </div>
    </aside>

    <!-- Mobile Sidebar Overlay -->
    <div
      v-if="mobileOpen"
      class="fixed inset-0 z-40 bg-black/60 lg:hidden"
      @click="mobileOpen = false"
    />
    <aside
      v-if="mobileOpen"
      class="fixed inset-y-0 left-0 z-50 w-64 bg-[var(--tq-rail)] text-slate-200 flex flex-col lg:hidden border-r border-[var(--tq-rail-border)]"
    >
      <div class="h-14 px-5 flex items-center justify-between border-b border-[var(--tq-rail-border)]">
        <span class="font-bold text-white flex items-center gap-2">
          <span class="w-2 h-2 rounded-sm bg-cyan-400" />
          TechQuote CRM
        </span>
        <button type="button" class="p-1 text-slate-400 hover:text-white" @click="mobileOpen = false">
          <X class="w-5 h-5" />
        </button>
      </div>
      <nav class="flex-1 px-3 py-4 space-y-4 overflow-y-auto">
        <div v-for="group in navGroups" :key="group.label" class="space-y-1">
          <div class="px-3 pb-1 text-[11px] font-semibold text-slate-500">{{ group.label }}</div>
          <NuxtLink
            v-for="item in group.items"
            :key="item.to"
            :to="item.to"
            class="flex items-center gap-2.5 px-3 py-2 rounded-[5px] text-[13px] font-medium"
            :class="isActive(item.to) ? 'bg-[var(--tq-rail-elevated)] text-white' : 'text-slate-400 hover:text-white'"
            @click="mobileOpen = false"
          >
            <component :is="item.icon" class="w-4 h-4" :class="isActive(item.to) ? 'text-cyan-400' : 'text-slate-500'" />
            <span>{{ item.label }}</span>
          </NuxtLink>
        </div>
      </nav>
    </aside>

    <!-- Main Content -->
    <div class="flex-1 flex flex-col min-w-0">
      <header class="h-14 bg-white border-b border-[var(--tq-border)] px-4 lg:px-8 flex items-center justify-between sticky top-0 z-20">
        <div class="flex items-center gap-3 min-w-0">
          <button
            type="button"
            class="lg:hidden p-1.5 rounded-[5px] text-slate-600 hover:bg-slate-100"
            @click="mobileOpen = true"
          >
            <Menu class="w-5 h-5" />
          </button>
          <div class="flex items-center gap-2 text-sm min-w-0">
            <span class="text-[var(--tq-ink-muted)] hidden sm:inline">TechQuote</span>
            <span class="text-slate-300 hidden sm:inline">/</span>
            <span class="font-semibold text-[var(--tq-ink)] truncate">{{ currentTitle }}</span>
          </div>
        </div>

        <div class="flex items-center gap-3">
          <span class="hidden md:inline-flex items-center gap-1.5 px-2.5 py-1 rounded-[4px] bg-slate-100 border border-[var(--tq-border)] text-[11px] font-medium text-[var(--tq-ink-muted)]">
            <Radio class="w-3 h-3 text-emerald-600" />
            API v1 Activa
          </span>
          <NuxtLink to="/quotes/create" class="tq-btn-primary text-xs py-1.5 px-3">
            <Plus class="w-3.5 h-3.5" />
            Nueva Cotización
          </NuxtLink>
        </div>
      </header>

      <main class="flex-1 p-5 lg:p-8 max-w-[1440px] w-full mx-auto">
        <slot />
      </main>
    </div>
  </div>
</template>
