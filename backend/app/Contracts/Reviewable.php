<?php

namespace App\Contracts;

/**
 * An employee request that an administrator approves or rejects.
 */
interface Reviewable
{
    /**
     * Short machine name used in URLs and the morph map, e.g. "leave".
     */
    public static function requestType(): string;

    /**
     * Human readable category, e.g. "Leave".
     */
    public static function requestCategory(): string;

    public function summary(): string;

    public function details(): string;

    /**
     * Side effects to perform once the request has been approved.
     */
    public function onApproved(): void;
}
