<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use App\Repository\CategorieRepository;

#[ORM\Entity(repositoryClass: CategorieRepository::class)]
#[ORM\Table(name: 'categorie')]
class Categorie
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'ID_Categorie', type: 'integer')]
    private ?int $ID_Categorie = null;

    #[ORM\Column(type: 'string', length: 255)]
    private ?string $Nom = null;

    #[ORM\OneToMany(mappedBy: 'categorie', targetEntity: OutilsDeTravail::class)]
    private Collection $outils;

    public function __construct()
    {
        $this->outils = new ArrayCollection();
    }

    public function getIDCategorie(): ?int
    {
        return $this->ID_Categorie;
    }

    public function getNom(): ?string
    {
        return $this->Nom;
    }

    public function setNom(string $nom): self
    {
        $this->Nom = $nom;
        return $this;
    }

    /**
     * @return Collection<int, OutilsDeTravail>
     */
    public function getOutils(): Collection
    {
        return $this->outils;
    }

    public function addOutil(OutilsDeTravail $outil): self
    {
        if (!$this->outils->contains($outil)) {
            $this->outils->add($outil);
            $outil->setCategorie($this);
        }

        return $this;
    }

    public function removeOutil(OutilsDeTravail $outil): self
    {
        if ($this->outils->removeElement($outil)) {
            if ($outil->getCategorie() === $this) {
                $outil->setCategorie(null);
            }
        }

        return $this;
    }
}