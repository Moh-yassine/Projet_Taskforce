<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
#[ORM\HasLifecycleCallbacks]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['task:read', 'user:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 180, unique: true)]
    #[Groups(['user:read'])]
    #[Assert\NotBlank(message: 'L\'email est requis')]
    #[Assert\Email(message: 'L\'email doit être valide')]
    private ?string $email = null;

    #[ORM\Column]
    #[Groups(['user:read'])]
    private array $roles = [];

    #[ORM\Column(length: 255)]
    #[Groups(['task:read', 'user:read'])]
    #[Assert\NotBlank(message: 'Le prénom est requis')]
    #[Assert\Length(min: 2, max: 255, minMessage: 'Le prénom doit contenir au moins 2 caractères', maxMessage: 'Le prénom ne peut pas dépasser 255 caractères')]
    private ?string $firstName = null;

    #[ORM\Column(length: 255)]
    #[Groups(['task:read', 'user:read'])]
    #[Assert\NotBlank(message: 'Le nom est requis')]
    #[Assert\Length(min: 2, max: 255, minMessage: 'Le nom doit contenir au moins 2 caractères', maxMessage: 'Le nom ne peut pas dépasser 255 caractères')]
    private ?string $lastName = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $company = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $initialRole = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Le mot de passe est requis')]
    #[Assert\Length(min: 8, minMessage: 'Le mot de passe doit contenir au moins 8 caractères')]
    #[Assert\Regex(
        pattern: '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]/',
        message: 'Le mot de passe doit contenir au moins une minuscule, une majuscule, un chiffre et un caractère spécial'
    )]
    private ?string $password = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\OneToMany(mappedBy: 'projectManager', targetEntity: Project::class)]
    private Collection $managedProjects;


    #[ORM\ManyToMany(targetEntity: Project::class, inversedBy: 'teamMembers')]
    #[ORM\JoinTable(name: 'user_project')]
    private Collection $assignedProjects;

    #[ORM\ManyToMany(targetEntity: Skill::class, inversedBy: 'users')]
    #[ORM\JoinTable(name: 'user_skill')]
    private Collection $skills;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: UserSkill::class, cascade: ['persist', 'remove'])]
    private Collection $userSkills;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: Workload::class, cascade: ['persist', 'remove'])]
    private Collection $workloads;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: Notification::class, cascade: ['persist', 'remove'])]
    private Collection $notifications;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: Subscription::class, cascade: ['persist', 'remove'])]
    private Collection $subscriptions;

    public function __construct()
    {
        $this->managedProjects = new ArrayCollection();
        $this->assignedProjects = new ArrayCollection();
        $this->skills = new ArrayCollection();
        $this->userSkills = new ArrayCollection();
        $this->workloads = new ArrayCollection();
        $this->notifications = new ArrayCollection();
        $this->subscriptions = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;
        return $this;
    }

    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    public function getRoles(): array
    {
        $roles = $this->roles;
        $roles[] = 'ROLE_USER';
        return array_unique($roles);
    }

    public function setRoles(array $roles): static
    {
        $this->roles = $roles;
        return $this;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): static
    {
        $this->firstName = $firstName;
        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): static
    {
        $this->lastName = $lastName;
        return $this;
    }

    public function getCompany(): ?string
    {
        return $this->company;
    }

    public function setCompany(?string $company): static
    {
        $this->company = $company;
        return $this;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;
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

    public function eraseCredentials(): void
    {
    }

    public function isProjectManager(): bool
    {
        return in_array('ROLE_PROJECT_MANAGER', $this->roles);
    }

    public function isManager(): bool
    {
        return in_array('ROLE_MANAGER', $this->roles);
    }

    public function isCollaborator(): bool
    {
        return in_array('ROLE_COLLABORATOR', $this->roles);
    }

    public function getPrimaryRole(): string
    {
        if ($this->isProjectManager()) {
            return 'ROLE_PROJECT_MANAGER';
        }
        if ($this->isManager()) {
            return 'ROLE_MANAGER';
        }
        if ($this->isCollaborator()) {
            return 'ROLE_COLLABORATOR';
        }
        return 'ROLE_USER';
    }

    public function hasRole(string $role): bool
    {
        return in_array($role, $this->roles);
    }

    public function canManageProjects(): bool
    {
        return $this->isProjectManager();
    }

    public function canSuperviseTasks(): bool
    {
        return $this->isProjectManager() || $this->isManager();
    }

    public function canAssignTasks(): bool
    {
        return $this->isProjectManager();
    }

    public function canViewAllTasks(): bool
    {
        return $this->isProjectManager() || $this->isManager();
    }

    public function canEditTasks(): bool
    {
        return $this->isProjectManager() || $this->isManager();
    }

    public function canViewReports(): bool
    {
        return $this->isProjectManager() || $this->isManager();
    }

    public function getFullName(): string
    {
        return $this->firstName . ' ' . $this->lastName;
    }

    public function getInitialRole(): ?string
    {
        return $this->initialRole;
    }

    public function setInitialRole(?string $initialRole): static
    {
        $this->initialRole = $initialRole;
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

    public function getManagedProjects(): Collection
    {
        return $this->managedProjects;
    }

    public function addManagedProject(Project $project): static
    {
        if (!$this->managedProjects->contains($project)) {
            $this->managedProjects->add($project);
            $project->setProjectManager($this);
        }
        return $this;
    }

    public function removeManagedProject(Project $project): static
    {
        if ($this->managedProjects->removeElement($project)) {
            if ($project->getProjectManager() === $this) {
                $project->setProjectManager(null);
            }
        }
        return $this;
    }

    public function getAssignedProjects(): Collection
    {
        return $this->assignedProjects;
    }

    public function addAssignedProject(Project $project): static
    {
        if (!$this->assignedProjects->contains($project)) {
            $this->assignedProjects->add($project);
        }
        return $this;
    }

    public function removeAssignedProject(Project $project): static
    {
        $this->assignedProjects->removeElement($project);
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
            $userSkill->setUser($this);
        }
        return $this;
    }

    public function removeUserSkill(UserSkill $userSkill): static
    {
        if ($this->userSkills->removeElement($userSkill)) {
            if ($userSkill->getUser() === $this) {
                $userSkill->setUser(null);
            }
        }
        return $this;
    }

    /**
     * @return Collection<int, Workload>
     */
    public function getWorkloads(): Collection
    {
        return $this->workloads;
    }

    public function addWorkload(Workload $workload): static
    {
        if (!$this->workloads->contains($workload)) {
            $this->workloads->add($workload);
            $workload->setUser($this);
        }
        return $this;
    }

    public function removeWorkload(Workload $workload): static
    {
        if ($this->workloads->removeElement($workload)) {
            if ($workload->getUser() === $this) {
                $workload->setUser(null);
            }
        }
        return $this;
    }

    /**
     * @return Collection<int, Notification>
     */
    public function getNotifications(): Collection
    {
        return $this->notifications;
    }

    public function addNotification(Notification $notification): static
    {
        if (!$this->notifications->contains($notification)) {
            $this->notifications->add($notification);
            $notification->setUser($this);
        }
        return $this;
    }

    public function removeNotification(Notification $notification): static
    {
        if ($this->notifications->removeElement($notification)) {
            if ($notification->getUser() === $this) {
                $notification->setUser(null);
            }
        }
        return $this;
    }

    /**
     * @return Collection<int, Subscription>
     */
    public function getSubscriptions(): Collection
    {
        return $this->subscriptions;
    }

    public function addSubscription(Subscription $subscription): static
    {
        if (!$this->subscriptions->contains($subscription)) {
            $this->subscriptions->add($subscription);
            $subscription->setUser($this);
        }
        return $this;
    }

    public function removeSubscription(Subscription $subscription): static
    {
        if ($this->subscriptions->removeElement($subscription)) {
            if ($subscription->getUser() === $this) {
                $subscription->setUser(null);
            }
        }
        return $this;
    }

    public function hasActivePremiumSubscription(): bool
    {
        foreach ($this->subscriptions as $subscription) {
            if ($subscription->isPremium()) {
                return true;
            }
        }
        return false;
    }

    public function getActivePremiumSubscription(): ?Subscription
    {
        foreach ($this->subscriptions as $subscription) {
            if ($subscription->isPremium()) {
                return $subscription;
            }
        }
        return null;
    }
}
