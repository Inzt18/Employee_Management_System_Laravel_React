<?php

namespace App\Models\Concerns;

use App\Enums\RequestStatus;
use App\Models\Attachment;
use App\Models\Employee;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Shared columns and behaviour of every reviewable employee request.
 */
trait IsReviewable
{
    public function initializeIsReviewable(): void
    {
        $this->attributes += [
            'status' => RequestStatus::Pending->value,
            'admin_comment' => null,
            'reviewed_by' => null,
            'reviewed_at' => null,
        ];

        $this->mergeFillable(['employee_id']);
        $this->mergeCasts([
            'status' => RequestStatus::class,
            'reviewed_at' => 'datetime',
        ]);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'reviewed_by');
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    public function scopeWithStatus(Builder $query, ?string $status): Builder
    {
        return in_array($status, RequestStatus::values(), true)
            ? $query->where('status', $status)
            : $query;
    }

    public function isPending(): bool
    {
        return $this->status === RequestStatus::Pending;
    }
}
