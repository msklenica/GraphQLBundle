<?php

declare(strict_types=1);

namespace Youshido\GraphQLBundle\Event;

use Youshido\GraphQL\Field\FieldInterface;

class ResolveEvent
{
    public function __construct(
        private readonly FieldInterface $field,
        private readonly array $astFields,
        private mixed $resolvedValue = null,
    ) {
    }

    public function getField(): FieldInterface
    {
        return $this->field;
    }

    public function getAstFields(): array
    {
        return $this->astFields;
    }

    public function getResolvedValue(): mixed
    {
        return $this->resolvedValue;
    }

    public function setResolvedValue(mixed $resolvedValue): void
    {
        $this->resolvedValue = $resolvedValue;
    }
}

