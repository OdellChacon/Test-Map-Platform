import axios from 'axios'

const client = axios.create({
  baseURL: import.meta.env.VITE_API_BASE_URL,
  withCredentials: true,
  headers: {
    Accept: 'application/json',
  },
})

let csrfCookiePromise = null

export function ensureCsrfCookie() {
  if (!csrfCookiePromise) {
    csrfCookiePromise = client.get('/sanctum/csrf-cookie')
  }

  return csrfCookiePromise
}

function readXsrfToken() {
  const match = document.cookie.match(/(?:^|;\s*)XSRF-TOKEN=([^;]+)/)
  return match ? decodeURIComponent(match[1]) : null
}

client.interceptors.request.use(async (config) => {
  if (config.method !== 'get') {
    await ensureCsrfCookie()
    config.headers['X-XSRF-TOKEN'] = readXsrfToken()
  }

  return config
})

client.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401) {
      csrfCookiePromise = null
    }

    return Promise.reject(error)
  },
)

export default client
