<?php
/**
 * RefreshToken.
 */

namespace Bmatovu\OAuthNegotiator\GrantTypes;

use GuzzleHttp\ClientInterface;

/**
 * Class RefreshToken.
 */
class RefreshToken implements GrantTypeInterface
{
    /**
     * The token endpoint client.
     *
     * @var \GuzzleHttp\ClientInterface
     */
    private $client;

    /**
     * Configuration settings.
     *
     * @var array
     */
    private $config;

    /**
     * Constructor.
     *
     * @param \GuzzleHttp\ClientInterface $client
     * @param array                       $config
     *
     * @throws \Exception
     */
    public function __construct(ClientInterface $client, array $config)
    {
        $this->client = $client;

        $this->config = array_merge([
            'token_uri'     => '',
            'client_id'     => '',
            'client_secret' => '',
        ], $config);
    }

    /**
     * {@inheritdoc}
     */
    public function getToken($refreshToken = null)
    {
        $response = $this->client->request('POST', $this->config['token_uri'], [
            'headers' => [
                'Accept'        => 'application/json',
                'Content-Type'  => 'application/json',
                'Authorization' => 'Basic '.base64_encode($this->config['client_id'].':'.$this->config['client_secret']),
            ],
            'json' => [
                'grant_type'    => 'refresh_token',
                'refresh_token' => $refreshToken,
            ],
        ]);

        return json_decode($response->getBody(), true);
    }
}