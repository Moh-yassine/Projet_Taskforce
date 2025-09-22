<?php

namespace App\Tests\Entity;

use App\Entity\Skill;
use PHPUnit\Framework\TestCase;

class SkillTest extends TestCase
{
    public function testSkillCreation(): void
    {
        $skill = new Skill();
        
        $this->assertInstanceOf(Skill::class, $skill);
        $this->assertNull($skill->getId());
    }

    public function testSkillName(): void
    {
        $skill = new Skill();
        $name = 'PHP';
        
        $skill->setName($name);
        
        $this->assertEquals($name, $skill->getName());
    }

    public function testSkillDescription(): void
    {
        $skill = new Skill();
        $description = 'PHP programming language';
        
        $skill->setDescription($description);
        
        $this->assertEquals($description, $skill->getDescription());
    }

    public function testSkillTasks(): void
    {
        $skill = new Skill();
        
        $this->assertInstanceOf(\Doctrine\Common\Collections\Collection::class, $skill->getTasks());
        $this->assertCount(0, $skill->getTasks());
    }

    public function testSkillUsers(): void
    {
        $skill = new Skill();
        
        $this->assertInstanceOf(\Doctrine\Common\Collections\Collection::class, $skill->getUsers());
        $this->assertCount(0, $skill->getUsers());
    }
}
