<?php

namespace App\Service;

use App\Entity\Classe;
use App\Entity\Emargement;
use App\Entity\User;
use Symfony\Component\Clock\ClockInterface;

/**
 * Prépare les données d'export CSV des émargements.
 */
final class EmargementExporter
{
    public function __construct(
        private readonly PresenceManager $presenceManager,
        private readonly ClockInterface $clock,
    ) {
    }

    public function buildFilename(?Classe $classe, ?User $etudiant): string
    {
        $parts = ['export'];

        if ($classe) {
            $classeClean = strtolower((string) preg_replace('/[^a-zA-Z0-9]/', '_', $classe->getNom()));
            $classeClean = (string) preg_replace('/_+/', '_', $classeClean);
            $parts[] = trim($classeClean, '_');
        }

        if ($etudiant) {
            $parts[] = strtolower($etudiant->getNom() . '_' . $etudiant->getPrenom());
        }

        $parts[] = $this->clock->now()->format('Y-m-d_H\hi');

        return implode('_', $parts) . '.csv';
    }

    /**
     * @param Emargement[] $emargements
     *
     * @return list<array<string, string>> données plates prêtes pour le CsvEncoder
     */
    public function toRows(array $emargements): array
    {
        $rows = [];

        foreach ($emargements as $emargement) {
            $rows[] = [
                'Date' => $emargement->getSession()->getDateCours()->format('d/m/Y'),
                'Classe' => $emargement->getSession()->getClasse()->getNom(),
                'Matiere' => $emargement->getSession()->getMatiere()->getNom(),
                'Nom' => $emargement->getEtudiant()->getNom(),
                'Prenom' => $emargement->getEtudiant()->getPrenom(),
                'Statut' => $this->presenceManager->resolveStatut($emargement)?->value ?? '',
                'Heure de signature' => $emargement->getHeureSignature()?->format('H:i') ?? 'Absence',
            ];
        }

        if ($rows === []) {
            $rows[] = ['Message' => 'Aucune donnee pour ces filtres'];
        }

        return $rows;
    }
}
