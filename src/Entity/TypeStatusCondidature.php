<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use App\Repository\TypeStatusCondidatureRepository;

#[ORM\Entity(repositoryClass: TypeStatusCondidatureRepository::class)]
#[ORM\Table(name: 'type_status_condidature')]
class TypeStatusCondidature
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $Code_Type_Status_Condidature = null;

    public function getCode_Type_Status_Condidature(): ?int
    {
        return $this->Code_Type_Status_Condidature;
    }

    public function setCode_Type_Status_Condidature(int $Code_Type_Status_Condidature): self
    {
        $this->Code_Type_Status_Condidature = $Code_Type_Status_Condidature;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $Description_Status_Condidature = null;

    public function getDescription_Status_Condidature(): ?string
    {
        return $this->Description_Status_Condidature;
    }

    public function setDescription_Status_Condidature(string $Description_Status_Condidature): self
    {
        $this->Description_Status_Condidature = $Description_Status_Condidature;
        return $this;
    }

    #[ORM\OneToMany(targetEntity: Condidature::class, mappedBy: 'typeStatusCondidature')]
    private Collection $condidatures;

    public function __construct()
    {
        $this->condidatures = new ArrayCollection();
    }

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

    public function getCodeTypeStatusCondidature(): ?int
    {
        return $this->Code_Type_Status_Condidature;
    }

    public function getDescriptionStatusCondidature(): ?string
    {
        return $this->Description_Status_Condidature;
    }

    public function setDescriptionStatusCondidature(string $Description_Status_Condidature): static
    {
        $this->Description_Status_Condidature = $Description_Status_Condidature;

        return $this;
    }

}
