<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use App\Repository\ProfilRepository;

#[ORM\Entity(repositoryClass: ProfilRepository::class)]
#[ORM\Table(name: 'profil')]
class Profil
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name : "ID_Profil",type: 'integer')]
    private ?int $ID_Profil = null;

    public function getID_Profil(): ?int
    {
        return $this->ID_Profil;
    }

    public function setID_Profil(int $ID_Profil): self
    {
        $this->ID_Profil = $ID_Profil;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $Nom_Profil = null;

    public function getNom_Profil(): ?string
    {
        return $this->Nom_Profil;
    }

    public function setNom_Profil(string $Nom_Profil): self
    {
        $this->Nom_Profil = $Nom_Profil;
        return $this;
    }

    #[ORM\OneToMany(targetEntity: Utilisateur::class, mappedBy: 'profil')]
    private Collection $utilisateurs;

    public function __construct()
    {
        $this->utilisateurs = new ArrayCollection();
    }

    /**
     * @return Collection<int, Utilisateur>
     */
    public function getUtilisateurs(): Collection
    {
        if (!$this->utilisateurs instanceof Collection) {
            $this->utilisateurs = new ArrayCollection();
        }
        return $this->utilisateurs;
    }

    public function addUtilisateur(Utilisateur $utilisateur): self
    {
        if (!$this->getUtilisateurs()->contains($utilisateur)) {
            $this->getUtilisateurs()->add($utilisateur);
        }
        return $this;
    }

    public function removeUtilisateur(Utilisateur $utilisateur): self
    {
        $this->getUtilisateurs()->removeElement($utilisateur);
        return $this;
    }

    public function getIDProfil(): ?int
    {
        return $this->ID_Profil;
    }

    public function getNomProfil(): ?string
    {
        return $this->Nom_Profil;
    }

    public function setNomProfil(string $Nom_Profil): static
    {
        $this->Nom_Profil = $Nom_Profil;

        return $this;
    }

}
