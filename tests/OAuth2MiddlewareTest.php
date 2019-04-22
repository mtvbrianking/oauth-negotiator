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
use Bmatovu\OAuthNegotiator\Exceptions\TokenRequestException;

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

    protected function extractAccessToken($authHeader)
    {
        if(!$authHeader) {
            return null;
        }

        $authHeaderParts = explode(' ', $authHeader);

        return end($authHeaderParts);
    }

    protected function buildSuccessOauthMiddleware($access_token, $refresh_token, $type = 'Bearer', $expires_in = 3600)
    {
        $apiResponse = [
            'access_token'  => $access_token,
            'refresh_token' => $refresh_token,
            'token_type'    => 'Bearer',
            'expires_in'    => 3600,
        ];

        $mockHandler = new MockHandler([
            new Response(200, [], json_encode($apiResponse)),
        ]);

        $historyContainer = [];
        $historyMiddleware = Middleware::history($historyContainer);

        $handlerStack = HandlerStack::create($mockHandler);

        $handlerStack->push($historyMiddleware);

        $headers = [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ];

        $client = new Client([
            'handler' => $handlerStack,
            'base_uri' => 'http://localhost:8000/',
            'headers' => $headers,
        ]);

        $config = [
            'token_uri' => 'oauth/token',
            'client_id' => 'fa5cc82b-6be5-41a4-be48-255fa2aae62b',
            'client_secret' => '3a4f0716-8216-4d2b-a526-3d001dec4832',
        ];

        $clientCredentials = new ClientCredentials($client, $config);

        // $refreshToken = new RefreshToken($client, $config);
        $refreshToken = null;

        $tokenRepo = new FileTokenRepository($this->testTokenFile);
        // $tokenRepo = null;

        return new OAuth2Middleware($clientCredentials, $refreshToken, $tokenRepo);
    }

    protected function buildFailureOauthMiddleware()
    {
        $apiResponse = [
            'error' => 'For some reason, you don\'t qualify for a token. Sorry'
        ];

        $mockHandler = new MockHandler([
            new Response(401, [], json_encode($apiResponse)),
        ]);

        $historyContainer = [];
        $historyMiddleware = Middleware::history($historyContainer);

        $handlerStack = HandlerStack::create($mockHandler);

        $handlerStack->push($historyMiddleware);

        $headers = [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ];

        $client = new Client([
            'handler' => $handlerStack,
            'base_uri' => 'http://localhost:8000/',
            'headers' => $headers,
        ]);

        $config = [
            'token_uri' => 'oauth/token',
            'client_id' => 'fa5cc82b-6be5-41a4-be48-255fa2aae62b',
            'client_secret' => '3a4f0761-8216-4d2b-a526-3d001dec4832',
        ];

        $clientCredentials = new ClientCredentials($client, $config);

        // $refreshToken = new RefreshToken($client, $config);
        $refreshToken = null;

        $tokenRepo = new FileTokenRepository($this->testTokenFile);
        // $tokenRepo = null;

        return new OAuth2Middleware($clientCredentials, $refreshToken, $tokenRepo);
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

    /**
     * @test
     */
    public function uses_provided_access_token()
    {
        $accessToken = '0wzIjZyzFilj0HWomm4Z6790xezQi5V6skFz81YB99IXHu9RE3';
        $refreshToken = '7yWd6bgLij5AkeuBQs0hx2EDDcCpXYTUkDVhEZQK8MagOuIuKw';

        $oauthMiddleware = $this->buildSuccessOauthMiddleware($accessToken, $refreshToken);

        // ...........................................................

        $apiResponse = [
            'status'  => 'some random data',
        ];

        $mockHandler = new MockHandler([
            new Response(200, [], json_encode($apiResponse)),
        ]);

        $historyContainer = [];
        $historyMiddleware = Middleware::history($historyContainer);

        $handlerStack = HandlerStack::create($mockHandler);

        $handlerStack->push($oauthMiddleware);
        $handlerStack->push($historyMiddleware);

        $userChosenAccessToken = 'QBKNcn10frGUSlrbzE17ngD5W1f8L8dcMNPMZGD4V7NDj4CGws';

        $client = new Client([
            'handler' => $handlerStack,
            'base_uri' => 'http://localhost:8000/v1/',
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer '.$userChosenAccessToken,
            ],
        ]);

        $response = $client->request('GET', 'resource');

        $this->assertNotEmpty($historyContainer);
        $request = $historyContainer[0]['request'];
        $apiRequestAuthorizationHeader = $request->getHeader('Authorization')[0];

        $presentAccessToken = $this->extractAccessToken($apiRequestAuthorizationHeader);

        $this->assertNotEquals($accessToken, $presentAccessToken);
        $this->assertEquals($userChosenAccessToken, $presentAccessToken);
        $this->assertEquals(json_decode($response->getBody(), true), $apiResponse);
    }

    /**
     * @test
     */
    public function can_request_new_token()
    {
        $accessToken = '0wzIjZyzFilj0HWomm4Z6790xezQi5V6skFz81YB99IXHu9RE3';
        $refreshToken = '7yWd6bgLij5AkeuBQs0hx2EDDcCpXYTUkDVhEZQK8MagOuIuKw';

        $oauthMiddleware = $this->buildSuccessOauthMiddleware($accessToken, $refreshToken);

        // ...........................................................

        $apiResponse = [
            'status'  => 'some random data',
        ];

        $mockHandler = new MockHandler([
            new Response(200, [], json_encode($apiResponse)),
        ]);

        $historyContainer = [];
        $historyMiddleware = Middleware::history($historyContainer);

        $handlerStack = HandlerStack::create($mockHandler);

        $handlerStack->push($oauthMiddleware);
        $handlerStack->push($historyMiddleware);

        $client = new Client([
            'handler' => $handlerStack,
            'base_uri' => 'http://localhost:8000/v1/',
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ]
        ]);

        $response = $client->request('GET', 'resource');

        $this->assertNotEmpty($historyContainer);
        $request = $historyContainer[0]['request'];
        $apiRequestAuthorizationHeader = $request->getHeader('Authorization')[0];

        $this->assertEquals(
            $accessToken,
            ltrim($apiRequestAuthorizationHeader, 'Bearer ')
        );
        $this->assertEquals(json_decode($response->getBody(), true), $apiResponse);
    }

    /**
     * @test
     */
    public function throws_exception_if_cant_obtain_new_access_token()
    {
        $oauthMiddleware = $this->buildFailureOauthMiddleware();

        $handlerStack = HandlerStack::create();

        $handlerStack->push($oauthMiddleware);

        $this->expectException(TokenRequestException::class);
        $this->expectExceptionMessage('Unable to request a new access token');

        $client = new Client([
            'handler' => $handlerStack,
            'base_uri' => 'http://localhost:8000/v1/',
        ]);

        $response = $client->request('GET', 'resource');
    }

    /**
     * @test
     */
    public function can_use_exiting_valid_token()
    {
        $tokenRepo = new FileTokenRepository($this->testTokenFile);

        $existingAccessToken = '6OQUFgtm1WgFwTpTK3Snl0qfOLbvAWwKGKTshsdxX0nI1NX4oQ';

        $tokenRepo->create([
            'access_token'  => $existingAccessToken,
            'refresh_token' => 'U51GH5zfLm1tshcNEp7HNvGs0vlgXmODfdEYWoFNc9jBa04iBd',
            'token_type'    => 'Bearer',
            'expires_in'    => 3600,
        ]);

        // ...........................................................

        $accessToken = '0wzIjZyzFilj0HWomm4Z6790xezQi5V6skFz81YB99IXHu9RE3';
        $refreshToken = '7yWd6bgLij5AkeuBQs0hx2EDDcCpXYTUkDVhEZQK8MagOuIuKw';

        $oauthMiddleware = $this->buildSuccessOauthMiddleware($accessToken, $refreshToken);

        // ...........................................................

        $apiResponse = [
            'status'  => 'some random data',
        ];

        $mockHandler = new MockHandler([
            new Response(200, [], json_encode($apiResponse)),
        ]);

        $historyContainer = [];
        $historyMiddleware = Middleware::history($historyContainer);

        $handlerStack = HandlerStack::create($mockHandler);

        $handlerStack->push($oauthMiddleware);
        $handlerStack->push($historyMiddleware);

        $client = new Client([
            'handler' => $handlerStack,
            'base_uri' => 'http://localhost:8000/v1/',
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ],
        ]);

        $response = $client->request('GET', 'resource');

        $this->assertNotEmpty($historyContainer);
        $request = $historyContainer[0]['request'];
        $apiRequestAuthorizationHeader = $request->getHeader('Authorization')[0];

        $presentAccessToken = $this->extractAccessToken($apiRequestAuthorizationHeader);

        $this->assertNotEquals($accessToken, $presentAccessToken);
        $this->assertEquals($existingAccessToken, $presentAccessToken);
        $this->assertEquals(json_decode($response->getBody(), true), $apiResponse);
    }

    /**
     * @test
     */
    public function can_refresh_existing_expired_token()
    {
        $tokenRepo = new FileTokenRepository($this->testTokenFile);

        $existingAccessToken = '6OQUFgtm1WgFwTpTK3Snl0qfOLbvAWwKGKTshsdxX0nI1NX4oQ';

        $tokenRepo->create([
            'access_token'  => $existingAccessToken,
            'refresh_token' => '7yWd6bgLij5AkeuBQs0hx2EDDcCpXYTUkDVhEZQK8MagOuIuKw',
            'token_type'    => 'Bearer',
            'expires_in'    => -3600,
        ]);

        // ...........................................................

        $accessToken = '0wzIjZyzFilj0HWomm4Z6790xezQi5V6skFz81YB99IXHu9RE3';
        $refreshToken = '7yWd6bgLij5AkeuBQs0hx2EDDcCpXYTUkDVhEZQK8MagOuIuKw';

        $oauthMiddleware = $this->buildSuccessOauthMiddleware($accessToken, $refreshToken);

        // ...........................................................

        $apiResponse = [
            'status'  => 'some random data',
        ];

        $mockHandler = new MockHandler([
            new Response(200, [], json_encode($apiResponse)),
        ]);

        $historyContainer = [];
        $historyMiddleware = Middleware::history($historyContainer);

        $handlerStack = HandlerStack::create($mockHandler);

        $handlerStack->push($oauthMiddleware);
        $handlerStack->push($historyMiddleware);

        $client = new Client([
            'handler' => $handlerStack,
            'base_uri' => 'http://localhost:8000/v1/',
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ],
        ]);

        $response = $client->request('GET', 'resource');

        $this->assertNotEmpty($historyContainer);
        $request = $historyContainer[0]['request'];
        $apiRequestAuthorizationHeader = $request->getHeader('Authorization')[0];

        $presentAccessToken = $this->extractAccessToken($apiRequestAuthorizationHeader);

        $this->assertEquals($accessToken, $presentAccessToken);
        $this->assertNotEquals($existingAccessToken, $presentAccessToken);
        $this->assertEquals(json_decode($response->getBody(), true), $apiResponse);
    }
}
