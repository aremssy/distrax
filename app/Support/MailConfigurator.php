<?php

namespace App\Support;

/**
 * Applies the admin-configured SMTP credentials (stored in settings) on top of the
 * static mail config from `config/mail.php`. Called once per boot so the running
 * mailer reflects whatever the admin saved, without an `.env` change or redeploy.
 */
class MailConfigurator
{
    public function __construct(private SettingRepository $settings) {}

    /**
     * Override the mail config from the database when SMTP is the selected driver.
     *
     * Any other stored value (the default "log", or an unconfigured fresh install)
     * leaves the compiled `.env` config untouched, so local and CI environments keep
     * working without any database configuration.
     */
    public function apply(): void
    {
        if ($this->settings->get('mail_mailer') !== 'smtp') {
            return;
        }

        config([
            'mail.default' => 'smtp',
            'mail.mailers.smtp.host' => $this->settings->get('mail_host'),
            'mail.mailers.smtp.port' => (int) ($this->settings->get('mail_port') ?: 587),
            'mail.mailers.smtp.username' => $this->settings->get('mail_username') ?: null,
            'mail.mailers.smtp.password' => $this->settings->get('mail_password') ?: null,
            // Symfony's SMTP transport uses implicit TLS only for the "smtps" scheme
            // (port 465); a null scheme upgrades opportunistically via STARTTLS (port 587).
            'mail.mailers.smtp.scheme' => $this->settings->get('mail_encryption') === 'ssl' ? 'smtps' : null,
        ]);

        if ($from = $this->settings->get('mail_from_address')) {
            config(['mail.from.address' => $from]);
        }

        if ($fromName = $this->settings->get('mail_from_name')) {
            config(['mail.from.name' => $fromName]);
        }
    }
}
