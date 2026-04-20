<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

use App\Repository\UtilisateurRepository;

#[ORM\Entity(repositoryClass: UtilisateurRepository::class)]
#[ORM\Table(name: 'utilisateur')]
class Utilisateur implements UserInterface, PasswordAuthenticatedUserInterface
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

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $Mot_Passe = null;

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

    #[ORM\ManyToOne(targetEntity: Ordre::class, inversedBy: 'utilisateurs', cascade: ['persist'])]
    #[ORM\JoinColumn(name: 'Num_Ordre_Sign_In', referencedColumnName: 'Num_Ordre')]
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

    #[ORM\Column(name: 'firstLogin', type: 'integer', nullable: true)]
    private ?int $firstLogin = null;

    #[ORM\Column(name: 'first_login', type: 'integer', nullable: true)]
    private ?int $first_login = null;

    #[ORM\Column(name: 'is_active', type: 'boolean', options: ['default' => true])]
    private bool $isActive = true;

    public function getFirstLogin(): ?int
    {
        return $this->firstLogin;
    }

    public function setFirstLogin(?int $firstLogin): self
    {
        $this->firstLogin = $firstLogin;
        return $this;
    }

    public function getFirst_login(): ?int
    {
        return $this->first_login;
    }

    public function setFirst_login(?int $first_login): self
    {
        $this->first_login = $first_login;
        return $this;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): self
    {
        $this->isActive = $isActive;

        return $this;
    }

    #[ORM\OneToMany(targetEntity: Condidat::class, mappedBy: 'utilisateur')]
    private Collection $condidats;

    /**
     * @return Collection<int, Condidat>
     */
    public function getCondidats(): Collection
    {
        if (!$this->condidats instanceof Collection) {
            $this->condidats = new ArrayCollection();
        }
        return $this->condidats;
    }

    public function addCondidat(Condidat $condidat): self
    {
        if (!$this->getCondidats()->contains($condidat)) {
            $this->getCondidats()->add($condidat);
        }
        return $this;
    }

    public function removeCondidat(Condidat $condidat): self
    {
        $this->getCondidats()->removeElement($condidat);
        return $this;
    }

    #[ORM\OneToMany(targetEntity: Email::class, mappedBy: 'utilisateurReceiver')]
    private Collection $emailsEmployee;

    /**
     * @return Collection<int, Email>
     */
    public function getEmailsEmployee(): Collection
    {
        if (!$this->emailsEmployee instanceof Collection) {
            $this->emailsEmployee = new ArrayCollection();
        }
        return $this->emailsEmployee;
    }

    public function addEmailEmployee(Email $email): self
    {
        if (!$this->getEmailsEmployee()->contains($email)) {
            $this->getEmailsEmployee()->add($email);
        }
        return $this;
    }

    public function removeEmailEmployee(Email $email): self
    {
        $this->getEmailsEmployee()->removeElement($email);
        return $this;
    }

    #[ORM\OneToMany(targetEntity: Email::class, mappedBy: 'utilisateurSender')]
    private Collection $emailsCandidat;

    /**
     * @return Collection<int, Email>
     */
    public function getEmailsCandidat(): Collection
    {
        if (!$this->emailsCandidat instanceof Collection) {
            $this->emailsCandidat = new ArrayCollection();
        }
        return $this->emailsCandidat;
    }

    public function addEmailCandidat(Email $email): self
    {
        if (!$this->getEmailsCandidat()->contains($email)) {
            $this->getEmailsCandidat()->add($email);
        }
        return $this;
    }

    public function removeEmailCandidat(Email $email): self
    {
        $this->getEmailsCandidat()->removeElement($email);
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

    #[ORM\OneToMany(targetEntity: Entretien::class, mappedBy: 'utilisateur')]
    private Collection $entretiens;

    /**
     * @return Collection<int, Entretien>
     */
    public function getEntretiens(): Collection
    {
        if (!$this->entretiens instanceof Collection) {
            $this->entretiens = new ArrayCollection();
        }
        return $this->entretiens;
    }

    public function addEntretien(Entretien $entretien): self
    {
        if (!$this->getEntretiens()->contains($entretien)) {
            $this->getEntretiens()->add($entretien);
        }
        return $this;
    }

    public function removeEntretien(Entretien $entretien): self
    {
        $this->getEntretiens()->removeElement($entretien);
        return $this;
    }

    #[ORM\OneToMany(targetEntity: Message::class, mappedBy: 'utilisateur')]
    private Collection $messages;

    /**
     * @return Collection<int, Message>
     */
    public function getMessages(): Collection
    {
        if (!$this->messages instanceof Collection) {
            $this->messages = new ArrayCollection();
        }
        return $this->messages;
    }

    public function addMessage(Message $message): self
    {
        if (!$this->getMessages()->contains($message)) {
            $this->getMessages()->add($message);
        }
        return $this;
    }

    public function removeMessage(Message $message): self
    {
        $this->getMessages()->removeElement($message);
        return $this;
    }

    #[ORM\OneToMany(targetEntity: ParticipationEvenement::class, mappedBy: 'utilisateur')]
    private Collection $participationEvenements;

    /**
     * @return Collection<int, ParticipationEvenement>
     */
    public function getParticipationEvenements(): Collection
    {
        if (!$this->participationEvenements instanceof Collection) {
            $this->participationEvenements = new ArrayCollection();
        }
        return $this->participationEvenements;
    }

    public function addParticipationEvenement(ParticipationEvenement $participationEvenement): self
    {
        if (!$this->getParticipationEvenements()->contains($participationEvenement)) {
            $this->getParticipationEvenements()->add($participationEvenement);
        }
        return $this;
    }

    public function removeParticipationEvenement(ParticipationEvenement $participationEvenement): self
    {
        $this->getParticipationEvenements()->removeElement($participationEvenement);
        return $this;
    }

    private Collection $ordres;

    public function __construct()
    {
        $this->condidats = new ArrayCollection();
        $this->emailsEmployee = new ArrayCollection();
        $this->emailsCandidat = new ArrayCollection();
        $this->employees = new ArrayCollection();
        $this->entretiens = new ArrayCollection();
        $this->messages = new ArrayCollection();
        $this->participationEvenements = new ArrayCollection();
        $this->ordres = new ArrayCollection();
    }

    /**
     * @return Collection<int, Ordre>
     */
    public function getOrdres(): Collection
    {
        if (!$this->ordres instanceof Collection) {
            $this->ordres = new ArrayCollection();
        }
        return $this->ordres;
    }

    public function addOrdre(Ordre $ordre): self
    {
        if (!$this->getOrdres()->contains($ordre)) {
            $this->getOrdres()->add($ordre);
        }
        return $this;
    }

    public function removeOrdre(Ordre $ordre): self
    {
        $this->getOrdres()->removeElement($ordre);
        return $this;
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

    public function addEmailsEmployee(Email $emailsEmployee): static
    {
        if (!$this->emailsEmployee->contains($emailsEmployee)) {
            $this->emailsEmployee->add($emailsEmployee);
            $emailsEmployee->setUtilisateurReceiver($this);
        }

        return $this;
    }

    public function removeEmailsEmployee(Email $emailsEmployee): static
    {
        if ($this->emailsEmployee->removeElement($emailsEmployee)) {
            // set the owning side to null (unless already changed)
            if ($emailsEmployee->getUtilisateurReceiver() === $this) {
                $emailsEmployee->setUtilisateurReceiver(null);
            }
        }

        return $this;
    }

    public function addEmailsCandidat(Email $emailsCandidat): static
    {
        if (!$this->emailsCandidat->contains($emailsCandidat)) {
            $this->emailsCandidat->add($emailsCandidat);
            $emailsCandidat->setUtilisateurSender($this);
        }

        return $this;
    }

    public function removeEmailsCandidat(Email $emailsCandidat): static
    {
        if ($this->emailsCandidat->removeElement($emailsCandidat)) {
            // set the owning side to null (unless already changed)
            if ($emailsCandidat->getUtilisateurSender() === $this) {
                $emailsCandidat->setUtilisateurSender(null);
            }
        }

        return $this;
    }

    public function getUserIdentifier(): string
    {
        if ($this->Email !== null && trim($this->Email) !== '') {
            return (string) $this->Email;
        }

        if ($this->Nom_Utilisateur !== null && trim($this->Nom_Utilisateur) !== '') {
            return (string) $this->Nom_Utilisateur;
        }

        return 'user_' . (string) ($this->ID_UTILISATEUR ?? 0);
    }

    public function getRoles(): array
    {
        $profilId = $this->profil?->getIDProfil() ?? $this->profil?->getID_Profil();
        $profilName = mb_strtolower((string) ($this->profil?->getNomProfil() ?? $this->profil?->getNom_Profil() ?? ''));

        $roles = match ($profilId) {
            1 => ['ROLE_ADMIN', 'ROLE_RH'],
            2 => ['ROLE_RH'],
            3 => ['ROLE_EMPLOYEE'],
            4 => ['ROLE_CANDIDAT'],
            default => [],
        };

        if ($roles === []) {
            if (str_contains($profilName, 'admin')) {
                $roles = ['ROLE_ADMIN', 'ROLE_RH'];
            } elseif (str_contains($profilName, 'rh')) {
                $roles = ['ROLE_RH'];
            } elseif (str_contains($profilName, 'employ')) {
                $roles = ['ROLE_EMPLOYEE'];
            } elseif (str_contains($profilName, 'candidat')) {
                $roles = ['ROLE_CANDIDAT'];
            }
        }

        $roles[] = 'ROLE_USER';

        return array_values(array_unique($roles));
    }

    public function getPassword(): ?string
    {
        return $this->Mot_Passe;
    }

    public function eraseCredentials(): void
    {
    }

    public function getProfileCompletionScore(): int
    {
        $fields = [
            $this->getNomUtilisateur(),
            $this->getEmail(),
            $this->getNumTel(),
            $this->getCIN(),
            $this->getAdresse(),
            $this->getDateNaissance(),
            $this->getGender(),
        ];

        $filled = 0;
        foreach ($fields as $field) {
            if ($field instanceof \DateTimeInterface) {
                ++$filled;
                continue;
            }

            if (is_string($field) && trim($field) !== '') {
                ++$filled;
            }
        }

        return (int) round(($filled / max(1, count($fields))) * 100);
    }

    public function isProfileComplete(): bool
    {
        return $this->getProfileCompletionScore() >= 85;
    }

}
