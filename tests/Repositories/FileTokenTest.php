<?php

namespace Bmatovu\OAuthNegotiator\Tests\Repositories;

use Bmatovu\OAuthNegotiator\Exceptions\TokenNotFoundException;
use Bmatovu\OAuthNegotiator\Models\Token;
use Bmatovu\OAuthNegotiator\Models\TokenInterface;
use Bmatovu\OAuthNegotiator\Repositories\FileTokenRepository;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

class FileTokenTest extends TestCase
{
    /**
     * @var string
     */
    protected $testTokenFile;

    /**
     * @var \Bmatovu\OAuthNegotiator\Repositories\TokenRepositoryInterface
     */
    protected $repository;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        $this->testTokenFile = tempnam(sys_get_temp_dir(), 'phpunit_test_');

        $this->repository = new FileTokenRepository($this->testTokenFile);
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

    /**
     * @test
     */
    public function can_create_token()
    {
        $token = $this->repository->create([
            'access_token'  => 'QC9jztmMfeHoRg5zyTiR',
            'refresh_token' => '4IAtuQ1aQZhHeRGlFcX6',
            'token_type'    => 'Bearer',
            'expires_in'    => 3600,
        ]);

        $this->assertFileExists($this->testTokenFile);

        $this->assertInstanceOf(TokenInterface::class, $token);
    }

    /**
     * @test
     */
    public function cant_retrieve_missing_token()
    {
        $this->assertNull($this->repository->retrieve());
    }

    /**
     * @test
     */
    public function cant_retrieve_unknown_token()
    {
        $accessToken = 'some_random_access_token';
        $this->expectException(TokenNotFoundException::class);
        $this->assertNull($this->repository->retrieve($accessToken));
    }

    /**
     * @test
     */
    public function can_retrieve_first_available_token()
    {
        $this->repository->create([
            'access_token'  => 'neGb9VrmDgeHVucZlYvn',
            'refresh_token' => 'tPh9XtPrr7w62lEH1RlK',
            'token_type'    => 'Bearer',
            'expires_in'    => 3600,
        ]);

        $token = $this->repository->retrieve();

        $this->assertInstanceOf(TokenInterface::class, $token);
        $this->assertEquals('neGb9VrmDgeHVucZlYvn', $token->getAccessToken());
    }

    /**
     * @test
     */
    public function can_retrieve_token()
    {
        $this->repository->create([
            'access_token'  => 'QC9jztmMfeHoRg5zyTiR',
            'refresh_token' => '4IAtuQ1aQZhHeRGlFcX6',
            'token_type'    => 'Bearer',
            'expires_in'    => 3600,
        ]);

        $token = $this->repository->retrieve();

        $this->assertInstanceOf(TokenInterface::class, $token);
        $this->assertEquals('QC9jztmMfeHoRg5zyTiR', $token->getAccessToken());
    }

    /**
     * @test
     */
    public function cant_update_missing_token()
    {
        $accessToken = 'some_random_access_token';
        $this->expectException(TokenNotFoundException::class);
        $this->assertNull($this->repository->update($accessToken, []));
    }

    /**
     * @test
     */
    public function can_update_token()
    {
        $tokenData = [
            'access_token'  => 'QC9jztmMfeHoRg5zyTiR',
            'refresh_token' => '4IAtuQ1aQZhHeRGlFcX6',
            'token_type'    => 'Bearer',
            'expires_in'    => 3600,
        ];

        $this->repository->create($tokenData);

        $newTokenData = [
            'access_token'  => 'ij5AkeuBQs0hx2EDDcCp',
            'refresh_token' => 'HWomm4Z6790xezQi5V6s',
            'token_type'    => 'Basic',
            'expires_in'    => 0,
        ];

        $token = $this->repository->update($tokenData['access_token'], $newTokenData);

        $this->assertInstanceOf(TokenInterface::class, $token);
        $this->assertEquals($newTokenData['access_token'], $token->getAccessToken());
        $this->assertEquals($newTokenData['refresh_token'], $token->getRefreshToken());
        $this->assertEquals($newTokenData['token_type'], $token->getTokenType());

        $expires_at = Carbon::now()->addSeconds($newTokenData['expires_in'])->format('Y-m-d H:i:s');
        $this->assertEquals($expires_at, $token->getExpiresAt());
    }

    /**
     * @test
     */
    public function cant_delete_missing_token()
    {
        $accessToken = 'some_random_access_token';
        $this->expectException(TokenNotFoundException::class);
        $this->assertNull($this->repository->delete($accessToken, []));
    }

    /**
     * @test
     */
    public function can_delete_token()
    {
        $this->repository->create([
            'access_token'  => 'QC9jztmMfeHoRg5zyTiR',
            'refresh_token' => '4IAtuQ1aQZhHeRGlFcX6',
            'token_type'    => 'Bearer',
            'expires_in'    => 3600,
        ]);

        $token = $this->repository->delete('QC9jztmMfeHoRg5zyTiR');

        $this->assertNull($token);
        $this->assertFileNotExists($this->testTokenFile);
    }
}
