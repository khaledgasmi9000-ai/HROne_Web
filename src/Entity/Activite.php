<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use App\Repository\ActiviteRepository;

#[ORM\Entity(repositoryClass: ActiviteRepository::class)]
#[ORM\Table(name: 'activite')]
class Activite
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'ID_Activite', type: 'integer')]
    private ?int $ID_Activite = null;

    public function getID_Activite(): ?int
    {
        return $this->ID_Activite;
    }

    public function setID_Activite(int $ID_Activite): self
    {
        $this->ID_Activite = $ID_Activite;
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

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $ID_Evenement = null;

    public function getID_Evenement(): ?int
    {
        return $this->ID_Evenement;
    }

    public function setID_Evenement(?int $ID_Evenement): self
    {
        $this->ID_Evenement = $ID_Evenement;
        return $this;
    }

    #[ORM\OneToMany(targetEntity: ParticipationEvenement::class, mappedBy: 'activite')]
    private Collection $participationEvenements;

    /**
     * @return Collection<int, ParticipationEvenement>
     */
    public function getParticipationEvenements(): Collection
    {
        if (!$this->participationEvenements instanceof Collection) {
            $this->participationEvenements = new ArrayCollection();
        }
        return $this->participationEvenements;
    }

    public function addParticipationEvenement(ParticipationEvenement $participationEvenement): self
    {
        if (!$this->getParticipationEvenements()->contains($participationEvenement)) {
            $this->getParticipationEvenements()->add($participationEvenement);
        }
        return $this;
    }

    public function removeParticipationEvenement(ParticipationEvenement $participationEvenement): self
    {
        $this->getParticipationEvenements()->removeElement($participationEvenement);
        return $this;
    }

    #[ORM\ManyToMany(targetEntity: Evenement::class, mappedBy: 'activites')]
    private Collection $evenements;

    public function __construct()
    {
        $this->participationEvenements = new ArrayCollection();
        $this->evenements = new ArrayCollection();
    }

    /**
     * @return Collection<int, Evenement>
     */
    public function getEvenements(): Collection
    {
        if (!$this->evenements instanceof Collection) {
            $this->evenements = new ArrayCollection();
        }
        return $this->evenements;
    }

    public function addEvenement(Evenement $evenement): self
    {
        if (!$this->getEvenements()->contains($evenement)) {
            $this->getEvenements()->add($evenement);
        }
        return $this;
    }

    public function removeEvenement(Evenement $evenement): self
    {
        $this->getEvenements()->removeElement($evenement);
        return $this;
    }

    public function getIDActivite(): ?int
    {
        return $this->ID_Activite;
    }

    public function getIDEvenement(): ?int
    {
        return $this->ID_Evenement;
    }

    public function setIDEvenement(?int $ID_Evenement): static
    {
        $this->ID_Evenement = $ID_Evenement;

        return $this;
    }

}
