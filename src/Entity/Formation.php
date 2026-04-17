<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use App\Repository\FormationRepository;

#[ORM\Entity(repositoryClass: FormationRepository::class)]
#[ORM\Table(name: 'formation')]
class Formation
{
      #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer', nullable: false)]
    private ?int $ID_Formation = null;

    public function getID_Formation(): ?int
    {
        return $this->ID_Formation;
    }

    public function setID_Formation(int $ID_Formation): self
    {
        $this->ID_Formation = $ID_Formation;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: false)]
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

    #[ORM\Column(type: 'text', nullable: true)]
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

    #[ORM\Column(type: 'integer', nullable: false)]
    private ?int $Num_Ordre_Creation = null;

    public function getNum_Ordre_Creation(): ?int
    {
        return $this->Num_Ordre_Creation;
    }

    public function setNum_Ordre_Creation(int $Num_Ordre_Creation): self
    {
        $this->Num_Ordre_Creation = $Num_Ordre_Creation;
        return $this;
    }

    #[ORM\Column(type: 'integer', nullable: false)]
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

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $Image = null;

    public function getImage(): ?string
    {
        return $this->Image;
    }

    public function setImage(?string $Image): self
    {
        $this->Image = $Image;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $Mode = null;

    public function getMode(): ?string
    {
        return $this->Mode;
    }

    public function setMode(?string $Mode): self
    {
        $this->Mode = $Mode;
        return $this;
    }

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $NombrePlaces = null;

    public function getNombrePlaces(): ?int
    {
        return $this->NombrePlaces;
    }

    public function setNombrePlaces(?int $NombrePlaces): self
    {
        $this->NombrePlaces = $NombrePlaces;
        return $this;
    }

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $PlacesRestantes = null;

    public function getPlacesRestantes(): ?int
    {
        return $this->PlacesRestantes;
    }

    public function setPlacesRestantes(?int $PlacesRestantes): self
    {
        $this->PlacesRestantes = $PlacesRestantes;
        return $this;
    }

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $Date_Debut = null;

    public function getDate_Debut(): ?int
    {
        return $this->Date_Debut;
    }

    public function setDate_Debut(?int $Date_Debut): self
    {
        $this->Date_Debut = $Date_Debut;
        return $this;
    }

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $Date_Fin = null;

    public function getDate_Fin(): ?int
    {
        return $this->Date_Fin;
    }

    public function setDate_Fin(?int $Date_Fin): self
    {
        $this->Date_Fin = $Date_Fin;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $Niveau = null;

    public function getNiveau(): ?string
    {
        return $this->Niveau;
    }

    public function setNiveau(?string $Niveau): self
    {
        $this->Niveau = $Niveau;
        return $this;
    }

    public function getIDFormation(): ?int
    {
        return $this->ID_Formation;
    }

    public function getNumOrdreCreation(): ?int
    {
        return $this->Num_Ordre_Creation;
    }

    public function setNumOrdreCreation(int $Num_Ordre_Creation): static
    {
        $this->Num_Ordre_Creation = $Num_Ordre_Creation;

        return $this;
    }

    public function getIDEntreprise(): ?int
    {
        return $this->ID_Entreprise;
    }

    public function setIDEntreprise(int $ID_Entreprise): static
    {
        $this->ID_Entreprise = $ID_Entreprise;

        return $this;
    }

    public function getDateDebut(): ?int
    {
        return $this->Date_Debut;
    }

    public function setDateDebut(?int $Date_Debut): static
    {
        $this->Date_Debut = $Date_Debut;

        return $this;
    }

    public function getDateFin(): ?int
    {
        return $this->Date_Fin;
    }

    public function setDateFin(?int $Date_Fin): static
    {
        $this->Date_Fin = $Date_Fin;

        return $this;
    }

}
