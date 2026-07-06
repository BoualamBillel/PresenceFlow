<?php

namespace App\Repository;

use App\Entity\Filiere;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Filiere>
 */
class FiliereRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Filiere::class);
    }

    public function findBySearch(string $searchTerm): array {
        return $this->createQueryBuilder('f')
        ->andWhere('LOWER(f.nom) LIKE LOWER(:query) OR LOWER(f.description) LIKE LOWER(:query)')
        ->setParameter('query', '%' . $searchTerm . '%')
        ->getQuery()
        ->getResult();
    }
}
