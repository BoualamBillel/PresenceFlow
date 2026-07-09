<?php

namespace App\Entity;

use App\Repository\SessionCoursRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SessionCoursRepository::class)]
class SessionCours
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    private ?\DateTimeImmutable $dateCours = null;

    #[ORM\Column(type: Types::TIME_IMMUTABLE)]
    private ?\DateTimeImmutable $heureDebut = null;

    #[ORM\Column(type: Types::TIME_IMMUTABLE)]
    private ?\DateTimeImmutable $heureFin = null;

    #[ORM\Column(nullable: true)]
    private ?int $toleranceRetard = null;

    #[ORM\Column(length: 255)]
    private ?string $emplacement = null;

    #[ORM\ManyToOne(inversedBy: 'sessions')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Classe $classe = null;

    #[ORM\ManyToOne(inversedBy: 'sessionsFormateur')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $formateur = null;

    #[ORM\ManyToOne(inversedBy: 'sessions')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Matiere $matiere = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $qrCodeToken = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $qrTokenExpiresAt = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDateCours(): ?\DateTimeImmutable
    {
        return $this->dateCours;
    }

    public function setDateCours(\DateTimeImmutable $dateCours): static
    {
        $this->dateCours = $dateCours;

        return $this;
    }

    public function getHeureDebut(): ?\DateTimeImmutable
    {
        return $this->heureDebut;
    }

    public function setHeureDebut(\DateTimeImmutable $heureDebut): static
    {
        $this->heureDebut = $heureDebut;

        return $this;
    }

    public function getHeureFin(): ?\DateTimeImmutable
    {
        return $this->heureFin;
    }

    public function setHeureFin(\DateTimeImmutable $heureFin): static
    {
        $this->heureFin = $heureFin;

        return $this;
    }

    public function getToleranceRetard(): ?int
    {
        return $this->toleranceRetard;
    }

    public function setToleranceRetard(?int $toleranceRetard): static
    {
        $this->toleranceRetard = $toleranceRetard;

        return $this;
    }

    public function getEmplacement(): ?string
    {
        return $this->emplacement;
    }

    public function setEmplacement(string $emplacement): static
    {
        $this->emplacement = $emplacement;

        return $this;
    }

    public function getClasse(): ?Classe
    {
        return $this->classe;
    }

    public function setClasse(?Classe $classe): static
    {
        $this->classe = $classe;

        return $this;
    }

    public function getFormateur(): ?User
    {
        return $this->formateur;
    }

    public function setFormateur(?User $formateur): static
    {
        $this->formateur = $formateur;

        return $this;
    }

    public function getMatiere(): ?Matiere
    {
        return $this->matiere;
    }

    public function setMatiere(?Matiere $matiere): static
    {
        $this->matiere = $matiere;

        return $this;
    }

   /**
     * Déduit dynamiquement le statut de la session (Comparaison stricte par chaînes)
     */
    public function getStatut(): string
    {
        if (!$this->dateCours || !$this->heureDebut || !$this->heureFin) {
            return 'INCONNU';
        }

        $now = new \DateTimeImmutable('now', new \DateTimeZone('Europe/Paris'));
        
        $todayStr = $now->format('Y-m-d');
        $currentTime = $now->format('H:i:s');

        $sessionDateStr = $this->dateCours->format('Y-m-d');
        $debut = $this->heureDebut->format('H:i:s');
        $fin = $this->heureFin->format('H:i:s');

        if ($sessionDateStr > $todayStr) {
            return 'A_VENIR';
        }
        
        if ($sessionDateStr < $todayStr) {
            return 'TERMINE';
        }

        if ($currentTime < $debut) {
            return 'A_VENIR';
        } elseif ($currentTime >= $debut && $currentTime <= $fin) {
            return 'EN_COURS';
        } else {
            return 'TERMINE';
        }
    }

    public function getQrCodeToken(): ?string
    {
        return $this->qrCodeToken;
    }

    public function setQrCodeToken(?string $qrCodeToken): static
    {
        $this->qrCodeToken = $qrCodeToken;

        return $this;
    }

    public function getQrTokenExpiresAt(): ?\DateTimeImmutable
    {
        return $this->qrTokenExpiresAt;
    }

    public function setQrTokenExpiresAt(?\DateTimeImmutable $qrTokenExpiresAt): static
    {
        $this->qrTokenExpiresAt = $qrTokenExpiresAt;

        return $this;
    }

    /**
     * Génère un nouveau jeton de sécurité pour l'émargement (valide 5 minutes)
     */
    public function generateNewQrToken(): void
    {
        // Génération d'une chaîne hexadécimale sécurisée de 32 caractères
        $this->qrCodeToken = bin2hex(random_bytes(16));
        
        // Expiration définie à +5 minutes par rapport à l'heure du serveur (Paris)
        $now = new \DateTimeImmutable('now', new \DateTimeZone('Europe/Paris'));
        $this->qrTokenExpiresAt = $now->modify('+5 minutes');
    }

    /**
     * Vérifie si le jeton actuel est toujours valide
     */
    public function isQrTokenValid(): bool
    {
        if (!$this->qrCodeToken || !$this->qrTokenExpiresAt) {
            return false;
        }

        $now = new \DateTimeImmutable('now', new \DateTimeZone('Europe/Paris'));
        return $this->qrTokenExpiresAt > $now;
    }
}
