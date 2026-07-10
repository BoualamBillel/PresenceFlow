<?php

namespace App\Entity;

use App\Repository\EmargementRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EmargementRepository::class)]
class Emargement
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $statut = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $heureSignature = null;

    #[ORM\ManyToOne(inversedBy: 'emargements')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $etudiant = null;

    #[ORM\ManyToOne(inversedBy: 'emargements')]
    #[ORM\JoinColumn(nullable: false)]
    private ?SessionCours $session = null;

    public function getId(): ?int
    {
        return $this->id;
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

    public function getHeureSignature(): ?\DateTimeImmutable
    {
        return $this->heureSignature;
    }

    public function setHeureSignature(?\DateTimeImmutable $heureSignature): static
    {
        $this->heureSignature = $heureSignature;

        return $this;
    }

    public function getEtudiant(): ?User
    {
        return $this->etudiant;
    }

    public function setEtudiant(?User $etudiant): static
    {
        $this->etudiant = $etudiant;

        return $this;
    }

    public function getSession(): ?SessionCours
    {
        return $this->session;
    }

    public function setSession(?SessionCours $session): static
    {
        $this->session = $session;

        return $this;
    }

    /**
     * Marque la présence de l'étudiant et déduit automatiquement s'il est en retard
     */
    public function marquerPresence(): void
    {
        $now = new \DateTimeImmutable('now', new \DateTimeZone('Europe/Paris'));
        $this->heureSignature = $now;

        $session = $this->getSession();
        if (!$session || !$session->getHeureDebut()) {
            return;
        }

        $heureDebut = $session->getHeureDebut();
        $tolerance = $session->getToleranceRetard() ?? 15; // 15 minute par défaut si null
        
        $limiteAcceptable = $heureDebut->modify("+$tolerance minutes");

        $heureScanStr = $now->format('H:i:s');
        $limiteStr = $limiteAcceptable->format('H:i:s');

        if ($heureScanStr <= $limiteStr) {
            $this->statut = 'PRESENT';
        } else {
            $this->statut = 'RETARD';
        }
    }
}
