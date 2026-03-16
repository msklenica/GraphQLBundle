<?php

declare(strict_types=1);

namespace Youshido\GraphQLBundle\Security\Manager;

use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Youshido\GraphQL\Execution\ResolveInfo;
use Youshido\GraphQL\Parser\Ast\Query;

/**
 * Date: 29.08.16
 *
 * @author Portey Vasil <portey@gmail.com>
 */
interface SecurityManagerInterface
{
    public const RESOLVE_ROOT_OPERATION_ATTRIBUTE = 'RESOLVE_ROOT_OPERATION';
    public const RESOLVE_FIELD_ATTRIBUTE          = 'RESOLVE_FIELD';

    public function isSecurityEnabledFor(string $attribute): bool;

    public function isGrantedToFieldResolve(ResolveInfo $resolveInfo): bool;

    public function isGrantedToOperationResolve(Query $query): bool;

    public function createNewFieldAccessDeniedException(ResolveInfo $resolveInfo): AccessDeniedException;

    public function createNewOperationAccessDeniedException(Query $query): AccessDeniedException;
}