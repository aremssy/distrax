import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

/*
 * Instantiates window.Echo when the page has published a broadcast config
 * (see resources/views/website/layouts/master.blade.php — only rendered for
 * authenticated users when the admin has configured Pusher). With no config
 * present this is a silent no-op, matching the existing "if (!window.Echo)"
 * fallbacks already used for admin notification polling.
 */
if (window.broadcastConfig?.key) {
    window.Pusher = Pusher;

    window.Echo = new Echo({
        broadcaster: 'pusher',
        key: window.broadcastConfig.key,
        cluster: window.broadcastConfig.cluster,
        forceTLS: true,
        authEndpoint: window.broadcastConfig.authEndpoint,
        auth: {
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
            },
        },
    });
}
