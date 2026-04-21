<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

use App\Repository\ParticipationEvenementRepository;

#[ORM\Entity(repositoryClass: ParticipationEvenementRepository::class)]
#[ORM\Table(name: 'participation_evenement')]
class ParticipationEvenement
{
    #[ORM\Id]
    #[ORM\Column(name: 'ID_Participant', type: 'integer')]
    private ?int $ID_Participant = null;

    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: Evenement::class, inversedBy: 'participationEvenements')]
    #[ORM\JoinColumn(name: 'ID_Evenement', referencedColumnName: 'ID_Evenement', nullable: false)]
    private ?Evenement $evenement = null;

    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: Activite::class)]
    #[ORM\JoinColumn(name: 'ID_Activite', referencedColumnName: 'ID_Activite', nullable: false)]
    private ?Activite $activite = null;

    #[ORM\Column(name: 'Num_Ordre_Participation', type: 'integer', nullable: true)]
    private ?int $Num_Ordre_Participation = null;

    #[ORM\Column(name: 'nom_complet', type: 'string', length: 255)]
    #[Assert\NotBlank(message: 'Le nom complet est obligatoire')]
    #[Assert\Length(min: 2, max: 50)]
    private ?string $nomComplet = null;

    #[ORM\Column(name: 'email', type: 'string', length: 255)]
    #[Assert\NotBlank(message: "L'email est obligatoire")]
    #[Assert\Email(message: "L'email transmis n'est pas valide")]
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

    public function getActivite(): ?Activite
    {
        return $this->activite;
    }

    public function setActivite(?Activite $activite): self
    {
        $this->activite = $activite;
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
