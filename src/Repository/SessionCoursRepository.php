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
     * Récupère les sessions d'une date spécifique avec optimisation des jointures
     */
    public function findSessionsByDate(\DateTimeImmutable $date): array
    {
        return $this->createQueryBuilder('s')
            ->leftJoin('s.matiere', 'm')
            ->addSelect('m')
            ->leftJoin('s.classe', 'c')
            ->addSelect('c')
            ->leftJoin('s.formateur', 'f')
            ->addSelect('f')
            ->andWhere('s.dateCours = :date')
            ->setParameter('date', $date->format('Y-m-d'))
            ->orderBy('s.heureDebut', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
