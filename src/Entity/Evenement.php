<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use App\Repository\EvenementRepository;

#[ORM\Entity(repositoryClass: EvenementRepository::class)]
#[ORM\Table(name: 'evenement')]
class Evenement
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'ID_Evenement', type: 'integer')]
    private ?int $ID_Evenement = null;

    public function getID_Evenement(): ?int
    {
        return $this->ID_Evenement;
    }

    public function setID_Evenement(int $ID_Evenement): self
    {
        $this->ID_Evenement = $ID_Evenement;
        return $this;
    }

    #[ORM\Column(name: 'Titre', type: 'string', nullable: false)]
    private ?string $Titre = null;

    public function getTitre(): ?string
    {
        return $this->Titre;
    }

    public function setTitre(string $Titre): self
    {
        $this->Titre = $Titre;
        return $this;
    }

    #[ORM\Column(name: 'Description', type: 'text', nullable: true)]
    private ?string $Description = null;

    public function getDescription(): ?string
    {
        return $this->Description;
    }

    public function setDescription(?string $Description): self
    {
        $this->Description = $Description;
        return $this;
    }

    #[ORM\ManyToOne(targetEntity: Ordre::class, inversedBy: 'evenementsCreation')]
    #[ORM\JoinColumn(name: 'Num_Ordre_Creation', referencedColumnName: 'Num_Ordre')]
    private ?Ordre $ordreCreation = null;

    public function getOrdreCreation(): ?Ordre
    {
        return $this->ordreCreation;
    }

    public function setOrdreCreation(?Ordre $ordreCreation): self
    {
        $this->ordreCreation = $ordreCreation;
        return $this;
    }

    #[ORM\ManyToOne(targetEntity: Ordre::class, inversedBy: 'evenementsDebut')]
    #[ORM\JoinColumn(name: 'Num_Ordre_Debut_Evenement', referencedColumnName: 'Num_Ordre')]
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

    #[ORM\ManyToOne(targetEntity: Ordre::class, inversedBy: 'evenementsFin')]
    #[ORM\JoinColumn(name: 'Num_Ordre_Fin_Evenement', referencedColumnName: 'Num_Ordre')]
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

    #[ORM\Column(name: 'Localisation', type: 'string', nullable: true)]
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

    #[ORM\Column(name: 'Image', type: 'string', nullable: true)]
    private ?string $Image = null;

    public function getImage(): ?string
    {
        return $this->Image;
    }

    public function setImage(?string $Image): self
    {
        $this->Image = $Image;
        return $this;
    }

    #[ORM\Column(name: 'est_payant', type: 'boolean', nullable: true)]
    private ?bool $est_payant = null;

    public function isEst_payant(): ?bool
    {
        return $this->est_payant;
    }

    public function setEst_payant(?bool $est_payant): self
    {
        $this->est_payant = $est_payant;
        return $this;
    }

    #[ORM\Column(name: 'prix', type: 'float', nullable: true)]
    private ?float $prix = null;

    public function getPrix(): ?float
    {
        return $this->prix;
    }

    public function setPrix(?float $prix): self
    {
        $this->prix = $prix;
        return $this;
    }

    #[ORM\Column(name: 'nbMax', type: 'integer', nullable: true)]
    private ?int $nbMax = null;

    public function getNbMax(): ?int
    {
        return $this->nbMax;
    }

    public function setNbMax(?int $nbMax): self
    {
        $this->nbMax = $nbMax;
        return $this;
    }

    #[ORM\OneToMany(targetEntity: ListeAttente::class, mappedBy: 'evenement')]
    private Collection $listeAttentes;

    /**
     * @return Collection<int, ListeAttente>
     */
    public function getListeAttentes(): Collection
    {
        if (!$this->listeAttentes instanceof Collection) {
            $this->listeAttentes = new ArrayCollection();
        }
        return $this->listeAttentes;
    }

    public function addListeAttente(ListeAttente $listeAttente): self
    {
        if (!$this->getListeAttentes()->contains($listeAttente)) {
            $this->getListeAttentes()->add($listeAttente);
        }
        return $this;
    }

    public function removeListeAttente(ListeAttente $listeAttente): self
    {
        $this->getListeAttentes()->removeElement($listeAttente);
        return $this;
    }

    #[ORM\OneToMany(targetEntity: ParticipationEvenement::class, mappedBy: 'evenement')]
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

    #[ORM\ManyToMany(targetEntity: Activite::class, inversedBy: 'evenements')]
    #[ORM\JoinTable(
        name: 'detail_evenement',
        joinColumns: [
            new ORM\JoinColumn(name: 'ID_Evenement', referencedColumnName: 'ID_Evenement')
        ],
        inverseJoinColumns: [
            new ORM\JoinColumn(name: 'ID_Activite', referencedColumnName: 'ID_Activite')
        ]
    )]
    private Collection $activites;

    #[ORM\OneToMany(targetEntity: DetailEvenement::class, mappedBy: 'evenement')]
    private Collection $details;

    public function __construct()
    {
        $this->listeAttentes = new ArrayCollection();
        $this->participationEvenements = new ArrayCollection();
        $this->activites = new ArrayCollection();
        $this->details = new ArrayCollection();
    }

    /**
     * @return Collection<int, DetailEvenement>
     */
    public function getDetails(): Collection
    {
        if (!$this->details instanceof Collection) {
            $this->details = new ArrayCollection();
        }
        return $this->details;
    }

    public function addDetail(DetailEvenement $detail): self
    {
        if (!$this->getDetails()->contains($detail)) {
            $this->getDetails()->add($detail);
        }
        return $this;
    }

    public function removeDetail(DetailEvenement $detail): self
    {
        $this->getDetails()->removeElement($detail);
        return $this;
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

    public function getIDEvenement(): ?int
    {
        return $this->ID_Evenement;
    }

    public function isEstPayant(): ?bool
    {
        return $this->est_payant;
    }

    public function setEstPayant(?bool $est_payant): static
    {
        $this->est_payant = $est_payant;

        return $this;
    }

    /**
     * Badge de Prix automatique
     * OFFERT: < 100 DT
     * PREMIUM: >= 100 DT
     */
    public function getBadgeLabel(): string
    {
        if ($this->prix >= 100) {
            return "PREMIUM";
        }
        return "OFFERT";
    }

    /**
     * Couleur du Badge selon le type
     */
    public function getBadgeColor(): string
    {
        if ($this->getBadgeLabel() === "PREMIUM") {
            return "#fef3c7"; // Or/Ambre
        }
        return "#dcfce7"; // Vert
    }

}
