<?php

namespace App\Policies;

use App\Models\ReportExport;
use App\Models\User;

class ReportExportPolicy
{
    public function view(User $user, ReportExport $export): bool
    {
        return $user->can('report.view') && $export->requested_by === $user->id;
    }

    public function download(User $user, ReportExport $export): bool
    {
        return $user->can('report.export')
            && $export->requested_by === $user->id
            && $export->status === 'COMPLETED'
            && $export->expires_at?->isFuture();
    }
}
