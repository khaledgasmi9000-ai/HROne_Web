<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use App\Repository\TypeLangueRepository;

#[ORM\Entity(repositoryClass: TypeLangueRepository::class)]
#[ORM\Table(name: 'type_langue')]
class TypeLangue
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $Code_Type_Langue = null;

    public function getCode_Type_Langue(): ?int
    {
        return $this->Code_Type_Langue;
    }

    public function setCode_Type_Langue(int $Code_Type_Langue): self
    {
        $this->Code_Type_Langue = $Code_Type_Langue;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $Description_Langue = null;

    public function getDescription_Langue(): ?string
    {
        return $this->Description_Langue;
    }

    public function setDescription_Langue(string $Description_Langue): self
    {
        $this->Description_Langue = $Description_Langue;
        return $this;
    }

    #[ORM\ManyToMany(targetEntity: Offre::class, inversedBy: 'typeLangues')]
    #[ORM\JoinTable(
        name: 'detail_offre_langue',
        joinColumns: [
            new ORM\JoinColumn(name: 'Code_Type_Langue', referencedColumnName: 'Code_Type_Langue')
        ],
        inverseJoinColumns: [
            new ORM\JoinColumn(name: 'ID_Offre', referencedColumnName: 'ID_Offre')
        ]
    )]
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

    public function getCodeTypeLangue(): ?int
    {
        return $this->Code_Type_Langue;
    }

    public function getDescriptionLangue(): ?string
    {
        return $this->Description_Langue;
    }

    public function setDescriptionLangue(string $Description_Langue): static
    {
        $this->Description_Langue = $Description_Langue;

        return $this;
    }

}
