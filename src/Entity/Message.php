<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use App\Repository\MessageRepository;

#[ORM\Entity(repositoryClass: MessageRepository::class)]
#[ORM\Table(name: 'message')]
class Message
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $ID_Message = null;

    public function getID_Message(): ?int
    {
        return $this->ID_Message;
    }

    public function setID_Message(int $ID_Message): self
    {
        $this->ID_Message = $ID_Message;
        return $this;
    }

    // #[ORM\ManyToOne(targetEntity: Chat::class, inversedBy: 'messages')]
    // #[ORM\JoinColumn(name: 'ID_Chat', referencedColumnName: 'ID_Chat')]
    // private ?Chat $chat = null;

    // public function getChat(): ?Chat
    // {
    //     return $this->chat;
    // }

    // public function setChat(?Chat $chat): self
    // {
    //     $this->chat = $chat;
    //     return $this;
    // }

    #[ORM\ManyToOne(targetEntity: Utilisateur::class, inversedBy: 'messages')]
    #[ORM\JoinColumn(name: 'ID_Sender', referencedColumnName: 'ID_UTILISATEUR')]
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

    #[ORM\Column(type: 'text', nullable: false)]
    private ?string $Contenue = null;

    public function getContenue(): ?string
    {
        return $this->Contenue;
    }

    public function setContenue(string $Contenue): self
    {
        $this->Contenue = $Contenue;
        return $this;
    }

    #[ORM\Column(type: 'integer', nullable: false)]
    private ?int $Status = null;

    public function getStatus(): ?int
    {
        return $this->Status;
    }

    public function setStatus(int $Status): self
    {
        $this->Status = $Status;
        return $this;
    }

    public function getIDMessage(): ?int
    {
        return $this->ID_Message;
    }

}
