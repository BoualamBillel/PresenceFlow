<?php

namespace App\Repository;

use App\Entity\Classe;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Classe>
 */
class ClasseRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Classe::class);
    }

    public function findBySearchAndFilter(?string $searchTerm, string $filter): array
    {
        $qb = $this->createQueryBuilder('c')
            ->leftJoin('c.filiere', 'f')
            ->addSelect('f') 
            ->leftJoin('c.etudiants', 'e')
            ->addSelect('e')
            ->orderBy('c.nom', 'ASC');

        // Filtre de statut
        if ($filter === 'archivees') {
            $qb->andWhere('c.isArchived = true');
        } else {
            $qb->andWhere('c.isArchived = false');
        }

        // Recherche textuelle sur la classe OU la filière
        if ($searchTerm) {
            $qb->andWhere('(LOWER(c.nom) LIKE LOWER(:search) OR LOWER(c.annee) LIKE LOWER(:search) OR LOWER(f.nom) LIKE LOWER(:search))')
               ->setParameter('search', '%' . $searchTerm . '%');
        }

        return $qb->getQuery()->getResult();
    }
}
