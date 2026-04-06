<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use App\Repository\EvenementRepository;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

#[ORM\Entity(repositoryClass: EvenementRepository::class)]
#[ORM\Table(name: 'evenement')]
class Evenement
{
    // -------------------------------------------------------------
    // PROPRIÉTÉS ET RÈGLES DE VALIDATION (CONTRÔLE DE SAISIE)
    // -------------------------------------------------------------

    #[ORM\Id]
    #[ORM\Column(name: "ID_Evenement", type: 'integer')]
    private ?int $ID_Evenement = null; // L'ID est géré manuellement (MAX + 1)

    public function getID_Evenement(): ?int
    {
        return $this->ID_Evenement;
    }

    public function setID_Evenement(int $ID_Evenement): self
    {
        $this->ID_Evenement = $ID_Evenement;
        return $this;
    }

    #[ORM\Column(name: "Titre", type: 'string', nullable: false)]
    #[Assert\NotBlank(message: "Le titre est obligatoire.")] // Sécurité : ne peut pas être vide
    #[Assert\Length(min: 3, max: 100)] // Sécurité : entre 3 et 100 lettres
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

    #[ORM\Column(name: "Description", type: 'text', nullable: true)]
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

    // Dates liées via l'entité Ordre
    #[ORM\ManyToOne(targetEntity: Ordre::class)]
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

    #[ORM\ManyToOne(targetEntity: Ordre::class)]
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

    #[ORM\ManyToOne(targetEntity: Ordre::class)]
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

    #[ORM\Column(name: "Localisation", type: 'string', nullable: true)]
    #[Assert\NotBlank(message: "La localisation est obligatoire.")]
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

    #[ORM\Column(name: "Image", type: 'string', nullable: true)]
    #[Assert\Url(message: "L'URL de l'image n'est pas valide.")]
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

    #[ORM\Column(name: "est_payant", type: 'boolean', nullable: true)]
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

    #[ORM\Column(name: "prix", type: 'decimal', precision: 10, scale: 2, nullable: true)]
    #[Assert\PositiveOrZero(message: "Le prix ne peut pas être négatif.")]
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

    #[ORM\Column(name: "nbMax", type: 'integer', nullable: true)]
    #[Assert\PositiveOrZero(message: "Le nombre maximum de participants doit être positif ou nul.")]
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

    // -------------------------------------------------------------
    // RELATION AVEC LES ACTIVITÉS (via la table de liaison)
    // -------------------------------------------------------------
    #[ORM\OneToMany(targetEntity: DetailEvenement::class, mappedBy: 'evenement', cascade: ['persist', 'remove'])]
    private Collection $details;

    public function __construct()
    {
        $this->details = new ArrayCollection();
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
        if ($this->details->removeElement($detail)) {
            if ($detail->getEvenement() === $this) {
                $detail->setEvenement(null);
            }
        }
        return $this;
    }

    /**
     * Méthode utilitaire pour garder la compatibilité avec vos formulaires actuels
     * @return Collection<int, Activite>
     */
    public function getActivites(): Collection
    {
        $activites = new ArrayCollection();
        foreach ($this->details as $detail) {
            $activites->add($detail->getActivite());
        }
        return $activites;
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

    // -------------------------------------------------------------
    // LOGIQUE MÉTIER (Vérifications personnalisées)
    // -------------------------------------------------------------
    
    /**
     * Vérification personnalisée : si c'est payant, le prix est obligatoire
     */
    #[Assert\Callback]
    public function validatePrice(ExecutionContextInterface $context): void
    {
        if ($this->est_payant && ($this->prix === null || $this->prix <= 0)) {
            $context->buildViolation('Le prix est obligatoire si l\'événement est payant.')
                ->atPath('prix')
                ->addViolation();
        }
    }

    /**
     * HELPER : Retourne la date de début réelle sous forme d'objet DateTime
     */
    public function getRealDateDebut(): \DateTime
    {
        $o = $this->getOrdreDebut();
        return (new \DateTime())->setDate($o->getAAAA(), $o->getMM(), $o->getJJ())->setTime($o->getHH(), $o->getMN(), $o->getSS());
    }

    /**
     * HELPER : Retourne la date de fin réelle sous forme d'objet DateTime
     */
    public function getRealDateFin(): \DateTime
    {
        $o = $this->getOrdreFin();
        return (new \DateTime())->setDate($o->getAAAA(), $o->getMM(), $o->getJJ())->setTime($o->getHH(), $o->getMN(), $o->getSS());
    }

    /**
     * MÉTIER 1 : Badge de Prix automatique
     */
    public function getBadgeLabel(): string
    {
        if ($this->prix == 0 || !$this->est_payant) return "OFFERT";
        if ($this->prix > 100) return "PREMIUM";
        return "STANDARD";
    }

    /**
     * MÉTIER 1 : Couleur du Badge
     */
    public function getBadgeColor(): string
    {
        if ($this->getBadgeLabel() === "OFFERT") return "#dcfce7"; // Vert
        if ($this->getBadgeLabel() === "PREMIUM") return "#fef3c7"; // Or/Ambre
        return "#f1f5f9"; // Gris
    }

    /**
     * MÉTIER 3 : Calcul de la durée en heures
     */
    public function getDurationHours(): int
    {
        $debut = $this->getRealDateDebut();
        $fin = $this->getRealDateFin();
        $diff = $debut->diff($fin);
        return ($diff->days * 24) + $diff->h;
    }
}
