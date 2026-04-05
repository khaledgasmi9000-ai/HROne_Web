<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use App\Repository\TypeContratRepository;

#[ORM\Entity(repositoryClass: TypeContratRepository::class)]
#[ORM\Table(name: 'type_contrat')]
class TypeContrat
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $Code_Type_Contrat = null;

    public function getCode_Type_Contrat(): ?int
    {
        return $this->Code_Type_Contrat;
    }

    public function setCode_Type_Contrat(int $Code_Type_Contrat): self
    {
        $this->Code_Type_Contrat = $Code_Type_Contrat;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $Description_Contrat = null;

    public function getDescription_Contrat(): ?string
    {
        return $this->Description_Contrat;
    }

    public function setDescription_Contrat(string $Description_Contrat): self
    {
        $this->Description_Contrat = $Description_Contrat;
        return $this;
    }

    #[ORM\OneToMany(targetEntity: Offre::class, mappedBy: 'typeContrat')]
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

    public function getCodeTypeContrat(): ?int
    {
        return $this->Code_Type_Contrat;
    }

    public function getDescriptionContrat(): ?string
    {
        return $this->Description_Contrat;
    }

    public function setDescriptionContrat(string $Description_Contrat): static
    {
        $this->Description_Contrat = $Description_Contrat;

        return $this;
    }

}
