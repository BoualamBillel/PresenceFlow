<?php

namespace App\Repository;

use App\Entity\Emargement;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Emargement>
 */
class EmargementRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Emargement::class);
    }

    /**
     * @return Emargement[] Returns an array of Emargement objects
     */
    public function findForExport(?\App\Entity\Classe $classe, ?\App\Entity\User $etudiant): array
    {
        $qb = $this->createQueryBuilder('e')
            ->join('e.session', 's')
            ->join('e.etudiant', 'u')
            ->orderBy('s.dateCours', 'DESC')
            ->addOrderBy('u.nom', 'ASC');

        if ($classe) {
            $qb->andWhere('s.classe = :classe')
                ->setParameter('classe', $classe);
        }

        if ($etudiant) {
            $qb->andWhere('e.etudiant = :etudiant')
                ->setParameter('etudiant', $etudiant);
        }

        return $qb->getQuery()->getResult();
    }
}
