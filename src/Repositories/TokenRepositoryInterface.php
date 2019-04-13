<?php
/**
 * TokenRepositoryInterface.
 */

namespace Bmatovu\OAuthNegotiator\Repositories;
use Bmatovu\OAuthNegotiator\Exceptions\TokenNotFoundException;

/**
 * Interface TokenRepositoryInterface.
 */
interface TokenRepositoryInterface
{
    /**
     * Create token.
     *
     * @param array $attributes
     *
     * @return \Bmatovu\OAuthNegotiator\Models\TokenInterface Token created.
     */
    public function create(array $attributes);

    /**
     * Retrieve token.
     *
     * Specified token, or any token available in storage.
     *
     * @param string $access_token
     *
     * @return \Bmatovu\OAuthNegotiator\Models\TokenInterface|null Token, null if non found.
     * @throws \Bmatovu\OAuthNegotiator\Exceptions\TokenNotFoundException
     */
    public function retrieve($access_token = null);

    /**
     * Updates token.
     *
     * @return \Bmatovu\OAuthNegotiator\Models\TokenInterface Token
     * @throws \Bmatovu\OAuthNegotiator\Exceptions\TokenNotFoundException
     */
    public function update($access_token, array $attributes);

    /**
     * Destroy token.
     *
     * @param string $access_token
     *
     * @return void
     * @throws \Bmatovu\OAuthNegotiator\Exceptions\TokenNotFoundException
     */
    public function delete($access_token);
}
