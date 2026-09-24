<?php

namespace App\Models;

use App\Enums\EmployeeStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable(['employee_number', 'national_id', 'full_name', 'email', 'phone', 'birth_date', 'gender', 'join_date', 'current_status', 'exit_date', 'exit_reason'])]
class Employee extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return ['birth_date' => 'date', 'join_date' => 'date', 'exit_date' => 'date', 'current_status' => EmployeeStatus::class];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(EmployeeAssignment::class);
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(EmployeeStatusHistory::class);
    }

    public function incidents(): HasMany
    {
        return $this->hasMany(EmployeeIncident::class);
    }

    public function evaluations(): HasMany
    {
        return $this->hasMany(EmployeeEvaluation::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(EmployeeDocument::class);
    }

    public function currentAssignment(): HasOne
    {
        return $this->hasOne(EmployeeAssignment::class)->ofMany(['start_date' => 'max'], fn ($query) => $query->whereNull('end_date'));
    }

    public function latestAssignment(): HasOne
    {
        return $this->hasOne(EmployeeAssignment::class)->ofMany('start_date', 'max');
    }
}
