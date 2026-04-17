<?php

namespace App\Entity;

use App\Repository\WorkSessionRepository;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

#[ORM\Entity(repositoryClass: WorkSessionRepository::class)]
class WorkSession
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Employee::class, inversedBy: 'workSessions')]
    #[ORM\JoinColumn(name: 'employee_id', referencedColumnName: 'ID_Employe', nullable: false)]
    private ?Employee $employee = null;

    #[ORM\Column(name: 'start_time', type: 'datetime')]
    private \DateTime $startTime;

    #[ORM\Column(name: 'end_time', type: 'datetime', nullable: true)]
    private ?\DateTime $endTime = null;

    #[ORM\Column(name: 'status', length: 20)]
    private string $status;

    // Optional metrics (recommended)
    #[ORM\Column(name: 'session_duration', type: 'float', nullable: true)]
    private ?float $sessionDuration = null;

    #[ORM\Column(name: 'active_time', type: 'float', nullable: true)]
    private ?float $activeTime = null;

    #[ORM\Column(name: 'afk_time', type: 'float', nullable: true)]
    private ?float $afkTime = null;

    #[ORM\Column(name: 'unknown_time', type: 'float', nullable: true)]
    private ?float $unknownTime = null;

    #[ORM\OneToMany(mappedBy: 'workSession', targetEntity: WorkSessionDetail::class, cascade: ['persist'], orphanRemoval: true)]
    private Collection $details;

    public function __construct()
    {
        $this->details = new ArrayCollection();
    }

    // =========================
    // GETTERS & SETTERS
    // =========================

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmployee(): ?Employee
    {
        return $this->employee;
    }

    public function setEmployee(?Employee $employee): self
    {
        $this->employee = $employee;
        return $this;
    }

    public function getStartTime(): \DateTime
    {
        return $this->startTime;
    }

    public function setStartTime(\DateTime $startTime): self
    {
        $this->startTime = $startTime;
        return $this;
    }

    public function getEndTime(): ?\DateTime
    {
        return $this->endTime;
    }

    public function setEndTime(?\DateTime $endTime): self
    {
        $this->endTime = $endTime;
        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;
        return $this;
    }

    public function getSessionDuration(): ?float
    {
        return $this->sessionDuration;
    }

    public function setSessionDuration(?float $sessionDuration): self
    {
        $this->sessionDuration = $sessionDuration;
        return $this;
    }

    public function getActiveTime(): ?float
    {
        return $this->activeTime;
    }

    public function setActiveTime(?float $activeTime): self
    {
        $this->activeTime = $activeTime;
        return $this;
    }

    public function getAfkTime(): ?float
    {
        return $this->afkTime;
    }

    public function setAfkTime(?float $afkTime): self
    {
        $this->afkTime = $afkTime;
        return $this;
    }

    public function getUnknownTime(): ?float
    {
        return $this->unknownTime;
    }

    public function setUnknownTime(?float $unknownTime): self
    {
        $this->unknownTime = $unknownTime;
        return $this;
    }

    // =========================
    // RELATION HELPERS
    // =========================

    public function getDetails(): Collection
    {
        return $this->details;
    }

    public function addDetail(WorkSessionDetail $detail): self
    {
        if (!$this->details->contains($detail)) {
            $this->details[] = $detail;
            $detail->setWorkSession($this);
        }

        return $this;
    }

    public function removeDetail(WorkSessionDetail $detail): self
    {
        if ($this->details->removeElement($detail)) {
            if ($detail->getWorkSession() === $this) {
                $detail->setWorkSession(null);
            }
        }

        return $this;
    }
}