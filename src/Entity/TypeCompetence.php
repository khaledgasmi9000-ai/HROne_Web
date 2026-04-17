<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use App\Repository\TypeCompetenceRepository;

#[ORM\Entity(repositoryClass: TypeCompetenceRepository::class)]
#[ORM\Table(name: 'type_competence')]
class TypeCompetence
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $Code_Type_Competence = null;

    public function getCode_Type_Competence(): ?int
    {
        return $this->Code_Type_Competence;
    }

    public function setCode_Type_Competence(int $Code_Type_Competence): self
    {
        $this->Code_Type_Competence = $Code_Type_Competence;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $Description_Competence = null;

    public function getDescription_Competence(): ?string
    {
        return $this->Description_Competence;
    }

    public function setDescription_Competence(string $Description_Competence): self
    {
        $this->Description_Competence = $Description_Competence;
        return $this;
    }

    #[ORM\ManyToMany(targetEntity: Offre::class, inversedBy: 'typeCompetences')]
    #[ORM\JoinTable(
        name: 'detail_offre_competence',
        joinColumns: [
            new ORM\JoinColumn(name: 'Code_Type_Competence', referencedColumnName: 'Code_Type_Competence')
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

    public function getCodeTypeCompetence(): ?int
    {
        return $this->Code_Type_Competence;
    }

    public function getDescriptionCompetence(): ?string
    {
        return $this->Description_Competence;
    }

    public function setDescriptionCompetence(string $Description_Competence): static
    {
        $this->Description_Competence = $Description_Competence;

        return $this;
    }

}
