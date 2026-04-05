<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use App\Repository\TypeActionRepository;

#[ORM\Entity(repositoryClass: TypeActionRepository::class)]
#[ORM\Table(name: 'type_action')]
class TypeAction
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $Code_Type_Action = null;

    public function getCode_Type_Action(): ?int
    {
        return $this->Code_Type_Action;
    }

    public function setCode_Type_Action(int $Code_Type_Action): self
    {
        $this->Code_Type_Action = $Code_Type_Action;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $Description_Action = null;

    public function getDescription_Action(): ?string
    {
        return $this->Description_Action;
    }

    public function setDescription_Action(string $Description_Action): self
    {
        $this->Description_Action = $Description_Action;
        return $this;
    }

    #[ORM\ManyToMany(targetEntity: Ordre::class, inversedBy: 'typeActions')]
    #[ORM\JoinTable(
        name: 'action_utilisateur',
        joinColumns: [
            new ORM\JoinColumn(name: 'Code_Type_Action', referencedColumnName: 'Code_Type_Action')
        ],
        inverseJoinColumns: [
            new ORM\JoinColumn(name: 'Num_Ordre', referencedColumnName: 'Num_Ordre')
        ]
    )]
    private Collection $ordres;

    public function __construct()
    {
        $this->ordres = new ArrayCollection();
    }

    /**
     * @return Collection<int, Ordre>
     */
    public function getOrdres(): Collection
    {
        if (!$this->ordres instanceof Collection) {
            $this->ordres = new ArrayCollection();
        }
        return $this->ordres;
    }

    public function addOrdre(Ordre $ordre): self
    {
        if (!$this->getOrdres()->contains($ordre)) {
            $this->getOrdres()->add($ordre);
        }
        return $this;
    }

    public function removeOrdre(Ordre $ordre): self
    {
        $this->getOrdres()->removeElement($ordre);
        return $this;
    }

    public function getCodeTypeAction(): ?int
    {
        return $this->Code_Type_Action;
    }

    public function getDescriptionAction(): ?string
    {
        return $this->Description_Action;
    }

    public function setDescriptionAction(string $Description_Action): static
    {
        $this->Description_Action = $Description_Action;

        return $this;
    }

}
