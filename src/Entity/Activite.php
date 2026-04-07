<?php

namespace App\Entity;

use App\Repository\ActiviteRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ActiviteRepository::class)]
#[ORM\Table(name: 'activite')]
class Activite
{
    #[ORM\Id]
    #[ORM\Column(name: 'ID_Activite', type: 'integer')]
    private ?int $ID_Activite = null;

    #[ORM\Column(name: 'Titre', type: 'string', nullable: false)]
    #[Assert\NotBlank(message: "Le titre de l'activite est obligatoire.")]
    private ?string $Titre = null;

    #[ORM\Column(name: 'Description', type: 'text', nullable: true)]
    #[Assert\NotBlank(message: "La description de l'activite est obligatoire.")]
    #[Assert\Length(min: 10, max: 500)]
    private ?string $Description = null;

    #[ORM\OneToMany(targetEntity: ParticipationEvenement::class, mappedBy: 'activite')]
    private Collection $participationEvenements;

    #[ORM\OneToMany(targetEntity: DetailEvenement::class, mappedBy: 'activite', cascade: ['persist', 'remove'])]
    private Collection $details;

    public function __construct()
    {
        $this->participationEvenements = new ArrayCollection();
        $this->details = new ArrayCollection();
    }

    public function getID_Activite(): ?int
    {
        return $this->ID_Activite;
    }

    public function setID_Activite(int $ID_Activite): self
    {
        $this->ID_Activite = $ID_Activite;

        return $this;
    }

    public function getTitre(): ?string
    {
        return $this->Titre;
    }

    public function setTitre(string $Titre): self
    {
        $this->Titre = $Titre;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->Description;
    }

    public function setDescription(?string $Description): self
    {
        $this->Description = $Description;

        return $this;
    }

    /**
     * @return Collection<int, ParticipationEvenement>
     */
    public function getParticipationEvenements(): Collection
    {
        return $this->participationEvenements;
    }

    public function addParticipationEvenement(ParticipationEvenement $participationEvenement): self
    {
        if (!$this->participationEvenements->contains($participationEvenement)) {
            $this->participationEvenements->add($participationEvenement);
            $participationEvenement->setActivite($this);
        }

        return $this;
    }

    public function removeParticipationEvenement(ParticipationEvenement $participationEvenement): self
    {
        if ($this->participationEvenements->removeElement($participationEvenement) && $participationEvenement->getActivite() === $this) {
            $participationEvenement->setActivite(null);
        }

        return $this;
    }

    /**
     * @return Collection<int, DetailEvenement>
     */
    public function getDetails(): Collection
    {
        return $this->details;
    }

    public function addDetail(DetailEvenement $detail): self
    {
        if (!$this->details->contains($detail)) {
            $this->details->add($detail);
            $detail->setActivite($this);
        }

        return $this;
    }

    public function removeDetail(DetailEvenement $detail): self
    {
        if ($this->details->removeElement($detail) && $detail->getActivite() === $this) {
            $detail->setActivite(null);
        }

        return $this;
    }

    /**
     * @return Collection<int, Evenement>
     */
    public function getEvenements(): Collection
    {
        $evenements = new ArrayCollection();

        foreach ($this->details as $detail) {
            $evenement = $detail->getEvenement();
            if ($evenement !== null && !$evenements->contains($evenement)) {
                $evenements->add($evenement);
            }
        }

        return $evenements;
    }

    public function addEvenement(Evenement $evenement): self
    {
        foreach ($this->details as $detail) {
            if ($detail->getEvenement() === $evenement) {
                return $this;
            }
        }

        $detail = new DetailEvenement();
        $detail->setActivite($this);
        $detail->setEvenement($evenement);
        $this->addDetail($detail);

        return $this;
    }

    public function removeEvenement(Evenement $evenement): self
    {
        foreach ($this->details as $detail) {
            if ($detail->getEvenement() === $evenement) {
                $this->removeDetail($detail);
            }
        }

        return $this;
    }

    public function getIDActivite(): ?int
    {
        return $this->ID_Activite;
    }
}
