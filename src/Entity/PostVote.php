<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use App\Repository\PostVoteRepository;

#[ORM\Entity(repositoryClass: PostVoteRepository::class)]
#[ORM\Table(name: 'post_votes')]
#[ORM\Index(columns: ['post_id'], name: 'idx_post_votes_post')]
#[ORM\Index(columns: ['user_id'], name: 'idx_post_votes_user')]
class PostVote
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Post::class, inversedBy: 'votes')]
    #[ORM\JoinColumn(name: 'post_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?Post $post = null;

    #[ORM\ManyToOne(targetEntity: Utilisateur::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'ID_UTILISATEUR', nullable: false, onDelete: 'CASCADE')]
    private ?Utilisateur $user = null;

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $vote_type = null;

    #[ORM\Column(type: 'datetime', nullable: false)]
    private ?\DateTimeInterface $created_at = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): self
    {
        $this->id = $id;
        return $this;
    }

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

    public function getVoteType(): ?string
    {
        return $this->vote_type;
    }

    public function setVoteType(string $vote_type): static
    {
        $this->vote_type = $vote_type;
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
    public function getPost_id(): ?int
    {
        return $this->post?->getId();
    }

    public function setPost_id(int $post_id): self
    {
        // For backwards compatibility - this method does nothing in new design
        return $this;
    }

    public function getPostId(): ?int
    {
        return $this->post?->getId();
    }

    public function setPostId(int $post_id): static
    {
        return $this;
    }

    public function getUser_id(): ?int
    {
        return $this->user?->getID_UTILISATEUR();
    }

    public function setUser_id(int $user_id): self
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

    public function getVote_type(): ?string
    {
        return $this->vote_type;
    }

    public function setVote_type(string $vote_type): self
    {
        $this->vote_type = $vote_type;
        return $this;
    }

    public function getCreated_at(): ?\DateTimeInterface
    {
        return $this->created_at;
    }

    public function setCreated_at(\DateTimeInterface $created_at): self
    {
        $this->created_at = $created_at;
        return $this;
    }
}
