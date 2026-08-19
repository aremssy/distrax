<?php

namespace App\Policies;

use App\Models\Payment;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class PaymentPolicy
{
    /**
     * Payments are private to the payer. Denials are reported as "not found" rather than
     * "forbidden" so the API never confirms that someone else's payment exists.
     */
    public function view(User $user, Payment $payment): Response
    {
        return $this->ownedBy($user, $payment);
    }

    public function initiate(User $user, Payment $payment): Response
    {
        return $this->ownedBy($user, $payment);
    }

    public function refund(User $user, Payment $payment): Response
    {
        return $this->ownedBy($user, $payment);
    }

    private function ownedBy(User $user, Payment $payment): Response
    {
        return $payment->user_id === $user->id
            ? Response::allow()
            : Response::denyAsNotFound('Payment not found.');
    }
}
