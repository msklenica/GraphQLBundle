<?php

declare(strict_types=1);

/**
 * Date: 9/12/16
 *
 * @author Portey Vasil <portey@gmail.com>
 */

namespace Youshido\GraphQLBundle\Security\Voter;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Youshido\GraphQLBundle\Security\Manager\SecurityManagerInterface;

abstract class AbstractListVoter extends Voter
{
    private array $list = [];

    private bool $enabled = false;

    protected function supports(string $attribute, mixed $subject): bool
    {
        return $this->enabled && $attribute === SecurityManagerInterface::RESOLVE_ROOT_OPERATION_ATTRIBUTE;
    }

    protected function isLoggedInUser(TokenInterface $token): bool
    {
        return is_object($token->getUser());
    }

    public function setList(array $list): self
    {
        $this->list = $list;
        return $this;
    }

    /**
     * @return array<int, string>
     */
    public function getList(): array
    {
        return $this->list;
    }

    protected function inList(mixed $query): bool
    {
        return in_array($query, $this->list, true);
    }

    public function setEnabled(bool $enabled): self
    {
        $this->enabled = $enabled;
        return $this;
    }
}
