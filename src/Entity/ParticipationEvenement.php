<?php

namespace App\Entity;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: \App\Repository\ParticipationEvenementRepository::class)]
#[ORM\Table(name: 'participation_evenement')]
class ParticipationEvenement
{
    // -------------------------------------------------------------------------
    // PROPRIÉTÉS ET RÈGLES DE VALIDATION (CONTRÔLE DE SAISIE)
    // -------------------------------------------------------------------------
    // Note : Le HTML5 gère le contrôle dans le navigateur (Client).
    // Les annotations #[Assert] ci-dessous gèrent le contrôle dans PHP (Serveur).
    // C'est cette "double sécurité" qui rend votre application professionnelle.
    // -------------------------------------------------------------------------

    #[ORM\Id]
    #[ORM\Column(name: 'ID_Participant', type: 'integer')]
    // RÔLE : Identifiant unique du participant dans la base de données.
    private ?int $ID_Participant = null;

    #[ORM\ManyToOne(targetEntity: Evenement::class)]
    #[ORM\JoinColumn(name: 'ID_Evenement', referencedColumnName: 'ID_Evenement', nullable: false)]
    // RÔLE : L'événement auquel la personne s'inscrit.
    private ?Evenement $evenement = null;

    #[ORM\ManyToMany(targetEntity: Activite::class)]
    #[ORM\JoinTable(name: 'participation_evenement_activite')]
    #[ORM\JoinColumn(name: 'participation_id', referencedColumnName: 'ID_Participant', onDelete: 'CASCADE')]
    #[ORM\InverseJoinColumn(name: 'activite_id', referencedColumnName: 'ID_Activite')]
    private Collection $activites;

    public function __construct()
    {
        $this->activites = new ArrayCollection();
    }

    #[ORM\Column(name: 'Num_Ordre_Participation', type: 'integer', nullable: true)]
    private ?int $Num_Ordre_Participation = null;

    #[ORM\Column(name: 'nom_complet', type: 'string', length: 255)]
    // SÉCURITÉ : On s'assure que le champ n'est pas vide et qu'il a une taille logique
    #[Assert\NotBlank(message: 'Le nom complet est obligatoire')]
    #[Assert\Length(
        min: 2, 
        max: 50, 
        minMessage: 'Le nom est trop court (min 2 caractères)', 
        maxMessage: 'Le nom est trop long (max 50 caractères)'
    )]
    private ?string $nomComplet = null;

    #[ORM\Column(name: 'email', type: 'string', length: 255)]
    // SÉCURITÉ : Vérifie le format de l'e-mail côté PHP même si le navigateur échoue.
    #[Assert\NotBlank(message: 'L\'email est obligatoire')]
    #[Assert\Email(message: 'L\'email transmis n\'est pas valide')]
    private ?string $email = null;

    #[ORM\Column(name: 'description', type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column(name: 'mode_paiement', type: 'string', length: 50, nullable: true)]
    private ?string $modePaiement = null;


    public function getIdParticipant(): ?int
    {
        return $this->ID_Participant;
    }

    public function setIdParticipant(int $id): self
    {
        $this->ID_Participant = $id;
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

    /**
     * @return Collection<int, Activite>
     */
    public function getActivites(): Collection
    {
        return $this->activites;
    }

    public function addActivite(Activite $activite): self
    {
        if (!$this->activites->contains($activite)) {
            $this->activites->add($activite);
        }
        return $this;
    }

    public function removeActivite(Activite $activite): self
    {
        $this->activites->removeElement($activite);
        return $this;
    }

    public function getNumOrdreParticipation(): ?int
    {
        return $this->Num_Ordre_Participation;
    }

    public function setNumOrdreParticipation(?int $Num_Ordre_Participation): self
    {
        $this->Num_Ordre_Participation = $Num_Ordre_Participation;
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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function getModePaiement(): ?string
    {
        return $this->modePaiement;
    }

    public function setModePaiement(?string $modePaiement): self
    {
        $this->modePaiement = $modePaiement;
        return $this;
    }
}
