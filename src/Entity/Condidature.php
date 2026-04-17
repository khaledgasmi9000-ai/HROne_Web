<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use App\Repository\CondidatureRepository;

#[ORM\Entity(repositoryClass: CondidatureRepository::class)]
#[ORM\Table(name: 'condidature')]
class Condidature
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $ID_Condidature = null;

    public function getID_Condidature(): ?int
    {
        return $this->ID_Condidature;
    }

    public function setID_Condidature(int $ID_Condidature): self
    {
        $this->ID_Condidature = $ID_Condidature;
        return $this;
    }

    #[ORM\ManyToOne(targetEntity: Condidat::class, inversedBy: 'condidatures')]
    #[ORM\JoinColumn(name: 'ID_Condidat', referencedColumnName: 'ID_Condidat')]
    private ?Condidat $condidat = null;

    public function getCondidat(): ?Condidat
    {
        return $this->condidat;
    }

    public function setCondidat(?Condidat $condidat): self
    {
        $this->condidat = $condidat;
        return $this;
    }

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $Lettre_Motivation = null;

    public function getLettre_Motivation(): ?string
    {
        return $this->Lettre_Motivation;
    }

    public function setLettre_Motivation(?string $Lettre_Motivation): self
    {
        $this->Lettre_Motivation = $Lettre_Motivation;
        return $this;
    }

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $Portfolio = null;

    public function getPortfolio(): ?string
    {
        return $this->Portfolio;
    }

    public function setPortfolio(?string $Portfolio): self
    {
        $this->Portfolio = $Portfolio;
        return $this;
    }

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $Lettre_Recomendation = null;

    public function getLettre_Recomendation(): ?string
    {
        return $this->Lettre_Recomendation;
    }

    public function setLettre_Recomendation(?string $Lettre_Recomendation): self
    {
        $this->Lettre_Recomendation = $Lettre_Recomendation;
        return $this;
    }

    #[ORM\ManyToOne(targetEntity: TypeStatusCondidature::class, inversedBy: 'condidatures')]
    #[ORM\JoinColumn(name: 'Code_Type_Status', referencedColumnName: 'Code_Type_Status_Condidature')]
    private ?TypeStatusCondidature $typeStatusCondidature = null;

    public function getTypeStatusCondidature(): ?TypeStatusCondidature
    {
        return $this->typeStatusCondidature;
    }

    public function setTypeStatusCondidature(?TypeStatusCondidature $typeStatusCondidature): self
    {
        $this->typeStatusCondidature = $typeStatusCondidature;
        return $this;
    }

    #[ORM\ManyToOne(targetEntity: Offre::class, inversedBy: 'condidatures')]
    #[ORM\JoinColumn(name: 'ID_Offre', referencedColumnName: 'ID_Offre')]
    private ?Offre $offre = null;

    public function getOffre(): ?Offre
    {
        return $this->offre;
    }

    public function setOffre(?Offre $offre): self
    {
        $this->offre = $offre;
        return $this;
    }

    public function getIDCondidature(): ?int
    {
        return $this->ID_Condidature;
    }

    public function getLettreMotivation(): ?string
    {
        return $this->Lettre_Motivation;
    }

    public function setLettreMotivation(?string $Lettre_Motivation): static
    {
        $this->Lettre_Motivation = $Lettre_Motivation;

        return $this;
    }

    public function getLettreRecomendation(): ?string
    {
        return $this->Lettre_Recomendation;
    }

    public function setLettreRecomendation(?string $Lettre_Recomendation): static
    {
        $this->Lettre_Recomendation = $Lettre_Recomendation;

        return $this;
    }

}
