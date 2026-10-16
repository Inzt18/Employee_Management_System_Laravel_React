<?php

namespace App\Models;

use App\Contracts\Reviewable;
use App\Models\Concerns\IsReviewable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class LeaveRequest extends Model implements Reviewable
{
    use IsReviewable;

    protected $fillable = [
        'leave_type',
        'start_date',
        'end_date',
        'reason',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    public static function requestType(): string
    {
        return 'leave';
    }

    public static function requestCategory(): string
    {
        return 'Leave';
    }

    public function summary(): string
    {
        return "Leave request for {$this->leave_type}";
    }

    public function details(): string
    {
        $days = $this->start_date->diffInDays($this->end_date) + 1;

        return sprintf(
            "From %s to %s (%d day%s)",
            $this->start_date->toDateString(),
            $this->end_date->toDateString(),
            $days,
            $days === 1 ? '' : 's',
        );
    }

    public function onApproved(): void
    {
    }

    public function scopeSearch(Builder $query, ?string $keyword): Builder
    {
        if (blank($keyword)) {
            return $query;
        }

        $like = '%' . $keyword . '%';

        return $query->where(fn (Builder $query) => $query
            ->where('leave_type', 'like', $like)
            ->orWhere('status', 'like', $like)
            ->orWhere('reason', 'like', $like)
            ->orWhere('start_date', 'like', $like)
            ->orWhere('end_date', 'like', $like));
    }
}
