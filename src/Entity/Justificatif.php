<?php

namespace App\Entity;

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

    #[ORM\Column(length: 50)]
    private ?string $statut = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    private ?\DateTimeImmutable $dateSoumission = null;

    #[ORM\ManyToOne(inversedBy: 'justificatifs')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Emargement $emargement = null;

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

    public function getStatut(): ?string
    {
        return $this->statut;
    }

    public function setStatut(string $statut): static
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
}
