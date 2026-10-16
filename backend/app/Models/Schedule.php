<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Schedule extends Model
{
    public const DAYS = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];

    protected $fillable = [
        'employee_subject_id',
        'day',
        'start_time',
        'end_time',
        'room',
    ];

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(EmployeeSubject::class, 'employee_subject_id');
    }
}
