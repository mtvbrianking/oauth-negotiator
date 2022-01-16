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
use Psr\Http\Message\RequestInterface;
use GuzzleHttp\Exception\ClientException;
use Bmatovu\OAuthNegotiator\OAuth2Middleware;
use Bmatovu\OAuthNegotiator\GrantTypes\ClientCredentials;
use Bmatovu\OAuthNegotiator\GrantTypes\RefreshToken;
use Bmatovu\OAuthNegotiator\Repositories\FileTokenRepository;
use Bmatovu\OAuthNegotiator\Exceptions\TokenRequestException;

class OAuth2MiddlewareTest extends TestCase
{
    protected $testTokenFile;

    public function setUp(): void
    {
        $this->testTokenFile = tempnam(sys_get_temp_dir(), 'phpunit_test_');
    }

    public function tearDown(): void
    {
        if (file_exists($this->testTokenFile)) {
            unlink($this->testTokenFile);
        }
    }

    protected function extractAccessToken(RequestInterface $request)
    {
        if(! $request->hasHeader('Authorization')) {
            return '';
        }

        $authHeader = $request->getHeader('Authorization')[0];

        $authHeaderParts = explode(' ', $authHeader);

        return end($authHeaderParts);
    }

    protected function buildSuccessOauthMiddleware($access_token, $refresh_token = '', $type = 'Bearer', $expires_in = 3600)
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

        $clientCredentialsGrantType = new ClientCredentials($client, $config);

        $refreshTokenGrantType = ! $refresh_token ? null : new RefreshToken($client, $config);

        $fileTokenRepository = new FileTokenRepository($this->testTokenFile);

        return new OAuth2Middleware($clientCredentialsGrantType, $refreshTokenGrantType, $fileTokenRepository);
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

        $clientCredentialsGrantType = new ClientCredentials($client, $config);

        $refreshTokenGrantType = null;

        $fileTokenRepository = new FileTokenRepository($this->testTokenFile);

        return new OAuth2Middleware($clientCredentialsGrantType, $refreshTokenGrantType, $fileTokenRepository);
    }

    public function test_can_instantiate_oauth2_middleware()
    {
        $client_grant_stub = $this->getMockBuilder(ClientCredentials::class)
            ->disableOriginalConstructor()
            ->getMock();

        $oauth_mw = new OAuth2Middleware($client_grant_stub);

        $this->assertInstanceOf(OAuth2Middleware::class, $oauth_mw);
    }

    public function test_uses_provided_access_token()
    {
        $accessToken = '0wzIjZyzFilj0HWomm4Z6790xezQi5V6skFz81YB99IXHu9RE3';

        $oauthMiddleware = $this->buildSuccessOauthMiddleware($accessToken);

        // ...........................................................

        $apiResponse = [
            'message' => 'some random data',
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
        $usedAccessToken = $this->extractAccessToken($request);

        $this->assertNotEquals($accessToken, $usedAccessToken);
        $this->assertEquals($userChosenAccessToken, $usedAccessToken);
        $this->assertEquals(json_decode($response->getBody(), true), $apiResponse);
    }

    public function test_throws_exception_if_cant_obtain_new_access_token()
    {
        $oauthMiddleware = $this->buildFailureOauthMiddleware();

        $handlerStack = HandlerStack::create();

        $handlerStack->push($oauthMiddleware);

        $this->expectException(TokenRequestException::class);
        $this->expectExceptionMessage('Unable to obtain a new access token');

        $client = new Client([
            'handler' => $handlerStack,
            'base_uri' => 'http://localhost:8000/v1/',
        ]);

        $response = $client->request('GET', 'resource');
    }

    public function test_can_request_new_token()
    {
        $accessToken = '0wzIjZyzFilj0HWomm4Z6790xezQi5V6skFz81YB99IXHu9RE3';

        $oauthMiddleware = $this->buildSuccessOauthMiddleware($accessToken);

        // ...........................................................

        $apiResponse = [
            'message' => 'some random data',
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
        $usedAccessToken = $this->extractAccessToken($request);

        $this->assertEquals($accessToken, $usedAccessToken);
        $this->assertEquals(json_decode($response->getBody(), true), $apiResponse);
    }

    public function test_can_refresh_existing_expired_token()
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
            'message' => 'some random data',
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
        $usedAccessToken = $this->extractAccessToken($request);

        $this->assertEquals($accessToken, $usedAccessToken);
        $this->assertNotEquals($existingAccessToken, $usedAccessToken);
        $this->assertEquals(json_decode($response->getBody(), true), $apiResponse);
    }

    public function test_can_use_exiting_valid_token()
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

        $oauthMiddleware = $this->buildSuccessOauthMiddleware($accessToken);

        // ...........................................................

        $apiResponse = [
            'message' => 'some random data',
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
        $usedAccessToken = $this->extractAccessToken($request);

        $this->assertNotEquals($accessToken, $usedAccessToken);
        $this->assertEquals($existingAccessToken, $usedAccessToken);
        $this->assertEquals(json_decode($response->getBody(), true), $apiResponse);
    }

    public function test_can_retry_request()
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

        $oauthMiddleware = $this->buildSuccessOauthMiddleware($accessToken);

        // ...........................................................

        $secondResponse = [
            'message' => 'some random data',
        ];

        $mockHandler = new MockHandler([
            new Response(401, [], json_encode(['message' => 'Access token is revoked'])),
            new Response(200, [], json_encode($secondResponse)),
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

        $finalResponse = $client->request('GET', 'resource');

        $this->assertEquals(2, count($historyContainer));
        
        // First request...

        $firstRequest = $historyContainer[0]['request'];
        $firstResponse = $historyContainer[0]['response'];
        $usedAccessToken = $this->extractAccessToken($firstRequest);

        $this->assertNotEquals($accessToken, $usedAccessToken);
        $this->assertEquals($existingAccessToken, $usedAccessToken);
        $this->assertFalse($firstRequest->hasHeader('X-Guzzle-Retry'));
        $this->assertEquals(401, $firstResponse->getStatusCode());

        // Second request...

        $secondRequest = $historyContainer[1]['request'];
        $secondResponse = $historyContainer[1]['response'];
        $usedAccessToken = $this->extractAccessToken($secondRequest);

        $this->assertNotEquals($existingAccessToken, $usedAccessToken);
        $this->assertEquals('0wzIjZyzFilj0HWomm4Z6790xezQi5V6skFz81YB99IXHu9RE3', $usedAccessToken);
        $this->assertTrue($secondRequest->hasHeader('X-Guzzle-Retry'));
        $this->assertEquals('1', $secondRequest->getHeader('X-Guzzle-Retry')[0]);
        $this->assertEquals(200, $secondResponse->getStatusCode());
    }

    /**
     * @see https://stackoverflow.com/a/45893639
     */
    public function test_retries_requests_only_once()
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

        $oauthMiddleware = $this->buildSuccessOauthMiddleware($accessToken);

        // ...........................................................

        $mockHandler = new MockHandler([
            new Response(401, [], json_encode(['message' => 'Client application is blocked.'])),
            new Response(200, [], json_encode(['message' => 'Some random data'])),
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
                'X-Guzzle-Retry' => '1',
            ],
        ]);

        $this->expectException(ClientException::class);
        $this->expectExceptionMessage('Client application is blocked.');

        try {
            $client->request('GET', 'resource');
        } finally {
            $this->assertEquals(1, count($historyContainer));
            $request = $historyContainer[0]['request'];
            $response = $historyContainer[0]['response'];
            $usedAccessToken = $this->extractAccessToken($request);

            $this->assertNotEquals($accessToken, $usedAccessToken);
            $this->assertEquals($existingAccessToken, $usedAccessToken);
            $this->assertTrue($request->hasHeader('X-Guzzle-Retry'));
            $this->assertEquals('1', $request->getHeader('X-Guzzle-Retry')[0]);
            $this->assertEquals(401, $response->getStatusCode());
        }
    }

    public function test_throws_guzzle_exception_on_rejection()
    {
        $accessToken = '0wzIjZyzFilj0HWomm4Z6790xezQi5V6skFz81YB99IXHu9RE3';

        $oauthMiddleware = $this->buildSuccessOauthMiddleware($accessToken);

        // ...........................................................

        $mockHandler = new MockHandler([
            new RequestException('Error Communicating with Server.', new Request('GET', 'On leave...')),
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

        $this->expectException(RequestException::class);

        $response = $client->request('GET', 'resource');
    }
}
