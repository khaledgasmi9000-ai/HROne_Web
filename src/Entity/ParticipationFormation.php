<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use App\Repository\ParticipationFormationRepository;

#[ORM\Entity(repositoryClass: ParticipationFormationRepository::class)]
#[ORM\Table(name: 'participation_formation')]
class ParticipationFormation
{
    #[ORM\Id]
    #[ORM\Column(name: 'ID_Formation', type: 'integer', nullable: false)]
    private ?int $ID_Formation = null;

    public function getID_Formation(): ?int
    {
        return $this->ID_Formation;
    }

    public function setID_Formation(int $ID_Formation): self
    {
        $this->ID_Formation = $ID_Formation;
        return $this;
    }

    #[ORM\Id]
    #[ORM\Column(name: 'ID_Participant', type: 'integer', nullable: false)]
    private ?int $ID_Participant = null;

    public function getID_Participant(): ?int
    {
        return $this->ID_Participant;
    }

    public function setID_Participant(int $ID_Participant): self
    {
        $this->ID_Participant = $ID_Participant;
        return $this;
    }

    #[ORM\Column(name: 'Num_Ordre_Participation', type: 'bigint', nullable: false)]
    private ?int $Num_Ordre_Participation = null;

    public function getNum_Ordre_Participation(): ?int
    {
        return $this->Num_Ordre_Participation;
    }

    public function setNum_Ordre_Participation(int $Num_Ordre_Participation): self
    {
        $this->Num_Ordre_Participation = $Num_Ordre_Participation;
        return $this;
    }

    #[ORM\Column(name: 'Statut', type: 'string', nullable: true)]
    private ?string $Statut = null;

    public function getStatut(): ?string
    {
        return $this->Statut;
    }

    public function setStatut(?string $Statut): self
    {
        $this->Statut = $Statut;
        return $this;
    }

    #[ORM\Column(name: 'Certificat', type: 'string', nullable: true)]
    private ?string $Certificat = null;

    public function getCertificat(): ?string
    {
        return $this->Certificat;
    }

    public function setCertificat(?string $Certificat): self
    {
        $this->Certificat = $Certificat;
        return $this;
    }

    public function getIDFormation(): ?int
    {
        return $this->ID_Formation;
    }

    public function getIDParticipant(): ?int
    {
        return $this->ID_Participant;
    }

    public function getNumOrdreParticipation(): ?int
    {
        return $this->Num_Ordre_Participation;
    }

}
