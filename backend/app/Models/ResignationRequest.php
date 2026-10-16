<?php

namespace App\Models;

use App\Contracts\Reviewable;
use App\Models\Concerns\IsReviewable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ResignationRequest extends Model implements Reviewable
{
    use IsReviewable;

    protected $fillable = [
        'resignation_date',
        'reason',
    ];

    protected function casts(): array
    {
        return [
            'resignation_date' => 'date',
        ];
    }

    public static function requestType(): string
    {
        return 'resignation';
    }

    public static function requestCategory(): string
    {
        return 'Resignation';
    }

    public function summary(): string
    {
        return 'Resignation effective ' . $this->resignation_date->toDateString();
    }

    public function details(): string
    {
        return $this->summary();
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
            ->where('reason', 'like', $like)
            ->orWhere('status', 'like', $like)
            ->orWhere('resignation_date', 'like', $like));
    }
}
