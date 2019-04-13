<?php
/**
 * GrantTypeInterface.
 */

namespace Bmatovu\OAuthNegotiator\GrantTypes;

/**
 * Interface GrantTypeInterface.
 */
interface GrantTypeInterface
{
    /**
     * Obtain the token data returned by the OAuth2 server.
     *
     * @param string $refreshToken
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     *
     * @return array API token
     */
    public function getToken($refreshToken = null);
}
