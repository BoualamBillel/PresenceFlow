<?php

namespace App\Repository;

use App\Entity\User;
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
        if ($filter === 'archivés') {
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
     * Récupère les étudiants actifs qui ne sont PAS encore dans la classe donnée.
     */
    public function findAvailableForClasse(int $classeId, ?string $searchTerm = null): array
    {
        $qb = $this->createQueryBuilder('u')
            ->andWhere('CAST_AS_TEXT(u.roles) LIKE :role')
            ->andWhere('u.isArchived = false')
            ->setParameter('role', '%"ROLE_ETUDIANT"%')
            ->andWhere(':classeId NOT MEMBER OF u.classes')
            ->setParameter('classeId', $classeId)
            ->orderBy('u.nom', 'ASC');

        if ($searchTerm) {
            $qb->andWhere('(LOWER(u.nom) LIKE LOWER(:search) OR LOWER(u.prenom) LIKE LOWER(:search) OR LOWER(u.email) LIKE LOWER(:search))')
               ->setParameter('search', '%' . $searchTerm . '%');
        }

        return $qb->getQuery()->getResult();
    }
}
