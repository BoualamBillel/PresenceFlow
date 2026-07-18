<?php

namespace App\Service;

use App\Entity\SessionCours;
use App\Enum\SessionStatut;
use Symfony\Component\Clock\ClockInterface;

/**
 * Déduit le statut temporel d'une session de cours.
 */
final class SessionStatutCalculator
{
    public function __construct(private readonly ClockInterface $clock)
    {
    }

    public function compute(SessionCours $session): SessionStatut
    {
        $now = $this->clock->now();

        $todayStr = $now->format('Y-m-d');
        $currentTime = $now->format('H:i:s');

        $sessionDateStr = $session->getDateCours()->format('Y-m-d');
        $debut = $session->getHeureDebut()->format('H:i:s');
        $fin = $session->getHeureFin()->format('H:i:s');

        if ($sessionDateStr !== $todayStr) {
            return $sessionDateStr > $todayStr ? SessionStatut::A_VENIR : SessionStatut::TERMINE;
        }

        if ($currentTime < $debut) {
            return SessionStatut::A_VENIR;
        }

        if ($currentTime <= $fin) {
            return SessionStatut::EN_COURS;
        }

        return SessionStatut::TERMINE;
    }
}
