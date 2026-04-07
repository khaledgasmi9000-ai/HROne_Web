<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use App\Repository\UtilisateurRepository;

#[ORM\Entity(repositoryClass: UtilisateurRepository::class)]
#[ORM\Table(name: 'utilisateur', uniqueConstraints: [
    new ORM\UniqueConstraint(name: 'uniq_utilisateur_email', columns: ['Email']),
    new ORM\UniqueConstraint(name: 'uniq_utilisateur_cin', columns: ['CIN']),
])]
class Utilisateur
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'ID_UTILISATEUR', type: 'integer')]
    private ?int $ID_UTILISATEUR = null;

    public function getID_UTILISATEUR(): ?int { return $this->ID_UTILISATEUR; }
    public function setID_UTILISATEUR(int $v): self { $this->ID_UTILISATEUR = $v; return $this; }

    // -------------------------------------------------------------------------

    #[ORM\ManyToOne(targetEntity: Entreprise::class, inversedBy: 'utilisateurs', cascade: ['persist'])]
    #[ORM\JoinColumn(name: 'ID_Entreprise', referencedColumnName: 'ID_Entreprise', nullable: true)]
    private ?Entreprise $entreprise = null;

    public function getEntreprise(): ?Entreprise { return $this->entreprise; }
    public function setEntreprise(?Entreprise $entreprise): self { $this->entreprise = $entreprise; return $this; }

    // -------------------------------------------------------------------------

    #[ORM\ManyToOne(targetEntity: Profil::class, inversedBy: 'utilisateurs')]
    #[ORM\JoinColumn(name: 'ID_Profil', referencedColumnName: 'ID_Profil', nullable: false)]
    private ?Profil $profil = null;

    public function getProfil(): ?Profil { return $this->profil; }
    public function setProfil(?Profil $profil): self { $this->profil = $profil; return $this; }

    // -------------------------------------------------------------------------

    #[ORM\Column(name: 'Nom_Utilisateur', type: 'string', nullable: false)]
    private ?string $Nom_Utilisateur = null;

    public function getNom_Utilisateur(): ?string { return $this->Nom_Utilisateur; }
    public function setNom_Utilisateur(string $v): self { $this->Nom_Utilisateur = $v; return $this; }
    // ✅ camelCase alias — required by Symfony form PropertyAccessor
    public function getNomUtilisateur(): ?string { return $this->Nom_Utilisateur; }
    public function setNomUtilisateur(string $v): self { $this->Nom_Utilisateur = $v; return $this; }

    // -------------------------------------------------------------------------

    #[ORM\Column(name: 'Mot_Passe', type: 'string', nullable: false)]
    private ?string $Mot_Passe = null;

    public function getMot_Passe(): ?string { return $this->Mot_Passe; }
    public function setMot_Passe(string $v): self { $this->Mot_Passe = $v; return $this; }
    // ✅ camelCase alias
    public function getMotPasse(): ?string { return $this->Mot_Passe; }
    public function setMotPasse(string $v): self { $this->Mot_Passe = $v; return $this; }

    // -------------------------------------------------------------------------

    #[ORM\Column(name: 'Email', type: 'string', nullable: true)]
    private ?string $Email = null;

    public function getEmail(): ?string { return $this->Email; }
    public function setEmail(?string $v): self { $this->Email = $v; return $this; }

    // -------------------------------------------------------------------------

    #[ORM\Column(name: 'Adresse', type: 'string', nullable: true)]
    private ?string $Adresse = null;

    public function getAdresse(): ?string { return $this->Adresse; }
    public function setAdresse(?string $v): self { $this->Adresse = $v; return $this; }

    // -------------------------------------------------------------------------

    #[ORM\Column(name: 'Num_Tel', type: 'string', nullable: true)]
    private ?string $Num_Tel = null;

    public function getNum_Tel(): ?string { return $this->Num_Tel; }
    public function setNum_Tel(?string $v): self { $this->Num_Tel = $v; return $this; }
    // ✅ camelCase alias
    public function getNumTel(): ?string { return $this->Num_Tel; }
    public function setNumTel(?string $v): self { $this->Num_Tel = $v; return $this; }

    // -------------------------------------------------------------------------

    #[ORM\Column(name: 'CIN', type: 'string', nullable: true)]
    private ?string $CIN = null;

    // ✅ Only one pair — CIN has no underscore so Symfony finds getCIN/setCIN directly
    public function getCIN(): ?string { return $this->CIN; }
    public function setCIN(?string $v): self { $this->CIN = $v; return $this; }

    // -------------------------------------------------------------------------

    #[ORM\ManyToOne(targetEntity: Ordre::class, inversedBy: 'utilisateurs')]
    #[ORM\JoinColumn(name: 'Num_Ordre_Sign_In', referencedColumnName: 'Num_Ordre')]
    private ?Ordre $ordre = null;

    public function getOrdre(): ?Ordre { return $this->ordre; }
    public function setOrdre(?Ordre $ordre): self { $this->ordre = $ordre; return $this; }

    // -------------------------------------------------------------------------

    #[ORM\Column(name: 'Date_Naissance', type: 'date', nullable: true)]
    private ?\DateTimeInterface $Date_Naissance = null;

    public function getDate_Naissance(): ?\DateTimeInterface { return $this->Date_Naissance; }
    public function setDate_Naissance(?\DateTimeInterface $v): self { $this->Date_Naissance = $v; return $this; }
    // ✅ camelCase alias
    public function getDateNaissance(): ?\DateTimeInterface { return $this->Date_Naissance; }
    public function setDateNaissance(?\DateTimeInterface $v): self { $this->Date_Naissance = $v; return $this; }

    // -------------------------------------------------------------------------

    #[ORM\Column(name: 'Gender', type: 'string', nullable: true)]
    private ?string $Gender = null;

    public function getGender(): ?string { return $this->Gender; }
    public function setGender(?string $v): self { $this->Gender = $v; return $this; }

    // -------------------------------------------------------------------------

    #[ORM\Column(name: 'firstLogin', type: 'integer', nullable: true)]
    private ?int $firstLogin = null;

    public function getFirstLogin(): ?int { return $this->firstLogin; }
    public function setFirstLogin(?int $v): self { $this->firstLogin = $v; return $this; }

    // -------------------------------------------------------------------------
    // COLLECTIONS
    // -------------------------------------------------------------------------

    #[ORM\OneToMany(targetEntity: Email::class, mappedBy: 'utilisateurReceiver')]
    private Collection $emailsEmployee;

    #[ORM\OneToMany(targetEntity: Email::class, mappedBy: 'utilisateurSender')]
    private Collection $emailsCandidat;

    #[ORM\OneToMany(targetEntity: Condidat::class, mappedBy: 'utilisateur')]
    private Collection $condidats;

    #[ORM\OneToMany(targetEntity: Employee::class, mappedBy: 'utilisateur')]
    private Collection $employees;

    #[ORM\OneToMany(targetEntity: Entretien::class, mappedBy: 'utilisateur')]
    private Collection $entretiens;

    #[ORM\OneToMany(targetEntity: Message::class, mappedBy: 'utilisateur')]
    private Collection $messages;

    #[ORM\OneToMany(targetEntity: ParticipationEvenement::class, mappedBy: 'utilisateur')]
    private Collection $participationEvenements;

    #[ORM\ManyToMany(targetEntity: Ordre::class, inversedBy: 'utilisateurs')]
    #[ORM\JoinTable(
        name: 'action_utilisateur',
        joinColumns: [new ORM\JoinColumn(name: 'ID_UTILISATEUR', referencedColumnName: 'ID_UTILISATEUR')],
        inverseJoinColumns: [new ORM\JoinColumn(name: 'Num_Ordre', referencedColumnName: 'Num_Ordre')]
    )]
    private Collection $ordres;

    public function __construct()
    {
        $this->condidats               = new ArrayCollection();
        $this->emailsEmployee          = new ArrayCollection();
        $this->emailsCandidat          = new ArrayCollection();
        $this->employees               = new ArrayCollection();
        $this->entretiens              = new ArrayCollection();
        $this->messages                = new ArrayCollection();
        $this->participationEvenements = new ArrayCollection();
        $this->ordres                  = new ArrayCollection();
    }

    // -------------------------------------------------------------------------
    // EMAIL EMPLOYEE
    // -------------------------------------------------------------------------

    public function getEmailsEmployee(): Collection
    {
        if (!$this->emailsEmployee instanceof Collection) $this->emailsEmployee = new ArrayCollection();
        return $this->emailsEmployee;
    }

    public function addEmailsEmployee(Email $email): self
    {
        if (!$this->emailsEmployee->contains($email)) {
            $this->emailsEmployee->add($email);
            $email->setUtilisateurReceiver($this);
        }
        return $this;
    }

    public function removeEmailsEmployee(Email $email): self
    {
        if ($this->emailsEmployee->removeElement($email)) {
            if ($email->getUtilisateurReceiver() === $this) $email->setUtilisateurReceiver(null);
        }
        return $this;
    }

    // -------------------------------------------------------------------------
    // EMAIL CANDIDAT
    // -------------------------------------------------------------------------

    public function getEmailsCandidat(): Collection
    {
        if (!$this->emailsCandidat instanceof Collection) $this->emailsCandidat = new ArrayCollection();
        return $this->emailsCandidat;
    }

    public function addEmailsCandidat(Email $email): self
    {
        if (!$this->emailsCandidat->contains($email)) {
            $this->emailsCandidat->add($email);
            $email->setUtilisateurSender($this);
        }
        return $this;
    }

    public function removeEmailsCandidat(Email $email): self
    {
        if ($this->emailsCandidat->removeElement($email)) {
            if ($email->getUtilisateurSender() === $this) $email->setUtilisateurSender(null);
        }
        return $this;
    }

    // -------------------------------------------------------------------------
    // CONDIDATS
    // -------------------------------------------------------------------------

    public function getCondidats(): Collection
    {
        if (!$this->condidats instanceof Collection) $this->condidats = new ArrayCollection();
        return $this->condidats;
    }

    public function addCondidat(Condidat $condidat): self
    {
        if (!$this->getCondidats()->contains($condidat)) $this->getCondidats()->add($condidat);
        return $this;
    }

    public function removeCondidat(Condidat $condidat): self
    {
        $this->getCondidats()->removeElement($condidat);
        return $this;
    }

    // -------------------------------------------------------------------------
    // EMPLOYEES
    // -------------------------------------------------------------------------

    public function getEmployees(): Collection
    {
        if (!$this->employees instanceof Collection) $this->employees = new ArrayCollection();
        return $this->employees;
    }

    public function addEmployee(Employee $employee): self
    {
        if (!$this->getEmployees()->contains($employee)) $this->getEmployees()->add($employee);
        return $this;
    }

    public function removeEmployee(Employee $employee): self
    {
        $this->getEmployees()->removeElement($employee);
        return $this;
    }

    // -------------------------------------------------------------------------
    // ENTRETIENS
    // -------------------------------------------------------------------------

    public function getEntretiens(): Collection
    {
        if (!$this->entretiens instanceof Collection) $this->entretiens = new ArrayCollection();
        return $this->entretiens;
    }

    public function addEntretien(Entretien $entretien): self
    {
        if (!$this->getEntretiens()->contains($entretien)) $this->getEntretiens()->add($entretien);
        return $this;
    }

    public function removeEntretien(Entretien $entretien): self
    {
        $this->getEntretiens()->removeElement($entretien);
        return $this;
    }

    // -------------------------------------------------------------------------
    // MESSAGES
    // -------------------------------------------------------------------------

    public function getMessages(): Collection
    {
        if (!$this->messages instanceof Collection) $this->messages = new ArrayCollection();
        return $this->messages;
    }

    public function addMessage(Message $message): self
    {
        if (!$this->getMessages()->contains($message)) $this->getMessages()->add($message);
        return $this;
    }

    public function removeMessage(Message $message): self
    {
        $this->getMessages()->removeElement($message);
        return $this;
    }

    // -------------------------------------------------------------------------
    // PARTICIPATION EVENEMENTS
    // -------------------------------------------------------------------------

    public function getParticipationEvenements(): Collection
    {
        if (!$this->participationEvenements instanceof Collection) $this->participationEvenements = new ArrayCollection();
        return $this->participationEvenements;
    }

    public function addParticipationEvenement(ParticipationEvenement $p): self
    {
        if (!$this->getParticipationEvenements()->contains($p)) $this->getParticipationEvenements()->add($p);
        return $this;
    }

    public function removeParticipationEvenement(ParticipationEvenement $p): self
    {
        $this->getParticipationEvenements()->removeElement($p);
        return $this;
    }

    // -------------------------------------------------------------------------
    // ORDRES
    // -------------------------------------------------------------------------

    public function getOrdres(): Collection
    {
        if (!$this->ordres instanceof Collection) $this->ordres = new ArrayCollection();
        return $this->ordres;
    }

    public function addOrdre(Ordre $ordre): self
    {
        if (!$this->getOrdres()->contains($ordre)) $this->getOrdres()->add($ordre);
        return $this;
    }

    public function removeOrdre(Ordre $ordre): self
    {
        $this->getOrdres()->removeElement($ordre);
        return $this;
    }
}
