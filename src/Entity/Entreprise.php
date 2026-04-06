<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use App\Repository\EntrepriseRepository;

#[ORM\Entity(repositoryClass: EntrepriseRepository::class)]
#[ORM\Table(name: 'entreprise', uniqueConstraints: [
    new ORM\UniqueConstraint(name: 'uniq_entreprise_nom', columns: ['Nom_Entreprise']),
    new ORM\UniqueConstraint(name: 'uniq_entreprise_reference', columns: ['Reference']),
])]
class Entreprise
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'ID_Entreprise', type: 'integer')]
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

    public function getIDEntreprise(): ?int
    {
        return $this->ID_Entreprise;
    }

    #[ORM\Column(name: 'Nom_Entreprise', type: 'string', nullable: false)]
    private ?string $Nom_Entreprise = null;

    // ✅ underscore version
    public function getNom_Entreprise(): ?string
    {
        return $this->Nom_Entreprise;
    }

    public function setNom_Entreprise(string $Nom_Entreprise): self
    {
        $this->Nom_Entreprise = $Nom_Entreprise;
        return $this;
    }

    // ✅ camelCase version — required by Symfony PropertyAccessor
    public function getNomEntreprise(): ?string
    {
        return $this->Nom_Entreprise;
    }

    public function setNomEntreprise(string $Nom_Entreprise): self
    {
        $this->Nom_Entreprise = $Nom_Entreprise;
        return $this;
    }

    #[ORM\Column(name: 'Reference', type: 'string', nullable: true)]
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

    #[ORM\OneToMany(targetEntity: Offre::class, mappedBy: 'entreprise')]
    private Collection $offres;

    #[ORM\OneToMany(targetEntity: Utilisateur::class, mappedBy: 'entreprise')]
    private Collection $utilisateurs;

    public function __construct()
    {
        $this->offres      = new ArrayCollection();
        $this->utilisateurs = new ArrayCollection();
    }

    public function getOffres(): Collection
    {
        if (!$this->offres instanceof Collection) {
            $this->offres = new ArrayCollection();
        }
        return $this->offres;
    }

    public function addOffre(Offre $offre): self
    {
        if (!$this->getOffres()->contains($offre)) {
            $this->getOffres()->add($offre);
        }
        return $this;
    }

    public function removeOffre(Offre $offre): self
    {
        $this->getOffres()->removeElement($offre);
        return $this;
    }

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
}
