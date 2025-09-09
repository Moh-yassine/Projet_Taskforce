<?php

namespace App\Entity;

use App\Repository\SkillRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: SkillRepository::class)]
class Skill
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['skill:read', 'task:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['skill:read', 'task:read'])]
    private ?string $name = null;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(['skill:read'])]
    private ?string $description = null;

    #[ORM\ManyToMany(targetEntity: Task::class, mappedBy: 'skills')]
    private Collection $tasks;

    #[ORM\ManyToMany(targetEntity: User::class, mappedBy: 'skills')]
    private Collection $users;

    #[ORM\OneToMany(mappedBy: 'skill', targetEntity: UserSkill::class, cascade: ['persist', 'remove'])]
    private Collection $userSkills;

    public function __construct()
    {
        $this->tasks = new ArrayCollection();
        $this->users = new ArrayCollection();
        $this->userSkills = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;
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

    /**
     * @return Collection<int, Task>
     */
    public function getTasks(): Collection
    {
        return $this->tasks;
    }

    public function addTask(Task $task): static
    {
        if (!$this->tasks->contains($task)) {
            $this->tasks->add($task);
            $task->addSkill($this);
        }
        return $this;
    }

    public function removeTask(Task $task): static
    {
        if ($this->tasks->removeElement($task)) {
            $task->removeSkill($this);
        }
        return $this;
    }

    /**
     * @return Collection<int, User>
     */
    public function getUsers(): Collection
    {
        return $this->users;
    }

    public function addUser(User $user): static
    {
        if (!$this->users->contains($user)) {
            $this->users->add($user);
            $user->addSkill($this);
        }
        return $this;
    }

    public function removeUser(User $user): static
    {
        if ($this->users->removeElement($user)) {
            $user->removeSkill($this);
        }
        return $this;
    }

    /**
     * @return Collection<int, UserSkill>
     */
    public function getUserSkills(): Collection
    {
        return $this->userSkills;
    }

    public function addUserSkill(UserSkill $userSkill): static
    {
        if (!$this->userSkills->contains($userSkill)) {
            $this->userSkills->add($userSkill);
            $userSkill->setSkill($this);
        }
        return $this;
    }

    public function removeUserSkill(UserSkill $userSkill): static
    {
        if ($this->userSkills->removeElement($userSkill)) {
            if ($userSkill->getSkill() === $this) {
                $userSkill->setSkill(null);
            }
        }
        return $this;
    }
}
