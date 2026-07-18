<?php

namespace App\Service;

use App\Entity\Emargement;
use App\Entity\Justificatif;
use App\Enum\JustificatifStatut;
use Symfony\Component\Clock\ClockInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Cycle de vie des justificatifs d'absence.
 * Les méthodes ne flush pas (à la charge de l'appelant).
 */
final class JustificatifManager
{
    public function __construct(
        private readonly FileUploader $fileUploader,
        private readonly ClockInterface $clock,
    ) {
    }

    /**
     * Finalise la soumission d'un justificatif pré-rempli par le formulaire
     * (motifAbsence) : dépose le fichier, rattache l'émargement et initialise
     * le statut et la date. Ne persist pas.
     */
    public function soumettre(Justificatif $justificatif, Emargement $emargement, UploadedFile $fichier): void
    {
        $justificatif->setUrlFichier($this->fileUploader->upload($fichier));
        $justificatif->setStatut(JustificatifStatut::EN_ATTENTE);
        $justificatif->setDateSoumission($this->clock->now());
        $justificatif->setEmargement($emargement);
    }

    public function valider(Justificatif $justificatif): void
    {
        $justificatif->setStatut(JustificatifStatut::VALIDE);
    }

    public function refuser(Justificatif $justificatif, string $motifRefus): void
    {
        $justificatif->setStatut(JustificatifStatut::REFUSE);
        $justificatif->setMotifRefus($motifRefus);
    }
}
