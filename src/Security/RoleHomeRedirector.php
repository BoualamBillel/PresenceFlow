<?php

namespace App\Security;

use App\Entity\User;
use App\Enum\Role;

/**
 * Détermine le tableau de bord d'atterrissage selon le rôle de l'utilisateur.
 */
final class RoleHomeRedirector
{
    public function dashboardRouteFor(User $user): string
    {
        $roles = $user->getRoles();

        if (in_array(Role::ADMIN->value, $roles, true)) {
            return 'app_admin_dashboard';
        }

        if (in_array(Role::FORMATEUR->value, $roles, true)) {
            return 'app_formateur_dashboard';
        }

        return 'app_etudiant_dashboard';
    }
}
