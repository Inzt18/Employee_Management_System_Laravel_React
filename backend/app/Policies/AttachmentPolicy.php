<?php

namespace App\Policies;

use App\Models\Attachment;
use App\Models\Employee;

class AttachmentPolicy
{
    /**
     * Admins can open any document; employees only their own.
     */
    public function view(Employee $employee, Attachment $attachment): bool
    {
        return $employee->isAdmin() || $attachment->employee_id === $employee->id;
    }
}
