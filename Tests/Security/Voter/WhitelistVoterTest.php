<?php

declare(strict_types=1);

namespace Youshido\GraphQLBundle\Tests\Security\Voter;

use PHPUnit\Framework\TestCase;
use Youshido\GraphQLBundle\Security\Voter\WhitelistVoter;

class WhitelistVoterTest extends TestCase
{
    private WhitelistVoter $voter;

    protected function setUp(): void
    {
        $this->voter = new WhitelistVoter();
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
        $list = ['allowed_query1', 'allowed_query2'];
        
        $result = $this->voter->setEnabled(true)
            ->setList($list);
        
        $this->assertSame($this->voter, $result);
        $this->assertEquals($list, $this->voter->getList());
    }

    public function testInstanceOfWhitelistVoter(): void
    {
        $this->assertInstanceOf(WhitelistVoter::class, $this->voter);
    }

    public function testDifferentListsAreIndependent(): void
    {
        $voter1 = new WhitelistVoter();
        $voter2 = new WhitelistVoter();
        
        $voter1->setList(['query1']);
        $voter2->setList(['query2']);
        
        $this->assertEquals(['query1'], $voter1->getList());
        $this->assertEquals(['query2'], $voter2->getList());
    }
}
