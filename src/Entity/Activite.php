<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use App\Repository\ActiviteRepository;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ActiviteRepository::class)]
#[ORM\Table(name: 'activite')]
class Activite
{
    // -------------------------------------------------------------------------
    // PROPRIÉTÉS DE L'ACTIVITÉ
    // -------------------------------------------------------------------------
    // Une activité est un composant d'un événement (ex: Atelier, Conférence).
    // -------------------------------------------------------------------------
    // Identifiant unique de l'activité (Clé primaire)
    #[ORM\Id]
    #[ORM\Column(name: 'ID_Activite', type: 'integer')]
    private ?int $ID_Activite = null;

    public function getID_Activite(): ?int
    {
        return $this->ID_Activite;
    }

    public function setID_Activite(int $ID_Activite): self
    {
        $this->ID_Activite = $ID_Activite;
        return $this;
    }

    // Titre de l'activité (Champ obligatoire)
    #[ORM\Column(name: "Titre", type: 'string', nullable: false)]
    #[Assert\NotBlank(message: "Le titre de l'activité est obligatoire.")] 
    private ?string $Titre = null;

    public function getTitre(): ?string
    {
        return $this->Titre;
    }

    public function setTitre(string $Titre): self
    {
        $this->Titre = $Titre;
        return $this;
    }

    // Description (Désormais obligatoire pour votre projet)
    #[ORM\Column(name: "Description", type: 'text', nullable: true)]
    #[Assert\NotBlank(message: "La description de l'activité est obligatoire.")]
    #[Assert\Length(min: 10, max: 500)] // Sécurité : entre 10 et 500 caractères
    private ?string $Description = null;

    public function getDescription(): ?string
    {
        return $this->Description;
    }

    public function setDescription(?string $Description): self
    {
        $this->Description = $Description;
        return $this;
    }




    // -------------------------------------------------------------------------
    // RELATION AVEC DETAIL_EVENEMENT (Liaison vers Evenement)
    // -------------------------------------------------------------------------
    // Une activité peut être liée à plusieurs événements via cette relation.
    // C'est une relation "OneToMany" car une Activite peut apparaître dans 
    // plusieurs lignes de la table DetailEvenement.
    // -------------------------------------------------------------------------
    #[ORM\OneToMany(targetEntity: DetailEvenement::class, mappedBy: 'activite')]
    private Collection $details;

    public function __construct()
    {
        $this->details = new ArrayCollection();
    }

    /**
     * @return Collection<int, DetailEvenement>
     */
    public function getDetails(): Collection
    {
        return $this->details;
    }

    /**
     * Méthode utilitaire pour garder la compatibilité
     * @return Collection<int, Evenement>
     */
    public function getEvenements(): Collection
    {
        $evenements = new ArrayCollection();
        foreach ($this->details as $detail) {
            $evenements->add($detail->getEvenement());
        }
        return $evenements;
    }

    public function getIDActivite(): ?int
    {
        return $this->ID_Activite;
    }


}
