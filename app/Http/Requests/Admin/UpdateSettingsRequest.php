<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateSettingsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return match ($this->tab()) {
            'general' => [
                'site_name' => ['required', 'string', 'max:100'],
                'site_email' => ['nullable', 'email'],
                'site_phone' => ['nullable', 'string', 'max:20'],
                'site_address' => ['nullable', 'string', 'max:255'],
                'meta_description' => ['nullable', 'string', 'max:255'],
                'support_hours' => ['nullable', 'string', 'max:100'],
                'support_reply_time' => ['nullable', 'string', 'max:100'],
                'office_lat' => ['nullable', 'numeric', 'between:-90,90'],
                'office_lng' => ['nullable', 'numeric', 'between:-180,180'],
                'google_maps_api_key' => ['nullable', 'string', 'max:255'],
            ],
            'localization' => [
                'default_language' => ['required', 'string', 'max:10'],
                'default_currency' => ['required', 'string', 'max:10'],
            ],
            'limits' => [
                'free_post_limit' => ['required', 'integer', 'min:0'],
                'saved_search_alert_enabled' => ['nullable'],
                'limit_count_rule' => ['required', 'in:active_only,include_archived'],
            ],
            'branding' => [
                'primary_color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
                'secondary_color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
                'dark_mode_default' => ['required', 'in:light,dark,system'],
                'site_logo' => ['nullable', 'image', 'mimes:png,jpg,jpeg,svg,webp', 'max:2048'],
                'site_favicon' => ['nullable', 'image', 'mimes:png,svg,ico,webp', 'max:1024'],
                'og_image' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:2048'],
            ],
            'social' => [
                'social_facebook' => ['nullable', 'url', 'max:255'],
                'social_twitter' => ['nullable', 'url', 'max:255'],
                'social_instagram' => ['nullable', 'url', 'max:255'],
                'social_youtube' => ['nullable', 'url', 'max:255'],
                'app_store_url' => ['nullable', 'url', 'max:255'],
                'play_store_url' => ['nullable', 'url', 'max:255'],
            ],
            'payments' => [
                'active_payment_gateways' => ['nullable', 'array'],
                'active_payment_gateways.*' => ['in:stripe,paypal,bkash,razorpay,paystack'],
                'stripe_publishable_key' => ['nullable', 'string', 'max:255'],
                'stripe_secret_key' => ['nullable', 'string', 'max:255'],
                'stripe_webhook_secret' => ['nullable', 'string', 'max:255'],
                'paypal_client_id' => ['nullable', 'string', 'max:255'],
                'paypal_client_secret' => ['nullable', 'string', 'max:255'],
                'paypal_webhook_id' => ['nullable', 'string', 'max:255'],
                'paypal_sandbox' => ['nullable', 'boolean'],
                'bkash_username' => ['nullable', 'string', 'max:255'],
                'bkash_password' => ['nullable', 'string', 'max:255'],
                'bkash_app_key' => ['nullable', 'string', 'max:255'],
                'bkash_app_secret' => ['nullable', 'string', 'max:255'],
                'bkash_sandbox' => ['nullable', 'boolean'],
                'razorpay_key_id' => ['nullable', 'string', 'max:255'],
                'razorpay_key_secret' => ['nullable', 'string', 'max:255'],
                'razorpay_webhook_secret' => ['nullable', 'string', 'max:255'],
                'paystack_secret_key' => ['nullable', 'string', 'max:255'],
            ],
            'sms' => [
                'otp_driver' => ['required', 'in:log,twilio,vonage,zan'],
                'twilio_sid' => ['nullable', 'string', 'max:100'],
                'twilio_token' => ['nullable', 'string', 'max:100'],
                'twilio_from' => ['nullable', 'string', 'max:20'],
                'vonage_api_key' => ['nullable', 'string', 'max:100'],
                'vonage_api_secret' => ['nullable', 'string', 'max:100'],
                'vonage_from' => ['nullable', 'string', 'max:20'],
                'zan_api_key' => ['nullable', 'string', 'max:100'],
                'zan_sender_id' => ['nullable', 'string', 'max:20'],
                'firebase_service_account' => ['nullable', 'string', 'max:8000', 'json'],
            ],
            'mail' => [
                'mail_mailer' => ['required', 'in:log,smtp'],
                'mail_host' => ['nullable', 'required_if:mail_mailer,smtp', 'string', 'max:255'],
                'mail_port' => ['nullable', 'required_if:mail_mailer,smtp', 'integer', 'between:1,65535'],
                'mail_encryption' => ['required', 'in:tls,ssl,none'],
                'mail_username' => ['nullable', 'string', 'max:255'],
                'mail_password' => ['nullable', 'string', 'max:255'],
                'mail_from_address' => ['nullable', 'required_if:mail_mailer,smtp', 'email', 'max:255'],
                'mail_from_name' => ['nullable', 'string', 'max:100'],
            ],
            'broadcasting' => [
                'broadcast_driver' => ['required', 'in:log,pusher'],
                'pusher_app_id' => ['nullable', 'required_if:broadcast_driver,pusher', 'string', 'max:100'],
                'pusher_key' => ['nullable', 'required_if:broadcast_driver,pusher', 'string', 'max:100'],
                'pusher_secret' => ['nullable', 'string', 'max:100'],
                'pusher_cluster' => ['nullable', 'string', 'max:20'],
            ],
            default => [],
        };
    }

    /**
     * Get the custom validation messages for this request.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'site_name.required' => 'Please enter a site name.',
            'site_name.max' => 'Keep the site name under 100 characters.',
            'site_email.email' => 'Please enter a valid site email address, like admin@example.com.',
            'site_phone.max' => 'Keep the site phone number under 20 characters.',
            'site_address.max' => 'Keep the site address under 255 characters.',
            'meta_description.max' => 'Keep the meta description under 255 characters for best SEO results.',
            'default_language.required' => 'Please choose a default language.',
            'default_language.max' => 'Use a language code of 10 characters or fewer, like en or bn.',
            'default_currency.required' => 'Please choose a default currency.',
            'default_currency.max' => 'Use a currency code of 10 characters or fewer, like BDT or USD.',
            'free_post_limit.required' => 'Please enter the free post limit.',
            'free_post_limit.integer' => 'The free post limit must be a whole number, like 3.',
            'free_post_limit.min' => 'The free post limit cannot be negative.',
            'limit_count_rule.required' => 'Please choose how listings are counted against the limit.',
            'limit_count_rule.in' => 'Please choose a valid counting rule: active listings only, or include archived listings.',
            'primary_color.required' => 'Please choose a primary colour.',
            'primary_color.regex' => 'Enter a valid hex colour, like #5352ED.',
            'secondary_color.required' => 'Please choose a secondary colour.',
            'secondary_color.regex' => 'Enter a valid hex colour, like #5352ED.',
            'dark_mode_default.required' => 'Please choose the default appearance.',
            'dark_mode_default.in' => 'Please choose a valid appearance: light, dark or system.',
            'site_logo.image' => 'The logo must be an image file.',
            'site_logo.mimes' => 'The logo must be a PNG, JPG, SVG or WEBP file.',
            'site_logo.max' => 'Keep the logo under 2 MB.',
            'social_facebook.url' => 'Please enter a valid Facebook URL, like https://facebook.com/yourpage.',
            'social_facebook.max' => 'Keep the Facebook URL under 255 characters.',
            'social_twitter.url' => 'Please enter a valid X (Twitter) URL, like https://x.com/yourhandle.',
            'social_twitter.max' => 'Keep the X (Twitter) URL under 255 characters.',
            'social_instagram.url' => 'Please enter a valid Instagram URL, like https://instagram.com/yourhandle.',
            'social_instagram.max' => 'Keep the Instagram URL under 255 characters.',
            'social_youtube.url' => 'Please enter a valid YouTube URL, like https://youtube.com/@yourchannel.',
            'social_youtube.max' => 'Keep the YouTube URL under 255 characters.',
            'app_store_url.url' => 'Please enter a valid App Store URL, starting with https://.',
            'app_store_url.max' => 'Keep the App Store URL under 255 characters.',
            'play_store_url.url' => 'Please enter a valid Play Store URL, starting with https://.',
            'play_store_url.max' => 'Keep the Play Store URL under 255 characters.',
            'active_payment_gateways.*.in' => 'One of the selected payment gateways is not supported.',
            'otp_driver.required' => 'Please choose an OTP driver.',
            'otp_driver.in' => 'Please choose a valid OTP driver: log, Twilio, Vonage or Zan.',
            'twilio_sid.max' => 'Keep the Twilio SID under 100 characters.',
            'twilio_token.max' => 'Keep the Twilio token under 100 characters.',
            'twilio_from.max' => 'Keep the Twilio sender number under 20 characters.',
            'vonage_api_key.max' => 'Keep the Vonage API key under 100 characters.',
            'vonage_api_secret.max' => 'Keep the Vonage API secret under 100 characters.',
            'vonage_from.max' => 'Keep the Vonage sender number under 20 characters.',
            'zan_api_key.max' => 'Keep the Zan API key under 100 characters.',
            'zan_sender_id.max' => 'Keep the Zan sender ID under 20 characters.',
            'firebase_service_account.max' => 'The Firebase service account key is too long. Keep it under 8000 characters.',
            'firebase_service_account.json' => 'The Firebase service account must be the full JSON key file contents.',
            'mail_mailer.required' => 'Please choose a mail driver.',
            'mail_mailer.in' => 'Please choose a valid mail driver: Log or SMTP.',
            'mail_host.required_if' => 'Please enter the SMTP host, like smtp.yourprovider.com.',
            'mail_port.required_if' => 'Please enter the SMTP port, like 587.',
            'mail_port.integer' => 'The SMTP port must be a whole number, like 587.',
            'mail_port.between' => 'Enter a valid port number between 1 and 65535.',
            'mail_encryption.required' => 'Please choose an encryption type.',
            'mail_encryption.in' => 'Please choose a valid encryption type: TLS, SSL or None.',
            'mail_from_address.required_if' => 'Please enter the "from" email address recipients will see.',
            'mail_from_address.email' => 'Please enter a valid "from" email address, like no-reply@example.com.',
            'broadcast_driver.required' => 'Please choose a broadcast driver.',
            'broadcast_driver.in' => 'Please choose a valid broadcast driver: Log or Pusher.',
            'pusher_app_id.required_if' => 'Please enter the Pusher App ID.',
            'pusher_key.required_if' => 'Please enter the Pusher Key.',
        ];
    }

    /**
     * Get the settings tab currently being saved.
     */
    private function tab(): string
    {
        return (string) $this->query('tab', 'general');
    }
}
