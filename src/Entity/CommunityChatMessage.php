<?php

namespace App\Entity;

use App\Repository\CommunityChatMessageRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CommunityChatMessageRepository::class)]
#[ORM\Table(name: 'community_chat_messages')]
#[ORM\Index(columns: ['user_id'], name: 'idx_ccm_user')]
#[ORM\Index(columns: ['created_at'], name: 'idx_ccm_created')]
class CommunityChatMessage
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Utilisateur::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'ID_UTILISATEUR', nullable: false)]
    private ?Utilisateur $user = null;

    #[ORM\Column(type: 'text')]
    private ?string $content = null;

    #[ORM\Column(type: 'datetime')]
    private ?\DateTimeInterface $created_at = null;

    #[ORM\Column(type: 'boolean', options: ['default' => true])]
    private ?bool $is_active = true;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?Utilisateur
    {
        return $this->user;
    }

    public function setUser(?Utilisateur $user): static
    {
        $this->user = $user;
        return $this;
    }

    public function getUserId(): ?int
    {
        return $this->user?->getID_UTILISATEUR();
    }

    public function setUserId(int $userId): static
    {
        // This is a convenience method - in real usage, set the user via setUser()
        return $this;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(string $content): static
    {
        $this->content = $content;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->created_at;
    }

    public function setCreatedAt(\DateTimeInterface $created_at): static
    {
        $this->created_at = $created_at;
        return $this;
    }

    public function isActive(): ?bool
    {
        return $this->is_active;
    }

    public function setIsActive(bool $is_active): static
    {
        $this->is_active = $is_active;
        return $this;
    }
}
