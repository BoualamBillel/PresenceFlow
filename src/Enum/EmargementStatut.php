<?php

namespace App\Enum;

enum EmargementStatut: string
{
    case PRESENT = 'PRESENT';
    case RETARD = 'RETARD';
    case ABSENT = 'ABSENT';
    case EN_ATTENTE = 'EN_ATTENTE';

    public function label(): string
    {
        return match ($this) {
            self::PRESENT => 'Présent',
            self::RETARD => 'Retard',
            self::ABSENT => 'Absent',
            self::EN_ATTENTE => 'En attente',
        };
    }

    /**
     * Un élève peut déposer un justificatif pour une absence ou un retard.
     */
    public function estJustifiable(): bool
    {
        return $this === self::ABSENT || $this === self::RETARD;
    }
}
