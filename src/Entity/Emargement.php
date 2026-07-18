<?php

namespace App\Entity;

use App\Enum\EmargementStatut;
use App\Repository\EmargementRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EmargementRepository::class)]
class Emargement
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, enumType: EmargementStatut::class)]
    private ?EmargementStatut $statut = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $heureSignature = null;

    #[ORM\ManyToOne(inversedBy: 'emargements')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $etudiant = null;

    #[ORM\ManyToOne(inversedBy: 'emargements')]
    #[ORM\JoinColumn(nullable: false)]
    private ?SessionCours $session = null;

    /**
     * @var Collection<int, Justificatif>
     */
    #[ORM\OneToMany(targetEntity: Justificatif::class, mappedBy: 'emargement')]
    private Collection $justificatifs;

    public function __construct()
    {
        $this->justificatifs = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getStatut(): ?EmargementStatut
    {
        return $this->statut;
    }

    public function setStatut(EmargementStatut $statut): static
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
     * @return Collection<int, Justificatif>
     */
    public function getJustificatifs(): Collection
    {
        return $this->justificatifs;
    }

    public function addJustificatif(Justificatif $justificatif): static
    {
        if (!$this->justificatifs->contains($justificatif)) {
            $this->justificatifs->add($justificatif);
            $justificatif->setEmargement($this);
        }

        return $this;
    }

    public function removeJustificatif(Justificatif $justificatif): static
    {
        if ($this->justificatifs->removeElement($justificatif)) {
            // set the owning side to null (unless already changed)
            if ($justificatif->getEmargement() === $this) {
                $justificatif->setEmargement(null);
            }
        }

        return $this;
    }
}
