<?php

namespace App\Repository;

use App\Entity\Justificatif;
use App\Enum\JustificatifStatut;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Justificatif>
 */
class JustificatifRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Justificatif::class);
    }

    public function countEnAttente(): int
    {
        return $this->count(['statut' => JustificatifStatut::EN_ATTENTE]);
    }

    /**
     * @return Justificatif[]
     */
    public function findEnAttente(): array
    {
        return $this->findBy(['statut' => JustificatifStatut::EN_ATTENTE]);
    }

    /**
     * Justificatifs traités (validés ou refusés).
     *
     * @return Justificatif[]
     */
    public function findTraites(): array
    {
        return $this->createQueryBuilder('j')
            ->andWhere('j.statut != :statut')
            ->setParameter('statut', JustificatifStatut::EN_ATTENTE)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Justificatif[]
     */
    public function findLatest(int $limit): array
    {
        return $this->findBy([], ['dateSoumission' => 'DESC'], $limit);
    }
}
