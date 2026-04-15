<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use App\Repository\OrdreRepository;

#[ORM\Entity(repositoryClass: OrdreRepository::class)]
#[ORM\Table(name: 'ordre')]
class Ordre
{
    #[ORM\Id]
    #[ORM\Column(name: 'Num_Ordre', type: 'integer')]
    private ?int $Num_Ordre = null;

    public function getNum_Ordre(): ?int
    {
        return $this->Num_Ordre;
    }

    public function setNum_Ordre(int $Num_Ordre): self
    {
        $this->Num_Ordre = $Num_Ordre;
        return $this;
    }

    #[ORM\Column(type: 'integer', nullable: false)]
    private ?int $AAAA = null;

    public function getAAAA(): ?int
    {
        return $this->AAAA;
    }

    public function setAAAA(int $AAAA): self
    {
        $this->AAAA = $AAAA;
        return $this;
    }

    #[ORM\Column(type: 'integer', nullable: false)]
    private ?int $MM = null;

    public function getMM(): ?int
    {
        return $this->MM;
    }

    public function setMM(int $MM): self
    {
        $this->MM = $MM;
        return $this;
    }

    #[ORM\Column(type: 'integer', nullable: false)]
    private ?int $JJ = null;

    public function getJJ(): ?int
    {
        return $this->JJ;
    }

    public function setJJ(int $JJ): self
    {
        $this->JJ = $JJ;
        return $this;
    }

    #[ORM\Column(type: 'integer', nullable: false)]
    private ?int $HH = null;

    public function getHH(): ?int
    {
        return $this->HH;
    }

    public function setHH(int $HH): self
    {
        $this->HH = $HH;
        return $this;
    }

    #[ORM\Column(type: 'integer', nullable: false)]
    private ?int $MN = null;

    public function getMN(): ?int
    {
        return $this->MN;
    }

    public function setMN(int $MN): self
    {
        $this->MN = $MN;
        return $this;
    }

    #[ORM\Column(type: 'integer', nullable: false)]
    private ?int $SS = null;

    public function getSS(): ?int
    {
        return $this->SS;
    }

    public function setSS(int $SS): self
    {
        $this->SS = $SS;
        return $this;
    }

    #[ORM\OneToMany(targetEntity: DemandeConge::class, mappedBy: 'ordre')]
    private Collection $demandeCongesDebut;

    /**
     * @return Collection<int, DemandeConge>
     */
    public function getDemandeCongesDebut(): Collection
    {
        if (!$this->demandeCongesDebut instanceof Collection) {
            $this->demandeCongesDebut = new ArrayCollection();
        }
        return $this->demandeCongesDebut;
    }

    public function addDemandeCongeDebut(DemandeConge $demandeConge): self
    {
        if (!$this->getDemandeCongesDebut()->contains($demandeConge)) {
            $this->getDemandeCongesDebut()->add($demandeConge);
        }
        return $this;
    }

    public function removeDemandeCongeDebut(DemandeConge $demandeConge): self
    {
        $this->getDemandeCongesDebut()->removeElement($demandeConge);
        return $this;
    }

    #[ORM\OneToMany(targetEntity: DemandeConge::class, mappedBy: 'ordre')]
    private Collection $demandeCongesFin;

    /**
     * @return Collection<int, DemandeConge>
     */
    public function getDemandeCongesFin(): Collection
    {
        if (!$this->demandeCongesFin instanceof Collection) {
            $this->demandeCongesFin = new ArrayCollection();
        }
        return $this->demandeCongesFin;
    }

    public function addDemandeCongeFin(DemandeConge $demandeConge): self
    {
        if (!$this->getDemandeCongesFin()->contains($demandeConge)) {
            $this->getDemandeCongesFin()->add($demandeConge);
        }
        return $this;
    }

    public function removeDemandeCongeFin(DemandeConge $demandeConge): self
    {
        $this->getDemandeCongesFin()->removeElement($demandeConge);
        return $this;
    }

    #[ORM\OneToMany(targetEntity: Email::class, mappedBy: 'ordre')]
    private Collection $emails;

    /**
     * @return Collection<int, Email>
     */
    public function getEmails(): Collection
    {
        if (!$this->emails instanceof Collection) {
            $this->emails = new ArrayCollection();
        }
        return $this->emails;
    }

    public function addEmail(Email $email): self
    {
        if (!$this->getEmails()->contains($email)) {
            $this->getEmails()->add($email);
        }
        return $this;
    }

    public function removeEmail(Email $email): self
    {
        $this->getEmails()->removeElement($email);
        return $this;
    }

    #[ORM\OneToMany(targetEntity: Entretien::class, mappedBy: 'ordre')]
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

    #[ORM\OneToMany(targetEntity: Evenement::class, mappedBy: 'ordre')]
    private Collection $evenementsDebut;

    /**
     * @return Collection<int, Evenement>
     */
    public function getEvenementsDebut(): Collection
    {
        if (!$this->evenementsDebut instanceof Collection) {
            $this->evenementsDebut = new ArrayCollection();
        }
        return $this->evenementsDebut;
    }

    public function addEvenementDebut(Evenement $evenement): self
    {
        if (!$this->getEvenementsDebut()->contains($evenement)) {
            $this->getEvenementsDebut()->add($evenement);
        }
        return $this;
    }

    public function removeEvenementDebut(Evenement $evenement): self
    {
        $this->getEvenementsDebut()->removeElement($evenement);
        return $this;
    }

    #[ORM\OneToMany(targetEntity: Evenement::class, mappedBy: 'ordre')]
    private Collection $evenementsFin;

    /**
     * @return Collection<int, Evenement>
     */
    public function getEvenementsFin(): Collection
    {
        if (!$this->evenementsFin instanceof Collection) {
            $this->evenementsFin = new ArrayCollection();
        }
        return $this->evenementsFin;
    }

    public function addEvenementFin(Evenement $evenement): self
    {
        if (!$this->getEvenementsFin()->contains($evenement)) {
            $this->getEvenementsFin()->add($evenement);
        }
        return $this;
    }

    public function removeEvenementFin(Evenement $evenement): self
    {
        $this->getEvenementsFin()->removeElement($evenement);
        return $this;
    }

    #[ORM\OneToMany(targetEntity: Evenement::class, mappedBy: 'ordre')]
    private Collection $evenementsCreation;

    /**
     * @return Collection<int, Evenement>
     */
    public function getEvenementsCreation(): Collection
    {
        if (!$this->evenementsCreation instanceof Collection) {
            $this->evenementsCreation = new ArrayCollection();
        }
        return $this->evenementsCreation;
    }

    public function addEvenementCreation(Evenement $evenement): self
    {
        if (!$this->getEvenementsCreation()->contains($evenement)) {
            $this->getEvenementsCreation()->add($evenement);
        }
        return $this;
    }

    public function removeEvenementCreation(Evenement $evenement): self
    {
        $this->getEvenementsCreation()->removeElement($evenement);
        return $this;
    }

    #[ORM\OneToMany(targetEntity: Offre::class, mappedBy: 'ordre')]
    private Collection $offresCreation;

    /**
     * @return Collection<int, Offre>
     */
    public function getOffresCreation(): Collection
    {
        if (!$this->offresCreation instanceof Collection) {
            $this->offresCreation = new ArrayCollection();
        }
        return $this->offresCreation;
    }

    public function addOffreCreation(Offre $offre): self
    {
        if (!$this->getOffresCreation()->contains($offre)) {
            $this->getOffresCreation()->add($offre);
        }
        return $this;
    }

    public function removeOffreCreation(Offre $offre): self
    {
        $this->getOffresCreation()->removeElement($offre);
        return $this;
    }

    #[ORM\OneToMany(targetEntity: Offre::class, mappedBy: 'ordre')]
    private Collection $offresExpiration;

    /**
     * @return Collection<int, Offre>
     */
    public function getOffresExpiration(): Collection
    {
        if (!$this->offresExpiration instanceof Collection) {
            $this->offresExpiration = new ArrayCollection();
        }
        return $this->offresExpiration;
    }

    public function addOffreExpiration(Offre $offre): self
    {
        if (!$this->getOffresExpiration()->contains($offre)) {
            $this->getOffresExpiration()->add($offre);
        }
        return $this;
    }

    public function removeOffreExpiration(Offre $offre): self
    {
        $this->getOffresExpiration()->removeElement($offre);
        return $this;
    }

    #[ORM\OneToMany(targetEntity: ParticipationEvenement::class, mappedBy: 'ordre')]
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

    #[ORM\OneToMany(targetEntity: Utilisateur::class, mappedBy: 'ordre')]
    private Collection $utilisateurs;

    /**
     * @return Collection<int, Utilisateur>
     */
    public function getUtilisateurs(): Collection
    {
        if (!$this->utilisateurs instanceof Collection) {
            $this->utilisateurs = new ArrayCollection();
        }
        return $this->utilisateurs;
    }

    public function addUtilisateur(Utilisateur $utilisateur): self
    {
        if (!$this->getUtilisateurs()->contains($utilisateur)) {
            $this->getUtilisateurs()->add($utilisateur);
        }
        return $this;
    }

    public function removeUtilisateur(Utilisateur $utilisateur): self
    {
        $this->getUtilisateurs()->removeElement($utilisateur);
        return $this;
    }

    #[ORM\ManyToMany(targetEntity: TypeAction::class, inversedBy: 'ordres')]
    #[ORM\JoinTable(
        name: 'action_utilisateur',
        joinColumns: [
            new ORM\JoinColumn(name: 'Num_Ordre', referencedColumnName: 'Num_Ordre')
        ],
        inverseJoinColumns: [
            new ORM\JoinColumn(name: 'Code_Type_Action', referencedColumnName: 'Code_Type_Action')
        ]
    )]
    private Collection $typeActions;

    /**
     * @return Collection<int, TypeAction>
     */
    public function getTypeActions(): Collection
    {
        if (!$this->typeActions instanceof Collection) {
            $this->typeActions = new ArrayCollection();
        }
        return $this->typeActions;
    }

    public function addTypeAction(TypeAction $typeAction): self
    {
        if (!$this->getTypeActions()->contains($typeAction)) {
            $this->getTypeActions()->add($typeAction);
        }
        return $this;
    }

    public function removeTypeAction(TypeAction $typeAction): self
    {
        $this->getTypeActions()->removeElement($typeAction);
        return $this;
    }

    #[ORM\ManyToMany(targetEntity: Activite::class, inversedBy: 'ordres')]
    #[ORM\JoinTable(
        name: 'detail_evenement',
        joinColumns: [
            new ORM\JoinColumn(name: 'Num_Ordre_Debut_Activite', referencedColumnName: 'Num_Ordre')
        ],
        inverseJoinColumns: [
            new ORM\JoinColumn(name: 'ID_Activite', referencedColumnName: 'ID_Activite')
        ]
    )]
    private Collection $activites;

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
        $this->activites = new ArrayCollection();
    }

    /**
     * @return Collection<int, Activite>
     */
    public function getActivites(): Collection
    {
        if (!$this->activites instanceof Collection) {
            $this->activites = new ArrayCollection();
        }
        return $this->activites;
    }

    public function addActivite(Activite $activite): self
    {
        if (!$this->getActivites()->contains($activite)) {
            $this->getActivites()->add($activite);
        }
        return $this;
    }

    public function removeActivite(Activite $activite): self
    {
        $this->getActivites()->removeElement($activite);
        return $this;
    }

    public function getNumOrdre(): ?int
    {
        return $this->Num_Ordre;
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
        if ($this->demandeCongesDebut->removeElement($demandeCongesDebut)) {
            // set the owning side to null (unless already changed)
            if ($demandeCongesDebut->getOrdreDebut() === $this) {
                $demandeCongesDebut->setOrdreDebut(null);
            }
        }

        return $this;
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
        if ($this->demandeCongesFin->removeElement($demandeCongesFin)) {
            // set the owning side to null (unless already changed)
            if ($demandeCongesFin->getOrdreFin() === $this) {
                $demandeCongesFin->setOrdreFin(null);
            }
        }

        return $this;
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
        if ($this->evenementsDebut->removeElement($evenementsDebut)) {
            // set the owning side to null (unless already changed)
            if ($evenementsDebut->getOrdreDebut() === $this) {
                $evenementsDebut->setOrdreDebut(null);
            }
        }

        return $this;
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
        if ($this->evenementsFin->removeElement($evenementsFin)) {
            // set the owning side to null (unless already changed)
            if ($evenementsFin->getOrdreFin() === $this) {
                $evenementsFin->setOrdreFin(null);
            }
        }

        return $this;
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
        if ($this->evenementsCreation->removeElement($evenementsCreation)) {
            // set the owning side to null (unless already changed)
            if ($evenementsCreation->getOrdreCreation() === $this) {
                $evenementsCreation->setOrdreCreation(null);
            }
        }

        return $this;
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
        if ($this->offresCreation->removeElement($offresCreation)) {
            // set the owning side to null (unless already changed)
            if ($offresCreation->getOrdreCreation() === $this) {
                $offresCreation->setOrdreCreation(null);
            }
        }

        return $this;
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
        if ($this->offresExpiration->removeElement($offresExpiration)) {
            // set the owning side to null (unless already changed)
            if ($offresExpiration->getOrdreExpiration() === $this) {
                $offresExpiration->setOrdreExpiration(null);
            }
        }

        return $this;
    }

}
