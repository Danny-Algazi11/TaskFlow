import Echo from 'laravel-echo';
import Pusher from 'pusher-js';
import client from './api/client';

// laravel-echo's pusher-protocol connectors (which 'reverb' is one of)
// expect a global Pusher constructor rather than importing it directly.
window.Pusher = Pusher;

const scheme = import.meta.env.VITE_REVERB_SCHEME ?? 'http';

// A custom authorizer instead of Echo's built-in authEndpoint/http options:
// the private-channel auth request needs the same Sanctum session cookie
// and X-XSRF-TOKEN header every other API call already gets from `client`
// (see client.js) — Echo's own auth transport doesn't know about either.
const echo = new Echo({
  broadcaster: 'reverb',
  key: import.meta.env.VITE_REVERB_APP_KEY,
  wsHost: import.meta.env.VITE_REVERB_HOST,
  wsPort: import.meta.env.VITE_REVERB_PORT,
  wssPort: import.meta.env.VITE_REVERB_PORT,
  forceTLS: scheme === 'https',
  enabledTransports: scheme === 'https' ? ['ws', 'wss'] : ['ws'],
  authorizer: (channel) => ({
    authorize: (socketId, callback) => {
      client
        .post('/api/broadcasting/auth', { socket_id: socketId, channel_name: channel.name })
        .then((response) => callback(false, response.data))
        .catch((error) => callback(true, error));
    },
  }),
});

export default echo;
