<?php

namespace App\Tests\Entity;

use App\Entity\Workload;
use App\Entity\User;
use App\Entity\Project;
use PHPUnit\Framework\TestCase;

class WorkloadTest extends TestCase
{
    public function testWorkloadCreation(): void
    {
        $workload = new Workload();
        
        $this->assertInstanceOf(Workload::class, $workload);
        $this->assertNull($workload->getId());
        $this->assertNull($workload->getUser());
        $this->assertNull($workload->getProject());
        $this->assertEquals(0, $workload->getHours());
        $this->assertNull($workload->getWeek());
        $this->assertInstanceOf(\DateTimeInterface::class, $workload->getCreatedAt());
        $this->assertInstanceOf(\DateTimeInterface::class, $workload->getUpdatedAt());
    }

    public function testWorkloadUser(): void
    {
        $workload = new Workload();
        $user = new User();
        $user->setEmail('test@example.com');

        $workload->setUser($user);
        $this->assertEquals($user, $workload->getUser());
    }

    public function testWorkloadProject(): void
    {
        $workload = new Workload();
        $project = new Project();
        $project->setName('Test Project');

        $workload->setProject($project);
        $this->assertEquals($project, $workload->getProject());
    }

    public function testWorkloadHours(): void
    {
        $workload = new Workload();
        $hours = 40;

        $workload->setHours($hours);
        $this->assertEquals($hours, $workload->getHours());
    }

    public function testWorkloadWeek(): void
    {
        $workload = new Workload();
        $week = '2023-W01';

        $workload->setWeek($week);
        $this->assertEquals($week, $workload->getWeek());
    }

    public function testWorkloadCreatedAt(): void
    {
        $workload = new Workload();
        $createdAt = new \DateTime('2023-01-01 10:00:00');

        $workload->setCreatedAt($createdAt);
        $this->assertEquals($createdAt, $workload->getCreatedAt());
    }

    public function testWorkloadUpdatedAt(): void
    {
        $workload = new Workload();
        $updatedAt = new \DateTime('2023-01-02 15:30:00');

        $workload->setUpdatedAt($updatedAt);
        $this->assertEquals($updatedAt, $workload->getUpdatedAt());
    }

    public function testWorkloadYear(): void
    {
        $workload = new Workload();
        $year = 2023;

        $workload->setYear($year);
        $this->assertEquals($year, $workload->getYear());
    }

    public function testWorkloadWeekNumber(): void
    {
        $workload = new Workload();
        $weekNumber = 15;

        $workload->setWeekNumber($weekNumber);
        $this->assertEquals($weekNumber, $workload->getWeekNumber());
    }

    public function testWorkloadStartDate(): void
    {
        $workload = new Workload();
        $startDate = new \DateTime('2023-01-02'); // Monday

        $workload->setStartDate($startDate);
        $this->assertEquals($startDate, $workload->getStartDate());
    }

    public function testWorkloadEndDate(): void
    {
        $workload = new Workload();
        $endDate = new \DateTime('2023-01-08'); // Sunday

        $workload->setEndDate($endDate);
        $this->assertEquals($endDate, $workload->getEndDate());
    }

    public function testWorkloadIsOverload(): void
    {
        $workload = new Workload();
        
        // Normal workload (40h/week)
        $workload->setHours(40);
        $this->assertFalse($workload->isOverload());
        
        // Overload (>40h/week)
        $workload->setHours(50);
        $this->assertTrue($workload->isOverload());
        
        // Heavy overload
        $workload->setHours(60);
        $this->assertTrue($workload->isOverload());
    }

    public function testWorkloadIsUnderload(): void
    {
        $workload = new Workload();
        
        // Normal workload
        $workload->setHours(40);
        $this->assertFalse($workload->isUnderload());
        
        // Underload (<35h/week)
        $workload->setHours(30);
        $this->assertTrue($workload->isUnderload());
        
        // Very low workload
        $workload->setHours(10);
        $this->assertTrue($workload->isUnderload());
    }

    public function testWorkloadUtilizationPercentage(): void
    {
        $workload = new Workload();
        
        // 100% utilization (40h standard)
        $workload->setHours(40);
        $this->assertEquals(100.0, $workload->getUtilizationPercentage());
        
        // 125% utilization (overload)
        $workload->setHours(50);
        $this->assertEquals(125.0, $workload->getUtilizationPercentage());
        
        // 75% utilization (underload)
        $workload->setHours(30);
        $this->assertEquals(75.0, $workload->getUtilizationPercentage());
        
        // 0% utilization
        $workload->setHours(0);
        $this->assertEquals(0.0, $workload->getUtilizationPercentage());
    }

    public function testWorkloadGetWeekFromDate(): void
    {
        $workload = new Workload();
        $date = new \DateTime('2023-01-15'); // Week 3 of 2023
        
        $week = $workload->getWeekFromDate($date);
        $this->assertEquals('2023-W03', $week);
    }

    public function testWorkloadSetWeekFromDate(): void
    {
        $workload = new Workload();
        $date = new \DateTime('2023-01-15'); // Week 3 of 2023
        
        $workload->setWeekFromDate($date);
        $this->assertEquals('2023-W03', $workload->getWeek());
        $this->assertEquals(2023, $workload->getYear());
        $this->assertEquals(3, $workload->getWeekNumber());
    }

    public function testWorkloadGetDaysInWeek(): void
    {
        $workload = new Workload();
        $workload->setWeek('2023-W03');
        
        $days = $workload->getDaysInWeek();
        $this->assertCount(7, $days);
        $this->assertInstanceOf(\DateTime::class, $days[0]);
        $this->assertInstanceOf(\DateTime::class, $days[6]);
    }

    public function testWorkloadGetWorkingDaysInWeek(): void
    {
        $workload = new Workload();
        $workload->setWeek('2023-W03');
        
        $workingDays = $workload->getWorkingDaysInWeek();
        $this->assertCount(5, $workingDays); // Monday to Friday
        $this->assertInstanceOf(\DateTime::class, $workingDays[0]);
        $this->assertInstanceOf(\DateTime::class, $workingDays[4]);
    }

    public function testWorkloadGetAverageHoursPerDay(): void
    {
        $workload = new Workload();
        
        $workload->setHours(40);
        $this->assertEquals(8.0, $workload->getAverageHoursPerDay()); // 40h / 5 days
        
        $workload->setHours(35);
        $this->assertEquals(7.0, $workload->getAverageHoursPerDay()); // 35h / 5 days
        
        $workload->setHours(50);
        $this->assertEquals(10.0, $workload->getAverageHoursPerDay()); // 50h / 5 days
    }

    public function testWorkloadIsCurrentWeek(): void
    {
        $workload = new Workload();
        
        // Current week
        $currentWeek = date('Y-\WW');
        $workload->setWeek($currentWeek);
        $this->assertTrue($workload->isCurrentWeek());
        
        // Past week
        $pastDate = new \DateTime('-1 week');
        $pastWeek = $pastDate->format('Y-\WW');
        $workload->setWeek($pastWeek);
        $this->assertFalse($workload->isCurrentWeek());
        
        // Future week
        $futureDate = new \DateTime('+1 week');
        $futureWeek = $futureDate->format('Y-\WW');
        $workload->setWeek($futureWeek);
        $this->assertFalse($workload->isCurrentWeek());
    }

    public function testWorkloadIsPastWeek(): void
    {
        $workload = new Workload();
        
        // Past week
        $pastDate = new \DateTime('-1 week');
        $pastWeek = $pastDate->format('Y-\WW');
        $workload->setWeek($pastWeek);
        $this->assertTrue($workload->isPastWeek());
        
        // Current week
        $currentWeek = date('Y-\WW');
        $workload->setWeek($currentWeek);
        $this->assertFalse($workload->isPastWeek());
        
        // Future week
        $futureDate = new \DateTime('+1 week');
        $futureWeek = $futureDate->format('Y-\WW');
        $workload->setWeek($futureWeek);
        $this->assertFalse($workload->isPastWeek());
    }

    public function testWorkloadIsFutureWeek(): void
    {
        $workload = new Workload();
        
        // Future week
        $futureDate = new \DateTime('+1 week');
        $futureWeek = $futureDate->format('Y-\WW');
        $workload->setWeek($futureWeek);
        $this->assertTrue($workload->isFutureWeek());
        
        // Current week
        $currentWeek = date('Y-\WW');
        $workload->setWeek($currentWeek);
        $this->assertFalse($workload->isFutureWeek());
        
        // Past week
        $pastDate = new \DateTime('-1 week');
        $pastWeek = $pastDate->format('Y-\WW');
        $workload->setWeek($pastWeek);
        $this->assertFalse($workload->isFutureWeek());
    }

    public function testWorkloadStatus(): void
    {
        $workload = new Workload();
        $status = 'planned';

        $workload->setStatus($status);
        $this->assertEquals($status, $workload->getStatus());
    }

    public function testWorkloadValidStatuses(): void
    {
        $workload = new Workload();
        $validStatuses = ['planned', 'in_progress', 'completed', 'cancelled'];

        foreach ($validStatuses as $status) {
            $workload->setStatus($status);
            $this->assertEquals($status, $workload->getStatus());
        }
    }

    public function testWorkloadNotes(): void
    {
        $workload = new Workload();
        $notes = 'Special project requiring overtime work';

        $workload->setNotes($notes);
        $this->assertEquals($notes, $workload->getNotes());
    }

    public function testWorkloadToArray(): void
    {
        $workload = new Workload();
        $user = new User();
        $user->setEmail('test@example.com');
        $project = new Project();
        $project->setName('Test Project');
        
        $workload->setUser($user);
        $workload->setProject($project);
        $workload->setHours(45);
        $workload->setWeek('2023-W15');
        $workload->setStatus('in_progress');

        $array = $workload->toArray();
        
        $this->assertIsArray($array);
        $this->assertEquals(45, $array['hours']);
        $this->assertEquals('2023-W15', $array['week']);
        $this->assertEquals('in_progress', $array['status']);
        $this->assertEquals(112.5, $array['utilizationPercentage']); // 45/40*100
        $this->assertTrue($array['isOverload']);
        $this->assertFalse($array['isUnderload']);
    }
}
