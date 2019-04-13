<?php
/**
 * TokenRepository.
 */

namespace Bmatovu\OAuthNegotiator\Repositories;

use Bmatovu\OAuthNegotiator\Exceptions\TokenNotFoundException;
use Bmatovu\OAuthNegotiator\Models\Token;

/**
 * Class TokenRepository.
 */
class FileTokenRepository implements TokenRepositoryInterface
{
    /**
     * Token file.
     *
     * @var string
     */
    protected $tokenFile = 'token.txt';

    /**
     * Get token file.
     *
     * @return string file
     */
    public function getTokenFile()
    {
        return $this->tokenFile;
    }

    /**
     * Set token file.
     *
     * @param string $tokenFile
     */
    public function setTokenFile($tokenFile)
    {
        $this->tokenFile = $tokenFile;
    }

    /**
     * Constructor.
     *
     * @param string $tokenFile
     */
    public function __construct($tokenFile)
    {
        $this->tokenFile = $tokenFile;
    }

    /**
     * {@inheritdoc}
     */
    public function create(array $attributes)
    {
        $token = new Token($attributes);

        file_put_contents($this->tokenFile, serialize($token));

        return new Token($attributes);
    }

    /**
     * {@inheritdoc}
     */
    public function retrieve($access_token = null)
    {
        $token = unserialize(file_get_contents($this->tokenFile));

        if ($access_token) {
            if (!$token || ($token->getAccessToken() != $access_token)) {
                throw new TokenNotFoundException('Missing token.');
            }
        }

        return $token;
    }

    /**
     * {@inheritdoc}
     */
    public function update($access_token, array $attributes)
    {
        $token = unserialize(file_get_contents($this->tokenFile));

        if ($token->getAccessToken() != $access_token) {
            throw new TokenNotFoundException('Missing token.');
        }

        // Rewrite token
        $token = new Token($attributes);

        // Overwrite
        file_put_contents($this->tokenFile, serialize($attributes));

        return $token;
    }

    /**
     * {@inheritdoc}
     */
    public function delete($access_token)
    {
        $token = unserialize(file_get_contents($this->tokenFile));

        if ($token->getAccessToken() != $access_token) {
            throw new TokenNotFoundException('Missing token.');
        }

        unlink($this->tokenFile);
    }
}
