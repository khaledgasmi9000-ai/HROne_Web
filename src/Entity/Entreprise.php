<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use App\Repository\EntrepriseRepository;

#[ORM\Entity(repositoryClass: EntrepriseRepository::class)]
#[ORM\Table(name: 'entreprise')]
class Entreprise
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $ID_Entreprise = null;

    public function getID_Entreprise(): ?int
    {
        return $this->ID_Entreprise;
    }

    public function setID_Entreprise(int $ID_Entreprise): self
    {
        $this->ID_Entreprise = $ID_Entreprise;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $Nom_Entreprise = null;

    public function getNom_Entreprise(): ?string
    {
        return $this->Nom_Entreprise;
    }

    public function setNom_Entreprise(string $Nom_Entreprise): self
    {
        $this->Nom_Entreprise = $Nom_Entreprise;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $Reference = null;

    public function getReference(): ?string
    {
        return $this->Reference;
    }

    public function setReference(?string $Reference): self
    {
        $this->Reference = $Reference;
        return $this;
    }

    //#[ORM\OneToMany(targetEntity: Utilisateur::class, mappedBy: 'entreprise')]
    private Collection $utilisateurs;

    public function __construct()
    {
        //$this->offres = new ArrayCollection();
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

    public function getIDEntreprise(): ?int
    {
        return $this->ID_Entreprise;
    }

    public function getNomEntreprise(): ?string
    {
        return $this->Nom_Entreprise;
    }

    public function setNomEntreprise(string $Nom_Entreprise): static
    {
        $this->Nom_Entreprise = $Nom_Entreprise;

        return $this;
    }

}
