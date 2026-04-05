<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use App\Repository\ListeAttenteRepository;

#[ORM\Entity(repositoryClass: ListeAttenteRepository::class)]
#[ORM\Table(name: 'liste_attente')]
class ListeAttente
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $ID_Attente = null;

    public function getID_Attente(): ?int
    {
        return $this->ID_Attente;
    }

    public function setID_Attente(int $ID_Attente): self
    {
        $this->ID_Attente = $ID_Attente;
        return $this;
    }

    #[ORM\ManyToOne(targetEntity: Evenement::class, inversedBy: 'listeAttentes')]
    #[ORM\JoinColumn(name: 'ID_Evenement', referencedColumnName: 'ID_Evenement')]
    private ?Evenement $evenement = null;

    public function getEvenement(): ?Evenement
    {
        return $this->evenement;
    }

    public function setEvenement(?Evenement $evenement): self
    {
        $this->evenement = $evenement;
        return $this;
    }

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $ID_Activite = null;

    public function getID_Activite(): ?int
    {
        return $this->ID_Activite;
    }

    public function setID_Activite(?int $ID_Activite): self
    {
        $this->ID_Activite = $ID_Activite;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $nom_complet = null;

    public function getNom_complet(): ?string
    {
        return $this->nom_complet;
    }

    public function setNom_complet(?string $nom_complet): self
    {
        $this->nom_complet = $nom_complet;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $email = null;

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): self
    {
        $this->email = $email;
        return $this;
    }

    #[ORM\Column(type: 'datetime', nullable: false)]
    private ?\DateTimeInterface $date_demande = null;

    public function getDate_demande(): ?\DateTimeInterface
    {
        return $this->date_demande;
    }

    public function setDate_demande(\DateTimeInterface $date_demande): self
    {
        $this->date_demande = $date_demande;
        return $this;
    }

    public function getIDAttente(): ?int
    {
        return $this->ID_Attente;
    }

    public function getIDActivite(): ?int
    {
        return $this->ID_Activite;
    }

    public function setIDActivite(?int $ID_Activite): static
    {
        $this->ID_Activite = $ID_Activite;

        return $this;
    }

    public function getNomComplet(): ?string
    {
        return $this->nom_complet;
    }

    public function setNomComplet(?string $nom_complet): static
    {
        $this->nom_complet = $nom_complet;

        return $this;
    }

    public function getDateDemande(): ?\DateTime
    {
        return $this->date_demande;
    }

    public function setDateDemande(\DateTime $date_demande): static
    {
        $this->date_demande = $date_demande;

        return $this;
    }

}
