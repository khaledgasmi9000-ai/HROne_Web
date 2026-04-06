<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use App\Repository\OrdreRepository;

#[ORM\Entity(repositoryClass: OrdreRepository::class)]
#[ORM\Table(name: 'ordre')]
class Ordre
{
    #[ORM\Id]
    #[ORM\Column(name: 'Num_Ordre', type: 'integer')]
    private ?int $Num_Ordre = null;

    public function getNum_Ordre(): ?int
    {
        return $this->Num_Ordre;
    }

    public function setNum_Ordre(int $Num_Ordre): self
    {
        $this->Num_Ordre = $Num_Ordre;
        return $this;
    }

    #[ORM\Column(name: "AAAA", type: 'integer', nullable: false)]
    private ?int $AAAA = null;

    public function getAAAA(): ?int
    {
        return $this->AAAA;
    }

    public function setAAAA(int $AAAA): self
    {
        $this->AAAA = $AAAA;
        return $this;
    }

    #[ORM\Column(name: "MM", type: 'integer', nullable: false)]
    private ?int $MM = null;

    public function getMM(): ?int
    {
        return $this->MM;
    }

    public function setMM(int $MM): self
    {
        $this->MM = $MM;
        return $this;
    }

    #[ORM\Column(name: "JJ", type: 'integer', nullable: false)]
    private ?int $JJ = null;

    public function getJJ(): ?int
    {
        return $this->JJ;
    }

    public function setJJ(int $JJ): self
    {
        $this->JJ = $JJ;
        return $this;
    }

    #[ORM\Column(name: "HH", type: 'integer', nullable: false)]
    private ?int $HH = null;

    public function getHH(): ?int
    {
        return $this->HH;
    }

    public function setHH(int $HH): self
    {
        $this->HH = $HH;
        return $this;
    }

    #[ORM\Column(name: "MN", type: 'integer', nullable: false)]
    private ?int $MN = null;

    public function getMN(): ?int
    {
        return $this->MN;
    }

    public function setMN(int $MN): self
    {
        $this->MN = $MN;
        return $this;
    }

    #[ORM\Column(name: "SS", type: 'integer', nullable: false)]
    private ?int $SS = null;

    public function getSS(): ?int
    {
        return $this->SS;
    }

    public function setSS(int $SS): self
    {
        $this->SS = $SS;
        return $this;
    }



    #[ORM\OneToMany(targetEntity: Evenement::class, mappedBy: 'ordreDebut')]
    private Collection $evenementsDebut;

    /**
     * @return Collection<int, Evenement>
     */
    public function getEvenementsDebut(): Collection
    {
        if (!$this->evenementsDebut instanceof Collection) {
            $this->evenementsDebut = new ArrayCollection();
        }
        return $this->evenementsDebut;
    }

    public function addEvenementDebut(Evenement $evenement): self
    {
        if (!$this->getEvenementsDebut()->contains($evenement)) {
            $this->getEvenementsDebut()->add($evenement);
        }
        return $this;
    }

    public function removeEvenementDebut(Evenement $evenement): self
    {
        $this->getEvenementsDebut()->removeElement($evenement);
        return $this;
    }

    #[ORM\OneToMany(targetEntity: Evenement::class, mappedBy: 'ordreFin')]
    private Collection $evenementsFin;

    /**
     * @return Collection<int, Evenement>
     */
    public function getEvenementsFin(): Collection
    {
        if (!$this->evenementsFin instanceof Collection) {
            $this->evenementsFin = new ArrayCollection();
        }
        return $this->evenementsFin;
    }

    public function addEvenementFin(Evenement $evenement): self
    {
        if (!$this->getEvenementsFin()->contains($evenement)) {
            $this->getEvenementsFin()->add($evenement);
        }
        return $this;
    }

    public function removeEvenementFin(Evenement $evenement): self
    {
        $this->getEvenementsFin()->removeElement($evenement);
        return $this;
    }

    #[ORM\OneToMany(targetEntity: Evenement::class, mappedBy: 'ordreCreation')]
    private Collection $evenementsCreation;

    /**
     * @return Collection<int, Evenement>
     */
    public function getEvenementsCreation(): Collection
    {
        if (!$this->evenementsCreation instanceof Collection) {
            $this->evenementsCreation = new ArrayCollection();
        }
        return $this->evenementsCreation;
    }

    public function addEvenementCreation(Evenement $evenement): self
    {
        if (!$this->getEvenementsCreation()->contains($evenement)) {
            $this->getEvenementsCreation()->add($evenement);
        }
        return $this;
    }

    public function removeEvenementCreation(Evenement $evenement): self
    {
        $this->getEvenementsCreation()->removeElement($evenement);
        return $this;
    }







    public function __construct()
    {
        $this->evenementsDebut = new ArrayCollection();
        $this->evenementsFin = new ArrayCollection();
        $this->evenementsCreation = new ArrayCollection();
    }



    public function getNumOrdre(): ?int
    {
        return $this->Num_Ordre;
    }



    public function addEvenementsDebut(Evenement $evenementsDebut): static
    {
        if (!$this->evenementsDebut->contains($evenementsDebut)) {
            $this->evenementsDebut->add($evenementsDebut);
            $evenementsDebut->setOrdreDebut($this);
        }

        return $this;
    }

    public function removeEvenementsDebut(Evenement $evenementsDebut): static
    {
        if ($this->evenementsDebut->removeElement($evenementsDebut)) {
            // set the owning side to null (unless already changed)
            if ($evenementsDebut->getOrdreDebut() === $this) {
                $evenementsDebut->setOrdreDebut(null);
            }
        }

        return $this;
    }

    public function addEvenementsFin(Evenement $evenementsFin): static
    {
        if (!$this->evenementsFin->contains($evenementsFin)) {
            $this->evenementsFin->add($evenementsFin);
            $evenementsFin->setOrdreFin($this);
        }

        return $this;
    }

    public function removeEvenementsFin(Evenement $evenementsFin): static
    {
        if ($this->evenementsFin->removeElement($evenementsFin)) {
            // set the owning side to null (unless already changed)
            if ($evenementsFin->getOrdreFin() === $this) {
                $evenementsFin->setOrdreFin(null);
            }
        }

        return $this;
    }

    public function addEvenementsCreation(Evenement $evenementsCreation): static
    {
        if (!$this->evenementsCreation->contains($evenementsCreation)) {
            $this->evenementsCreation->add($evenementsCreation);
            $evenementsCreation->setOrdreCreation($this);
        }

        return $this;
    }

    public function removeEvenementsCreation(Evenement $evenementsCreation): static
    {
        if ($this->evenementsCreation->removeElement($evenementsCreation)) {
            // set the owning side to null (unless already changed)
            if ($evenementsCreation->getOrdreCreation() === $this) {
                $evenementsCreation->setOrdreCreation(null);
            }
        }

        return $this;
    }



}
