export default defineNuxtRouteMiddleware(async (to) => {
  if (to.path.startsWith('/p/')) {
    return
  }

  const auth = useAuthStore()

  if (to.path === '/login') {
    if (auth.isAuthenticated) {
      return navigateTo('/')
    }
    return
  }

  if (!auth.isAuthenticated) {
    return navigateTo('/login')
  }

  if (!auth.user) {
    await auth.fetchMe()
    if (!auth.user) {
      return navigateTo('/login')
    }
  }
})
