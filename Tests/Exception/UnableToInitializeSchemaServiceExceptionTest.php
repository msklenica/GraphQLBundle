<?php

declare(strict_types=1);

namespace Youshido\GraphQLBundle\Tests\Exception;

use PHPUnit\Framework\TestCase;
use Youshido\GraphQLBundle\Exception\UnableToInitializeSchemaServiceException;

class UnableToInitializeSchemaServiceExceptionTest extends TestCase
{
    public function testExceptionCanBeThrown(): void
    {
        $this->expectException(UnableToInitializeSchemaServiceException::class);
        throw new UnableToInitializeSchemaServiceException();
    }

    public function testExceptionExtendsException(): void
    {
        $exception = new UnableToInitializeSchemaServiceException();
        $this->assertInstanceOf(\Exception::class, $exception);
    }

    public function testExceptionWithMessage(): void
    {
        $message = 'Schema class does not exist';
        $exception = new UnableToInitializeSchemaServiceException($message);
        
        $this->assertEquals($message, $exception->getMessage());
    }

    public function testExceptionWithMessageAndCode(): void
    {
        $message = 'Unable to initialize';
        $code = 123;
        $exception = new UnableToInitializeSchemaServiceException($message, $code);
        
        $this->assertEquals($message, $exception->getMessage());
        $this->assertEquals($code, $exception->getCode());
    }

    public function testExceptionWithPreviousException(): void
    {
        $previous = new \RuntimeException('Previous error');
        $exception = new UnableToInitializeSchemaServiceException('Error', 0, $previous);
        
        $this->assertSame($previous, $exception->getPrevious());
    }

    public function testExceptionIsSerializable(): void
    {
        $exception = new UnableToInitializeSchemaServiceException('Test error');
        $serialized = serialize($exception);
        
        $this->assertIsString($serialized);
        
        $unserialized = unserialize($serialized);
        $this->assertInstanceOf(UnableToInitializeSchemaServiceException::class, $unserialized);
        $this->assertEquals('Test error', $unserialized->getMessage());
    }
}
