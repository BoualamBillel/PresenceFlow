<?php

namespace App\Twig;

use App\Entity\Emargement;
use App\Entity\SessionCours;
use App\Enum\EmargementStatut;
use App\Enum\SessionStatut;
use App\Service\PresenceManager;
use App\Service\SessionStatutCalculator;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Expose les statuts métier calculés aux templates.
 */
final class StatutExtension extends AbstractExtension
{
    public function __construct(
        private readonly PresenceManager $presenceManager,
        private readonly SessionStatutCalculator $sessionStatutCalculator,
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('emargement_statut', fn (Emargement $e): ?EmargementStatut => $this->presenceManager->resolveStatut($e)),
            new TwigFunction('session_statut', fn (SessionCours $s): SessionStatut => $this->sessionStatutCalculator->compute($s)),
        ];
    }
}
