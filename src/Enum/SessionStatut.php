<?php

namespace App\Enum;

enum SessionStatut: string
{
    case A_VENIR = 'A_VENIR';
    case EN_COURS = 'EN_COURS';
    case TERMINE = 'TERMINE';

    public function label(): string
    {
        return match ($this) {
            self::A_VENIR => 'À venir',
            self::EN_COURS => 'En cours',
            self::TERMINE => 'Terminé',
        };
    }
}
