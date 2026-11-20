<script setup lang="ts">
import { Lock, Mail, ArrowRight, ShieldCheck, Network, Camera, Cpu } from 'lucide-vue-next'

definePageMeta({
  layout: 'auth',
})

const auth = useAuthStore()
const email = ref('')
const password = ref('')
const loading = ref(false)
const errorMsg = ref('')

async function handleLogin() {
  errorMsg.value = ''
  loading.value = true
  try {
    await auth.login(email.value, password.value)
    await navigateTo('/')
  } catch (err: any) {
    errorMsg.value = err?.data?.message || err?.message || 'Credenciales inválidas.'
  } finally {
    loading.value = false
  }
}

async function quickLogin(roleEmail: string) {
  email.value = roleEmail
  password.value = 'password'
  await handleLogin()
}
</script>

<template>
  <div class="w-full min-h-screen grid grid-cols-1 lg:grid-cols-12">
    <!-- Left Architectural / Network Brand Panel -->
    <div class="hidden lg:flex lg:col-span-7 bg-[#0B0F14] text-white p-12 flex-col justify-between relative overflow-hidden border-r border-[#1E2733]">
      <svg class="absolute inset-0 w-full h-full opacity-[0.07] pointer-events-none" xmlns="http://www.w3.org/2000/svg">
        <defs>
          <pattern id="tq-grid" width="48" height="48" patternUnits="userSpaceOnUse">
            <path d="M 48 0 L 0 0 0 48" fill="none" stroke="#38BDF8" stroke-width="1" />
          </pattern>
        </defs>
        <rect width="100%" height="100%" fill="url(#tq-grid)" />
      </svg>

      <div class="relative z-10 flex items-center gap-3">
        <span class="w-3 h-3 rounded-sm bg-cyan-400 shadow-[0_0_12px_rgba(34,211,238,0.65)]" />
        <span class="text-lg font-bold tracking-tight">TechQuote</span>
        <span class="tq-code text-xs px-2 py-0.5 rounded bg-[#141B23] text-cyan-300 border border-[#243242]">
          CRM // B2B
        </span>
      </div>

      <div class="relative z-10 max-w-xl space-y-6">
        <h1 class="text-4xl font-bold tracking-tight leading-[1.15] text-white">
          Ingeniería comercial, cotizaciones técnicas y control de obra en un solo núcleo.
        </h1>
        <p class="text-slate-400 text-base leading-relaxed">
          Diseñado para integradoras de videovigilancia CCTV, cableado estructurado, redes corporativas Wi-Fi y contratos de mantenimiento SLA.
        </p>

        <div class="grid grid-cols-3 gap-4 pt-4 border-t border-[#1E2733]">
          <div class="space-y-1.5">
            <Camera class="w-4 h-4 text-cyan-400" />
            <div class="text-xs font-semibold text-white">Videovigilancia</div>
            <div class="text-xs text-slate-400">Kits BOM y site survey</div>
          </div>
          <div class="space-y-1.5">
            <Network class="w-4 h-4 text-cyan-400" />
            <div class="text-xs font-semibold text-white">Redes &amp; Fibra</div>
            <div class="text-xs text-slate-400">Control de margen real</div>
          </div>
          <div class="space-y-1.5">
            <Cpu class="w-4 h-4 text-cyan-400" />
            <div class="text-xs font-semibold text-white">Proyectos &amp; SLA</div>
            <div class="text-xs text-slate-400">Series, stock y cobros</div>
          </div>
        </div>
      </div>

      <div class="relative z-10 flex items-center gap-4 text-xs text-slate-500 tq-code">
        <span>MONEDA PEN / USD</span>
        <span>·</span>
        <span>FIRMA DIGITAL EN PORTAL</span>
        <span>·</span>
        <span>CONTROL RBAC</span>
      </div>
    </div>

    <!-- Right Sign-In Form -->
    <div class="lg:col-span-5 bg-white flex items-center justify-center p-6 sm:p-12">
      <div class="w-full max-w-sm space-y-7">
        <div class="lg:hidden flex items-center gap-2.5 pb-4 border-b border-[var(--tq-border)]">
          <ShieldCheck class="w-5 h-5 text-cyan-600" />
          <span class="font-bold text-base text-[var(--tq-ink)]">TechQuote CRM</span>
        </div>

        <div>
          <h2 class="text-2xl font-bold text-[var(--tq-ink)] tracking-tight">Iniciar sesión</h2>
          <p class="text-sm text-[var(--tq-ink-muted)] mt-1">
            Accede con tu cuenta corporativa para operar el sistema.
          </p>
        </div>

        <div
          v-if="errorMsg"
          class="p-3 rounded-[5px] bg-rose-50 border border-rose-200 text-rose-700 text-sm"
        >
          {{ errorMsg }}
        </div>

        <form class="space-y-4" @submit.prevent="handleLogin">
          <div>
            <label class="block text-xs font-semibold text-[var(--tq-ink)] mb-1.5">
              Correo Electrónico
            </label>
            <div class="relative">
              <Mail class="w-4 h-4 text-slate-400 absolute left-3 top-2.5" />
              <input
                v-model="email"
                type="email"
                required
                placeholder="usuario@techcrm.com"
                class="tq-input pl-9"
              >
            </div>
          </div>

          <div>
            <label class="block text-xs font-semibold text-[var(--tq-ink)] mb-1.5">
              Contraseña
            </label>
            <div class="relative">
              <Lock class="w-4 h-4 text-slate-400 absolute left-3 top-2.5" />
              <input
                v-model="password"
                type="password"
                required
                placeholder="••••••••"
                class="tq-input pl-9"
              >
            </div>
          </div>

          <button
            type="submit"
            :disabled="loading"
            class="tq-btn-primary w-full py-2.5"
          >
            <span>{{ loading ? 'Autenticando...' : 'Ingresar al CRM' }}</span>
            <ArrowRight class="w-4 h-4" />
          </button>
        </form>

        <div class="pt-5 border-t border-[var(--tq-border)] space-y-2.5">
          <div class="text-xs font-semibold text-[var(--tq-ink-muted)]">
            Acceso rápido de prueba
          </div>
          <div class="grid grid-cols-2 gap-2.5">
            <button
              type="button"
              :disabled="loading"
              class="tq-btn-secondary text-xs py-2"
              @click="quickLogin('admin@techcrm.com')"
            >
              Demo Admin
            </button>
            <button
              type="button"
              :disabled="loading"
              class="tq-btn-secondary text-xs py-2 border-cyan-300 text-cyan-800 bg-cyan-50/60 hover:bg-cyan-50"
              @click="quickLogin('comercial@techcrm.com')"
            >
              Demo Comercial
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
