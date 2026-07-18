<?php

namespace App\Repository;

use App\Entity\SessionCours;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\Collection;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<SessionCours>
 */
class SessionCoursRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SessionCours::class);
    }

    /**
     * Récupère les sessions d'une date spécifique avec filtres optionnels
     */
    public function findSessionsByDate(\DateTimeImmutable $date, ?int $classeId = null, ?int $formateurId = null): array
    {
        $qb = $this->createQueryBuilder('s')
            ->leftJoin('s.matiere', 'm')->addSelect('m')
            ->leftJoin('s.classe', 'c')->addSelect('c')
            ->leftJoin('s.formateur', 'f')->addSelect('f')
            ->andWhere('s.dateCours = :date')
            ->setParameter('date', $date->format('Y-m-d'));

        // Injection dynamique des filtres si présents
        if ($classeId) {
            $qb->andWhere('c.id = :classeId')
                ->setParameter('classeId', $classeId);
        }

        if ($formateurId) {
            $qb->andWhere('f.id = :formateurId')
                ->setParameter('formateurId', $formateurId);
        }

        return $qb->orderBy('s.heureDebut', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Sessions d'un formateur pour une date donnée, triées par heure de début.
     *
     * @return SessionCours[]
     */
    public function findForFormateurOnDate(User $formateur, \DateTimeImmutable $date): array
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.formateur = :formateur')
            ->andWhere('s.dateCours = :date')
            ->setParameter('formateur', $formateur)
            ->setParameter('date', $date->format('Y-m-d'))
            ->orderBy('s.heureDebut', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Sessions rattachées à l'une des classes données pour une date donnée.
     *
     * @param Collection<int, \App\Entity\Classe> $classes
     *
     * @return SessionCours[]
     */
    public function findForClassesOnDate(Collection $classes, \DateTimeImmutable $date): array
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.classe IN (:classes)')
            ->andWhere('s.dateCours = :date')
            ->setParameter('classes', $classes)
            ->setParameter('date', $date->format('Y-m-d'))
            ->orderBy('s.heureDebut', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function countForDate(\DateTimeImmutable $date): int
    {
        return (int) $this->createQueryBuilder('s')
            ->select('COUNT(s.id)')
            ->andWhere('s.dateCours = :date')
            ->setParameter('date', $date->format('Y-m-d'))
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function findByFormateurAndDate(User $formateur, \DateTimeImmutable $date): array
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.formateur = :formateur')
            ->andWhere('s.dateCours = :date')
            ->setParameter('formateur', $formateur)
            ->setParameter('date', $date->format('Y-m-d'))
            ->orderBy('s.heureDebut', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findClassesByFormateur(User $formateur): array
    {
        return $this->createQueryBuilder('s')
            ->select('c')
            ->distinct()
            ->innerJoin('s.classe', 'c')
            ->andWhere('s.formateur = :formateur')
            ->setParameter('formateur', $formateur)
            ->orderBy('c.nom', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
