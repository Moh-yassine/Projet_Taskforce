<?php

namespace App\Entity;

use App\Repository\WorkloadRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: WorkloadRepository::class)]
class Workload
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['workload:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'workloads')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['workload:read'])]
    private ?User $user = null;

    #[ORM\Column(type: 'date')]
    #[Groups(['workload:read', 'workload:write'])]
    private ?\DateTimeInterface $date = null;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    #[Groups(['workload:read', 'workload:write'])]
    private ?int $assignedTasks = 0;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    #[Groups(['workload:read', 'workload:write'])]
    private ?int $completedTasks = 0;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    #[Groups(['workload:read', 'workload:write'])]
    private ?int $estimatedHours = 0;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    #[Groups(['workload:read', 'workload:write'])]
    private ?int $actualHours = 0;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    #[Groups(['workload:read', 'workload:write'])]
    private ?int $workloadPercentage = 0;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    #[Groups(['workload:read', 'workload:write'])]
    private ?bool $isOverloaded = false;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $updatedAt = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;
        return $this;
    }

    public function getDate(): ?\DateTimeInterface
    {
        return $this->date;
    }

    public function setDate(\DateTimeInterface $date): static
    {
        $this->date = $date;
        return $this;
    }

    public function getAssignedTasks(): ?int
    {
        return $this->assignedTasks;
    }

    public function setAssignedTasks(int $assignedTasks): static
    {
        $this->assignedTasks = $assignedTasks;
        return $this;
    }

    public function getCompletedTasks(): ?int
    {
        return $this->completedTasks;
    }

    public function setCompletedTasks(int $completedTasks): static
    {
        $this->completedTasks = $completedTasks;
        return $this;
    }

    public function getEstimatedHours(): ?int
    {
        return $this->estimatedHours;
    }

    public function setEstimatedHours(int $estimatedHours): static
    {
        $this->estimatedHours = $estimatedHours;
        return $this;
    }

    public function getActualHours(): ?int
    {
        return $this->actualHours;
    }

    public function setActualHours(int $actualHours): static
    {
        $this->actualHours = $actualHours;
        return $this;
    }

    public function getWorkloadPercentage(): ?int
    {
        return $this->workloadPercentage;
    }

    public function setWorkloadPercentage(int $workloadPercentage): static
    {
        $this->workloadPercentage = $workloadPercentage;
        return $this;
    }

    public function isOverloaded(): ?bool
    {
        return $this->isOverloaded;
    }

    public function setIsOverloaded(bool $isOverloaded): static
    {
        $this->isOverloaded = $isOverloaded;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    #[ORM\PrePersist]
    public function setCreatedAtValue(): void
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    #[ORM\PreUpdate]
    public function setUpdatedAtValue(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }
}
