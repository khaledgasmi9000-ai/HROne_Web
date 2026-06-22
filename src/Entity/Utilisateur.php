<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use App\Repository\UtilisateurRepository;

#[ORM\Entity(repositoryClass: UtilisateurRepository::class)]
#[ORM\Table(name: 'utilisateur')]
class Utilisateur
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'ID_UTILISATEUR', type: 'integer')]
    private ?int $ID_UTILISATEUR = null;

    public function getID_UTILISATEUR(): ?int
    {
        return $this->ID_UTILISATEUR;
    }

    public function setID_UTILISATEUR(int $ID_UTILISATEUR): self
    {
        $this->ID_UTILISATEUR = $ID_UTILISATEUR;
        return $this;
    }

    #[ORM\ManyToOne(targetEntity: Entreprise::class, inversedBy: 'utilisateurs')]
    #[ORM\JoinColumn(name: 'ID_Entreprise', referencedColumnName: 'ID_Entreprise')]
    private ?Entreprise $entreprise = null;

    public function getEntreprise(): ?Entreprise
    {
        return $this->entreprise;
    }

    public function setEntreprise(?Entreprise $entreprise): self
    {
        $this->entreprise = $entreprise;
        return $this;
    }

    #[ORM\ManyToOne(targetEntity: Profil::class, inversedBy: 'utilisateurs')]
    #[ORM\JoinColumn(name: 'ID_Profil', referencedColumnName: 'ID_Profil')]
    private ?Profil $profil = null;

    public function getProfil(): ?Profil
    {
        return $this->profil;
    }

    public function setProfil(?Profil $profil): self
    {
        $this->profil = $profil;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $Nom_Utilisateur = null;

    public function getNom_Utilisateur(): ?string
    {
        return $this->Nom_Utilisateur;
    }

    public function setNom_Utilisateur(string $Nom_Utilisateur): self
    {
        $this->Nom_Utilisateur = $Nom_Utilisateur;
        return $this;
    }

    #[ORM\Column(name:"Mot_Passe", type: 'string', nullable: false)]
    private ?string $Mot_Passe = "TempPassword";

    public function getMot_Passe(): ?string
    {
        return $this->Mot_Passe;
    }

    public function setMot_Passe(string $Mot_Passe): self
    {
        $this->Mot_Passe = $Mot_Passe;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $Email = null;

    public function getEmail(): ?string
    {
        return $this->Email;
    }

    public function setEmail(?string $Email): self
    {
        $this->Email = $Email;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $Adresse = null;

    public function getAdresse(): ?string
    {
        return $this->Adresse;
    }

    public function setAdresse(?string $Adresse): self
    {
        $this->Adresse = $Adresse;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $Num_Tel = null;

    public function getNum_Tel(): ?string
    {
        return $this->Num_Tel;
    }

    public function setNum_Tel(?string $Num_Tel): self
    {
        $this->Num_Tel = $Num_Tel;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $CIN = null;

    public function getCIN(): ?string
    {
        return $this->CIN;
    }

    public function setCIN(?string $CIN): self
    {
        $this->CIN = $CIN;
        return $this;
    }

    // #[ORM\ManyToOne(targetEntity: Ordre::class, inversedBy: 'utilisateurs')]
    // #[ORM\JoinColumn(name: 'Num_Ordre_Sign_In', referencedColumnName: 'Num_Ordre')]
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

    #[ORM\Column(type: 'date', nullable: true)]
    private ?\DateTimeInterface $Date_Naissance = null;

    public function getDate_Naissance(): ?\DateTimeInterface
    {
        return $this->Date_Naissance;
    }

    public function setDate_Naissance(?\DateTimeInterface $Date_Naissance): self
    {
        $this->Date_Naissance = $Date_Naissance;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $Gender = null;

    public function getGender(): ?string
    {
        return $this->Gender;
    }

    public function setGender(?string $Gender): self
    {
        $this->Gender = $Gender;
        return $this;
    }

    #[ORM\Column(name:"firstLogin", type: 'integer', nullable: true)]
    private ?int $firstLogin = 0;

    public function getFirstLogin(): ?int
    {
        return $this->firstLogin;
    }

    public function setFirstLogin(?int $firstLogin): self
    {
        $this->firstLogin = $firstLogin;
        return $this;
    }


    #[ORM\OneToMany(targetEntity: Employee::class, mappedBy: 'utilisateur')]
    private Collection $employees;

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

    public function __construct()
    {
        //$this->condidats = new ArrayCollection();
        //$this->emailsEmployee = new ArrayCollection();
        //$this->emailsCandidat = new ArrayCollection();
        $this->employees = new ArrayCollection();
        //$this->entretiens = new ArrayCollection();
        //$this->messages = new ArrayCollection();
        //$this->participationEvenements = new ArrayCollection();
        //$this->ordres = new ArrayCollection();
    }


    public function getIDUTILISATEUR(): ?int
    {
        return $this->ID_UTILISATEUR;
    }

    public function getNomUtilisateur(): ?string
    {
        return $this->Nom_Utilisateur;
    }

    public function setNomUtilisateur(string $Nom_Utilisateur): static
    {
        $this->Nom_Utilisateur = $Nom_Utilisateur;

        return $this;
    }

    public function getMotPasse(): ?string
    {
        return $this->Mot_Passe;
    }

    public function setMotPasse(string $Mot_Passe): static
    {
        $this->Mot_Passe = $Mot_Passe;

        return $this;
    }

    public function getNumTel(): ?string
    {
        return $this->Num_Tel;
    }

    public function setNumTel(?string $Num_Tel): static
    {
        $this->Num_Tel = $Num_Tel;

        return $this;
    }

    public function getDateNaissance(): ?\DateTime
    {
        return $this->Date_Naissance;
    }

    public function setDateNaissance(?\DateTime $Date_Naissance): static
    {
        $this->Date_Naissance = $Date_Naissance;

        return $this;
    }

}
