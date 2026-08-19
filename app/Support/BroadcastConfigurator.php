<?php

namespace App\Support;

/**
 * Applies the admin-configured Pusher credentials (stored in settings) on top of the
 * static broadcasting config from `config/broadcasting.php`. Called once per boot so
 * real-time chat/notifications reflect whatever the admin saved, without an `.env`
 * change or redeploy.
 */
class BroadcastConfigurator
{
    public function __construct(private SettingRepository $settings) {}

    /**
     * Override the broadcasting config from the database when Pusher is the selected
     * driver. Any other stored value (the default "log", or an unconfigured fresh
     * install) leaves the compiled `.env` config untouched.
     */
    public function apply(): void
    {
        if ($this->settings->get('broadcast_driver') !== 'pusher') {
            return;
        }

        if (! $this->settings->get('pusher_key') || ! $this->settings->get('pusher_secret')) {
            return;
        }

        $cluster = $this->settings->get('pusher_cluster') ?: 'mt1';

        config([
            'broadcasting.default' => 'pusher',
            'broadcasting.connections.pusher.key' => $this->settings->get('pusher_key'),
            'broadcasting.connections.pusher.secret' => $this->settings->get('pusher_secret'),
            'broadcasting.connections.pusher.app_id' => $this->settings->get('pusher_app_id'),
            'broadcasting.connections.pusher.options.cluster' => $cluster,
            'broadcasting.connections.pusher.options.host' => "api-{$cluster}.pusher.com",
        ]);
    }
}
