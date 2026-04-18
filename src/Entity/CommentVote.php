<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use App\Repository\CommentVoteRepository;

#[ORM\Entity(repositoryClass: CommentVoteRepository::class)]
#[ORM\Table(name: 'comment_votes')]
#[ORM\Index(columns: ['comment_id'], name: 'idx_comment_votes_comment')]
#[ORM\Index(columns: ['user_id'], name: 'idx_comment_votes_user')]
class CommentVote
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Comment::class, inversedBy: 'votes')]
    #[ORM\JoinColumn(name: 'comment_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?Comment $comment = null;

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

    public function getComment(): ?Comment
    {
        return $this->comment;
    }

    public function setComment(?Comment $comment): static
    {
        $this->comment = $comment;
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
    public function getComment_id(): ?int
    {
        return $this->comment?->getId();
    }

    public function setComment_id(int $comment_id): self
    {
        return $this;
    }

    public function getCommentId(): ?int
    {
        return $this->comment?->getId();
    }

    public function setCommentId(int $comment_id): static
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

    public function setCreated_at(\DateTime $created_at): self
    {
        $this->created_at = $created_at;
        return $this;
    }

}
