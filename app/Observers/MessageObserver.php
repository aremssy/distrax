<?php

namespace App\Observers;

use App\Models\Message;
use App\Support\PhoneNumberMasker;

class MessageObserver
{
    /**
     * Mask any phone number typed into the message body before it is persisted,
     * regardless of which controller (website or API) created it.
     */
    public function creating(Message $message): void
    {
        $message->body = PhoneNumberMasker::mask((string) $message->body);
    }
}
