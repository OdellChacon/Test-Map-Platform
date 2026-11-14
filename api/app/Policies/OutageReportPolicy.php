<?php

namespace App\Policies;

use App\Models\OutageReport;
use App\Models\User;

class OutageReportPolicy
{
    public function update(User $user, OutageReport $report): bool
    {
        return $user->id === $report->user_id;
    }

    public function close(User $user, OutageReport $report): bool
    {
        return $user->id === $report->user_id;
    }

    public function confirm(User $user, OutageReport $report): bool
    {
        return $user->id !== $report->user_id;
    }

    public function moderate(User $user, OutageReport $report): bool
    {
        return (bool) $user->is_moderator;
    }
}
