<?php

declare(strict_types=1);

/**
 * Date: 29.08.16
 *
 * @author Portey Vasil <portey@gmail.com>
 */

namespace Youshido\GraphQLBundle\Security\Manager;

use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Youshido\GraphQL\Execution\ResolveInfo;
use Youshido\GraphQL\Parser\Ast\Query;

class DefaultSecurityManager implements SecurityManagerInterface
{
    private bool $fieldSecurityEnabled = false;

    private bool $rootOperationSecurityEnabled = false;

    public function __construct(private readonly AuthorizationCheckerInterface $authorizationChecker, array $guardConfig = [])
    {
        $this->fieldSecurityEnabled         = $guardConfig['field'] ?? false;
        $this->rootOperationSecurityEnabled = $guardConfig['operation'] ?? false;
    }

    public function isSecurityEnabledFor(string $attribute): bool
    {
        if (SecurityManagerInterface::RESOLVE_FIELD_ATTRIBUTE === $attribute) {
            return $this->fieldSecurityEnabled;
        } elseif (SecurityManagerInterface::RESOLVE_ROOT_OPERATION_ATTRIBUTE === $attribute) {
            return $this->rootOperationSecurityEnabled;
        }

        return false;
    }

    public function setFieldSecurityEnabled(bool $fieldSecurityEnabled): self
    {
        $this->fieldSecurityEnabled = $fieldSecurityEnabled;
        return $this;
    }

    public function setRootOperationSecurityEnabled(bool $rootOperationSecurityEnabled): self
    {
        $this->rootOperationSecurityEnabled = $rootOperationSecurityEnabled;
        return $this;
    }

    public function isGrantedToOperationResolve(Query $query): bool
    {
        return $this->authorizationChecker->isGranted(SecurityManagerInterface::RESOLVE_ROOT_OPERATION_ATTRIBUTE, $query);
    }

    public function isGrantedToFieldResolve(ResolveInfo $resolveInfo): bool
    {
        return $this->authorizationChecker->isGranted(SecurityManagerInterface::RESOLVE_FIELD_ATTRIBUTE, $resolveInfo);
    }

    public function createNewFieldAccessDeniedException(ResolveInfo $resolveInfo): AccessDeniedException
    {
        $fieldName = $resolveInfo->getFieldName();
        $parentType = $resolveInfo->getParentType();
        return new AccessDeniedException(sprintf(
            'Access denied to field "%s" on type "%s"',
            $fieldName,
            $parentType?->getName() ?? 'Unknown'
        ));
    }

    public function createNewOperationAccessDeniedException(Query $query): AccessDeniedException
    {
        $operationName = $query->getName();
        return new AccessDeniedException(sprintf(
            'Access denied to operation "%s"',
            $operationName ?? 'anonymous query'
        ));
    }
}
