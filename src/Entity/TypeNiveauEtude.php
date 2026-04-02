<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use App\Repository\TypeNiveauEtudeRepository;

#[ORM\Entity(repositoryClass: TypeNiveauEtudeRepository::class)]
#[ORM\Table(name: 'type_niveau_etude')]
class TypeNiveauEtude
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $Code_Type_Niveau_Etude = null;

    public function getCode_Type_Niveau_Etude(): ?int
    {
        return $this->Code_Type_Niveau_Etude;
    }

    public function setCode_Type_Niveau_Etude(int $Code_Type_Niveau_Etude): self
    {
        $this->Code_Type_Niveau_Etude = $Code_Type_Niveau_Etude;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $Description_Type_Etude = null;

    public function getDescription_Type_Etude(): ?string
    {
        return $this->Description_Type_Etude;
    }

    public function setDescription_Type_Etude(string $Description_Type_Etude): self
    {
        $this->Description_Type_Etude = $Description_Type_Etude;
        return $this;
    }

    #[ORM\OneToMany(targetEntity: Offre::class, mappedBy: 'typeNiveauEtude')]
    private Collection $offres;

    public function __construct()
    {
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

    public function getCodeTypeNiveauEtude(): ?int
    {
        return $this->Code_Type_Niveau_Etude;
    }

    public function getDescriptionTypeEtude(): ?string
    {
        return $this->Description_Type_Etude;
    }

    public function setDescriptionTypeEtude(string $Description_Type_Etude): static
    {
        $this->Description_Type_Etude = $Description_Type_Etude;

        return $this;
    }

}
