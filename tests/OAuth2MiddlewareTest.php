<?php

namespace Bmatovu\OAuthNegotiator\Tests;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Bmatovu\OAuthNegotiator\OAuth2Middleware;
use Bmatovu\OAuthNegotiator\GrantTypes\ClientCredentials;
use Bmatovu\OAuthNegotiator\GrantTypes\RefreshToken;
use Bmatovu\OAuthNegotiator\Repositories\FileTokenRepository;

class OAuth2MiddlewareTest extends TestCase
{
    /**
     * @var string
     */
    protected $testTokenFile;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        $this->testTokenFile = tempnam(sys_get_temp_dir(), 'phpunit_test_');
    }

    /**
     * {@inheritdoc}
     */
    public function tearDown()
    {
        if (file_exists($this->testTokenFile)) {
            unlink($this->testTokenFile);
        }
    }

    /**
     * @test
     */
    public function can_instantiate_oauth2_middleware()
    {
        $client_grant_stub = $this->getMockBuilder(ClientCredentials::class)
            ->disableOriginalConstructor()
            ->getMock();

        $oauth_mw = new OAuth2Middleware($client_grant_stub);

        $this->assertInstanceOf(OAuth2Middleware::class, $oauth_mw);
    }

    public function can_refresh_expired()
    {
        // TODO
    }

    public function can_request_new_token()
    {
        // TODO
    }

    public function can_use_exiting_valid_token()
    {
        // TODO
    }

    /**
     * @test
     *
     * @group staged
     */
    public function can_sign_http_request()
    {
        $apiResponse1 = [
            'access_token'  => '0wzIjZyzFilj0HWomm4Z6790xezQi5V6skFz81YB99IXHu9RE3',
            'refresh_token' => '7yWd6bgLij5AkeuBQs0hx2EDDcCpXYTUkDVhEZQK8MagOuIuKw',
            'token_type'    => 'Bearer',
            'expires_in'    => 3600,
        ];

        $mockHandler1 = new MockHandler([
            new Response(200, [], json_encode($apiResponse1)),
        ]);

        $historyContainer1 = [];
        $historyMiddleware1 = Middleware::history($historyContainer1);

        $handlerStack1 = HandlerStack::create($mockHandler1);

        $handlerStack1->push($historyMiddleware1);

        $headers = [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ];

        $client1 = new Client([
            'handler' => $handlerStack1,
            'base_uri' => 'http://localhost:8000/',
            'headers' => $headers,
        ]);

        $config = [
            'token_uri' => 'oauth/token',
            'client_id' => 'fa5cc82b-6be5-41a4-be48-255fa2aae62b',
            'client_secret' => '3a4f0761-8216-4d2b-a526-3d001dec4832',
        ];

        $clientCredentials = new ClientCredentials($client1, $config);

        // $refreshToken = new RefreshToken($client1, $config);
        $refreshToken = null;

        $tokenRepo = new FileTokenRepository($this->testTokenFile);
        // $tokenRepo = null;

        $oauthMiddleware = new OAuth2Middleware($clientCredentials, $refreshToken, $tokenRepo);

        // ...........................................................

        $apiResponse2 = [
            'status'  => 'some random data',
        ];

        $mockHandler2 = new MockHandler([
            new Response(200, [], json_encode($apiResponse2)),
        ]);

        $historyContainer2 = [];
        $historyMiddleware2 = Middleware::history($historyContainer2);

        $handlerStack2 = HandlerStack::create($mockHandler2);

        $handlerStack2->push($oauthMiddleware);
        $handlerStack2->push($historyMiddleware2);

        $client2 = new Client([
            'handler' => $handlerStack2,
            'base_uri' => 'http://localhost:8000/v1/',
            'headers' => $headers,
        ]);

        $response = $client2->request('GET', 'resource');

        $this->assertNotEmpty($historyContainer2);
        $request = $historyContainer2[0]['request'];
        $api_request_authorization_header = $request->getHeader('Authorization')[0];

        $this->assertEquals(
            '0wzIjZyzFilj0HWomm4Z6790xezQi5V6skFz81YB99IXHu9RE3',
            ltrim($api_request_authorization_header, 'Bearer ')
        );
        $this->assertEquals(json_decode($response->getBody(), true), $apiResponse2);
    }
}
