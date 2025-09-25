<?php

namespace App\Entity;

use App\Repository\TaskRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: TaskRepository::class)]
class Task
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['task:read', 'task:write'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['task:read', 'task:write'])]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['task:read', 'task:write'])]
    private ?string $description = null;

    #[ORM\Column(length: 50)]
    #[Groups(['task:read', 'task:write'])]
    private ?string $status = null;

    #[ORM\Column(length: 50)]
    #[Groups(['task:read', 'task:write'])]
    private ?string $priority = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Groups(['task:read'])]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Groups(['task:read'])]
    private ?\DateTimeInterface $updatedAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    #[Groups(['task:read', 'task:write'])]
    private ?\DateTimeInterface $dueDate = null;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    #[Groups(['task:read', 'task:write'])]
    private ?int $estimatedHours = 0;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    #[Groups(['task:read', 'task:write'])]
    private ?int $actualHours = 0;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    #[Groups(['task:read', 'task:write'])]
    private ?bool $isAutoAssigned = false;

    #[ORM\ManyToOne(targetEntity: Project::class, inversedBy: 'tasks')]
    #[ORM\JoinColumn(nullable: true)]
    #[Groups(['task:read', 'task:write'])]
    private ?Project $project = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true)]
    #[Groups(['task:read', 'task:write'])]
    private ?User $assignee = null;


    #[ORM\ManyToMany(targetEntity: Skill::class, inversedBy: 'tasks')]
    #[Groups(['task:read', 'task:write'])]
    private Collection $skills;

    #[ORM\ManyToOne(targetEntity: Column::class, inversedBy: 'tasks')]
    #[ORM\JoinColumn(nullable: true)]
    #[Groups(['task:read', 'task:write'])]
    private ?Column $column = null;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    #[Groups(['task:read', 'task:write'])]
    private ?int $position = 0;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
        $this->skills = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;
        return $this;
    }

    public function getPriority(): ?string
    {
        return $this->priority;
    }

    public function setPriority(string $priority): static
    {
        $this->priority = $priority;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeInterface $updatedAt): static
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    public function getProject(): ?Project
    {
        return $this->project;
    }

    public function setProject(?Project $project): static
    {
        $this->project = $project;
        return $this;
    }

    public function getAssignee(): ?User
    {
        return $this->assignee;
    }

    public function setAssignee(?User $assignee): static
    {
        $this->assignee = $assignee;
        return $this;
    }


    /**
     * @return Collection<int, Skill>
     */
    public function getSkills(): Collection
    {
        return $this->skills;
    }

    public function addSkill(Skill $skill): static
    {
        if (!$this->skills->contains($skill)) {
            $this->skills->add($skill);
        }
        return $this;
    }

    public function removeSkill(Skill $skill): static
    {
        $this->skills->removeElement($skill);
        return $this;
    }

    public function getDueDate(): ?\DateTimeInterface
    {
        return $this->dueDate;
    }

    public function setDueDate(?\DateTimeInterface $dueDate): static
    {
        $this->dueDate = $dueDate;
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

    public function isAutoAssigned(): ?bool
    {
        return $this->isAutoAssigned;
    }

    public function setIsAutoAssigned(bool $isAutoAssigned): static
    {
        $this->isAutoAssigned = $isAutoAssigned;
        return $this;
    }

    public function getColumn(): ?Column
    {
        return $this->column;
    }

    public function setColumn(?Column $column): static
    {
        $this->column = $column;
        return $this;
    }

    public function getPosition(): ?int
    {
        return $this->position;
    }

    public function setPosition(int $position): static
    {
        $this->position = $position;
        return $this;
    }
}
