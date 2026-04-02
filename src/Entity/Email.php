<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use App\Repository\EmailRepository;

#[ORM\Entity(repositoryClass: EmailRepository::class)]
#[ORM\Table(name: 'email')]
class Email
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $ID_Email = null;

    public function getID_Email(): ?int
    {
        return $this->ID_Email;
    }

    public function setID_Email(int $ID_Email): self
    {
        $this->ID_Email = $ID_Email;
        return $this;
    }

    #[ORM\ManyToOne(targetEntity: Utilisateur::class, inversedBy: 'emails')]
    #[ORM\JoinColumn(name: 'ID_Receiver', referencedColumnName: 'ID_UTILISATEUR')]
    private ?Utilisateur $utilisateurReceiver = null;

    public function getUtilisateurReceiver(): ?Utilisateur
    {
        return $this->utilisateurReceiver;
    }

    public function setUtilisateurReceiver(?Utilisateur $utilisateurReceiver): self
    {
        $this->utilisateurReceiver = $utilisateurReceiver;
        return $this;
    }

    #[ORM\ManyToOne(targetEntity: Utilisateur::class, inversedBy: 'emails')]
    #[ORM\JoinColumn(name: 'ID_Sender', referencedColumnName: 'ID_UTILISATEUR')]
    private ?Utilisateur $utilisateurSender = null;

    public function getUtilisateurSender(): ?Utilisateur
    {
        return $this->utilisateurSender;
    }

    public function setUtilisateurSender(?Utilisateur $utilisateurSender): self
    {
        $this->utilisateurSender = $utilisateurSender;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $Objet = null;

    public function getObjet(): ?string
    {
        return $this->Objet;
    }

    public function setObjet(?string $Objet): self
    {
        $this->Objet = $Objet;
        return $this;
    }

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $Contenue = null;

    public function getContenue(): ?string
    {
        return $this->Contenue;
    }

    public function setContenue(?string $Contenue): self
    {
        $this->Contenue = $Contenue;
        return $this;
    }

    #[ORM\ManyToOne(targetEntity: Ordre::class, inversedBy: 'emails')]
    #[ORM\JoinColumn(name: 'Num_Ordre_Envoi', referencedColumnName: 'Num_Ordre')]
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

    #[ORM\Column(type: 'integer', nullable: false)]
    private ?int $Status_Mail = null;

    public function getStatus_Mail(): ?int
    {
        return $this->Status_Mail;
    }

    public function setStatus_Mail(int $Status_Mail): self
    {
        $this->Status_Mail = $Status_Mail;
        return $this;
    }

    public function getIDEmail(): ?int
    {
        return $this->ID_Email;
    }

    public function getStatusMail(): ?int
    {
        return $this->Status_Mail;
    }

    public function setStatusMail(int $Status_Mail): static
    {
        $this->Status_Mail = $Status_Mail;

        return $this;
    }

}
