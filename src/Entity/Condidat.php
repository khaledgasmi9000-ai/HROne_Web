<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use App\Repository\CondidatRepository;

#[ORM\Entity(repositoryClass: CondidatRepository::class)]
#[ORM\Table(name: 'condidat')]
class Condidat
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'ID_Condidat', type: 'integer')]
    private ?int $ID_Condidat = null;

    public function getID_Condidat(): ?int
    {
        return $this->ID_Condidat;
    }

    public function setID_Condidat(int $ID_Condidat): self
    {
        $this->ID_Condidat = $ID_Condidat;
        return $this;
    }

    #[ORM\ManyToOne(targetEntity: Utilisateur::class, inversedBy: 'condidats')]
    #[ORM\JoinColumn(name: 'ID_UTILISATEUR', referencedColumnName: 'ID_UTILISATEUR')]
    private ?Utilisateur $utilisateur = null;

    public function getUtilisateur(): ?Utilisateur
    {
        return $this->utilisateur;
    }

    public function setUtilisateur(?Utilisateur $utilisateur): self
    {
        $this->utilisateur = $utilisateur;
        return $this;
    }

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $CV = null;

    public function getCV(): ?string
    {
        return $this->CV;
    }

    public function setCV(?string $CV): self
    {
        $this->CV = $CV;
        return $this;
    }

    #[ORM\OneToMany(targetEntity: Condidature::class, mappedBy: 'condidat')]
    private Collection $condidatures;

    /**
     * @return Collection<int, Condidature>
     */
    public function getCondidatures(): Collection
    {
        if (!$this->condidatures instanceof Collection) {
            $this->condidatures = new ArrayCollection();
        }
        return $this->condidatures;
    }

    public function addCondidature(Condidature $condidature): self
    {
        if (!$this->getCondidatures()->contains($condidature)) {
            $this->getCondidatures()->add($condidature);
        }
        return $this;
    }

    public function removeCondidature(Condidature $condidature): self
    {
        $this->getCondidatures()->removeElement($condidature);
        return $this;
    }

    #[ORM\OneToMany(targetEntity: Entretien::class, mappedBy: 'condidat')]
    private Collection $entretiens;

    public function __construct()
    {
        $this->condidatures = new ArrayCollection();
        $this->entretiens = new ArrayCollection();
    }

    /**
     * @return Collection<int, Entretien>
     */
    public function getEntretiens(): Collection
    {
        if (!$this->entretiens instanceof Collection) {
            $this->entretiens = new ArrayCollection();
        }
        return $this->entretiens;
    }

    public function addEntretien(Entretien $entretien): self
    {
        if (!$this->getEntretiens()->contains($entretien)) {
            $this->getEntretiens()->add($entretien);
        }
        return $this;
    }

    public function removeEntretien(Entretien $entretien): self
    {
        $this->getEntretiens()->removeElement($entretien);
        return $this;
    }

    public function getIDCondidat(): ?int
    {
        return $this->ID_Condidat;
    }

}
