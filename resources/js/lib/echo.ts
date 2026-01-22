import Echo from 'laravel-echo'
import Pusher from 'pusher-js'

declare global {
  interface Window {
    Pusher?: typeof Pusher
  }
}

const isBrowser = typeof window !== 'undefined'

if (isBrowser && !window.Pusher) {
  window.Pusher = Pusher
}

const csrfToken = isBrowser
  ? document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? ''
  : ''

const pusherKey = import.meta.env.VITE_PUSHER_APP_KEY

export const echo =
  isBrowser && pusherKey
    ? new Echo({
        broadcaster: 'pusher',
        key: pusherKey,
        cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER ?? 'mt1',
        wsHost: import.meta.env.VITE_PUSHER_HOST,
        wsPort: Number(import.meta.env.VITE_PUSHER_PORT ?? 80),
        wssPort: Number(import.meta.env.VITE_PUSHER_PORT ?? 443),
        forceTLS: (import.meta.env.VITE_PUSHER_SCHEME ?? 'https') === 'https',
        enabledTransports: ['ws', 'wss'],
        authEndpoint: '/broadcasting/auth',
        auth: {
          headers: {
            'X-CSRF-TOKEN': csrfToken,
          },
        },
      })
    : null
