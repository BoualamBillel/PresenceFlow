<?php

namespace App\Repository;

use App\Entity\SessionCours;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
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
}
