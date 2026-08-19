<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            // General
            ['key' => 'site_name',        'value' => 'Rentdo',        'group' => 'general',      'type' => 'text'],
            ['key' => 'site_email',       'value' => 'admin@rentdo.com', 'group' => 'general',   'type' => 'text'],
            ['key' => 'site_phone',       'value' => '+1 415 555 0100',    'group' => 'general',      'type' => 'text'],
            ['key' => 'site_address',     'value' => '350 Fifth Avenue, New York, NY 10118, United States', 'group' => 'general', 'type' => 'text'],
            ['key' => 'meta_description', 'value' => '',                     'group' => 'general',      'type' => 'text'],
            ['key' => 'support_hours',      'value' => 'Mon - Fri, 9:00 AM - 6:00 PM', 'group' => 'general', 'type' => 'text'],
            ['key' => 'support_reply_time', 'value' => 'We reply within 24 hours',     'group' => 'general', 'type' => 'text'],
            ['key' => 'office_lat',         'value' => '40.7484',              'group' => 'general',      'type' => 'text'],
            ['key' => 'office_lng',         'value' => '-73.9857',              'group' => 'general',      'type' => 'text'],
            // Social & App Links
            ['key' => 'social_facebook',  'value' => 'https://facebook.com/readyrental', 'group' => 'social', 'type' => 'text'],
            ['key' => 'social_twitter',   'value' => 'https://x.com/readyrental', 'group' => 'social', 'type' => 'text'],
            ['key' => 'social_instagram', 'value' => 'https://instagram.com/readyrental', 'group' => 'social', 'type' => 'text'],
            ['key' => 'social_youtube',   'value' => 'https://youtube.com/@readyrental', 'group' => 'social', 'type' => 'text'],
            ['key' => 'app_store_url',    'value' => 'https://apps.apple.com/app/readyrental', 'group' => 'social', 'type' => 'text'],
            ['key' => 'play_store_url',   'value' => 'https://play.google.com/store/apps/details?id=com.app.rentdo&pcampaignid=web_share&client_id=503358715.1783245793&session_id=1783926518', 'group' => 'social', 'type' => 'text'],
            // Localization
            ['key' => 'default_language', 'value' => 'en',                   'group' => 'localization', 'type' => 'text'],
            ['key' => 'default_currency', 'value' => 'USD',                  'group' => 'localization', 'type' => 'text'],
            // Limits
            ['key' => 'free_post_limit',            'value' => '5',          'group' => 'limits', 'type' => 'integer'],
            ['key' => 'saved_search_alert_enabled', 'value' => '1',          'group' => 'limits', 'type' => 'boolean'],
            ['key' => 'limit_count_rule',           'value' => 'active_only', 'group' => 'limits', 'type' => 'text'],
            // Branding
            ['key' => 'primary_color',     'value' => '#5352ED', 'group' => 'branding', 'type' => 'text'],
            ['key' => 'secondary_color',   'value' => '#0EA5E9', 'group' => 'branding', 'type' => 'text'],
            ['key' => 'dark_mode_default', 'value' => 'system',  'group' => 'branding', 'type' => 'text'],
            ['key' => 'site_logo',         'value' => '',        'group' => 'branding', 'type' => 'text'],
            // Maps
            ['key' => 'map_tile_url',         'value' => 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', 'group' => 'general', 'type' => 'text'],
            ['key' => 'map_tile_attribution', 'value' => '© OpenStreetMap contributors',                       'group' => 'general', 'type' => 'text'],
            ['key' => 'google_maps_api_key',  'value' => '',                                                   'group' => 'general', 'type' => 'text'],
            ['key' => 'google_translate_api_key', 'value' => '',                                               'group' => 'general', 'type' => 'text'],
            // Listings
            ['key' => 'listing_freshness_days',  'value' => '30',    'group' => 'limits',  'type' => 'integer'],
            ['key' => 'listing_max_images',      'value' => '10',    'group' => 'limits',  'type' => 'integer'],
            ['key' => 'listing_max_videos',      'value' => '3',     'group' => 'limits',  'type' => 'integer'],
            // Media / Storage
            ['key' => 'storage_driver',   'value' => 'local',  'group' => 'general', 'type' => 'text'],
            ['key' => 'image_max_width',  'value' => '1920',   'group' => 'general', 'type' => 'integer'],
            ['key' => 'image_quality',    'value' => '85',     'group' => 'general', 'type' => 'integer'],
            // SMS / OTP
            ['key' => 'otp_driver',    'value' => 'log',    'group' => 'sms', 'type' => 'text'],
            ['key' => 'twilio_sid',    'value' => '',        'group' => 'sms', 'type' => 'text'],
            ['key' => 'twilio_token',  'value' => '',        'group' => 'sms', 'type' => 'text'],
            ['key' => 'twilio_from',   'value' => '',        'group' => 'sms', 'type' => 'text'],
            ['key' => 'vonage_api_key',    'value' => '', 'group' => 'sms', 'type' => 'text'],
            ['key' => 'vonage_api_secret', 'value' => '', 'group' => 'sms', 'type' => 'text'],
            ['key' => 'vonage_from',       'value' => '', 'group' => 'sms', 'type' => 'text'],
            // Broadcasting (real-time chat/notifications)
            ['key' => 'broadcast_driver', 'value' => 'log', 'group' => 'broadcasting', 'type' => 'text'],
            ['key' => 'pusher_app_id',    'value' => '',    'group' => 'broadcasting', 'type' => 'text'],
            ['key' => 'pusher_key',       'value' => '',    'group' => 'broadcasting', 'type' => 'text'],
            ['key' => 'pusher_secret',    'value' => '',    'group' => 'broadcasting', 'type' => 'text'],
            ['key' => 'pusher_cluster',   'value' => 'mt1', 'group' => 'broadcasting', 'type' => 'text'],
        ];

        foreach ($settings as $data) {
            Setting::firstOrCreate(['key' => $data['key']], $data);
        }
    }
}
