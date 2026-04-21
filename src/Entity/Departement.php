<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use App\Repository\DepartementRepository;

#[ORM\Entity(repositoryClass: DepartementRepository::class)]
#[ORM\Table(name: 'departement')]
class Departement
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'ID_Departement', type: 'integer')]
    private ?int $ID_Departement = null;

    #[ORM\Column(name:'Nom',type: 'string', length: 255)]
    private ?string $Nom = null;

    #[ORM\OneToMany(mappedBy: 'departement', targetEntity: Employee::class)]
    private Collection $employees;

    public function __construct()
    {
        $this->employees = new ArrayCollection();
    }

    public function getIDDepartement(): ?int
    {
        return $this->ID_Departement;
    }

    public function getNom(): ?string
    {
        return $this->Nom;
    }

    public function setNom(string $nom): self
    {
        $this->Nom = $nom;
        return $this;
    }

    public function getEmployees(): Collection
    {
        return $this->employees;
    }
}