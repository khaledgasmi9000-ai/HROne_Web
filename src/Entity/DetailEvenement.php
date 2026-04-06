<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Repository\DetailEvenementRepository;

/**
 * C'est l'entité de liaison (le pont) entre Evenement et Activite.
 * On l'utilise car la table 'detail_evenement' possède des colonnes de date.
 */
#[ORM\Entity(repositoryClass: DetailEvenementRepository::class)]
#[ORM\Table(name: 'detail_evenement')]
class DetailEvenement
{
    // Clé composite : ID_Evenement + ID_Activite
    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: Evenement::class, inversedBy: 'details')]
    #[ORM\JoinColumn(name: 'ID_Evenement', referencedColumnName: 'ID_Evenement', nullable: false)]
    private ?Evenement $evenement = null;

    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: Activite::class, inversedBy: 'details')]
    #[ORM\JoinColumn(name: 'ID_Activite', referencedColumnName: 'ID_Activite', nullable: false)]
    private ?Activite $activite = null;

    // Dates obligatoires pour la base hr_one
    #[ORM\ManyToOne(targetEntity: Ordre::class)]
    #[ORM\JoinColumn(name: 'Num_Ordre_Debut_Activite', referencedColumnName: 'Num_Ordre', nullable: true)]
    private ?Ordre $ordreDebut = null;

    #[ORM\ManyToOne(targetEntity: Ordre::class)]
    #[ORM\JoinColumn(name: 'Num_Ordre_Fin_Activite', referencedColumnName: 'Num_Ordre', nullable: true)]
    private ?Ordre $ordreFin = null;

    public function getEvenement(): ?Evenement
    {
        return $this->evenement;
    }

    public function setEvenement(?Evenement $evenement): self
    {
        $this->evenement = $evenement;
        return $this;
    }

    public function getActivite(): ?Activite
    {
        return $this->activite;
    }

    public function setActivite(?Activite $activite): self
    {
        $this->activite = $activite;
        return $this;
    }

    public function getOrdreDebut(): ?Ordre
    {
        return $this->ordreDebut;
    }

    public function setOrdreDebut(?Ordre $ordreDebut): self
    {
        $this->ordreDebut = $ordreDebut;
        return $this;
    }

    public function getOrdreFin(): ?Ordre
    {
        return $this->ordreFin;
    }

    public function setOrdreFin(?Ordre $ordreFin): self
    {
        $this->ordreFin = $ordreFin;
        return $this;
    }
}
