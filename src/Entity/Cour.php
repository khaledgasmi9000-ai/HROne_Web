<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use App\Repository\CourRepository;

#[ORM\Entity(repositoryClass: CourRepository::class)]
#[ORM\Table(name: 'cours')]
class Cour
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $ID_Cours = null;

    public function getID_Cours(): ?int
    {
        return $this->ID_Cours;
    }

    public function setID_Cours(int $ID_Cours): self
    {
        $this->ID_Cours = $ID_Cours;
        return $this;
    }

    #[ORM\ManyToOne(targetEntity: TypeBackgroundEtude::class, inversedBy: 'cours')]
    #[ORM\JoinColumn(name: 'Code_Type_Background_Etude', referencedColumnName: 'Code_Type_Background_Etude')]
    private ?TypeBackgroundEtude $typeBackgroundEtude = null;

    public function getTypeBackgroundEtude(): ?TypeBackgroundEtude
    {
        return $this->typeBackgroundEtude;
    }

    public function setTypeBackgroundEtude(?TypeBackgroundEtude $typeBackgroundEtude): self
    {
        $this->typeBackgroundEtude = $typeBackgroundEtude;
        return $this;
    }

    #[ORM\Column(type: 'text', nullable: false)]
    private ?string $Contenu = null;

    public function getContenu(): ?string
    {
        return $this->Contenu;
    }

    public function setContenu(string $Contenu): self
    {
        $this->Contenu = $Contenu;
        return $this;
    }

    public function getIDCours(): ?int
    {
        return $this->ID_Cours;
    }

}
