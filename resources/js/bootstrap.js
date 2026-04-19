import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

// Echo is the browser-side WebSocket client used by the dashboard.
// Reverb speaks the Pusher protocol, so Echo uses the pusher-js transport.
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

const reverbKey = import.meta.env.VITE_REVERB_APP_KEY;
const clientFlag = String(import.meta.env.VITE_REVERB_CLIENT ?? '').toLowerCase();
const truthy = ['true', '1', 'on', 'yes'];
const falsy = ['false', '0', 'off', 'no'];

// Production: connect when the key is set unless explicitly disabled.
// Development (Vite dev server): opt-in only — avoids console WebSocket errors when Reverb is not running.
const shouldConnectEcho =
    !!reverbKey &&
    (import.meta.env.PROD ? !falsy.includes(clientFlag) : truthy.includes(clientFlag));

if (shouldConnectEcho) {
    window.Echo = new Echo({
        broadcaster: 'reverb',
        key: reverbKey,
        wsHost: import.meta.env.VITE_REVERB_HOST ?? window.location.hostname,
        wsPort: Number(import.meta.env.VITE_REVERB_PORT ?? 8080),
        wssPort: Number(import.meta.env.VITE_REVERB_PORT ?? 443),
        forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
        enabledTransports: ['ws', 'wss'],
    });
} else if (import.meta.env.DEV && reverbKey && !truthy.includes(clientFlag)) {
    console.debug(
        '[Echo] Reverb client disabled in dev. Set VITE_REVERB_CLIENT=true while `php artisan reverb:start` is running for live updates.'
    );
}
