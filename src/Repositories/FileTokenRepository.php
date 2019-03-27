<?php
/**
 * TokenRepository.
 */

namespace Bmatovu\OAuthNegotiator\Repositories;

use Bmatovu\OAuthNegotiator\Models\Token;

/**
 * Class TokenRepository.
 */
class FileTokenRepository implements TokenRepositoryInterface
{
    /**
     * Token file.
     * @var string
     */
    protected $file = 'token.txt';

    /**
     * Constructor.
     *
     * @param string $file
     */
    public function __construct($file)
    {
        return $this->file = $file;
    }

    /**
     * Get token file.
     * @return string file
     */
    public function getTokenFile()
    {
        return $this->file;
    }

    /**
     * Set token file.
     * @param string $file
     */
    public function setTokenFile($file)
    {
        $this->file = $file;
    }

    /**
     * {@inheritdoc}
     */
    public function create(array $attributes)
    {
        $token = new Token($attributes);

        file_put_contents($this->file, serialize($token));

        return new Token($attributes);
    }

    /**
     * {@inheritdoc}
     */
    public function retrieve($access_token = null)
    {
        if(!file_exists($this->file)) {
            return null;
        }

        $token = unserialize(file_get_contents($this->file));

        if($token == null) {
            return null;
        }

        if($access_token && ($token->getAccessToken() != $access_token)) {
            return null;
        }

        return $token;
    }

    /**
     * {@inheritdoc}
     */
    public function update($access_token, array $attributes)
    {
        $token = unserialize(file_get_contents($this->file));

        if($token->getAccessToken() != $access_token) {
            return null;
        }

        // Rewrite token
        $token = new Token($attributes);

        // Overwrite
        file_put_contents($this->file, serialize($attributes));

        return $token;
    }

    /**
     * {@inheritdoc}
     */
    public function delete($access_token)
    {
        $token = unserialize(file_get_contents($this->file));

        if($token->getAccessToken() != $access_token) {
            return null;
        }

        unlink($this->file);
    }
}
