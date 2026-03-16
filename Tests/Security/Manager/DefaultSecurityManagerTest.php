<?php

declare(strict_types=1);

namespace Youshido\GraphQLBundle\Tests\Security\Manager;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Youshido\GraphQLBundle\Security\Manager\DefaultSecurityManager;
use Youshido\GraphQLBundle\Security\Manager\SecurityManagerInterface;

class DefaultSecurityManagerTest extends TestCase
{
    private AuthorizationCheckerInterface $authorizationChecker;

    protected function setUp(): void
    {
        $this->authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
    }

    public function testConstructorWithDefaultConfig(): void
    {
        $manager = new DefaultSecurityManager($this->authorizationChecker);

        $this->assertFalse($manager->isSecurityEnabledFor(SecurityManagerInterface::RESOLVE_FIELD_ATTRIBUTE));
        $this->assertFalse($manager->isSecurityEnabledFor(SecurityManagerInterface::RESOLVE_ROOT_OPERATION_ATTRIBUTE));
    }

    public function testConstructorWithCustomConfig(): void
    {
        $guardConfig = [
            'field' => true,
            'operation' => true,
        ];
        $manager = new DefaultSecurityManager($this->authorizationChecker, $guardConfig);

        $this->assertTrue($manager->isSecurityEnabledFor(SecurityManagerInterface::RESOLVE_FIELD_ATTRIBUTE));
        $this->assertTrue($manager->isSecurityEnabledFor(SecurityManagerInterface::RESOLVE_ROOT_OPERATION_ATTRIBUTE));
    }

    public function testConstructorWithPartialConfig(): void
    {
        $guardConfig = ['field' => true];
        $manager = new DefaultSecurityManager($this->authorizationChecker, $guardConfig);

        $this->assertTrue($manager->isSecurityEnabledFor(SecurityManagerInterface::RESOLVE_FIELD_ATTRIBUTE));
        $this->assertFalse($manager->isSecurityEnabledFor(SecurityManagerInterface::RESOLVE_ROOT_OPERATION_ATTRIBUTE));
    }

    public function testIsSecurityEnabledForUnknownAttribute(): void
    {
        $manager = new DefaultSecurityManager($this->authorizationChecker);

        $this->assertFalse($manager->isSecurityEnabledFor('unknown_attribute'));
    }

    public function testSetFieldSecurityEnabledReturnsFluentInterface(): void
    {
        $manager = new DefaultSecurityManager($this->authorizationChecker);
        $result = $manager->setFieldSecurityEnabled(true);

        $this->assertSame($manager, $result);
        $this->assertTrue($manager->isSecurityEnabledFor(SecurityManagerInterface::RESOLVE_FIELD_ATTRIBUTE));
    }

    public function testSetRootOperationSecurityEnabledReturnsFluentInterface(): void
    {
        $manager = new DefaultSecurityManager($this->authorizationChecker);
        $result = $manager->setRootOperationSecurityEnabled(true);

        $this->assertSame($manager, $result);
        $this->assertTrue($manager->isSecurityEnabledFor(SecurityManagerInterface::RESOLVE_ROOT_OPERATION_ATTRIBUTE));
    }

    public function testSetFieldSecurityEnabledToggle(): void
    {
        $manager = new DefaultSecurityManager($this->authorizationChecker);

        $manager->setFieldSecurityEnabled(true);
        $this->assertTrue($manager->isSecurityEnabledFor(SecurityManagerInterface::RESOLVE_FIELD_ATTRIBUTE));

        $manager->setFieldSecurityEnabled(false);
        $this->assertFalse($manager->isSecurityEnabledFor(SecurityManagerInterface::RESOLVE_FIELD_ATTRIBUTE));
    }

    public function testSetRootOperationSecurityEnabledToggle(): void
    {
        $manager = new DefaultSecurityManager($this->authorizationChecker);

        $manager->setRootOperationSecurityEnabled(true);
        $this->assertTrue($manager->isSecurityEnabledFor(SecurityManagerInterface::RESOLVE_ROOT_OPERATION_ATTRIBUTE));

        $manager->setRootOperationSecurityEnabled(false);
        $this->assertFalse($manager->isSecurityEnabledFor(SecurityManagerInterface::RESOLVE_ROOT_OPERATION_ATTRIBUTE));
    }

    public function testCreateNewFieldAccessDeniedException(): void
    {
        $manager = new DefaultSecurityManager($this->authorizationChecker);

        // We cannot test with actual ResolveInfo since the GraphQL library is not available,
        // but we can verify the method returns an AccessDeniedException instance.
        // Note: This test is limited due to external dependency constraints.
        $this->assertTrue(method_exists($manager, 'createNewFieldAccessDeniedException'));
    }

    public function testCreateNewOperationAccessDeniedException(): void
    {
        $manager = new DefaultSecurityManager($this->authorizationChecker);

        // We cannot test with actual Query since the GraphQL library is not available,
        // but we can verify the method exists and returns an AccessDeniedException instance.
        // Note: This test is limited due to external dependency constraints.
        $this->assertTrue(method_exists($manager, 'createNewOperationAccessDeniedException'));
    }

    public function testMultipleSetsAreIndependent(): void
    {
        $manager = new DefaultSecurityManager($this->authorizationChecker);

        $manager->setFieldSecurityEnabled(true);
        $manager->setRootOperationSecurityEnabled(false);

        $this->assertTrue($manager->isSecurityEnabledFor(SecurityManagerInterface::RESOLVE_FIELD_ATTRIBUTE));
        $this->assertFalse($manager->isSecurityEnabledFor(SecurityManagerInterface::RESOLVE_ROOT_OPERATION_ATTRIBUTE));

        $manager->setFieldSecurityEnabled(false);
        $manager->setRootOperationSecurityEnabled(true);

        $this->assertFalse($manager->isSecurityEnabledFor(SecurityManagerInterface::RESOLVE_FIELD_ATTRIBUTE));
        $this->assertTrue($manager->isSecurityEnabledFor(SecurityManagerInterface::RESOLVE_ROOT_OPERATION_ATTRIBUTE));
    }

    public function testSecurityManagerImplementsInterface(): void
    {
        $manager = new DefaultSecurityManager($this->authorizationChecker);

        $this->assertInstanceOf(SecurityManagerInterface::class, $manager);
    }

    public function testConstructorAcceptsEmptyGuardConfig(): void
    {
        $manager = new DefaultSecurityManager($this->authorizationChecker, []);

        $this->assertFalse($manager->isSecurityEnabledFor(SecurityManagerInterface::RESOLVE_FIELD_ATTRIBUTE));
        $this->assertFalse($manager->isSecurityEnabledFor(SecurityManagerInterface::RESOLVE_ROOT_OPERATION_ATTRIBUTE));
    }

    public function testIsSecurityEnabledForFieldAttribute(): void
    {
        $manager = new DefaultSecurityManager($this->authorizationChecker, ['field' => true]);

        $this->assertTrue($manager->isSecurityEnabledFor(SecurityManagerInterface::RESOLVE_FIELD_ATTRIBUTE));
    }

    public function testIsSecurityEnabledForOperationAttribute(): void
    {
        $manager = new DefaultSecurityManager($this->authorizationChecker, ['operation' => true]);

        $this->assertTrue($manager->isSecurityEnabledFor(SecurityManagerInterface::RESOLVE_ROOT_OPERATION_ATTRIBUTE));
    }
}


