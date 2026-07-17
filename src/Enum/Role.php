<?php

namespace App\Enum;

enum Role: string
{
    case ADMIN = 'ROLE_ADMIN';
    case FORMATEUR = 'ROLE_FORMATEUR';
    case ETUDIANT = 'ROLE_ETUDIANT';

    public function label(): string
    {
        return match ($this) {
            self::ADMIN => 'Administrateur',
            self::FORMATEUR => 'Formateur',
            self::ETUDIANT => 'Apprenant',
        };
    }
}
