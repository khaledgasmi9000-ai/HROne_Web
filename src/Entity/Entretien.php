<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use App\Repository\EntretienRepository;

#[ORM\Entity(repositoryClass: EntretienRepository::class)]
#[ORM\Table(name: 'entretien')]
class Entretien
{
    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: Condidat::class, inversedBy: 'entretiens')]
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

    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: Utilisateur::class, inversedBy: 'entretiens')]
    #[ORM\JoinColumn(name: 'ID_RH', referencedColumnName: 'ID_UTILISATEUR')]
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
    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: Ordre::class, inversedBy: 'entretiens')]
    #[ORM\JoinColumn(name: 'Num_Ordre_Entretien', referencedColumnName: 'Num_Ordre')]
    private ?Ordre $ordre = null;

    public function getOrdre(): ?Ordre
    {
        return $this->ordre;
    }

    public function setOrdre(?Ordre $ordre): self
    {
        $this->ordre = $ordre;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $Localisation = null;

    public function getLocalisation(): ?string
    {
        return $this->Localisation;
    }

    public function setLocalisation(?string $Localisation): self
    {
        $this->Localisation = $Localisation;
        return $this;
    }

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $Status_Entretien = null;

    public function getStatus_Entretien(): ?int
    {
        return $this->Status_Entretien;
    }

    public function setStatus_Entretien(?int $Status_Entretien): self
    {
        $this->Status_Entretien = $Status_Entretien;
        return $this;
    }

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $Evaluation = null;

    public function getEvaluation(): ?string
    {
        return $this->Evaluation;
    }

    public function setEvaluation(?string $Evaluation): self
    {
        $this->Evaluation = $Evaluation;
        return $this;
    }

    public function getStatusEntretien(): ?int
    {
        return $this->Status_Entretien;
    }

    public function setStatusEntretien(?int $Status_Entretien): static
    {
        $this->Status_Entretien = $Status_Entretien;

        return $this;
    }

}
