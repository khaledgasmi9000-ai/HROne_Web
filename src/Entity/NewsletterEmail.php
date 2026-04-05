<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use App\Repository\NewsletterEmailRepository;

#[ORM\Entity(repositoryClass: NewsletterEmailRepository::class)]
#[ORM\Table(name: 'newsletter_emails')]
class NewsletterEmail
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): self
    {
        $this->id = $id;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $email = null;

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;
        return $this;
    }

    #[ORM\Column(type: 'datetime', nullable: false)]
    private ?\DateTimeInterface $subscribed_at = null;

    public function getSubscribed_at(): ?\DateTimeInterface
    {
        return $this->subscribed_at;
    }

    public function setSubscribed_at(\DateTimeInterface $subscribed_at): self
    {
        $this->subscribed_at = $subscribed_at;
        return $this;
    }

    public function getSubscribedAt(): ?\DateTime
    {
        return $this->subscribed_at;
    }

    public function setSubscribedAt(\DateTime $subscribed_at): static
    {
        $this->subscribed_at = $subscribed_at;

        return $this;
    }

}
