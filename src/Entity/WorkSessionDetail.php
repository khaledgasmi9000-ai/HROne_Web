<?php

namespace App\Entity;

use App\Repository\WorkSessionDetailRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: WorkSessionDetailRepository::class)]
#[ORM\UniqueConstraint(name: "unique_session_app", columns: ["work_session_id", "app"])]
class WorkSessionDetail
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: WorkSession::class, inversedBy: 'details')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?WorkSession $workSession = null;

    #[ORM\Column(name: 'app', length: 255)]
    private string $app;

    #[ORM\Column(name: 'duration', type: 'float')]
    private float $duration;

    // Future-proof (optional)
    #[ORM\Column(name: 'tool_id', nullable: true)]
    private ?int $toolId = null;

    #[ORM\Column(name: 'percentage', type: 'float', nullable: true)]
    private ?float $percentage = null;

    // =========================
    // GETTERS & SETTERS
    // =========================

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getWorkSession(): ?WorkSession
    {
        return $this->workSession;
    }

    public function setWorkSession(?WorkSession $workSession): self
    {
        $this->workSession = $workSession;
        return $this;
    }

    public function getApp(): string
    {
        return $this->app;
    }

    public function setApp(string $app): self
    {
        $this->app = $app;
        return $this;
    }

    public function getDuration(): float
    {
        return $this->duration;
    }

    public function setDuration(float $duration): self
    {
        $this->duration = $duration;
        return $this;
    }

    public function getToolId(): ?int
    {
        return $this->toolId;
    }

    public function setToolId(?int $toolId): self
    {
        $this->toolId = $toolId;
        return $this;
    }

    public function getPercentage(): ?float
    {
        return $this->percentage;
    }

    public function setPercentage(?float $percentage): self
    {
        $this->percentage = $percentage;
        return $this;
    }
}