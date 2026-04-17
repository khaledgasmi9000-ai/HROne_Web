<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use App\Repository\EmployeeRepository;

#[ORM\Entity(repositoryClass: EmployeeRepository::class)]
#[ORM\Table(name: 'employee')]
class Employee
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'ID_Employe', type: 'integer')]
    private ?int $ID_Employe = null;

    public function getID_Employe(): ?int
    {
        return $this->ID_Employe;
    }

    public function setID_Employe(int $ID_Employe): self
    {
        $this->ID_Employe = $ID_Employe;
        return $this;
    }

    #[ORM\ManyToOne(targetEntity: Utilisateur::class, inversedBy: 'employees')]
    #[ORM\JoinColumn(name: 'ID_UTILISATEUR',referencedColumnName: 'ID_UTILISATEUR',nullable: false)]
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

    #[ORM\Column(type: 'integer', nullable: false)]
    private ?int $Solde_Conge = null;

    public function getSolde_Conge(): ?int
    {
        return $this->Solde_Conge;
    }

    public function setSolde_Conge(int $Solde_Conge): self
    {
        $this->Solde_Conge = $Solde_Conge;
        return $this;
    }

    #[ORM\Column(type: 'integer', nullable: false)]
    private ?int $Nbr_Heure_De_Travail = null;

    public function getNbr_Heure_De_Travail(): ?int
    {
        return $this->Nbr_Heure_De_Travail;
    }

    public function setNbr_Heure_De_Travail(int $Nbr_Heure_De_Travail): self
    {
        $this->Nbr_Heure_De_Travail = $Nbr_Heure_De_Travail;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $Mac_Machine = null;

    public function getMac_Machine(): ?string
    {
        return $this->Mac_Machine;
    }

    public function setMac_Machine(?string $Mac_Machine): self
    {
        $this->Mac_Machine = $Mac_Machine;
        return $this;
    }

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $SALAIRE = null;

    public function getSALAIRE(): ?int
    {
        return $this->SALAIRE;
    }

    public function setSALAIRE(?int $SALAIRE): self
    {
        $this->SALAIRE = $SALAIRE;
        return $this;
    }

    #[ORM\OneToMany(targetEntity: DemandeConge::class, mappedBy: 'employee',orphanRemoval: true)]
    private Collection $demandeConges;

    /**
     * @return Collection<int, DemandeConge>
     */
    public function getDemandeConges(): Collection
    {
        if (!$this->demandeConges instanceof Collection) {
            $this->demandeConges = new ArrayCollection();
        }
        return $this->demandeConges;
    }

    public function addDemandeConge(DemandeConge $demandeConge): self
    {
        if (!$this->getDemandeConges()->contains($demandeConge)) {
            $this->getDemandeConges()->add($demandeConge);
            $demandeConge->setEmployee($this);
        }
        return $this;
    }

    public function removeDemandeConge(DemandeConge $demandeConge): self
    {
        if ($this->demandeConges->removeElement($demandeConge)) {
            if ($demandeConge->getEmployee() === $this) {
                $demandeConge->setEmployee(null);
            }
        }
        return $this;
    }

    #[ORM\ManyToMany(targetEntity: OutilsDeTravail::class, inversedBy: 'employees')]
    #[ORM\JoinTable(
        name: 'outil_employee',
        joinColumns: [
            new ORM\JoinColumn(name: 'ID_EMPLOYEE', referencedColumnName: 'ID_Employe')
        ],
        inverseJoinColumns: [
            new ORM\JoinColumn(name: 'ID_OUTIL', referencedColumnName: 'ID_Outil')
        ]
    )]
    private Collection $outilsDeTravails;

    public function __construct()
    {
        $this->demandeConges = new ArrayCollection();
        $this->outilsDeTravails = new ArrayCollection();
    }

    /**
     * @return Collection<int, OutilsDeTravail>
     */
    public function getOutilsDeTravails(): Collection
    {
        if (!$this->outilsDeTravails instanceof Collection) {
            $this->outilsDeTravails = new ArrayCollection();
        }
        return $this->outilsDeTravails;
    }

    public function addOutilsDeTravail(OutilsDeTravail $outilsDeTravail): self
    {
        if (!$this->getOutilsDeTravails()->contains($outilsDeTravail)) {
            $this->getOutilsDeTravails()->add($outilsDeTravail);
        }
        return $this;
    }

    public function removeOutilsDeTravail(OutilsDeTravail $outilsDeTravail): self
    {
        $this->getOutilsDeTravails()->removeElement($outilsDeTravail);
        return $this;
    }

    public function getIDEmploye(): ?int
    {
        return $this->ID_Employe;
    }

    public function getSoldeConge(): ?int
    {
        return $this->Solde_Conge;
    }

    public function setSoldeConge(int $Solde_Conge): static
    {
        $this->Solde_Conge = $Solde_Conge;

        return $this;
    }

    public function getNbrHeureDeTravail(): ?int
    {
        return $this->Nbr_Heure_De_Travail;
    }

    public function setNbrHeureDeTravail(int $Nbr_Heure_De_Travail): static
    {
        $this->Nbr_Heure_De_Travail = $Nbr_Heure_De_Travail;

        return $this;
    }

    public function getMacMachine(): ?string
    {
        return $this->Mac_Machine;
    }

    public function setMacMachine(?string $Mac_Machine): static
    {
        $this->Mac_Machine = $Mac_Machine;

        return $this;
    }

    #[ORM\ManyToOne(targetEntity: Departement::class, inversedBy: 'employees')]
    #[ORM\JoinColumn(name: 'ID_Departement', referencedColumnName: 'ID_Departement', nullable: true)]
    private ?Departement $departement = null;

    public function getDepartement(): ?Departement
    {
        return $this->departement;
    }

    public function setDepartement(?Departement $departement): self
    {
        $this->departement = $departement;
        return $this;
    }

}
