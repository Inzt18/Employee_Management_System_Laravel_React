<?php

namespace App\Services;

use App\Contracts\Reviewable;
use App\Enums\RequestStatus;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\ResignationRequest;
use App\Models\SubjectRequest;
use App\Models\TaskRequest;
use App\Support\RequestRegistry;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RequestReviewService
{
    /**
     * Every request of every type, newest first, optionally filtered by status.
     *
     * @return Collection<int, Model&Reviewable>
     */
    public function list(?string $status = null): Collection
    {
        return collect(RequestRegistry::map())
            ->flatMap(fn (string $model) => $model::query()
                ->with(['employee', 'reviewer', 'attachments'])
                ->withStatus($status)
                ->get())
            ->sortByDesc('created_at')
            ->values();
    }

    /**
     * Number of pending requests for one employee, grouped by type.
     */
    public function pendingCountsFor(Employee $employee): array
    {
        $counts = [
            'leave' => LeaveRequest::whereBelongsTo($employee)->withStatus(RequestStatus::Pending->value)->count(),
            'resignation' => ResignationRequest::whereBelongsTo($employee)->withStatus(RequestStatus::Pending->value)->count(),
            'subject' => SubjectRequest::whereBelongsTo($employee)->withStatus(RequestStatus::Pending->value)->count(),
            'task' => TaskRequest::whereBelongsTo($employee)->withStatus(RequestStatus::Pending->value)->count(),
        ];

        return $counts + ['total' => array_sum($counts)];
    }

    /**
     * @return Model&Reviewable
     */
    public function find(string $type, int $id): Model
    {
        return RequestRegistry::modelFor($type)::query()->findOrFail($id);
    }

    /**
     * Approve or reject a pending request and run the approval side effects atomically.
     *
     * @param  Model&Reviewable  $request
     */
    public function review(Model $request, RequestStatus $decision, Employee $reviewer, ?string $comment): Model
    {
        if (! $request->isPending()) {
            throw ValidationException::withMessages([
                'status' => 'This request has already been ' . strtolower($request->status->value) . '.',
            ]);
        }

        return DB::transaction(function () use ($request, $decision, $reviewer, $comment) {
            $request->forceFill([
                'status' => $decision,
                'admin_comment' => $comment,
                'reviewed_by' => $reviewer->id,
                'reviewed_at' => now(),
            ])->save();

            if ($decision === RequestStatus::Approved) {
                $request->onApproved();
            }

            return $request->load(['employee', 'reviewer', 'attachments']);
        });
    }
}
