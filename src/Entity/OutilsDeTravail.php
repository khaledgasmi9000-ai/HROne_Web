<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use App\Repository\OutilsDeTravailRepository;

#[ORM\Entity(repositoryClass: OutilsDeTravailRepository::class)]
#[ORM\Table(name: 'outils_de_travail')]
class OutilsDeTravail
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'ID_Outil', type: 'integer')]
    private ?int $ID_Outil = null;

    public function getID_Outil(): ?int
    {
        return $this->ID_Outil;
    }

    public function setID_Outil(int $ID_Outil): self
    {
        $this->ID_Outil = $ID_Outil;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $Identifiant_Universelle = null;

    public function getIdentifiant_Universelle(): ?string
    {
        return $this->Identifiant_Universelle;
    }

    public function setIdentifiant_Universelle(string $Identifiant_Universelle): self
    {
        $this->Identifiant_Universelle = $Identifiant_Universelle;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $Hash_App = null;

    public function getHash_App(): ?string
    {
        return $this->Hash_App;
    }

    public function setHash_App(string $Hash_App): self
    {
        $this->Hash_App = $Hash_App;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $Nom_Outil = null;

    public function getNom_Outil(): ?string
    {
        return $this->Nom_Outil;
    }

    public function setNom_Outil(?string $Nom_Outil): self
    {
        $this->Nom_Outil = $Nom_Outil;
        return $this;
    }

    #[ORM\ManyToMany(targetEntity: Employee::class, mappedBy: 'outilsDeTravails')]
    private Collection $employees;

    public function __construct()
    {
        $this->employees = new ArrayCollection();
    }

    /**
     * @return Collection<int, Employee>
     */
    public function getEmployees(): Collection
    {
        if (!$this->employees instanceof Collection) {
            $this->employees = new ArrayCollection();
        }
        return $this->employees;
    }

    public function addEmployee(Employee $employee): self
    {
        if (!$this->getEmployees()->contains($employee)) {
            $this->getEmployees()->add($employee);
        }
        return $this;
    }

    public function removeEmployee(Employee $employee): self
    {
        $this->getEmployees()->removeElement($employee);
        return $this;
    }

    // #[ORM\ManyToMany(targetEntity: Employee::class, inversedBy: 'outilsDeTravails')]
    // #[ORM\JoinTable(
    //     name: 'performance',
    //     joinColumns: [
    //         new ORM\JoinColumn(name: 'ID_Outil', referencedColumnName: 'ID_Outil')
    //     ],
    //     inverseJoinColumns: [
    //         new ORM\JoinColumn(name: 'ID_Employe', referencedColumnName: 'ID_Employe')
    //     ]
    // )]
    // private Collection $employees;

    // /**
    //  * @return Collection<int, Employee>
    //  */
    // public function getEmployees(): Collection
    // {
    //     if (!$this->employees instanceof Collection) {
    //         $this->employees = new ArrayCollection();
    //     }
    //     return $this->employees;
    // }

    // public function addEmployee(Employee $employee): self
    // {
    //     if (!$this->getEmployees()->contains($employee)) {
    //         $this->getEmployees()->add($employee);
    //     }
    //     return $this;
    // }

    // public function removeEmployee(Employee $employee): self
    // {
    //     $this->getEmployees()->removeElement($employee);
    //     return $this;
    // }

    public function getIDOutil(): ?int
    {
        return $this->ID_Outil;
    }

    public function getIdentifiantUniverselle(): ?string
    {
        return $this->Identifiant_Universelle;
    }

    public function setIdentifiantUniverselle(string $Identifiant_Universelle): static
    {
        $this->Identifiant_Universelle = $Identifiant_Universelle;

        return $this;
    }

    public function getHashApp(): ?string
    {
        return $this->Hash_App;
    }

    public function setHashApp(string $Hash_App): static
    {
        $this->Hash_App = $Hash_App;

        return $this;
    }

    public function getNomOutil(): ?string
    {
        return $this->Nom_Outil;
    }

    public function setNomOutil(?string $Nom_Outil): static
    {
        $this->Nom_Outil = $Nom_Outil;

        return $this;
    }

}
