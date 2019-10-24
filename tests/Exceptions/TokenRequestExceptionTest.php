<?php

namespace Bmatovu\OAuthNegotiator\Tests\Exceptions;

use Mockery as m;
use PHPUnit\Framework\TestCase;
use GuzzleHttp\Exception\TransferException;
use Bmatovu\OAuthNegotiator\Exceptions\TokenRequestException;

class TokenRequestExceptionTest extends TestCase
{
    public function test_exception_formation()
    {
        $this->expectException(TokenRequestException::class);
        $this->expectExceptionCode(159);
        $this->expectExceptionMessage('Unable to request token.');

        $transferException = m::mock(TransferException::class);

        $exception = new TokenRequestException('Unable to request token.', 159, $transferException);

        $this->assertInstanceOf(TransferException::class, $exception->getPrevious());

        throw $exception;
    }
}
