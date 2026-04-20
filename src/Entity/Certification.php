<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use App\Repository\CertificationRepository;

#[ORM\Entity(repositoryClass: CertificationRepository::class)]
#[ORM\Table(name: 'certification')]
class Certification
{
      
    #[ORM\Id]
    #[ORM\Column(type: 'integer', nullable: false)]
    private ?int $ID_Certif = null;

    public function getID_Certif(): ?int
    {
        return $this->ID_Certif;
    }

    public function setID_Certif(int $ID_Certif): self
    {
        $this->ID_Certif = $ID_Certif;
        return $this;
    }

    #[ORM\Column(type: 'integer', nullable: false)]
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

    #[ORM\Column(type: 'integer', nullable: false)]
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

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $Description_Certif = null;

    public function getDescription_Certif(): ?string
    {
        return $this->Description_Certif;
    }

    public function setDescription_Certif(?string $Description_Certif): self
    {
        $this->Description_Certif = $Description_Certif;
        return $this;
    }

    #[ORM\Column(type: 'blob', nullable: true)]
    private mixed $Fichier_PDF = null;

    public function getFichier_PDF(): ?string
    {
        if (is_resource($this->Fichier_PDF)) {
            $content = stream_get_contents($this->Fichier_PDF);

            if ($content === false) {
                return null;
            }

            return $content;
        }

        return is_string($this->Fichier_PDF) ? $this->Fichier_PDF : null;
    }

    public function setFichier_PDF(mixed $Fichier_PDF): self
    {
        $this->Fichier_PDF = $Fichier_PDF;
        return $this;
    }

    public function getIDCertif(): ?int
    {
        return $this->ID_Certif;
    }

    public function getIDFormation(): ?int
    {
        return $this->ID_Formation;
    }

    public function setIDFormation(int $ID_Formation): static
    {
        $this->ID_Formation = $ID_Formation;

        return $this;
    }

    public function getIDParticipant(): ?int
    {
        return $this->ID_Participant;
    }

    public function setIDParticipant(int $ID_Participant): static
    {
        $this->ID_Participant = $ID_Participant;

        return $this;
    }

    public function getDescriptionCertif(): ?string
    {
        return $this->Description_Certif;
    }

    public function setDescriptionCertif(?string $Description_Certif): static
    {
        $this->Description_Certif = $Description_Certif;

        return $this;
    }

    public function getFichierPDF(): mixed
    {
        return $this->getFichier_PDF();
    }

    public function setFichierPDF(mixed $Fichier_PDF): static
    {
        $this->Fichier_PDF = $Fichier_PDF;

        return $this;
    }

}
