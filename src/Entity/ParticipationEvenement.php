<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use App\Repository\ParticipationEvenementRepository;

#[ORM\Entity(repositoryClass: ParticipationEvenementRepository::class)]
#[ORM\Table(name: 'participation_evenement')]
class ParticipationEvenement
{
      #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: Evenement::class, inversedBy: 'participationEvenements')]
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

      #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: Activite::class, inversedBy: 'participationEvenements')]
    #[ORM\JoinColumn(name: 'ID_Activite', referencedColumnName: 'ID_Activite')]
    private ?Activite $activite = null;

    public function getActivite(): ?Activite
    {
        return $this->activite;
    }

    public function setActivite(?Activite $activite): self
    {
        $this->activite = $activite;
        return $this;
    }

      #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: Utilisateur::class, inversedBy: 'participationEvenements')]
    #[ORM\JoinColumn(name: 'ID_Participant', referencedColumnName: 'ID_UTILISATEUR')]
    private ?Utilisateur $utilisateur = null;

    public function getUtilisateur(): ?Utilisateur
    {
        return $this->utilisateur;
    }

    public function setUtilisateur(?Utilisateur $utilisateur): self
    {
        $this->utilisateur = $utilisateur;
        return $this;
    }

    #[ORM\ManyToOne(targetEntity: Ordre::class, inversedBy: 'participationEvenements')]
    #[ORM\JoinColumn(name: 'Num_Ordre_Participation', referencedColumnName: 'Num_Ordre')]
    private ?Ordre $ordre = null;

    public function getOrdre(): ?Ordre
    {
        return $this->ordre;
    }

    public function setOrdre(?Ordre $ordre): self
    {
        $this->ordre = $ordre;
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

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $mode_paiement = null;

    public function getMode_paiement(): ?string
    {
        return $this->mode_paiement;
    }

    public function setMode_paiement(?string $mode_paiement): self
    {
        $this->mode_paiement = $mode_paiement;
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

    public function getModePaiement(): ?string
    {
        return $this->mode_paiement;
    }

    public function setModePaiement(?string $mode_paiement): static
    {
        $this->mode_paiement = $mode_paiement;

        return $this;
    }

}
