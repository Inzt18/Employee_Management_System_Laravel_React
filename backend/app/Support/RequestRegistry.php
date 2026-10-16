<?php

namespace App\Support;

use App\Contracts\Reviewable;
use App\Models\LeaveRequest;
use App\Models\ResignationRequest;
use App\Models\SubjectRequest;
use App\Models\TaskRequest;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

/**
 * Central list of every reviewable request type, keyed by its URL/morph name.
 */
final class RequestRegistry
{
    /**
     * @var array<int, class-string<Model&Reviewable>>
     */
    private const MODELS = [
        LeaveRequest::class,
        ResignationRequest::class,
        SubjectRequest::class,
        TaskRequest::class,
    ];

    /**
     * @return array<string, class-string<Model&Reviewable>>
     */
    public static function map(): array
    {
        $map = [];
        foreach (self::MODELS as $model) {
            $map[$model::requestType()] = $model;
        }

        return $map;
    }

    public static function types(): array
    {
        return array_keys(self::map());
    }

    /**
     * @return class-string<Model&Reviewable>
     */
    public static function modelFor(string $type): string
    {
        return self::map()[$type] ?? throw new InvalidArgumentException("Unknown request type [{$type}].");
    }
}
