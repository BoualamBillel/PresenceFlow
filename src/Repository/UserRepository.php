<?php

namespace App\Repository;

use App\Entity\Classe;
use App\Entity\User;
use App\Enum\Role;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

/**
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * Used to upgrade (rehash) the user's password automatically over time.
     */
    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', $user::class));
        }

        $user->setPassword($newHashedPassword);
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
    }

    public function findBySearchAndFilter(?string $searchTerm, string $filter, int $limit = 5): Paginator
    {
        $qb = $this->createQueryBuilder('u')
            ->orderBy('u.nom', 'ASC')
            ->andWhere('CAST_AS_TEXT(u.roles) NOT LIKE :adminRole')
            ->setParameter('adminRole', '%"ROLE_ADMIN"%');

        // Gestion du filtre par statut/rôle
        if ($filter === 'archives') {
            $qb->andWhere('u.isArchived = true');
        } else {
            $qb->andWhere('u.isArchived = false');

            if ($filter === 'formateurs') {
                $qb->andWhere('CAST_AS_TEXT(u.roles) LIKE :role')
                    ->setParameter('role', '%"ROLE_FORMATEUR"%');
            } elseif ($filter === 'apprenants') {
                $qb->andWhere('CAST_AS_TEXT(u.roles) LIKE :role')
                    ->setParameter('role', '%"ROLE_ETUDIANT"%');
            }
        }

        // Gestion de la recherche textuelle
        if ($searchTerm) {
            $qb->andWhere('(LOWER(u.nom) LIKE LOWER(:search) OR LOWER(u.email) LIKE LOWER(:search) OR LOWER(u.prenom) LIKE LOWER(:search))')
                ->setParameter('search', '%' . $searchTerm . '%');
        }

        $qb->setMaxResults($limit);

        return new Paginator($qb);
    }

    /**
     * Étudiants n'appartenant pas à la classe, filtrables par nom/prénom/email.
     *
     * @return User[]
     */
    public function findEtudiantsDisponiblesPourClasse(Classe $classe, string $search = ''): array
    {
        $qb = $this->createQueryBuilder('u')
            ->andWhere('u.isArchived = false')
            ->andWhere('CAST_AS_TEXT(u.roles) LIKE :role')
            ->setParameter('role', '%"ROLE_ETUDIANT"%')
            ->andWhere(':classe NOT MEMBER OF u.classes')
            ->setParameter('classe', $classe)
            ->orderBy('u.nom', 'ASC')
            ->addOrderBy('u.prenom', 'ASC');

        if ($search !== '') {
            $qb->andWhere('LOWER(u.nom) LIKE :search OR LOWER(u.prenom) LIKE :search OR LOWER(u.email) LIKE :search')
                ->setParameter('search', '%' . mb_strtolower($search) . '%');
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Formateurs non archivés, triés par nom.
     *
     * @return User[]
     */
    public function findActiveFormateurs(): array
    {
        return $this->createQueryBuilder('u')
            ->andWhere('CAST_AS_TEXT(u.roles) LIKE :role')
            ->andWhere('u.isArchived = false')
            ->setParameter('role', '%"' . Role::FORMATEUR->value . '"%')
            ->orderBy('u.nom', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère tous les utilisateurs et exclut les administrateurs via PHP
     * pour contourner le typage strict JSON de PostgreSQL.
     */
    public function findAllExceptAdmins(): array
    {
        $allUsers = $this->createQueryBuilder('u')
            ->orderBy('u.nom', 'ASC')
            ->addOrderBy('u.prenom', 'ASC')
            ->getQuery()
            ->getResult();

        $filteredUsers = array_filter($allUsers, function ($user) {
            return !in_array('ROLE_ADMIN', $user->getRoles(), true);
        });

        return array_values($filteredUsers);
    }
}
