<?php

namespace Database\Seeders;

use App\Models\Faq;
use Illuminate\Database\Seeder;

class FaqSeeder extends Seeder
{
    public function run(): void
    {
        if (Faq::exists()) {
            return;
        }

        $faqs = [
            'General' => [
                ['What is your platform?', 'Our platform is a modern property listing solution that helps you discover, compare and book properties with ease. We connect buyers, renters and property owners in one seamless experience.'],
                ['How do I create an account?', 'Click Register in the top navigation, enter your name, phone or email, and choose a password. You can start browsing immediately after signing up.'],
                ['Is my personal information secure?', 'Yes. Your details are encrypted in transit, and your phone number is only revealed to other users according to the visibility setting you choose in your account.'],
                ['How do I contact customer support?', 'Use the contact form on our Contact page, or email our support team directly. We reply to most enquiries within one business day.'],
                ['Are there any hidden fees?', 'No. Browsing and contacting owners is free. Any optional paid promotion or subscription is shown with its full price before you confirm.'],
            ],
            'Property' => [
                ['Can I list my property on your platform?', 'Yes. Sign in, open Add Property from the navigation, and complete the listing form. Your listing goes live once it passes review.'],
                ['How do I get my listing verified?', 'Submit your ownership documents from your dashboard. Once our team confirms them, a Verified badge appears on your listing.'],
                ['How do I stay updated with new listings?', 'Save a search from the property search page and enable alerts. We will notify you whenever a new property matches your criteria.'],
                ['Why was my listing rejected?', 'Listings are rejected when they are duplicates, contain misleading information, or have unusable photos. You will receive the specific reason and can edit and resubmit.'],
            ],
            'Booking' => [
                ['How do I schedule a property visit?', 'Open any property and use Schedule a Visit to propose a date and time. The owner or agent confirms or suggests an alternative.'],
                ['Can I cancel a booking?', 'Yes. Open the booking from your dashboard and select cancel. Cancellation terms depend on the property owner’s policy.'],
                ['How do short-stay hotel bookings work?', 'Choose your check-in and check-out dates on a hotel listing, confirm availability, and complete payment to secure the reservation.'],
            ],
            'Payments' => [
                ['Which payment methods are supported?', 'We support the major mobile financial services and card payments available in your region. Available options appear at checkout.'],
                ['How do I pay my rent?', 'Open Rent Management in your dashboard, select the invoice due, and pay directly. Each payment is receipted in your ledger.'],
                ['When will I receive a refund?', 'Approved refunds are returned to the original payment method, typically within five to ten business days depending on your provider.'],
            ],
            'Account' => [
                ['How do I reset my password?', 'Select Forgot Password on the login screen and follow the emailed link. The link expires after a short time for your security.'],
                ['How do I change my language or currency?', 'Use the language and currency selectors in the top bar. Your choice is remembered for future visits.'],
                ['How do I delete my account?', 'Request deletion from your account settings. We remove your personal data after any active agreements or bookings are closed.'],
            ],
            'Support' => [
                ['How do I report a suspicious listing?', 'Open the listing and use Report. Our moderation team reviews every report and removes listings that break our rules.'],
                ['What do I do if an owner does not respond?', 'Give the owner up to 48 hours. If there is still no reply, raise a dispute from your dashboard and our team will step in.'],
                ['How do I raise a dispute?', 'Go to Disputes in your dashboard, choose the related booking or agreement, and describe the issue. We mediate between both parties.'],
            ],
        ];

        $sortOrder = 0;

        foreach ($faqs as $category => $items) {
            foreach ($items as [$question, $answer]) {
                Faq::create([
                    'question' => $question,
                    'answer' => $answer,
                    'category' => $category,
                    'sort_order' => $sortOrder++,
                    'is_active' => true,
                ]);
            }
        }
    }
}
