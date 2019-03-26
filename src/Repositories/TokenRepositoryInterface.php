<?php
/**
 * TokenRepositoryInterface.
 */

namespace Bmatovu\OAuthNegotiator\Repositories;

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
     * @param string $access_token
     *
     * @return \Bmatovu\OAuthNegotiator\Models\TokenInterface|null Token, null if non found..
     */
    public function retrieve($access_token = null);

    /**
     * Destory token.
     *
     * return void
     */
    public function delete($access_token);

    /**
     * Updates token.
     *
     * \Bmatovu\OAuthNegotiator\Models\TokenInterface|null
     */
    public function update($access_token, array $attributes);
}
