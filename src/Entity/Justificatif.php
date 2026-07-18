<?php

namespace App\Entity;

use App\Enum\JustificatifStatut;
use App\Repository\JustificatifRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: JustificatifRepository::class)]
class Justificatif
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $urlFichier = null;

    #[ORM\Column(length: 50, enumType: JustificatifStatut::class)]
    private ?JustificatifStatut $statut = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    private ?\DateTimeImmutable $dateSoumission = null;

    #[ORM\ManyToOne(inversedBy: 'justificatifs')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Emargement $emargement = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $motifRefus = null;

    #[ORM\Column(length: 255)]
    private ?string $motifAbsence = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUrlFichier(): ?string
    {
        return $this->urlFichier;
    }

    public function setUrlFichier(string $urlFichier): static
    {
        $this->urlFichier = $urlFichier;

        return $this;
    }

    public function getStatut(): ?JustificatifStatut
    {
        return $this->statut;
    }

    public function setStatut(JustificatifStatut $statut): static
    {
        $this->statut = $statut;

        return $this;
    }

    public function getDateSoumission(): ?\DateTimeImmutable
    {
        return $this->dateSoumission;
    }

    public function setDateSoumission(\DateTimeImmutable $dateSoumission): static
    {
        $this->dateSoumission = $dateSoumission;

        return $this;
    }

    public function getEmargement(): ?Emargement
    {
        return $this->emargement;
    }

    public function setEmargement(?Emargement $emargement): static
    {
        $this->emargement = $emargement;

        return $this;
    }

    public function getMotifRefus(): ?string
    {
        return $this->motifRefus;
    }

    public function setMotifRefus(?string $motifRefus): static
    {
        $this->motifRefus = $motifRefus;

        return $this;
    }

    public function getMotifAbsence(): ?string
    {
        return $this->motifAbsence;
    }

    public function setMotifAbsence(string $motif): static
    {
        $this->motifAbsence = $motif;

        return $this;
    }
}
