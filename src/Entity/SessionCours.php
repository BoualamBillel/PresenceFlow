<?php

namespace App\Entity;

use App\Repository\SessionCoursRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

#[ORM\Entity(repositoryClass: SessionCoursRepository::class)]
class SessionCours
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    #[Assert\NotNull(message: 'La date du cours est obligatoire.')]
    private ?\DateTimeImmutable $dateCours = null;

    #[ORM\Column(type: Types::TIME_IMMUTABLE)]
    #[Assert\NotNull(message: "L'heure de début est obligatoire.")]
    private ?\DateTimeImmutable $heureDebut = null;

    #[ORM\Column(type: Types::TIME_IMMUTABLE)]
    #[Assert\NotNull(message: "L'heure de fin est obligatoire.")]
    private ?\DateTimeImmutable $heureFin = null;

    #[ORM\Column(nullable: true)]
    #[Assert\PositiveOrZero(message: 'La tolérance doit être un nombre positif.')]
    private ?int $toleranceRetard = 15;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'La salle / le lieu est obligatoire.')]
    private ?string $emplacement = null;

    #[ORM\ManyToOne(inversedBy: 'sessions')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull(message: 'Sélectionnez une classe.')]
    private ?Classe $classe = null;

    #[ORM\ManyToOne(inversedBy: 'sessionsFormateur')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull(message: 'Sélectionnez un formateur.')]
    private ?User $formateur = null;

    #[ORM\ManyToOne(inversedBy: 'sessions')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull(message: 'Sélectionnez une matière.')]
    private ?Matiere $matiere = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $qrCodeToken = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $qrTokenExpiresAt = null;

    /**
     * @var Collection<int, Emargement>
     */
    #[ORM\OneToMany(targetEntity: Emargement::class, mappedBy: 'session', orphanRemoval: true)]
    private Collection $emargements;

    public function __construct()
    {
        $this->emargements = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDateCours(): ?\DateTimeImmutable
    {
        return $this->dateCours;
    }

    public function setDateCours(?\DateTimeImmutable $dateCours): static
    {
        $this->dateCours = $dateCours;

        return $this;
    }

    public function getHeureDebut(): ?\DateTimeImmutable
    {
        return $this->heureDebut;
    }

    public function setHeureDebut(?\DateTimeImmutable $heureDebut): static
    {
        $this->heureDebut = $heureDebut;

        return $this;
    }

    public function getHeureFin(): ?\DateTimeImmutable
    {
        return $this->heureFin;
    }

    public function setHeureFin(?\DateTimeImmutable $heureFin): static
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

    public function setEmplacement(?string $emplacement): static
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
     * @return Collection<int, Emargement>
     */
    public function getEmargements(): Collection
    {
        return $this->emargements;
    }

    public function addEmargement(Emargement $emargement): static
    {
        if (!$this->emargements->contains($emargement)) {
            $this->emargements->add($emargement);
            $emargement->setSession($this);
        }

        return $this;
    }

    public function removeEmargement(Emargement $emargement): static
    {
        if ($this->emargements->removeElement($emargement)) {
            // set the owning side to null (unless already changed)
            if ($emargement->getSession() === $this) {
                $emargement->setSession(null);
            }
        }

        return $this;
    }

    #[Assert\Callback]
    public function validateHoraires(ExecutionContextInterface $context): void
    {
        if ($this->heureDebut && $this->heureFin && $this->heureFin <= $this->heureDebut) {
            $context->buildViolation("L'heure de fin doit être postérieure à l'heure de début.")
                ->atPath('heureFin')
                ->addViolation();
        }
    }
}