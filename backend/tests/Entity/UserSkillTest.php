<?php

namespace App\Tests\Entity;

use App\Entity\UserSkill;
use App\Entity\User;
use App\Entity\Skill;
use PHPUnit\Framework\TestCase;

class UserSkillTest extends TestCase
{
    public function testUserSkillCreation(): void
    {
        $userSkill = new UserSkill();
        
        $this->assertInstanceOf(UserSkill::class, $userSkill);
        $this->assertNull($userSkill->getId());
        $this->assertNull($userSkill->getUser());
        $this->assertNull($userSkill->getSkill());
        $this->assertEquals(1, $userSkill->getLevel()); // Default level
        $this->assertInstanceOf(\DateTimeInterface::class, $userSkill->getCreatedAt());
        $this->assertInstanceOf(\DateTimeInterface::class, $userSkill->getUpdatedAt());
    }

    public function testUserSkillUser(): void
    {
        $userSkill = new UserSkill();
        $user = new User();
        $user->setEmail('test@example.com');

        $userSkill->setUser($user);
        $this->assertEquals($user, $userSkill->getUser());
    }

    public function testUserSkillSkill(): void
    {
        $userSkill = new UserSkill();
        $skill = new Skill();
        $skill->setName('PHP');

        $userSkill->setSkill($skill);
        $this->assertEquals($skill, $userSkill->getSkill());
    }

    public function testUserSkillLevel(): void
    {
        $userSkill = new UserSkill();
        $level = 5;

        $userSkill->setLevel($level);
        $this->assertEquals($level, $userSkill->getLevel());
    }

    public function testUserSkillValidLevels(): void
    {
        $userSkill = new UserSkill();
        $validLevels = [1, 2, 3, 4, 5];

        foreach ($validLevels as $level) {
            $userSkill->setLevel($level);
            $this->assertEquals($level, $userSkill->getLevel());
        }
    }

    public function testUserSkillExperience(): void
    {
        $userSkill = new UserSkill();
        $experience = 24; // months

        $userSkill->setExperience($experience);
        $this->assertEquals($experience, $userSkill->getExperience());
    }

    public function testUserSkillCertified(): void
    {
        $userSkill = new UserSkill();
        
        $this->assertFalse($userSkill->isCertified()); // Default should be false
        
        $userSkill->setCertified(true);
        $this->assertTrue($userSkill->isCertified());
        
        $userSkill->setCertified(false);
        $this->assertFalse($userSkill->isCertified());
    }

    public function testUserSkillCertificationDate(): void
    {
        $userSkill = new UserSkill();
        $certificationDate = new \DateTime('2023-06-15');

        $userSkill->setCertificationDate($certificationDate);
        $this->assertEquals($certificationDate, $userSkill->getCertificationDate());
    }

    public function testUserSkillLastUsed(): void
    {
        $userSkill = new UserSkill();
        $lastUsed = new \DateTime('2023-10-01');

        $userSkill->setLastUsed($lastUsed);
        $this->assertEquals($lastUsed, $userSkill->getLastUsed());
    }

    public function testUserSkillCreatedAt(): void
    {
        $userSkill = new UserSkill();
        $createdAt = new \DateTime('2023-01-01 10:00:00');

        $userSkill->setCreatedAt($createdAt);
        $this->assertEquals($createdAt, $userSkill->getCreatedAt());
    }

    public function testUserSkillUpdatedAt(): void
    {
        $userSkill = new UserSkill();
        $updatedAt = new \DateTime('2023-01-02 15:30:00');

        $userSkill->setUpdatedAt($updatedAt);
        $this->assertEquals($updatedAt, $userSkill->getUpdatedAt());
    }

    public function testUserSkillIsExpert(): void
    {
        $userSkill = new UserSkill();
        
        // Level 5 should be expert
        $userSkill->setLevel(5);
        $this->assertTrue($userSkill->isExpert());
        
        // Level 4 should not be expert
        $userSkill->setLevel(4);
        $this->assertFalse($userSkill->isExpert());
        
        // Level 3 should not be expert
        $userSkill->setLevel(3);
        $this->assertFalse($userSkill->isExpert());
    }

    public function testUserSkillIsBeginner(): void
    {
        $userSkill = new UserSkill();
        
        // Level 1 should be beginner
        $userSkill->setLevel(1);
        $this->assertTrue($userSkill->isBeginner());
        
        // Level 2 should not be beginner
        $userSkill->setLevel(2);
        $this->assertFalse($userSkill->isBeginner());
    }

    public function testUserSkillIsIntermediate(): void
    {
        $userSkill = new UserSkill();
        
        // Level 2-3 should be intermediate
        $userSkill->setLevel(2);
        $this->assertTrue($userSkill->isIntermediate());
        
        $userSkill->setLevel(3);
        $this->assertTrue($userSkill->isIntermediate());
        
        // Level 1 should not be intermediate
        $userSkill->setLevel(1);
        $this->assertFalse($userSkill->isIntermediate());
        
        // Level 4-5 should not be intermediate
        $userSkill->setLevel(4);
        $this->assertFalse($userSkill->isIntermediate());
    }

    public function testUserSkillIsAdvanced(): void
    {
        $userSkill = new UserSkill();
        
        // Level 4-5 should be advanced
        $userSkill->setLevel(4);
        $this->assertTrue($userSkill->isAdvanced());
        
        $userSkill->setLevel(5);
        $this->assertTrue($userSkill->isAdvanced());
        
        // Level 1-3 should not be advanced
        $userSkill->setLevel(3);
        $this->assertFalse($userSkill->isAdvanced());
    }

    public function testUserSkillGetLevelName(): void
    {
        $userSkill = new UserSkill();
        
        $userSkill->setLevel(1);
        $this->assertEquals('Débutant', $userSkill->getLevelName());
        
        $userSkill->setLevel(2);
        $this->assertEquals('Intermédiaire', $userSkill->getLevelName());
        
        $userSkill->setLevel(3);
        $this->assertEquals('Intermédiaire', $userSkill->getLevelName());
        
        $userSkill->setLevel(4);
        $this->assertEquals('Avancé', $userSkill->getLevelName());
        
        $userSkill->setLevel(5);
        $this->assertEquals('Expert', $userSkill->getLevelName());
    }

    public function testUserSkillGetExperienceInYears(): void
    {
        $userSkill = new UserSkill();
        
        $userSkill->setExperience(12); // 12 months = 1 year
        $this->assertEquals(1.0, $userSkill->getExperienceInYears());
        
        $userSkill->setExperience(18); // 18 months = 1.5 years
        $this->assertEquals(1.5, $userSkill->getExperienceInYears());
        
        $userSkill->setExperience(6); // 6 months = 0.5 years
        $this->assertEquals(0.5, $userSkill->getExperienceInYears());
    }

    public function testUserSkillIsCertificationValid(): void
    {
        $userSkill = new UserSkill();
        
        // No certification
        $this->assertFalse($userSkill->isCertificationValid());
        
        // Valid certification (recent)
        $userSkill->setCertified(true);
        $userSkill->setCertificationDate(new \DateTime('-1 year'));
        $this->assertTrue($userSkill->isCertificationValid());
        
        // Expired certification (too old - assuming 3 years validity)
        $userSkill->setCertificationDate(new \DateTime('-4 years'));
        $this->assertFalse($userSkill->isCertificationValid());
    }

    public function testUserSkillNotes(): void
    {
        $userSkill = new UserSkill();
        $notes = 'Excellent knowledge of advanced PHP concepts and frameworks';

        $userSkill->setNotes($notes);
        $this->assertEquals($notes, $userSkill->getNotes());
    }

    public function testUserSkillToArray(): void
    {
        $userSkill = new UserSkill();
        $user = new User();
        $user->setEmail('test@example.com');
        $skill = new Skill();
        $skill->setName('PHP');
        
        $userSkill->setUser($user);
        $userSkill->setSkill($skill);
        $userSkill->setLevel(4);
        $userSkill->setExperience(24);
        $userSkill->setCertified(true);

        $array = $userSkill->toArray();
        
        $this->assertIsArray($array);
        $this->assertEquals(4, $array['level']);
        $this->assertEquals('Avancé', $array['levelName']);
        $this->assertEquals(24, $array['experience']);
        $this->assertEquals(2.0, $array['experienceInYears']);
        $this->assertTrue($array['certified']);
    }
}


