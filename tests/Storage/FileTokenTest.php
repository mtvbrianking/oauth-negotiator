<?php

namespace Bmatovu\OAuthNegotiator\Tests\Storage;

use Bmatovu\OAuthNegotiator\Exceptions\TokenNotFoundException;
use Bmatovu\OAuthNegotiator\Models\Token;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;
use Bmatovu\OAuthNegotiator\Models\TokenInterface;
use Bmatovu\OAuthNegotiator\Repositories\FileTokenRepository;

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

        if (file_exists($this->testTokenFile)) {
            unlink($this->testTokenFile);
        }

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
     *
     * @group passing
     */
    public function can_create_token()
    {
        $token = $this->repository->create([
            'access_token' => 'QC9jztmMfeHoRg5zyTiR',
            'refresh_token' => '4IAtuQ1aQZhHeRGlFcX6',
            'token_type' => 'Bearer',
            'expires_in' => 3600,
        ]);

        $this->assertFileExists($this->testTokenFile);

        $this->assertInstanceOf(TokenInterface::class, $token);
    }

    /**
     * @test
     *
     * @group token
     */
    public function can_sets_correct_expires_at()
    {
        $token = new Token([
            'access_token' => 'QC9jztmMfeHoRg5zyTiR',
            'refresh_token' => '4IAtuQ1aQZhHeRGlFcX6',
            'token_type' => 'Bearer',
            'expires_in' => 3600,
        ]);

        $expires_at = Carbon::now()->addSeconds(3600)->format('Y-m-d H:i:s');

        $this->assertInstanceOf(TokenInterface::class, $token);
        $this->assertEquals('QC9jztmMfeHoRg5zyTiR', $token->getAccessToken());
        $this->assertEquals('4IAtuQ1aQZhHeRGlFcX6', $token->getRefreshToken());
        $this->assertEquals('Bearer', $token->getTokenType());
        $this->assertEquals($expires_at, $token->getExpiresAt());
    }

    /**
     * @test
     *
     * @group failing
     */
    public function cant_retrieve_missing_token()
    {
        $this->assertNull($this->repository->retrieve());
    }

    /**
     * @test
     *
     * @group failing
     */
    public function cant_retrieve_unknown_token()
    {
        $accessToken = 'some_random_access_token';
        $this->expectException(TokenNotFoundException::class);
        $this->assertNull($this->repository->retrieve($accessToken));
    }

    /**
     * @test
     *
     * @group passing
     */
    public function can_retrieve_first_available_token()
    {
        $this->repository->create([
            'access_token' => 'QC9jztmMfeHoRg5zyTiR',
            'refresh_token' => '4IAtuQ1aQZhHeRGlFcX6',
            'token_type' => 'Bearer',
            'expires_in' => 3600,
        ]);

        $this->repository->create([
            'access_token' => 'neGb9VrmDgeHVucZlYvn',
            'refresh_token' => 'tPh9XtPrr7w62lEH1RlK',
            'token_type' => 'Bearer',
            'expires_in' => 3600,
        ]);

        $token = $this->repository->retrieve();

        $this->assertInstanceOf(TokenInterface::class, $token);
        $this->assertEquals('neGb9VrmDgeHVucZlYvn', $token->getAccessToken());
    }

    /**
     * @test
     *
     * @group passing
     */
    public function can_retrieve_token()
    {
        $this->repository->create([
            'access_token' => 'QC9jztmMfeHoRg5zyTiR',
            'refresh_token' => '4IAtuQ1aQZhHeRGlFcX6',
            'token_type' => 'Bearer',
            'expires_in' => 3600,
        ]);

        $token = $this->repository->retrieve();

        $this->assertInstanceOf(TokenInterface::class, $token);
        $this->assertEquals('QC9jztmMfeHoRg5zyTiR', $token->getAccessToken());
    }

    /**
     * @test
     *
     * @group failing
     */
    public function cant_update_missing_token()
    {
        $accessToken = 'some_random_access_token';
        $this->expectException(TokenNotFoundException::class);
        $this->assertNull($this->repository->update($accessToken, []));
    }
}