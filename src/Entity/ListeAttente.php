<?php

namespace App\Entity;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: \App\Repository\ListeAttenteRepository::class)]
#[ORM\Table(name: 'liste_attente')]
class ListeAttente
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'ID_Attente', type: 'integer')]
    private ?int $ID_Attente = null;

    #[ORM\ManyToOne(targetEntity: Evenement::class)]
    #[ORM\JoinColumn(name: 'ID_Evenement', referencedColumnName: 'ID_Evenement', nullable: true)]
    private ?Evenement $evenement = null;

    #[ORM\Column(name: 'ID_Activite', type: 'integer', nullable: true)]
    private ?int $idActivite = null;

    #[ORM\Column(name: 'nom_complet', type: 'string', length: 255)]
    private ?string $nomComplet = null;

    #[ORM\Column(name: 'email', type: 'string', length: 255)]
    private ?string $email = null;

    #[ORM\Column(name: 'date_demande', type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $dateDemande = null;


    public function getIdAttente(): ?int
    {
        return $this->ID_Attente;
    }

    public function setIdAttente(int $id): self
    {
        $this->ID_Attente = $id;
        return $this;
    }

    public function getEvenement(): ?Evenement
    {
        return $this->evenement;
    }

    public function setEvenement(?Evenement $evenement): self
    {
        $this->evenement = $evenement;
        return $this;
    }

    public function getIdActivite(): ?int
    {
        return $this->idActivite;
    }

    public function setIdActivite(?int $idActivite): self
    {
        $this->idActivite = $idActivite;
        return $this;
    }

    public function getNomComplet(): ?string
    {
        return $this->nomComplet;
    }

    public function setNomComplet(string $nomComplet): self
    {
        $this->nomComplet = $nomComplet;
        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;
        return $this;
    }

    public function getDateDemande(): ?\DateTimeInterface
    {
        return $this->dateDemande;
    }

    public function setDateDemande(?\DateTimeInterface $dateDemande): self
    {
        $this->dateDemande = $dateDemande;
        return $this;
    }
}
