<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use App\Repository\DemandeCongeRepository;

#[ORM\Entity(repositoryClass: DemandeCongeRepository::class)]
#[ORM\Table(name: 'demande_conge')]
class DemandeConge
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'ID_Demende', type: 'integer')]
    private ?int $ID_Demende = null;

    public function getID_Demende(): ?int
    {
        return $this->ID_Demende;
    }

    public function setID_Demende(int $ID_Demende): self
    {
        $this->ID_Demende = $ID_Demende;
        return $this;
    }

    #[ORM\ManyToOne(targetEntity: Employee::class, inversedBy: 'demandeConges')]
    #[ORM\JoinColumn(name: 'ID_Employe', referencedColumnName: 'ID_Employe')]
    private ?Employee $employee = null;

    public function getEmployee(): ?Employee
    {
        return $this->employee;
    }

    public function setEmployee(?Employee $employee): self
    {
        $this->employee = $employee;
        return $this;
    }

    #[ORM\Column(type: 'integer', nullable: false)]
    private ?int $Nbr_Jour_Demande = null;

    public function getNbr_Jour_Demande(): ?int
    {
        return $this->Nbr_Jour_Demande;
    }

    public function setNbr_Jour_Demande(int $Nbr_Jour_Demande): self
    {
        $this->Nbr_Jour_Demande = $Nbr_Jour_Demande;
        return $this;
    }

    #[ORM\ManyToOne(targetEntity: Ordre::class, inversedBy: 'demandeCongesDebut')]
    #[ORM\JoinColumn(name: 'Num_Ordre_Debut_Conge', referencedColumnName: 'Num_Ordre')]
    private ?Ordre $ordreDebut = null;

    public function getOrdreDebut(): ?Ordre
    {
        return $this->ordreDebut;
    }

    public function setOrdreDebut(?Ordre $ordreDebut): self
    {
        $this->ordreDebut = $ordreDebut;
        return $this;
    }

    #[ORM\ManyToOne(targetEntity: Ordre::class, inversedBy: 'demandeCongesFin')]
    #[ORM\JoinColumn(name: 'Num_Ordre_Fin_Conge', referencedColumnName: 'Num_Ordre')]
    private ?Ordre $ordreFin = null;

    public function getOrdreFin(): ?Ordre
    {
        return $this->ordreFin;
    }

    public function setOrdreFin(?Ordre $ordreFin): self
    {
        $this->ordreFin = $ordreFin;
        return $this;
    }

    #[ORM\Column(type: 'integer', nullable: false)]
    private ?int $Status = null;

    public function getStatus(): ?int
    {
        return $this->Status;
    }

    public function setStatus(int $Status): self
    {
        $this->Status = $Status;
        return $this;
    }

    public function getIDDemende(): ?int
    {
        return $this->ID_Demende;
    }

    public function getNbrJourDemande(): ?int
    {
        return $this->Nbr_Jour_Demande;
    }

    public function setNbrJourDemande(int $Nbr_Jour_Demande): static
    {
        $this->Nbr_Jour_Demande = $Nbr_Jour_Demande;

        return $this;
    }

}
