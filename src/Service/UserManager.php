<?php

namespace App\Service;

use App\Entity\User;
use App\Enum\Role;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Gestion des comptes utilisateurs (création, rôle, mot de passe).
 * Ne flush pas (à la charge de l'appelant).
 */
final class UserManager
{
    private const TEMP_PASSWORD_BYTES = 8;

    public function __construct(private readonly UserPasswordHasherInterface $passwordHasher)
    {
    }

    /**
     * Prépare un nouveau compte : rôle, mot de passe temporaire hashé
     * et changement de mot de passe forcé à la première connexion.
     *
     * @return string le mot de passe temporaire en clair (à communiquer une seule fois)
     */
    public function createUser(User $user, Role $role): string
    {
        $user->setRoles([$role->value]);
        $user->setMustChangePassword(true);

        $plainPassword = $this->generateTemporaryPassword();
        $user->setPassword($this->passwordHasher->hashPassword($user, $plainPassword));

        return $plainPassword;
    }

    public function updateRole(User $user, Role $role): void
    {
        $user->setRoles([$role->value]);
    }

    public function changePassword(User $user, string $plainPassword): void
    {
        $user->setPassword($this->passwordHasher->hashPassword($user, $plainPassword));
    }

    private function generateTemporaryPassword(): string
    {
        return substr(bin2hex(random_bytes(self::TEMP_PASSWORD_BYTES)), 0, 8);
    }
}
