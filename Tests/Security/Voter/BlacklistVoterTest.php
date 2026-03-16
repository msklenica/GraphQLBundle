<?php

declare(strict_types=1);

namespace Youshido\GraphQLBundle\Tests\Security\Voter;

use PHPUnit\Framework\TestCase;
use Youshido\GraphQLBundle\Security\Voter\BlacklistVoter;

class BlacklistVoterTest extends TestCase
{
    private BlacklistVoter $voter;

    protected function setUp(): void
    {
        $this->voter = new BlacklistVoter();
    }

    public function testSetListReturnsFluentInterface(): void
    {
        $list = ['query1', 'query2'];
        $result = $this->voter->setList($list);

        $this->assertSame($this->voter, $result);
    }

    public function testGetListReturnsSetList(): void
    {
        $list = ['query1', 'query2', 'query3'];
        $this->voter->setList($list);

        $this->assertEquals($list, $this->voter->getList());
    }

    public function testSetEnabledReturnsFluentInterface(): void
    {
        $result = $this->voter->setEnabled(true);

        $this->assertSame($this->voter, $result);
    }

    public function testGetListReturnsEmptyByDefault(): void
    {
        $this->assertEmpty($this->voter->getList());
    }

    public function testFluentInterfaceChaining(): void
    {
        $list = ['query1', 'query2'];
        
        $result = $this->voter->setEnabled(true)
            ->setList($list);
        
        $this->assertSame($this->voter, $result);
        $this->assertEquals($list, $this->voter->getList());
    }

    public function testInstanceOfBlacklistVoter(): void
    {
        $this->assertInstanceOf(BlacklistVoter::class, $this->voter);
    }
}
