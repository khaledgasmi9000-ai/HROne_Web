<?php

namespace App\Entity;

use App\Repository\PostFavoriteRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PostFavoriteRepository::class)]
#[ORM\Table(name: 'post_favorites')]
#[ORM\Index(columns: ['user_id'], name: 'idx_post_favorites_user')]
#[ORM\Index(columns: ['post_id'], name: 'idx_post_favorites_post')]
class PostFavorite
{
    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: Post::class, inversedBy: 'favorites')]
    #[ORM\JoinColumn(name: 'post_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?Post $post = null;

    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: Utilisateur::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'ID_UTILISATEUR', nullable: false, onDelete: 'CASCADE')]
    private ?Utilisateur $user = null;

    #[ORM\Column(type: 'datetime')]
    private ?\DateTimeInterface $created_at = null;

    public function getPost(): ?Post
    {
        return $this->post;
    }

    public function setPost(?Post $post): static
    {
        $this->post = $post;
        return $this;
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

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->created_at;
    }

    public function setCreatedAt(\DateTimeInterface $created_at): static
    {
        $this->created_at = $created_at;
        return $this;
    }

    // Backwards compatibility methods
    public function getPostId(): ?int
    {
        return $this->post?->getId();
    }

    public function setPostId(int $post_id): static
    {
        return $this;
    }

    public function getUserId(): ?int
    {
        return $this->user?->getID_UTILISATEUR();
    }

    public function setUserId(int $user_id): static
    {
        return $this;
    }
}
