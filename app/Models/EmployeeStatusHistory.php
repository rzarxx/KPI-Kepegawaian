<?php

namespace App\Models;

use App\Enums\EmployeeStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['previous_status', 'new_status', 'effective_date', 'reason'])]
class EmployeeStatusHistory extends Model
{
    protected function casts(): array
    {
        return ['previous_status' => EmployeeStatus::class, 'new_status' => EmployeeStatus::class, 'effective_date' => 'date'];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
