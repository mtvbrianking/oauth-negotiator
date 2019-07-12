<?php

namespace Bmatovu\OAuthNegotiator\Tests\Exceptions;

use PHPUnit\Framework\TestCase;
use Bmatovu\OAuthNegotiator\Exceptions\TokenNotFoundException;

class TokenNotFoundExceptionTest extends TestCase
{
    public function test_exception_formation()
    {
        $this->expectException(TokenNotFoundException::class);
        $this->expectExceptionCode(123);
        $this->expectExceptionMessage('Token missing.');

        $exception = new TokenNotFoundException('Token missing.', 123);

        $this->assertNull($exception->getPrevious());

        throw $exception;
    }
}
