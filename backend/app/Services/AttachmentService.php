<?php

namespace App\Services;

use App\Contracts\Reviewable;
use App\Models\Attachment;
use App\Support\DocumentRequirement;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class AttachmentService
{
    /**
     * Create a request record and store its uploaded documents in one transaction.
     * Files already written to disk are removed again if anything fails.
     *
     * @param  Closure(): (Model&Reviewable)  $createRequest
     * @param  DocumentRequirement[]  $requirements
     * @return Model&Reviewable
     */
    public function createWithAttachments(Closure $createRequest, Request $request, array $requirements): Model
    {
        $storedPaths = [];

        try {
            return DB::transaction(function () use ($createRequest, $request, $requirements, &$storedPaths) {
                $owner = $createRequest();

                foreach ($requirements as $requirement) {
                    $file = $request->file($requirement->field());
                    if ($file === null) {
                        continue;
                    }

                    $fileName = sprintf(
                        '%s_%d_%s_%s.%s',
                        $owner::requestType(),
                        $owner->getKey(),
                        $requirement->key,
                        Str::random(12),
                        $file->guessExtension() ?? $file->getClientOriginalExtension(),
                    );

                    $path = $file->storeAs("attachments/{$owner->employee_id}", $fileName, Attachment::DISK);
                    $storedPaths[] = $path;

                    $owner->attachments()->create([
                        'employee_id' => $owner->employee_id,
                        'document_key' => $requirement->key,
                        'document_label' => $requirement->label,
                        'original_name' => $file->getClientOriginalName(),
                        'stored_path' => $path,
                        'mime_type' => $file->getMimeType() ?? 'application/octet-stream',
                        'file_size' => $file->getSize(),
                    ]);
                }

                return $owner->load('attachments');
            });
        } catch (Throwable $exception) {
            Storage::disk(Attachment::DISK)->delete($storedPaths);

            throw $exception;
        }
    }
}
