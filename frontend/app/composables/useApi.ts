export interface ApiValidationError {
  message: string
  errors: Record<string, string[]>
}

interface ApiRequestOptions {
  method?: 'GET' | 'POST' | 'PUT' | 'PATCH' | 'DELETE'
  query?: Record<string, any>
  body?: any
  headers?: Record<string, string>
}

export function useApi() {
  const config = useRuntimeConfig()
  const token = useCookie<string | null>('auth_token')
  const router = useRouter()

  async function request<T>(
    endpoint: string,
    options: ApiRequestOptions = {},
  ): Promise<T> {
    const headers: Record<string, string> = {
      Accept: 'application/json',
      ...(options.headers ?? {}),
    }

    if (token.value) {
      headers.Authorization = `Bearer ${token.value}`
    }

    try {
      return await ($fetch as <R>(url: string, opts?: any) => Promise<R>)(
        `${config.public.apiBase}${endpoint}`,
        {
          ...options,
          headers,
        },
      )
    } catch (error: any) {
      const status = error?.response?.status ?? error?.statusCode

      if (status === 401) {
        token.value = null
        if (router.currentRoute.value.path !== '/login') {
          await router.push('/login')
        }
      }

      if (status === 422 && error?.data) {
        const validationErr = new Error(
          error.data.message || 'Error de validación en los datos enviados.',
        ) as Error & { status?: number; validationErrors?: Record<string, string[]> }
        validationErr.status = 422
        validationErr.validationErrors = error.data.errors || {}
        throw validationErr
      }

      throw error
    }
  }

  async function downloadPdf(endpoint: string, filename: string): Promise<void> {
    const headers: Record<string, string> = {
      Accept: 'application/pdf',
    }
    if (token.value) {
      headers.Authorization = `Bearer ${token.value}`
    }

    const blob = await ($fetch as <R>(url: string, opts?: any) => Promise<R>)<Blob>(
      `${config.public.apiBase}${endpoint}`,
      {
        method: 'GET',
        headers,
        responseType: 'blob',
      },
    )

    const url = window.URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = url
    link.download = filename
    document.body.appendChild(link)
    link.click()
    link.remove()
    window.URL.revokeObjectURL(url)
  }

  return {
    get: <T>(url: string, query?: Record<string, any>) =>
      request<T>(url, { method: 'GET', query }),
    post: <T>(url: string, body?: any) =>
      request<T>(url, { method: 'POST', body }),
    put: <T>(url: string, body?: any) =>
      request<T>(url, { method: 'PUT', body }),
    patch: <T>(url: string, body?: any) =>
      request<T>(url, { method: 'PATCH', body }),
    del: <T>(url: string) =>
      request<T>(url, { method: 'DELETE' }),
    downloadPdf,
  }
}
