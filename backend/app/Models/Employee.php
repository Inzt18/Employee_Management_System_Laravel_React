<?php

namespace App\Models;

use App\Enums\EmploymentStatus;
use App\Enums\Role;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Employee extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    public const GENDERS = ['Male', 'Female'];

    public const DEPARTMENTS = [
        'Information Technology',
        'Human Resources',
        'Accounting',
        'Finance',
        'Registrar',
        'Guidance',
    ];

    protected $fillable = [
        'first_name',
        'middle_name',
        'last_name',
        'birth_date',
        'gender',
        'email',
        'department',
        'address',
        'username',
        'password',
        'role',
        'employment_status',
        'google_id',
        'avatar_url',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'google_id',
    ];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'password' => 'hashed',
            'role' => Role::class,
            'employment_status' => EmploymentStatus::class,
        ];
    }

    protected function fullName(): Attribute
    {
        return Attribute::get(fn () => trim(implode(' ', array_filter([
            $this->first_name,
            $this->middle_name,
            $this->last_name,
        ]))));
    }

    protected function sortableName(): Attribute
    {
        return Attribute::get(fn () => trim("{$this->last_name}, {$this->first_name} {$this->middle_name}"));
    }

    public function isAdmin(): bool
    {
        return $this->role === Role::Admin;
    }

    public function isActive(): bool
    {
        return $this->employment_status === EmploymentStatus::Active;
    }

    public function scopeSearch(Builder $query, ?string $keyword): Builder
    {
        if (blank($keyword)) {
            return $query;
        }

        $like = '%' . $keyword . '%';

        return $query->where(function (Builder $query) use ($like) {
            $query->where('first_name', 'like', $like)
                ->orWhere('middle_name', 'like', $like)
                ->orWhere('last_name', 'like', $like)
                ->orWhere('email', 'like', $like)
                ->orWhere('department', 'like', $like)
                ->orWhere('username', 'like', $like)
                ->orWhere('role', 'like', $like);
        });
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(EmployeeSubject::class);
    }

    public function subjects(): BelongsToMany
    {
        return $this->belongsToMany(Subject::class, 'employee_subjects')->withTimestamps();
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    public function leaveRequests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class);
    }

    public function resignationRequests(): HasMany
    {
        return $this->hasMany(ResignationRequest::class);
    }

    public function subjectRequests(): HasMany
    {
        return $this->hasMany(SubjectRequest::class);
    }

    public function taskRequests(): HasMany
    {
        return $this->hasMany(TaskRequest::class);
    }
}
