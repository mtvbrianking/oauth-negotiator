<?php

namespace Bmatovu\OAuthNegotiator\Tests\GrantTypes;

use Bmatovu\OAuthNegotiator\GrantTypes\RefreshToken;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

class RefreshTokenTest extends TestCase
{
    /**
     * @test
     */
    public function throws_exception_for_missing_args()
    {
        $this->expectException(\InvalidArgumentException::class);

        $missing = ['token_uri', 'client_id', 'client_secret'];

        $message = 'Parameters: '.implode(', ', $missing).' are required.';

        $this->expectExceptionMessage($message);

        new RefreshToken(new Client(), []);
    }

    /**
     * @test
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function can_refresh_access_token()
    {
        $refresh_token = '7yWd6bgLij5AkeuBQs0hx2EDDcCpXYTUkDVhEZQK8MagOuIuKw';

        $api_response = [
            'access_token'  => '0wzIjZyzFilj0HWomm4Z6790xezQi5V6skFz81YB99IXHu9RE3',
            'refresh_token' => $refresh_token,
            'token_type'    => 'Bearer',
            'expires_in'    => 3600,
        ];

        // Create a mock and queue two responses.
        $mockHandler = new MockHandler([
            new Response(200, [], json_encode($api_response)),
            new Response(401, [], null),
            new RequestException('Error Communicating with Server', new Request('GET', 'last')),
        ]);

        $handlerStack = HandlerStack::create($mockHandler);

        $historyContainer = [];
        $historyMiddleware = Middleware::history($historyContainer);

        // Add the history middleware to the handler stack.
        $handlerStack->push($historyMiddleware);

        $client = new Client([
            'base_uri' => 'http://localhost:8000/',
            'handler'  => $handlerStack,
            'headers'  => [
                'Accept'       => 'application/json',
                'Content-Type' => 'application/json',
            ],
        ]);

        $client_id = 'bd3sL3Cx2j';
        $client_secret = 'On8dC2YE7LHwo0fwqOQH';

        $grantType = new RefreshToken($client, [
            'client_id'     => $client_id,
            'client_secret' => $client_secret,
            'token_uri'     => 'oauth/token',
        ]);

        $api_token = $grantType->getToken($refresh_token);

        $this->assertNotEmpty($historyContainer);
        $request = $historyContainer[0]['request'];
        $api_request_authorization_header = $request->getHeader('Authorization')[0];
        $api_request_body = json_decode($request->getBody(), true);

        $this->assertEquals(base64_encode($client_id.':'.$client_secret), ltrim($api_request_authorization_header, 'Basic '));
        $this->assertEquals('refresh_token', $api_request_body['grant_type']);
        $this->assertEquals($refresh_token, $api_request_body['refresh_token']);
        $this->assertArrayNotHasKey('scope', $api_request_body);
        $this->assertEquals($api_response, $api_token);
    }
}
