<?php

namespace App\Entity;

use App\Repository\UserSkillRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: UserSkillRepository::class)]
#[ORM\Table(name: 'user_skill_level')]
#[ORM\UniqueConstraint(name: 'unique_user_skill_level', columns: ['user_id', 'skill_id'])]
class UserSkill
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['user_skill:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'userSkills')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['user_skill:read'])]
    private ?User $user = null;

    #[ORM\ManyToOne(targetEntity: Skill::class, inversedBy: 'userSkills')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['user_skill:read'])]
    private ?Skill $skill = null;

    #[ORM\Column(type: 'integer', options: ['default' => 1])]
    #[Groups(['user_skill:read', 'user_skill:write'])]
    private ?int $level = 1;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    #[Groups(['user_skill:read', 'user_skill:write'])]
    private ?int $experience = 0;

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

    public function getSkill(): ?Skill
    {
        return $this->skill;
    }

    public function setSkill(?Skill $skill): static
    {
        $this->skill = $skill;
        return $this;
    }

    public function getLevel(): ?int
    {
        return $this->level;
    }

    public function setLevel(int $level): static
    {
        $this->level = $level;
        return $this;
    }

    public function getExperience(): ?int
    {
        return $this->experience;
    }

    public function setExperience(int $experience): static
    {
        $this->experience = $experience;
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
