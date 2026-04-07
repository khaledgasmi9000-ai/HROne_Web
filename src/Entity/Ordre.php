<?php

namespace App\Entity;

use App\Repository\OrdreRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: OrdreRepository::class)]
#[ORM\Table(name: 'ordre')]
class Ordre
{

    private const BASE_DATE = '2020-01-01 00:00:00';

    /**
     * Current time → integer (seconds since BASE_DATE)
     */
    public static function GetNumOrdreNow(): int
    {
        return self::dateToNumOrdre(new \DateTime());
    }

    /**
     * Convert DateTime → integer
     */
    public static function dateToNumOrdre(\DateTime $date): int
    {
        $base = new \DateTime(self::BASE_DATE);

        return $date->getTimestamp() - $base->getTimestamp();
    }

    /**
     * Convert integer → DateTime
     */
    public static function numOrdreToDate(int $numOrdre): \DateTime
    {
        $base = new \DateTime(self::BASE_DATE);

        $date = clone $base;
        $date->modify("+{$numOrdre} seconds");

        return $date;
    }

    #[ORM\Id]
    #[ORM\Column(name: 'Num_Ordre', type: 'integer')]
    private ?int $Num_Ordre = null;

    #[ORM\Column(name: 'AAAA', type: 'integer', nullable: false)]
    private ?int $AAAA = null;

    #[ORM\Column(name: 'MM', type: 'integer', nullable: false)]
    private ?int $MM = null;

    #[ORM\Column(name: 'JJ', type: 'integer', nullable: false)]
    private ?int $JJ = null;

    #[ORM\Column(name: 'HH', type: 'integer', nullable: false)]
    private ?int $HH = null;

    #[ORM\Column(name: 'MN', type: 'integer', nullable: false)]
    private ?int $MN = null;

    #[ORM\Column(name: 'SS', type: 'integer', nullable: false)]
    private ?int $SS = null;

    #[ORM\OneToMany(targetEntity: DemandeConge::class, mappedBy: 'ordreDebut')]
    private Collection $demandeCongesDebut;

    #[ORM\OneToMany(targetEntity: DemandeConge::class, mappedBy: 'ordreFin')]
    private Collection $demandeCongesFin;

    #[ORM\OneToMany(targetEntity: Email::class, mappedBy: 'ordre')]
    private Collection $emails;

    #[ORM\OneToMany(targetEntity: Entretien::class, mappedBy: 'ordre')]
    private Collection $entretiens;

    #[ORM\OneToMany(targetEntity: Evenement::class, mappedBy: 'ordreDebut')]
    private Collection $evenementsDebut;

    #[ORM\OneToMany(targetEntity: Evenement::class, mappedBy: 'ordreFin')]
    private Collection $evenementsFin;

    #[ORM\OneToMany(targetEntity: Evenement::class, mappedBy: 'ordreCreation')]
    private Collection $evenementsCreation;

    #[ORM\OneToMany(targetEntity: Offre::class, mappedBy: 'ordreCreation')]
    private Collection $offresCreation;

    #[ORM\OneToMany(targetEntity: Offre::class, mappedBy: 'ordreExpiration')]
    private Collection $offresExpiration;

    #[ORM\OneToMany(targetEntity: ParticipationEvenement::class, mappedBy: 'ordre')]
    private Collection $participationEvenements;

    #[ORM\OneToMany(targetEntity: Utilisateur::class, mappedBy: 'ordre')]
    private Collection $utilisateurs;

    #[ORM\ManyToMany(targetEntity: TypeAction::class, inversedBy: 'ordres')]
    #[ORM\JoinTable(
        name: 'action_utilisateur',
        joinColumns: [new ORM\JoinColumn(name: 'Num_Ordre', referencedColumnName: 'Num_Ordre')],
        inverseJoinColumns: [new ORM\JoinColumn(name: 'Code_Type_Action', referencedColumnName: 'Code_Type_Action')]
    )]
    private Collection $typeActions;

    public function __construct()
    {
        $this->demandeCongesDebut = new ArrayCollection();
        $this->demandeCongesFin = new ArrayCollection();
        $this->emails = new ArrayCollection();
        $this->entretiens = new ArrayCollection();
        $this->evenementsDebut = new ArrayCollection();
        $this->evenementsFin = new ArrayCollection();
        $this->evenementsCreation = new ArrayCollection();
        $this->offresCreation = new ArrayCollection();
        $this->offresExpiration = new ArrayCollection();
        $this->participationEvenements = new ArrayCollection();
        $this->utilisateurs = new ArrayCollection();
        $this->typeActions = new ArrayCollection();
    }

    public function getNum_Ordre(): ?int
    {
        return $this->Num_Ordre;
    }

    public function setNum_Ordre(int $Num_Ordre): self
    {
        $this->Num_Ordre = $Num_Ordre;

        return $this;
    }

    public function getAAAA(): ?int
    {
        return $this->AAAA;
    }

    public function setAAAA(int $AAAA): self
    {
        $this->AAAA = $AAAA;

        return $this;
    }

    public function getMM(): ?int
    {
        return $this->MM;
    }

    public function setMM(int $MM): self
    {
        $this->MM = $MM;

        return $this;
    }

    public function getJJ(): ?int
    {
        return $this->JJ;
    }

    public function setJJ(int $JJ): self
    {
        $this->JJ = $JJ;

        return $this;
    }

    public function getHH(): ?int
    {
        return $this->HH;
    }

    public function setHH(int $HH): self
    {
        $this->HH = $HH;

        return $this;
    }

    public function getMN(): ?int
    {
        return $this->MN;
    }

    public function setMN(int $MN): self
    {
        $this->MN = $MN;

        return $this;
    }

    public function getSS(): ?int
    {
        return $this->SS;
    }

    public function setSS(int $SS): self
    {
        $this->SS = $SS;

        return $this;
    }

    /**
     * @return Collection<int, DemandeConge>
     */
    public function getDemandeCongesDebut(): Collection
    {
        return $this->demandeCongesDebut;
    }

    public function addDemandeCongesDebut(DemandeConge $demandeCongesDebut): static
    {
        if (!$this->demandeCongesDebut->contains($demandeCongesDebut)) {
            $this->demandeCongesDebut->add($demandeCongesDebut);
            $demandeCongesDebut->setOrdreDebut($this);
        }

        return $this;
    }

    public function removeDemandeCongesDebut(DemandeConge $demandeCongesDebut): static
    {
        if ($this->demandeCongesDebut->removeElement($demandeCongesDebut) && $demandeCongesDebut->getOrdreDebut() === $this) {
            $demandeCongesDebut->setOrdreDebut(null);
        }

        return $this;
    }

    /**
     * @return Collection<int, DemandeConge>
     */
    public function getDemandeCongesFin(): Collection
    {
        return $this->demandeCongesFin;
    }

    public function addDemandeCongesFin(DemandeConge $demandeCongesFin): static
    {
        if (!$this->demandeCongesFin->contains($demandeCongesFin)) {
            $this->demandeCongesFin->add($demandeCongesFin);
            $demandeCongesFin->setOrdreFin($this);
        }

        return $this;
    }

    public function removeDemandeCongesFin(DemandeConge $demandeCongesFin): static
    {
        if ($this->demandeCongesFin->removeElement($demandeCongesFin) && $demandeCongesFin->getOrdreFin() === $this) {
            $demandeCongesFin->setOrdreFin(null);
        }

        return $this;
    }

    /**
     * @return Collection<int, Email>
     */
    public function getEmails(): Collection
    {
        return $this->emails;
    }

    public function addEmail(Email $email): self
    {
        if (!$this->emails->contains($email)) {
            $this->emails->add($email);
            $email->setOrdre($this);
        }

        return $this;
    }

    public function removeEmail(Email $email): self
    {
        if ($this->emails->removeElement($email) && $email->getOrdre() === $this) {
            $email->setOrdre(null);
        }

        return $this;
    }

    /**
     * @return Collection<int, Entretien>
     */
    public function getEntretiens(): Collection
    {
        return $this->entretiens;
    }

    public function addEntretien(Entretien $entretien): self
    {
        if (!$this->entretiens->contains($entretien)) {
            $this->entretiens->add($entretien);
            $entretien->setOrdre($this);
        }

        return $this;
    }

    public function removeEntretien(Entretien $entretien): self
    {
        if ($this->entretiens->removeElement($entretien) && $entretien->getOrdre() === $this) {
            $entretien->setOrdre(null);
        }

        return $this;
    }

    /**
     * @return Collection<int, Evenement>
     */
    public function getEvenementsDebut(): Collection
    {
        return $this->evenementsDebut;
    }

    public function addEvenementsDebut(Evenement $evenementsDebut): static
    {
        if (!$this->evenementsDebut->contains($evenementsDebut)) {
            $this->evenementsDebut->add($evenementsDebut);
            $evenementsDebut->setOrdreDebut($this);
        }

        return $this;
    }

    public function removeEvenementsDebut(Evenement $evenementsDebut): static
    {
        if ($this->evenementsDebut->removeElement($evenementsDebut) && $evenementsDebut->getOrdreDebut() === $this) {
            $evenementsDebut->setOrdreDebut(null);
        }

        return $this;
    }

    /**
     * @return Collection<int, Evenement>
     */
    public function getEvenementsFin(): Collection
    {
        return $this->evenementsFin;
    }

    public function addEvenementsFin(Evenement $evenementsFin): static
    {
        if (!$this->evenementsFin->contains($evenementsFin)) {
            $this->evenementsFin->add($evenementsFin);
            $evenementsFin->setOrdreFin($this);
        }

        return $this;
    }

    public function removeEvenementsFin(Evenement $evenementsFin): static
    {
        if ($this->evenementsFin->removeElement($evenementsFin) && $evenementsFin->getOrdreFin() === $this) {
            $evenementsFin->setOrdreFin(null);
        }

        return $this;
    }

    /**
     * @return Collection<int, Evenement>
     */
    public function getEvenementsCreation(): Collection
    {
        return $this->evenementsCreation;
    }

    public function addEvenementsCreation(Evenement $evenementsCreation): static
    {
        if (!$this->evenementsCreation->contains($evenementsCreation)) {
            $this->evenementsCreation->add($evenementsCreation);
            $evenementsCreation->setOrdreCreation($this);
        }

        return $this;
    }

    public function removeEvenementsCreation(Evenement $evenementsCreation): static
    {
        if ($this->evenementsCreation->removeElement($evenementsCreation) && $evenementsCreation->getOrdreCreation() === $this) {
            $evenementsCreation->setOrdreCreation(null);
        }

        return $this;
    }

    /**
     * @return Collection<int, Offre>
     */
    public function getOffresCreation(): Collection
    {
        return $this->offresCreation;
    }

    public function addOffresCreation(Offre $offresCreation): static
    {
        if (!$this->offresCreation->contains($offresCreation)) {
            $this->offresCreation->add($offresCreation);
            $offresCreation->setOrdreCreation($this);
        }

        return $this;
    }

    public function removeOffresCreation(Offre $offresCreation): static
    {
        if ($this->offresCreation->removeElement($offresCreation) && $offresCreation->getOrdreCreation() === $this) {
            $offresCreation->setOrdreCreation(null);
        }

        return $this;
    }

    /**
     * @return Collection<int, Offre>
     */
    public function getOffresExpiration(): Collection
    {
        return $this->offresExpiration;
    }

    public function addOffresExpiration(Offre $offresExpiration): static
    {
        if (!$this->offresExpiration->contains($offresExpiration)) {
            $this->offresExpiration->add($offresExpiration);
            $offresExpiration->setOrdreExpiration($this);
        }

        return $this;
    }

    public function removeOffresExpiration(Offre $offresExpiration): static
    {
        if ($this->offresExpiration->removeElement($offresExpiration) && $offresExpiration->getOrdreExpiration() === $this) {
            $offresExpiration->setOrdreExpiration(null);
        }

        return $this;
    }

    /**
     * @return Collection<int, ParticipationEvenement>
     */
    public function getParticipationEvenements(): Collection
    {
        return $this->participationEvenements;
    }

    public function addParticipationEvenement(ParticipationEvenement $participationEvenement): self
    {
        if (!$this->participationEvenements->contains($participationEvenement)) {
            $this->participationEvenements->add($participationEvenement);
            $participationEvenement->setOrdre($this);
        }

        return $this;
    }

    public function removeParticipationEvenement(ParticipationEvenement $participationEvenement): self
    {
        if ($this->participationEvenements->removeElement($participationEvenement) && $participationEvenement->getOrdre() === $this) {
            $participationEvenement->setOrdre(null);
        }

        return $this;
    }

    /**
     * @return Collection<int, Utilisateur>
     */
    public function getUtilisateurs(): Collection
    {
        return $this->utilisateurs;
    }

    public function addUtilisateur(Utilisateur $utilisateur): self
    {
        if (!$this->utilisateurs->contains($utilisateur)) {
            $this->utilisateurs->add($utilisateur);
            $utilisateur->setOrdre($this);
        }

        return $this;
    }

    public function removeUtilisateur(Utilisateur $utilisateur): self
    {
        if ($this->utilisateurs->removeElement($utilisateur) && $utilisateur->getOrdre() === $this) {
            $utilisateur->setOrdre(null);
        }

        return $this;
    }

    /**
     * @return Collection<int, TypeAction>
     */
    public function getTypeActions(): Collection
    {
        return $this->typeActions;
    }

    public function addTypeAction(TypeAction $typeAction): self
    {
        if (!$this->typeActions->contains($typeAction)) {
            $this->typeActions->add($typeAction);
        }

        return $this;
    }

    public function removeTypeAction(TypeAction $typeAction): self
    {
        $this->typeActions->removeElement($typeAction);

        return $this;
    }

    public function getNumOrdre(): ?int
    {
        return $this->Num_Ordre;
    }
}
