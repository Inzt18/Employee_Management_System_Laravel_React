<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Subject extends Model
{
    use HasFactory;

    protected $fillable = [
        'subject_code',
        'subject_name',
        'units',
    ];

    protected function casts(): array
    {
        return [
            'units' => 'integer',
        ];
    }

    public function scopeSearch(Builder $query, ?string $keyword): Builder
    {
        if (blank($keyword)) {
            return $query;
        }

        $like = '%' . $keyword . '%';

        return $query->where(fn (Builder $query) => $query
            ->where('subject_code', 'like', $like)
            ->orWhere('subject_name', 'like', $like));
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(EmployeeSubject::class);
    }

    public function employees(): BelongsToMany
    {
        return $this->belongsToMany(Employee::class, 'employee_subjects')->withTimestamps();
    }
}
