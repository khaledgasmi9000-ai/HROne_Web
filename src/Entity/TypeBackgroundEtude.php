<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use App\Repository\TypeBackgroundEtudeRepository;

#[ORM\Entity(repositoryClass: TypeBackgroundEtudeRepository::class)]
#[ORM\Table(name: 'type_background_etude')]
class TypeBackgroundEtude
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $Code_Type_Background_Etude = null;

    public function getCode_Type_Background_Etude(): ?int
    {
        return $this->Code_Type_Background_Etude;
    }

    public function setCode_Type_Background_Etude(int $Code_Type_Background_Etude): self
    {
        $this->Code_Type_Background_Etude = $Code_Type_Background_Etude;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $Description_Type_Background_Etude = null;

    public function getDescription_Type_Background_Etude(): ?string
    {
        return $this->Description_Type_Background_Etude;
    }

    public function setDescription_Type_Background_Etude(string $Description_Type_Background_Etude): self
    {
        $this->Description_Type_Background_Etude = $Description_Type_Background_Etude;
        return $this;
    }

    #[ORM\OneToMany(targetEntity: Cour::class, mappedBy: 'typeBackgroundEtude')]
    private Collection $cours;

    /**
     * @return Collection<int, Cour>
     */
    public function getCours(): Collection
    {
        if (!$this->cours instanceof Collection) {
            $this->cours = new ArrayCollection();
        }
        return $this->cours;
    }

    public function addCour(Cour $cour): self
    {
        if (!$this->getCours()->contains($cour)) {
            $this->getCours()->add($cour);
        }
        return $this;
    }

    public function removeCour(Cour $cour): self
    {
        $this->getCours()->removeElement($cour);
        return $this;
    }

    #[ORM\ManyToMany(targetEntity: Offre::class, inversedBy: 'typeBackgroundEtudes')]
    #[ORM\JoinTable(
        name: 'detail_offre_background',
        joinColumns: [
            new ORM\JoinColumn(name: 'Code_Type_Background_Etude', referencedColumnName: 'Code_Type_Background_Etude')
        ],
        inverseJoinColumns: [
            new ORM\JoinColumn(name: 'ID_Offre', referencedColumnName: 'ID_Offre')
        ]
    )]
    private Collection $offres;

    public function __construct()
    {
        $this->cours = new ArrayCollection();
        $this->offres = new ArrayCollection();
    }

    /**
     * @return Collection<int, Offre>
     */
    public function getOffres(): Collection
    {
        if (!$this->offres instanceof Collection) {
            $this->offres = new ArrayCollection();
        }
        return $this->offres;
    }

    public function addOffre(Offre $offre): self
    {
        if (!$this->getOffres()->contains($offre)) {
            $this->getOffres()->add($offre);
        }
        return $this;
    }

    public function removeOffre(Offre $offre): self
    {
        $this->getOffres()->removeElement($offre);
        return $this;
    }

    public function getCodeTypeBackgroundEtude(): ?int
    {
        return $this->Code_Type_Background_Etude;
    }

    public function getDescriptionTypeBackgroundEtude(): ?string
    {
        return $this->Description_Type_Background_Etude;
    }

    public function setDescriptionTypeBackgroundEtude(string $Description_Type_Background_Etude): static
    {
        $this->Description_Type_Background_Etude = $Description_Type_Background_Etude;

        return $this;
    }

}
