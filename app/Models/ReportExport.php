<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['requested_by', 'filters', 'scope_snapshot', 'row_count', 'status', 'file_path', 'error_message', 'completed_at', 'expires_at'])]
class ReportExport extends Model
{
    protected function casts(): array
    {
        return ['filters' => 'array', 'scope_snapshot' => 'array', 'completed_at' => 'datetime', 'expires_at' => 'datetime'];
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }
}
