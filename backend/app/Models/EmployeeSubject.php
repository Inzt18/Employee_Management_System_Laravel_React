<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * A subject assigned to an employee, together with its class schedule.
 */
class EmployeeSubject extends Model
{
    protected $fillable = [
        'employee_id',
        'subject_id',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function schedule(): HasOne
    {
        return $this->hasOne(Schedule::class);
    }

    public function scopeSearch(Builder $query, ?string $keyword): Builder
    {
        if (blank($keyword)) {
            return $query;
        }

        $like = '%' . $keyword . '%';

        return $query->where(fn (Builder $query) => $query
            ->whereHas('subject', fn (Builder $subject) => $subject
                ->where('subject_code', 'like', $like)
                ->orWhere('subject_name', 'like', $like))
            ->orWhereHas('schedule', fn (Builder $schedule) => $schedule
                ->where('day', 'like', $like)
                ->orWhere('room', 'like', $like)));
    }
}
