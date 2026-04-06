<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use App\Repository\OffreRepository;

#[ORM\Entity(repositoryClass: OffreRepository::class)]
#[ORM\Table(name: 'offre')]
class Offre
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $ID_Offre = null;

    public function getID_Offre(): ?int
    {
        return $this->ID_Offre;
    }

    public function setID_Offre(int $ID_Offre): self
    {
        $this->ID_Offre = $ID_Offre;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: false)]
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

    #[ORM\Column(type: 'text', nullable: true)]
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

    #[ORM\Column(type: 'string', nullable: true)]
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

    #[ORM\ManyToOne(targetEntity: Entreprise::class, inversedBy: 'offres')]
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

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $Work_Type = null;

    public function getWork_Type(): ?string
    {
        return $this->Work_Type;
    }

    public function setWork_Type(?string $Work_Type): self
    {
        $this->Work_Type = $Work_Type;
        return $this;
    }

    #[ORM\ManyToOne(targetEntity: TypeContrat::class, inversedBy: 'offres')]
    #[ORM\JoinColumn(name: 'Code_Type_Contrat', referencedColumnName: 'Code_Type_Contrat')]
    private ?TypeContrat $typeContrat = null;

    public function getTypeContrat(): ?TypeContrat
    {
        return $this->typeContrat;
    }

    public function setTypeContrat(?TypeContrat $typeContrat): self
    {
        $this->typeContrat = $typeContrat;
        return $this;
    }

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $Nbr_Annee_Experience = null;

    public function getNbr_Annee_Experience(): ?int
    {
        return $this->Nbr_Annee_Experience;
    }

    public function setNbr_Annee_Experience(?int $Nbr_Annee_Experience): self
    {
        $this->Nbr_Annee_Experience = $Nbr_Annee_Experience;
        return $this;
    }

    #[ORM\ManyToOne(targetEntity: TypeNiveauEtude::class, inversedBy: 'offres')]
    #[ORM\JoinColumn(name: 'Code_Type_Niveau_Etude', referencedColumnName: 'Code_Type_Niveau_Etude')]
    private ?TypeNiveauEtude $typeNiveauEtude = null;

    public function getTypeNiveauEtude(): ?TypeNiveauEtude
    {
        return $this->typeNiveauEtude;
    }

    public function setTypeNiveauEtude(?TypeNiveauEtude $typeNiveauEtude): self
    {
        $this->typeNiveauEtude = $typeNiveauEtude;
        return $this;
    }

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $Min_Salaire = null;

    public function getMin_Salaire(): ?int
    {
        return $this->Min_Salaire;
    }

    public function setMin_Salaire(?int $Min_Salaire): self
    {
        $this->Min_Salaire = $Min_Salaire;
        return $this;
    }

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $Max_Salaire = null;

    public function getMax_Salaire(): ?int
    {
        return $this->Max_Salaire;
    }

    public function setMax_Salaire(?int $Max_Salaire): self
    {
        $this->Max_Salaire = $Max_Salaire;
        return $this;
    }

    #[ORM\ManyToOne(targetEntity: Ordre::class, inversedBy: 'offres')]
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

    #[ORM\ManyToOne(targetEntity: Ordre::class, inversedBy: 'offres')]
    #[ORM\JoinColumn(name: 'Num_Ordre_Expiration', referencedColumnName: 'Num_Ordre')]
    private ?Ordre $ordreExpiration = null;

    public function getOrdreExpiration(): ?Ordre
    {
        return $this->ordreExpiration;
    }

    public function setOrdreExpiration(?Ordre $ordreExpiration): self
    {
        $this->ordreExpiration = $ordreExpiration;
        return $this;
    }

    #[ORM\OneToMany(targetEntity: Condidature::class, mappedBy: 'offre')]
    private Collection $condidatures;

    /**
     * @return Collection<int, Condidature>
     */
    public function getCondidatures(): Collection
    {
        if (!$this->condidatures instanceof Collection) {
            $this->condidatures = new ArrayCollection();
        }
        return $this->condidatures;
    }

    public function addCondidature(Condidature $condidature): self
    {
        if (!$this->getCondidatures()->contains($condidature)) {
            $this->getCondidatures()->add($condidature);
        }
        return $this;
    }

    public function removeCondidature(Condidature $condidature): self
    {
        $this->getCondidatures()->removeElement($condidature);
        return $this;
    }

    #[ORM\ManyToMany(targetEntity: TypeBackgroundEtude::class, inversedBy: 'offres')]
    #[ORM\JoinTable(
        name: 'detail_offre_background',
        joinColumns: [
            new ORM\JoinColumn(name: 'ID_Offre', referencedColumnName: 'ID_Offre')
        ],
        inverseJoinColumns: [
            new ORM\JoinColumn(name: 'Code_Type_Background_Etude', referencedColumnName: 'Code_Type_Background_Etude')
        ]
    )]
    private Collection $typeBackgroundEtudes;

    /**
     * @return Collection<int, TypeBackgroundEtude>
     */
    public function getTypeBackgroundEtudes(): Collection
    {
        if (!$this->typeBackgroundEtudes instanceof Collection) {
            $this->typeBackgroundEtudes = new ArrayCollection();
        }
        return $this->typeBackgroundEtudes;
    }

    public function addTypeBackgroundEtude(TypeBackgroundEtude $typeBackgroundEtude): self
    {
        if (!$this->getTypeBackgroundEtudes()->contains($typeBackgroundEtude)) {
            $this->getTypeBackgroundEtudes()->add($typeBackgroundEtude);
        }
        return $this;
    }

    public function removeTypeBackgroundEtude(TypeBackgroundEtude $typeBackgroundEtude): self
    {
        $this->getTypeBackgroundEtudes()->removeElement($typeBackgroundEtude);
        return $this;
    }

    #[ORM\ManyToMany(targetEntity: TypeCompetence::class, inversedBy: 'offres')]
    #[ORM\JoinTable(
        name: 'detail_offre_competence',
        joinColumns: [
            new ORM\JoinColumn(name: 'ID_Offre', referencedColumnName: 'ID_Offre')
        ],
        inverseJoinColumns: [
            new ORM\JoinColumn(name: 'Code_Type_Competence', referencedColumnName: 'Code_Type_Competence')
        ]
    )]
    private Collection $typeCompetences;

    /**
     * @return Collection<int, TypeCompetence>
     */
    public function getTypeCompetences(): Collection
    {
        if (!$this->typeCompetences instanceof Collection) {
            $this->typeCompetences = new ArrayCollection();
        }
        return $this->typeCompetences;
    }

    public function addTypeCompetence(TypeCompetence $typeCompetence): self
    {
        if (!$this->getTypeCompetences()->contains($typeCompetence)) {
            $this->getTypeCompetences()->add($typeCompetence);
        }
        return $this;
    }

    public function removeTypeCompetence(TypeCompetence $typeCompetence): self
    {
        $this->getTypeCompetences()->removeElement($typeCompetence);
        return $this;
    }

    #[ORM\ManyToMany(targetEntity: TypeLangue::class, inversedBy: 'offres')]
    #[ORM\JoinTable(
        name: 'detail_offre_langue',
        joinColumns: [
            new ORM\JoinColumn(name: 'ID_Offre', referencedColumnName: 'ID_Offre')
        ],
        inverseJoinColumns: [
            new ORM\JoinColumn(name: 'Code_Type_Langue', referencedColumnName: 'Code_Type_Langue')
        ]
    )]
    private Collection $typeLangues;

    public function __construct()
    {
        $this->condidatures = new ArrayCollection();
        $this->typeBackgroundEtudes = new ArrayCollection();
        $this->typeCompetences = new ArrayCollection();
        $this->typeLangues = new ArrayCollection();
    }

    /**
     * @return Collection<int, TypeLangue>
     */
    public function getTypeLangues(): Collection
    {
        if (!$this->typeLangues instanceof Collection) {
            $this->typeLangues = new ArrayCollection();
        }
        return $this->typeLangues;
    }

    public function addTypeLangue(TypeLangue $typeLangue): self
    {
        if (!$this->getTypeLangues()->contains($typeLangue)) {
            $this->getTypeLangues()->add($typeLangue);
        }
        return $this;
    }

    public function removeTypeLangue(TypeLangue $typeLangue): self
    {
        $this->getTypeLangues()->removeElement($typeLangue);
        return $this;
    }

    public function getIDOffre(): ?int
    {
        return $this->ID_Offre;
    }

    public function getWorkType(): ?string
    {
        return $this->Work_Type;
    }

    public function setWorkType(?string $Work_Type): static
    {
        $this->Work_Type = $Work_Type;

        return $this;
    }

    public function getNbrAnneeExperience(): ?int
    {
        return $this->Nbr_Annee_Experience;
    }

    public function setNbrAnneeExperience(?int $Nbr_Annee_Experience): static
    {
        $this->Nbr_Annee_Experience = $Nbr_Annee_Experience;

        return $this;
    }

    public function getMinSalaire(): ?int
    {
        return $this->Min_Salaire;
    }

    public function setMinSalaire(?int $Min_Salaire): static
    {
        $this->Min_Salaire = $Min_Salaire;

        return $this;
    }

    public function getMaxSalaire(): ?int
    {
        return $this->Max_Salaire;
    }

    public function setMaxSalaire(?int $Max_Salaire): static
    {
        $this->Max_Salaire = $Max_Salaire;

        return $this;
    }

    public function getLocation(): ?string
    {
        return $this->Localisation;
    }

    public function setLocation(?string $Localisation): static
    {
        $this->Localisation = $Localisation;

        return $this;
    }

}
