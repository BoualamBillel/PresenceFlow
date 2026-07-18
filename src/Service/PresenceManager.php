<?php

namespace App\Service;

use App\Entity\Emargement;
use App\Enum\EmargementStatut;
use Symfony\Component\Clock\ClockInterface;

/**
 * Règles métier de marquage et de résolution des statuts d'émargement.
 */
final class PresenceManager
{
    private const DEFAULT_TOLERANCE_MINUTES = 15;

    public function __construct(private readonly ClockInterface $clock)
    {
    }

    /**
     * Marque la présence de l'étudiant et déduit automatiquement s'il est en retard.
     */
    public function marquer(Emargement $emargement): void
    {
        $now = $this->clock->now();
        $emargement->setHeureSignature($now);

        $session = $emargement->getSession();
        if (!$session || !$session->getHeureDebut()) {
            return;
        }

        $tolerance = $session->getToleranceRetard() ?? self::DEFAULT_TOLERANCE_MINUTES;
        $limiteAcceptable = $session->getHeureDebut()->modify("+$tolerance minutes");

        if ($now->format('H:i:s') <= $limiteAcceptable->format('H:i:s')) {
            $emargement->setStatut(EmargementStatut::PRESENT);
        } else {
            $emargement->setStatut(EmargementStatut::RETARD);
        }
    }

    /**
     * Corrige manuellement le statut d'une fiche (action du formateur).
     * Horodate la signature si l'élève est marqué présent ou en retard
     * sans heure de signature connue.
     */
    public function corriger(Emargement $emargement, EmargementStatut $statut): void
    {
        $emargement->setStatut($statut);

        if (!$emargement->getHeureSignature()
            && ($statut === EmargementStatut::PRESENT || $statut === EmargementStatut::RETARD)) {
            $emargement->setHeureSignature($this->clock->now());
        }
    }

    /**
     * Résout le statut courant : une fiche EN_ATTENTE dont le cours est terminé
     * est considérée comme ABSENT (sans modification en base).
     */
    public function resolveStatut(Emargement $emargement): ?EmargementStatut
    {
        $statut = $emargement->getStatut();

        if ($statut !== EmargementStatut::EN_ATTENTE) {
            return $statut;
        }

        $session = $emargement->getSession();

        if ($session && $session->getDateCours() && $session->getHeureFin()) {
            $finDuCours = new \DateTimeImmutable(
                $session->getDateCours()->format('Y-m-d') . ' ' . $session->getHeureFin()->format('H:i:s'),
                new \DateTimeZone('Europe/Paris')
            );

            if ($this->clock->now() > $finDuCours) {
                return EmargementStatut::ABSENT;
            }
        }

        return $statut;
    }

    /**
     * @param Emargement[] $emargements
     * @return Emargement[] Émargements dont le statut résolu est justifiable (ABSENT ou RETARD)
     */
    public function filterJustifiables(array $emargements): array
    {
        return array_filter(
            $emargements,
            fn (Emargement $e) => $this->resolveStatut($e)?->estJustifiable()
        );
    }
}
