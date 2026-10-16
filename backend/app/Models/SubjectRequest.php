<?php

namespace App\Models;

use App\Contracts\Reviewable;
use App\Models\Concerns\IsReviewable;
use Illuminate\Database\Eloquent\Model;

class SubjectRequest extends Model implements Reviewable
{
    use IsReviewable;

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

    public static function requestType(): string
    {
        return 'subject';
    }

    public static function requestCategory(): string
    {
        return 'Subject';
    }

    public function summary(): string
    {
        return "Add subject {$this->subject_code}";
    }

    public function details(): string
    {
        return "Code: {$this->subject_code}\nName: {$this->subject_name}\nUnits: {$this->units}";
    }

    /**
     * Reuse the subject if the code already exists, then assign it to the employee.
     */
    public function onApproved(): void
    {
        $subject = Subject::firstOrCreate(
            ['subject_code' => $this->subject_code],
            ['subject_name' => $this->subject_name, 'units' => $this->units],
        );

        EmployeeSubject::firstOrCreate([
            'employee_id' => $this->employee_id,
            'subject_id' => $subject->id,
        ]);
    }
}
