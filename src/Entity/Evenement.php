<?php

namespace App\Entity;

use App\Repository\EvenementRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

#[ORM\Entity(repositoryClass: EvenementRepository::class)]
#[ORM\Table(name: 'evenement')]
class Evenement
{
    #[ORM\Id]
    #[ORM\Column(name: 'ID_Evenement', type: 'integer')]
    private ?int $ID_Evenement = null;

    #[ORM\Column(name: 'Titre', type: 'string', nullable: false)]
    #[Assert\NotBlank(message: "Le titre est obligatoire.")]
    #[Assert\Length(min: 3, max: 100)]
    private ?string $Titre = null;

    #[ORM\Column(name: 'Description', type: 'text', nullable: true)]
    private ?string $Description = null;

    #[ORM\ManyToOne(targetEntity: Ordre::class, inversedBy: 'evenementsCreation')]
    #[ORM\JoinColumn(name: 'Num_Ordre_Creation', referencedColumnName: 'Num_Ordre')]
    private ?Ordre $ordreCreation = null;

    #[ORM\ManyToOne(targetEntity: Ordre::class, inversedBy: 'evenementsDebut')]
    #[ORM\JoinColumn(name: 'Num_Ordre_Debut_Evenement', referencedColumnName: 'Num_Ordre')]
    private ?Ordre $ordreDebut = null;

    #[ORM\ManyToOne(targetEntity: Ordre::class, inversedBy: 'evenementsFin')]
    #[ORM\JoinColumn(name: 'Num_Ordre_Fin_Evenement', referencedColumnName: 'Num_Ordre')]
    private ?Ordre $ordreFin = null;

    #[ORM\Column(name: 'Localisation', type: 'string', nullable: true)]
    #[Assert\NotBlank(message: 'La localisation est obligatoire.')]
    private ?string $Localisation = null;

    #[ORM\Column(name: 'Image', type: 'string', nullable: true)]
    #[Assert\Url(message: "L'URL de l'image n'est pas valide.")]
    private ?string $Image = null;

    #[ORM\Column(name: 'est_payant', type: 'boolean', nullable: true)]
    private ?bool $est_payant = null;

    #[ORM\Column(name: 'prix', type: 'decimal', precision: 10, scale: 2, nullable: true)]
    #[Assert\PositiveOrZero(message: 'Le prix ne peut pas etre negatif.')]
    private ?float $prix = null;

    #[ORM\Column(name: 'nbMax', type: 'integer', nullable: true)]
    #[Assert\PositiveOrZero(message: 'Le nombre maximum de participants doit etre positif ou nul.')]
    private ?int $nbMax = null;

    #[ORM\OneToMany(targetEntity: ListeAttente::class, mappedBy: 'evenement')]
    private Collection $listeAttentes;

    #[ORM\OneToMany(targetEntity: ParticipationEvenement::class, mappedBy: 'evenement')]
    private Collection $participationEvenements;

    #[ORM\OneToMany(targetEntity: DetailEvenement::class, mappedBy: 'evenement', cascade: ['persist', 'remove'])]
    private Collection $details;

    public function __construct()
    {
        $this->listeAttentes = new ArrayCollection();
        $this->participationEvenements = new ArrayCollection();
        $this->details = new ArrayCollection();
    }

    public function getID_Evenement(): ?int
    {
        return $this->ID_Evenement;
    }

    public function setID_Evenement(int $ID_Evenement): self
    {
        $this->ID_Evenement = $ID_Evenement;

        return $this;
    }

    public function getTitre(): ?string
    {
        return $this->Titre;
    }

    public function setTitre(string $Titre): self
    {
        $this->Titre = $Titre;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->Description;
    }

    public function setDescription(?string $Description): self
    {
        $this->Description = $Description;

        return $this;
    }

    public function getOrdreCreation(): ?Ordre
    {
        return $this->ordreCreation;
    }

    public function setOrdreCreation(?Ordre $ordreCreation): self
    {
        $this->ordreCreation = $ordreCreation;

        return $this;
    }

    public function getOrdreDebut(): ?Ordre
    {
        return $this->ordreDebut;
    }

    public function setOrdreDebut(?Ordre $ordreDebut): self
    {
        $this->ordreDebut = $ordreDebut;

        return $this;
    }

    public function getOrdreFin(): ?Ordre
    {
        return $this->ordreFin;
    }

    public function setOrdreFin(?Ordre $ordreFin): self
    {
        $this->ordreFin = $ordreFin;

        return $this;
    }

    public function getLocalisation(): ?string
    {
        return $this->Localisation;
    }

    public function setLocalisation(?string $Localisation): self
    {
        $this->Localisation = $Localisation;

        return $this;
    }

    public function getImage(): ?string
    {
        return $this->Image;
    }

    public function setImage(?string $Image): self
    {
        $this->Image = $Image;

        return $this;
    }

    public function isEst_payant(): ?bool
    {
        return $this->est_payant;
    }

    public function setEst_payant(?bool $est_payant): self
    {
        $this->est_payant = $est_payant;

        return $this;
    }

    public function getPrix(): ?float
    {
        return $this->prix;
    }

    public function setPrix(?float $prix): self
    {
        $this->prix = $prix;

        return $this;
    }

    public function getNbMax(): ?int
    {
        return $this->nbMax;
    }

    public function setNbMax(?int $nbMax): self
    {
        $this->nbMax = $nbMax;

        return $this;
    }

    /**
     * @return Collection<int, ListeAttente>
     */
    public function getListeAttentes(): Collection
    {
        return $this->listeAttentes;
    }

    public function addListeAttente(ListeAttente $listeAttente): self
    {
        if (!$this->listeAttentes->contains($listeAttente)) {
            $this->listeAttentes->add($listeAttente);
            $listeAttente->setEvenement($this);
        }

        return $this;
    }

    public function removeListeAttente(ListeAttente $listeAttente): self
    {
        if ($this->listeAttentes->removeElement($listeAttente) && $listeAttente->getEvenement() === $this) {
            $listeAttente->setEvenement(null);
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
            $participationEvenement->setEvenement($this);
        }

        return $this;
    }

    public function removeParticipationEvenement(ParticipationEvenement $participationEvenement): self
    {
        if ($this->participationEvenements->removeElement($participationEvenement) && $participationEvenement->getEvenement() === $this) {
            $participationEvenement->setEvenement(null);
        }

        return $this;
    }

    /**
     * @return Collection<int, DetailEvenement>
     */
    public function getDetails(): Collection
    {
        return $this->details;
    }

    public function addDetail(DetailEvenement $detail): self
    {
        if (!$this->details->contains($detail)) {
            $this->details->add($detail);
            $detail->setEvenement($this);
        }

        return $this;
    }

    public function removeDetail(DetailEvenement $detail): self
    {
        if ($this->details->removeElement($detail) && $detail->getEvenement() === $this) {
            $detail->setEvenement(null);
        }

        return $this;
    }

    /**
     * @return Collection<int, Activite>
     */
    public function getActivites(): Collection
    {
        $activites = new ArrayCollection();

        foreach ($this->details as $detail) {
            $activite = $detail->getActivite();
            if ($activite !== null && !$activites->contains($activite)) {
                $activites->add($activite);
            }
        }

        return $activites;
    }

    public function addActivite(Activite $activite): self
    {
        foreach ($this->details as $detail) {
            if ($detail->getActivite() === $activite) {
                return $this;
            }
        }

        $detail = new DetailEvenement();
        $detail->setEvenement($this);
        $detail->setActivite($activite);
        $this->addDetail($detail);

        return $this;
    }

    public function removeActivite(Activite $activite): self
    {
        foreach ($this->details as $detail) {
            if ($detail->getActivite() === $activite) {
                $this->removeDetail($detail);
            }
        }

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

    #[Assert\Callback]
    public function validatePrice(ExecutionContextInterface $context): void
    {
        if ($this->est_payant && ($this->prix === null || $this->prix <= 0)) {
            $context->buildViolation("Le prix est obligatoire si l'evenement est payant.")
                ->atPath('prix')
                ->addViolation();
        }
    }

    public function getRealDateDebut(): \DateTime
    {
        $ordre = $this->getOrdreDebut();

        if ($ordre === null) {
            return new \DateTime();
        }

        return (new \DateTime())
            ->setDate($ordre->getAAAA(), $ordre->getMM(), $ordre->getJJ())
            ->setTime($ordre->getHH(), $ordre->getMN(), $ordre->getSS());
    }

    public function getRealDateFin(): \DateTime
    {
        $ordre = $this->getOrdreFin();

        if ($ordre === null) {
            return new \DateTime();
        }

        return (new \DateTime())
            ->setDate($ordre->getAAAA(), $ordre->getMM(), $ordre->getJJ())
            ->setTime($ordre->getHH(), $ordre->getMN(), $ordre->getSS());
    }

    public function getBadgeLabel(): string
    {
        if ($this->prix == 0 || !$this->est_payant) {
            return 'OFFERT';
        }

        if ($this->prix > 100) {
            return 'PREMIUM';
        }

        return 'STANDARD';
    }

    public function getBadgeColor(): string
    {
        if ($this->getBadgeLabel() === 'OFFERT') {
            return '#dcfce7';
        }

        if ($this->getBadgeLabel() === 'PREMIUM') {
            return '#fef3c7';
        }

        return '#f1f5f9';
    }

    public function getDurationHours(): int
    {
        if ($this->ordreDebut === null || $this->ordreFin === null) {
            return 0;
        }

        $diff = $this->getRealDateDebut()->diff($this->getRealDateFin());

        return ($diff->days * 24) + $diff->h;
    }
}
