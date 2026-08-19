<?php

use App\Models\Agency;
use App\Models\ContactMessage;
use App\Models\Dispute;
use App\Models\HotelBooking;
use App\Models\MaintenanceRequest;
use App\Models\Payment;
use App\Models\Payout;
use App\Models\Refund;
use App\Models\Review;
use App\Models\Technician;
use App\Models\TechnicianBooking;
use App\Models\Testimonial;

return [

    /*
    |--------------------------------------------------------------------------
    | Admin activity notifications
    |--------------------------------------------------------------------------
    |
    | Maps a model to the admin-facing notification it should generate on the
    | given Eloquent lifecycle events. A generic observer (NotifiesAdminsObserver)
    | is attached to every model listed here, so covering a new domain event is a
    | one-line addition — no bespoke observer required.
    |
    | Each entry:
    |   type       - key into AdminNotification::TYPE_META (drives icon + colour).
    |   label      - human name used to build the notification title.
    |   events     - Eloquent events to react to: created | updated | deleted.
    |   permission - only admins holding it are notified (null = all admins).
    |   route      - named route the notification links to; the model is passed as
    |                the route parameter when the route needs one.
    |
    | Config-cache safe: values are scalars only (no closures).
    |
    */

    'models' => [
        HotelBooking::class => [
            'type' => 'booking_created',
            'label' => 'hotel booking',
            'events' => ['created'],
            'permission' => 'hotel_bookings.view',
            'route' => 'admin.hotel-bookings.show',
        ],
        TechnicianBooking::class => [
            'type' => 'booking_created',
            'label' => 'technician booking',
            'events' => ['created'],
            'permission' => 'technician_bookings.view',
            'route' => 'admin.tech-bookings.show',
        ],
        Payment::class => [
            'type' => 'payment_received',
            'label' => 'payment',
            'events' => ['created'],
            'permission' => 'payments.view',
            'route' => 'admin.payments.index',
        ],
        Refund::class => [
            'type' => 'refund_requested',
            'label' => 'refund request',
            'events' => ['created'],
            'permission' => 'payments.view',
            'route' => 'admin.refunds.index',
        ],
        Payout::class => [
            'type' => 'payment_received',
            'label' => 'payout request',
            'events' => ['created'],
            'permission' => 'payments.view',
            'route' => 'admin.payouts.index',
        ],
        Dispute::class => [
            'type' => 'dispute_opened',
            'label' => 'dispute',
            'events' => ['created'],
            'permission' => null,
            'route' => 'admin.disputes.index',
        ],
        Review::class => [
            'type' => 'review_flagged',
            'label' => 'review',
            'events' => ['created'],
            'permission' => 'reviews.view',
            'route' => 'admin.reviews.index',
        ],
        ContactMessage::class => [
            'type' => 'new_user_registered',
            'label' => 'contact message',
            'events' => ['created'],
            'permission' => 'contact_messages.view',
            'route' => 'admin.contact-messages.index',
        ],
        Technician::class => [
            'type' => 'technician_application',
            'label' => 'technician application',
            'events' => ['created'],
            'permission' => 'technicians.view',
            'route' => 'admin.technicians.index',
        ],
        Agency::class => [
            'type' => 'new_user_registered',
            'label' => 'agency',
            'events' => ['created'],
            'permission' => 'agencies.view',
            'route' => 'admin.agencies.index',
        ],
        MaintenanceRequest::class => [
            'type' => 'booking_created',
            'label' => 'maintenance request',
            'events' => ['created'],
            'permission' => null,
            'route' => 'admin.maintenance-requests.index',
        ],
        Testimonial::class => [
            'type' => 'review_flagged',
            'label' => 'testimonial',
            'events' => ['created'],
            'permission' => 'testimonials.view',
            'route' => 'admin.testimonials.index',
        ],
    ],
];
