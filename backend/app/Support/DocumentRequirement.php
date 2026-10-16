<?php

namespace App\Support;

use JsonSerializable;

/**
 * Describes one file an employee has to attach to a request.
 * The uploaded file is expected in the form field "attachment_{key}".
 */
final class DocumentRequirement implements JsonSerializable
{
    public const MAX_KILOBYTES = 5120;

    public const ALLOWED_EXTENSIONS = ['pdf', 'jpg', 'jpeg', 'png', 'webp'];

    public function __construct(
        public readonly string $key,
        public readonly string $label,
        public readonly bool $required,
        public readonly string $hint,
    ) {
    }

    public function field(): string
    {
        return 'attachment_' . $this->key;
    }

    /**
     * Laravel validation rules for the uploaded file.
     */
    public function rules(): array
    {
        return [
            $this->required ? 'required' : 'nullable',
            'file',
            'mimes:' . implode(',', self::ALLOWED_EXTENSIONS),
            'max:' . self::MAX_KILOBYTES,
        ];
    }

    /**
     * @return DocumentRequirement[]
     */
    public static function forResignation(): array
    {
        return [
            new self('resignation_letter', 'Resignation Letter', true,
                'Required. Upload a signed resignation letter stating your last day and reason.'),
            new self('clearance_document', 'Clearance / Supporting Document', false,
                'Optional. Attach clearance, notice, or other related documents if you have them.'),
        ];
    }

    public function jsonSerialize(): array
    {
        return [
            'key' => $this->key,
            'field' => $this->field(),
            'label' => $this->label,
            'required' => $this->required,
            'hint' => $this->hint,
        ];
    }
}
