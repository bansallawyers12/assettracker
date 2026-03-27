import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

const csrf =
    document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';

const authOptions = {
    authEndpoint: '/broadcasting/auth',
    auth: {
        headers: {
            'X-CSRF-TOKEN': csrf,
            'X-Requested-With': 'XMLHttpRequest',
        },
    },
};

const reverbKey = import.meta.env.VITE_REVERB_APP_KEY;

if (reverbKey) {
    const scheme = import.meta.env.VITE_REVERB_SCHEME ?? 'https';
    const port = import.meta.env.VITE_REVERB_PORT
        ? Number(import.meta.env.VITE_REVERB_PORT)
        : scheme === 'https'
          ? 443
          : 80;

    window.Pusher = Pusher;
    window.Echo = new Echo({
        ...authOptions,
        broadcaster: 'reverb',
        key: reverbKey,
        wsHost: import.meta.env.VITE_REVERB_HOST,
        wsPort: port,
        wssPort: port,
        forceTLS: scheme === 'https',
        enabledTransports: ['ws', 'wss'],
    });
} else {
    const pusherKey = import.meta.env.VITE_PUSHER_APP_KEY;

    if (!pusherKey) {
        window.Echo = null;
    } else {
        window.Pusher = Pusher;

        const scheme = import.meta.env.VITE_PUSHER_SCHEME ?? 'https';
        const cluster = import.meta.env.VITE_PUSHER_APP_CLUSTER ?? 'mt1';
        const customHost = import.meta.env.VITE_PUSHER_HOST;

        const echoConfig = {
            ...authOptions,
            broadcaster: 'pusher',
            key: pusherKey,
            cluster,
            forceTLS: scheme === 'https',
            encrypted: scheme === 'https',
        };

        if (customHost) {
            const port = import.meta.env.VITE_PUSHER_PORT
                ? Number(import.meta.env.VITE_PUSHER_PORT)
                : scheme === 'https'
                  ? 443
                  : 80;
            echoConfig.wsHost = customHost;
            echoConfig.wsPort = port;
            echoConfig.wssPort = port;
            echoConfig.enabledTransports = ['ws', 'wss'];
        }

        window.Echo = new Echo(echoConfig);
    }
}
