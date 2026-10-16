<?php

namespace App\Enums;

use App\Support\DocumentRequirement;

enum LeaveType: string
{
    case Vacation = 'Vacation';
    case Sick = 'Sick';
    case Emergency = 'Emergency';
    case Maternity = 'Maternity';
    case Paternity = 'Paternity';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function label(): string
    {
        return $this->value . ' Leave';
    }

    /**
     * Documents the employee must (or may) upload for this leave type.
     *
     * @return DocumentRequirement[]
     */
    public function documentRequirements(): array
    {
        return match ($this) {
            self::Vacation => [
                new DocumentRequirement('supporting_document', 'Travel / Supporting Document', false,
                    'Optional. You may attach an itinerary, booking, or other travel-related document.'),
            ],
            self::Sick => [
                new DocumentRequirement('medical_certificate', 'Medical Certificate', true,
                    'Required. Upload a medical certificate from a licensed physician covering the leave dates.'),
            ],
            self::Emergency => [
                new DocumentRequirement('supporting_document', 'Emergency Supporting Document', true,
                    'Required. Attach a document that supports the emergency, such as a hospital note, incident report, or death certificate.'),
            ],
            self::Maternity => [
                new DocumentRequirement('medical_certificate', 'Medical Certificate', true,
                    'Required. Upload a medical certificate confirming pregnancy or delivery.'),
                new DocumentRequirement('hospital_record', 'Hospital / Clinic Record', false,
                    'Optional. Ultrasound, admission record, or other clinic documents may be attached.'),
            ],
            self::Paternity => [
                new DocumentRequirement('birth_certificate', "Child's Birth Certificate", true,
                    'Required. Upload the birth certificate or hospital certificate of live birth.'),
                new DocumentRequirement('supporting_document', 'Supporting Document', false,
                    'Optional. Marriage certificate or related supporting document may be attached.'),
            ],
        };
    }
}
