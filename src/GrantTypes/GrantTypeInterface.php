<?php
/**
 * GrantTypeInterface.
 */

namespace Bmatovu\GrantTypes;

/**
 * Interface GrantTypeInterface.
 */
interface GrantTypeInterface
{
    /**
     * Obtain the token data returned by the OAuth2 server.
     *
     * @param string $grantType    Name
     * @param string $refreshToken
     *
     * @return array API token
     */
    public function getToken($grantType = 'client_credentials', $refreshToken = null);
}
