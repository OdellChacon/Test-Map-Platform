<?php

namespace App\Policies;

use App\Models\OutageSubscription;
use App\Models\User;

class OutageSubscriptionPolicy
{
    public function delete(User $user, OutageSubscription $subscription): bool
    {
        return $user->id === $subscription->user_id;
    }
}
