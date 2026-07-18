<?php

namespace App\Repository;

use App\Entity\Classe;
use App\Entity\Emargement;
use App\Entity\User;
use App\Enum\EmargementStatut;
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
     * Persiste sans flush (à la charge de l'appelant).
     */
    public function add(Emargement $emargement): void
    {
        $this->getEntityManager()->persist($emargement);
    }

    /**
     * Émargements d'un étudiant, du plus récent au plus ancien.
     *
     * @return Emargement[]
     */
    public function findForEtudiant(User $etudiant): array
    {
        return $this->createQueryBuilder('e')
            ->join('e.session', 's')
            ->andWhere('e.etudiant = :etudiant')
            ->setParameter('etudiant', $etudiant)
            ->orderBy('s.dateCours', 'DESC')
            ->addOrderBy('s.heureDebut', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function countAbsents(): int
    {
        return $this->count(['statut' => EmargementStatut::ABSENT]);
    }

    /**
     * Dernières fiches ABSENT ne disposant d'aucun justificatif.
     *
     * @return Emargement[]
     */
    public function findRecentesAbsencesNonJustifiees(int $limit): array
    {
        return $this->createQueryBuilder('e')
            ->leftJoin('e.justificatifs', 'j')
            ->andWhere('e.statut = :statut')
            ->andWhere('j.id IS NULL')
            ->setParameter('statut', EmargementStatut::ABSENT)
            ->orderBy('e.id', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Emargement[]
     */
    public function findForExport(?Classe $classe, ?User $etudiant): array
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

    public function findStatistiquesParClasse(User $formateur): array
    {
        return $this->createQueryBuilder('e')
            ->select('c.id AS classeId', 'COUNT(e.id) AS total', "SUM(CASE WHEN e.statut IN ('PRESENT', 'RETARD') THEN 1 ELSE 0 END) AS presents")
            ->innerJoin('e.session', 's')
            ->innerJoin('s.classe', 'c')
            ->andWhere('s.formateur = :formateur')
            ->andWhere("e.statut != 'EN_ATTENTE'")
            ->setParameter('formateur', $formateur)
            ->groupBy('c.id')
            ->getQuery()
            ->getResult();
    }

    public function findStatistiquesParEtudiant(Classe $classe, User $formateur): array
    {
        return $this->createQueryBuilder('e')
            ->select('u.id AS etudiantId', 'COUNT(e.id) AS total', "SUM(CASE WHEN e.statut IN ('PRESENT', 'RETARD') THEN 1 ELSE 0 END) AS presents", "SUM(CASE WHEN e.statut = 'ABSENT' THEN 1 ELSE 0 END) AS absents")
            ->innerJoin('e.session', 's')
            ->innerJoin('e.etudiant', 'u')
            ->andWhere('s.classe = :classe')
            ->andWhere('s.formateur = :formateur')
            ->andWhere("e.statut != 'EN_ATTENTE'")
            ->setParameter('classe', $classe)
            ->setParameter('formateur', $formateur)
            ->groupBy('u.id')
            ->getQuery()
            ->getResult();
    }
}
