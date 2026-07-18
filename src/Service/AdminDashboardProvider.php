<?php

namespace App\Service;

use App\Repository\EmargementRepository;
use App\Repository\JustificatifRepository;
use App\Repository\SessionCoursRepository;
use Symfony\Component\Clock\ClockInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Agrège les données du tableau de bord administrateur.
 */
final class AdminDashboardProvider
{
    public function __construct(
        private readonly JustificatifRepository $justificatifRepository,
        private readonly SessionCoursRepository $sessionCoursRepository,
        private readonly EmargementRepository $emargementRepository,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly ClockInterface $clock,
    ) {
    }

    /**
     * @return array{
     *     count_justificatifs: int,
     *     count_sessions: int,
     *     taux_presence: int,
     *     activites: list<array{type: string, titre: string, temps: string, raw_date: \DateTimeImmutable, couleur: string, route: string}>
     * }
     */
    public function getDashboardData(): array
    {
        return [
            'count_justificatifs' => $this->justificatifRepository->countEnAttente(),
            'count_sessions' => $this->sessionCoursRepository->countForDate($this->clock->now()),
            'taux_presence' => $this->computeTauxPresence(),
            'activites' => $this->buildActivites(),
        ];
    }

    private function computeTauxPresence(): int
    {
        $total = $this->emargementRepository->count([]);
        if ($total === 0) {
            return 100;
        }

        $absences = $this->emargementRepository->countAbsents();

        return (int) round((($total - $absences) / $total) * 100);
    }

    /**
     * @return list<array{type: string, titre: string, temps: string, raw_date: \DateTimeImmutable, couleur: string, route: string}>
     */
    private function buildActivites(): array
    {
        $activites = [];

        foreach ($this->justificatifRepository->findLatest(3) as $justificatif) {
            $etudiant = $justificatif->getEmargement()->getEtudiant();
            $classe = $etudiant->getClasses()->first();

            $activites[] = [
                'type' => 'justificatif',
                'titre' => sprintf(
                    'Nouveau justificatif soumis : %s %s (%s)',
                    $etudiant->getPrenom(),
                    $etudiant->getNom(),
                    $classe ? $classe->getNom() : 'Sans classe'
                ),
                'temps' => 'Le ' . $justificatif->getDateSoumission()->format('d/m à H:i'),
                'raw_date' => $justificatif->getDateSoumission(),
                'couleur' => 'bg-blue-500',
                'route' => $this->urlGenerator->generate('admin_justificatif_index'),
            ];
        }

        foreach ($this->emargementRepository->findRecentesAbsencesNonJustifiees(3) as $emargement) {
            $etudiant = $emargement->getEtudiant();
            $classe = $etudiant->getClasses()->first();
            $session = $emargement->getSession();

            $activites[] = [
                'type' => 'absence',
                'titre' => sprintf(
                    'Absence non justifiée : %s %s (%s)',
                    $etudiant->getPrenom(),
                    $etudiant->getNom(),
                    $classe ? $classe->getNom() : 'Sans classe'
                ),
                'temps' => 'Cours du ' . $session->getDateCours()->format('d/m'),
                'raw_date' => \DateTimeImmutable::createFromInterface($session->getDateCours()),
                'couleur' => 'bg-red-500',
                'route' => '#',
            ];
        }

        usort($activites, fn (array $a, array $b) => $b['raw_date'] <=> $a['raw_date']);

        return array_slice($activites, 0, 5);
    }
}
