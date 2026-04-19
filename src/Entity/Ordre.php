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

    private const BASE_DATE = '2020-01-01 00:00:00';

    /**
     * Current time → integer (seconds since BASE_DATE)
     */
    public static function GetNumOrdreNow(): int
    {
        return self::dateToNumOrdre(new \DateTime());
    }

    /**
     * Convert DateTime → integer
     */
    public static function dateToNumOrdre(\DateTime $date): int
    {
        $base = new \DateTime(self::BASE_DATE);

        return $date->getTimestamp() - $base->getTimestamp();
    }

    /**
     * Convert integer → DateTime
     */
    public static function numOrdreToDate(int $numOrdre): \DateTime
    {
        $base = new \DateTime(self::BASE_DATE);

        $date = clone $base;
        $date->modify("+{$numOrdre} seconds");

        return $date;
    }

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

    #[ORM\Column(type: 'integer', nullable: false)]
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

    #[ORM\Column(type: 'integer', nullable: false)]
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

    #[ORM\Column(type: 'integer', nullable: false)]
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

    #[ORM\Column(type: 'integer', nullable: false)]
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

    #[ORM\Column(type: 'integer', nullable: false)]
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

    #[ORM\Column(type: 'integer', nullable: false)]
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

    #[ORM\OneToMany(targetEntity: DemandeConge::class, mappedBy: 'ordreDebut')]
    private Collection $demandeCongesDebut;

    /**
     * @return Collection<int, DemandeConge>
     */
    public function getDemandeCongesDebut(): Collection
    {
        if (!$this->demandeCongesDebut instanceof Collection) {
            $this->demandeCongesDebut = new ArrayCollection();
        }
        return $this->demandeCongesDebut;
    }

    public function addDemandeCongeDebut(DemandeConge $demandeConge): self
    {
        if (!$this->getDemandeCongesDebut()->contains($demandeConge)) {
            $this->getDemandeCongesDebut()->add($demandeConge);
        }
        return $this;
    }

    public function removeDemandeCongeDebut(DemandeConge $demandeConge): self
    {
        $this->getDemandeCongesDebut()->removeElement($demandeConge);
        return $this;
    }

    #[ORM\OneToMany(targetEntity: DemandeConge::class, mappedBy: 'ordreFin')]
    private Collection $demandeCongesFin;

    /**
     * @return Collection<int, DemandeConge>
     */
    public function getDemandeCongesFin(): Collection
    {
        if (!$this->demandeCongesFin instanceof Collection) {
            $this->demandeCongesFin = new ArrayCollection();
        }
        return $this->demandeCongesFin;
    }

    public function addDemandeCongeFin(DemandeConge $demandeConge): self
    {
        if (!$this->getDemandeCongesFin()->contains($demandeConge)) {
            $this->getDemandeCongesFin()->add($demandeConge);
        }
        return $this;
    }

    public function removeDemandeCongeFin(DemandeConge $demandeConge): self
    {
        $this->getDemandeCongesFin()->removeElement($demandeConge);
        return $this;
    }


    public function __construct()
    {
        $this->demandeCongesDebut = new ArrayCollection();
        $this->demandeCongesFin = new ArrayCollection();
        // $this->emails = new ArrayCollection();
        // $this->entretiens = new ArrayCollection();
        // $this->evenementsDebut = new ArrayCollection();
        // $this->evenementsFin = new ArrayCollection();
        // $this->evenementsCreation = new ArrayCollection();
        // $this->offresCreation = new ArrayCollection();
        // $this->offresExpiration = new ArrayCollection();
        // $this->participationEvenements = new ArrayCollection();
        //$this->utilisateurs = new ArrayCollection();
        // $this->typeActions = new ArrayCollection();
        // $this->activites = new ArrayCollection();
    }


    public function getNumOrdre(): ?int
    {
        return $this->Num_Ordre;
    }

    public function addDemandeCongesDebut(DemandeConge $demandeCongesDebut): static
    {
        if (!$this->demandeCongesDebut->contains($demandeCongesDebut)) {
            $this->demandeCongesDebut->add($demandeCongesDebut);
            $demandeCongesDebut->setOrdreDebut($this);
        }

        return $this;
    }

    public function removeDemandeCongesDebut(DemandeConge $demandeCongesDebut): static
    {
        if ($this->demandeCongesDebut->removeElement($demandeCongesDebut)) {
            // set the owning side to null (unless already changed)
            if ($demandeCongesDebut->getOrdreDebut() === $this) {
                $demandeCongesDebut->setOrdreDebut(null);
            }
        }

        return $this;
    }

    public function addDemandeCongesFin(DemandeConge $demandeCongesFin): static
    {
        if (!$this->demandeCongesFin->contains($demandeCongesFin)) {
            $this->demandeCongesFin->add($demandeCongesFin);
            $demandeCongesFin->setOrdreFin($this);
        }

        return $this;
    }

    public function removeDemandeCongesFin(DemandeConge $demandeCongesFin): static
    {
        if ($this->demandeCongesFin->removeElement($demandeCongesFin)) {
            // set the owning side to null (unless already changed)
            if ($demandeCongesFin->getOrdreFin() === $this) {
                $demandeCongesFin->setOrdreFin(null);
            }
        }

        return $this;
    }
}
