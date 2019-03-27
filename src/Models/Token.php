<?php
/**
 * Token.
 */

namespace Bmatovu\OAuthNegotiator\Models;

use Carbon\Carbon;

/**
 * Class Token.
 */
class Token implements TokenInterface
{
    /**
     * Access token.
     *
     * @var string
     */
    protected $access_token;

    /**
     * Refresh token.
     *
     * @var string
     */
    protected $refresh_token = null;

    /**
     * Token type.
     *
     * @var string
     */
    protected $token_type = 'Bearer';

    /**
     * Expires at.
     *
     * @var string Datatime
     */
    protected $expires_at = null;

    /**
     * Constructor.
     *
     * @param array $attributes
     */
    public function __construct(array $attributes = [])
    {
        if (empty($attributes)) {
            return;
        }

        $this->access_token = $attributes['access_token'];

        if (isset($attributes['refresh_token'])) {
            $this->refresh_token = $attributes['refresh_token'];
        }

        $this->token_type = $attributes['token_type'];

        if (isset($attributes['expires_at'])) {
            $this->expires_at = $attributes['expires_at'];
        } else {
            $this->expires_at = Carbon::now()
                ->addSeconds($attributes['expires_in'])
                ->format('Y-m-d H:i:s');
        }
    }

    /**
     * Set access token.
     *
     * @param string $access_token
     */
    public function setAccessToken($access_token)
    {
        $this->access_token = $access_token;
    }

    /**
     * Get access token.
     *
     * @return string
     */
    public function getAccessToken()
    {
        return $this->access_token;
    }

    /**
     * Set refresh token.
     *
     * @param string $refresh_token
     */
    public function setRefreshToken($refresh_token)
    {
        $this->refresh_token = $refresh_token;
    }

    /**
     * Get refresh token.
     *
     * @return string|null
     */
    public function getRefreshToken()
    {
        return $this->refresh_token;
    }

    /**
     * Set token type.
     *
     * @param string $token_type
     */
    public function setTokenType($token_type)
    {
        $this->token_type = $token_type;
    }

    /**
     * Get token type.
     *
     * @return string
     */
    public function getTokenType()
    {
        return $this->token_type;
    }

    /**
     * Set expires at.
     *
     * @param string $expires_at
     */
    public function setExpiresAt($expires_at)
    {
        $this->expires_at = $expires_at;
    }

    /**
     * Get expires at.
     *
     * @return string Datetime
     */
    public function getExpiresAt()
    {
        return $this->expires_at;
    }

    /**
     * Determine if a token is expired.
     *
     * @return bool
     */
    public function isExpired()
    {
        if (is_null($this->expires_at)) {
            return false;
        }

        $expires_at = Carbon::createFromFormat('Y-m-d H:i:s', $this->expires_at);

        if ($expires_at->isFuture()) {
            return false;
        }

        return true;
    }
}
