import axios from 'axios';
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.axios = axios;
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

// CSRF token for every request
const csrfToken = document.head.querySelector('meta[name="csrf-token"]');
if (csrfToken) {
    window.axios.defaults.headers.common['X-CSRF-TOKEN'] = csrfToken.content;
}

window.Pusher = Pusher;

window.Echo = new Echo({
    broadcaster:       'reverb',
    key:               import.meta.env.VITE_REVERB_APP_KEY,
    wsHost:            import.meta.env.VITE_REVERB_HOST,
    // Vite env vars are always strings — parse to int so Pusher.js is happy
    wsPort:            parseInt(import.meta.env.VITE_REVERB_PORT  ?? 80),
    wssPort:           parseInt(import.meta.env.VITE_REVERB_PORT  ?? 443),
    forceTLS:          (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
    enabledTransports: ['ws', 'wss'],
    disableStats:      true,
});

// Once the WebSocket is established, tell axios to include the socket ID so
// broadcast()->toOthers() can exclude the sender's socket correctly.
window.Echo.connector.pusher.connection.bind('connected', () => {
    window.axios.defaults.headers.common['X-Socket-ID'] = window.Echo.socketId();
});
