<?php

use NAL\TimeTracker\Exception\InvalidUnitName;
use NAL\TimeTracker\Unit;
use PHPUnit\Framework\TestCase;

class UnitTest extends TestCase
{
    public function testAddCustomUnit(): void
    {
        $unit = new Unit();
        $unit->add('custom', '*', 500);

        $this->assertContains('custom', $unit->getSupportedUnits());
    }

    public function testAddCustomUnitWhichAlreadyExist(): void
    {
        $this->expectException(InvalidUnitName::class);

        $unit = new Unit();
        $unit->add('ms', '*', 1000);
    }

    public function testGetCustomUnits(): void
    {
        $unit = new Unit();
        $unit->add('custom', '*', 500);
        $customUnits = $unit->getCustomUnits();

        $this->assertSame(['custom'], $customUnits);
    }

    public function testGetUnitDefinitions(): void
    {
        $unit = new Unit();

        $definition = $unit->getUnitDefinitions('ms');

        $this->assertSame(['operator' => '*', 'value' => 1000], $definition);
    }
}
