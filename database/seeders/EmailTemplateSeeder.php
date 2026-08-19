<?php

namespace Database\Seeders;

use App\Models\EmailTemplate;
use Illuminate\Database\Seeder;

class EmailTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            [
                'key' => 'welcome_email',
                'name' => 'Welcome Email',
                'channel' => 'email',
                'subject' => 'Welcome to {{app_name}}!',
                'body' => "Hi {{user_name}},\n\nWelcome to {{app_name}}! Your account has been created successfully.\n\nLogin here: {{login_url}}\n\nThanks,\nThe {{app_name}} Team",
                'variables' => ['app_name', 'user_name', 'login_url'],
                'is_active' => true,
            ],
            [
                'key' => 'password_reset',
                'name' => 'Password Reset',
                'channel' => 'email',
                'subject' => 'Reset Your Password — {{app_name}}',
                'body' => "Hi {{user_name}},\n\nYou requested a password reset. Click below to set a new password:\n\n{{reset_url}}\n\nThis link expires in 60 minutes. If you did not request this, ignore this email.\n\nThanks,\nThe {{app_name}} Team",
                'variables' => ['app_name', 'user_name', 'reset_url'],
                'is_active' => true,
            ],
            [
                'key' => 'booking_confirmed',
                'name' => 'Booking Confirmed',
                'channel' => 'email',
                'subject' => 'Your Booking is Confirmed — {{property_title}}',
                'body' => "Hi {{user_name}},\n\nYour booking for {{property_title}} has been confirmed.\n\nCheck-in: {{check_in}}\nCheck-out: {{check_out}}\nAmount: {{amount}}\n\nThanks,\nThe {{app_name}} Team",
                'variables' => ['app_name', 'user_name', 'property_title', 'check_in', 'check_out', 'amount'],
                'is_active' => true,
            ],
            [
                'key' => 'booking_cancelled',
                'name' => 'Booking Cancelled',
                'channel' => 'email',
                'subject' => 'Booking Cancelled — {{property_title}}',
                'body' => "Hi {{user_name}},\n\nYour booking for {{property_title}} (Check-in: {{check_in}}) has been cancelled.\n\nIf you have questions, reply to this email.\n\nThanks,\nThe {{app_name}} Team",
                'variables' => ['app_name', 'user_name', 'property_title', 'check_in'],
                'is_active' => true,
            ],
            [
                'key' => 'subscription_activated',
                'name' => 'Subscription Activated',
                'channel' => 'email',
                'subject' => 'Your {{plan_name}} Subscription is Active',
                'body' => "Hi {{user_name}},\n\nYour {{plan_name}} subscription is now active.\n\nExpires: {{expires_at}}\nPost limit: {{post_limit}}\n\nThanks,\nThe {{app_name}} Team",
                'variables' => ['app_name', 'user_name', 'plan_name', 'expires_at', 'post_limit'],
                'is_active' => true,
            ],
            [
                'key' => 'technician_booking_confirmed',
                'name' => 'Technician Booking Confirmed',
                'channel' => 'email',
                'subject' => 'Technician Visit Confirmed',
                'body' => "Hi {{user_name}},\n\nYour technician booking has been confirmed.\n\nTechnician: {{technician_name}}\nScheduled: {{scheduled_at}}\nAddress: {{address}}\n\nThanks,\nThe {{app_name}} Team",
                'variables' => ['app_name', 'user_name', 'technician_name', 'scheduled_at', 'address'],
                'is_active' => true,
            ],
        ];

        foreach ($templates as $data) {
            EmailTemplate::firstOrCreate(['key' => $data['key']], $data);
        }
    }
}
