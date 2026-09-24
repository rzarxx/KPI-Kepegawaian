<?php

namespace App\Policies;

use App\Models\EmployeeDocument;
use App\Models\User;

class EmployeeDocumentPolicy
{
    public function view(User $user, EmployeeDocument $document): bool
    {
        return $user->can('employee_document.view') && $user->can('view', $document->employee);
    }

    public function create(User $user): bool
    {
        return $user->can('employee_document.create');
    }

    public function delete(User $user, EmployeeDocument $document): bool
    {
        return $user->can('employee_document.delete') && $user->can('view', $document->employee);
    }
}
