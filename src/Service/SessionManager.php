<?php

namespace App\Service;

use App\Entity\Emargement;
use App\Entity\SessionCours;
use App\Entity\User;
use App\Enum\EmargementStatut;
use App\Repository\EmargementRepository;
use App\Repository\SessionCoursRepository;
use Symfony\Component\Clock\ClockInterface;

/**
 * Orchestre le cycle de vie des sessions de cours (lancement, clôture)
 * et la recherche des sessions du jour.
 */
final class SessionManager
{
    private const START_ADVANCE_MINUTES = 15;

    public function __construct(
        private readonly SessionCoursRepository $sessionRepository,
        private readonly EmargementRepository $emargementRepository,
        private readonly QrCodeManager $qrCodeManager,
        private readonly ClockInterface $clock,
    ) {
    }

    /**
     * Première session du jour du formateur dont l'heure de fin n'est pas passée.
     */
    public function findCurrentSessionForFormateur(User $formateur): ?SessionCours
    {
        $now = $this->clock->now();
        $currentTime = $now->format('H:i:s');

        foreach ($this->sessionRepository->findForFormateurOnDate($formateur, $now) as $session) {
            if ($currentTime <= $session->getHeureFin()->format('H:i:s')) {
                return $session;
            }
        }

        return null;
    }

    /**
     * @return array{current: ?SessionCours, prochaines: SessionCours[]}
     */
    public function findCurrentAndUpcomingForEtudiant(User $etudiant): array
    {
        $current = null;
        $prochaines = [];

        $classes = $etudiant->getClasses();
        if ($classes->isEmpty()) {
            return ['current' => null, 'prochaines' => []];
        }

        $now = $this->clock->now();
        $currentTime = $now->format('H:i:s');

        foreach ($this->sessionRepository->findForClassesOnDate($classes, $now) as $session) {
            $debut = $session->getHeureDebut()->format('H:i:s');
            $fin = $session->getHeureFin()->format('H:i:s');

            if ($currentTime >= $debut && $currentTime <= $fin) {
                $current = $session;
            } elseif ($currentTime < $debut) {
                $prochaines[] = $session;
            }
        }

        return ['current' => $current, 'prochaines' => $prochaines];
    }

    /**
     * Une session est affichable comme lançable 15 minutes avant son début.
     */
    public function isStartable(SessionCours $session): bool
    {
        $now = $this->clock->now();
        $debutAutorise = $session->getHeureDebut()->modify('-' . self::START_ADVANCE_MINUTES . ' minutes');

        return $now->format('H:i:s') >= $debutAutorise->format('H:i:s');
    }

    /**
     * Lance la session : génère le jeton QR et initialise les fiches d'émargement
     * de chaque élève de la classe. Ne flush pas (à la charge de l'appelant).
     *
     * @return bool false si l'action est hors des plages horaires autorisées
     */
    public function start(SessionCours $session): bool
    {
        $now = $this->clock->now();
        $debutAutorise = $session->getHeureDebut()->modify('-' . self::START_ADVANCE_MINUTES . ' minutes');

        if ($session->getDateCours()->format('Y-m-d') !== $now->format('Y-m-d')
            || $now->format('H:i:s') < $debutAutorise->format('H:i:s')) {
            return false;
        }

        $this->qrCodeManager->regenerateToken($session);

        $classe = $session->getClasse();
        if ($classe) {
            foreach ($classe->getEtudiants() as $etudiant) {
                // Garde anti-doublon si le formateur clique plusieurs fois
                $existant = $this->emargementRepository->findOneBy([
                    'session' => $session,
                    'etudiant' => $etudiant,
                ]);

                if (!$existant) {
                    $emargement = new Emargement();
                    $emargement->setSession($session);
                    $emargement->setEtudiant($etudiant);
                    $emargement->setStatut(EmargementStatut::EN_ATTENTE);
                    $this->emargementRepository->add($emargement);
                }
            }
        }

        return true;
    }

    /**
     * Clôture la session : invalide le jeton QR et fige les fiches
     * restées EN_ATTENTE en ABSENT. Ne flush pas (à la charge de l'appelant).
     */
    public function close(SessionCours $session): void
    {
        $this->qrCodeManager->invalidateToken($session);

        foreach ($session->getEmargements() as $emargement) {
            if ($emargement->getStatut() === EmargementStatut::EN_ATTENTE) {
                $emargement->setStatut(EmargementStatut::ABSENT);
            }
        }
    }
}
