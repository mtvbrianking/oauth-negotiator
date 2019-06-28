<?php

namespace Bmatovu\OAuthNegotiator\Tests\Models;

use Bmatovu\OAuthNegotiator\Models\Token;
use Bmatovu\OAuthNegotiator\Models\TokenInterface;
use PHPUnit\Framework\TestCase;

class TokenTest extends TestCase
{
    /**
     * @test
     */
    public function can_create_token()
    {
        $token = new Token(
            'QC9jztmMfeHoRg5zyTiR',
            '4IAtuQ1aQZhHeRGlFcX6',
            'Bearer',
            3600
        );

        $this->assertInstanceOf(TokenInterface::class, $token);
        $this->assertEquals('QC9jztmMfeHoRg5zyTiR', $token->getAccessToken());
        $this->assertEquals('4IAtuQ1aQZhHeRGlFcX6', $token->getRefreshToken());
        $this->assertEquals('Bearer', $token->getTokenType());
    }

    /**
     * @test
     */
    public function can_sets_correct_expires_at()
    {
        $token = new Token(
            'QC9jztmMfeHoRg5zyTiR',
            '4IAtuQ1aQZhHeRGlFcX6',
            'Bearer',
            3600
        );

        $expires_at = (new \DateTime())->add(new \DateInterval('PT3600S'))->format('Y-m-d H:i:s');

        $this->assertEquals($expires_at, $token->getExpiresAt());
    }

    /**
     * @test
     */
    public function can_determine_token_expiration()
    {
        $token = new Token(
            'QC9jztmMfeHoRg5zyTiR',
            '4IAtuQ1aQZhHeRGlFcX6',
            'Bearer',
            3600
        );

        $this->assertFalse($token->isExpired());

        $expires_at = (new \DateTime())->sub(new \DateInterval("PT7200S"))->format('Y-m-d H:i:s');

        $token->setExpiresAt($expires_at);

        $this->assertTrue($token->isExpired());
    }
}
