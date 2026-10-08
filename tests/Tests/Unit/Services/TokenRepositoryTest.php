<?php

declare(strict_types=1);

namespace OpenEMR\Modules\DiscoverAndChange\Assessments\Tests;

use OpenEMR\Modules\DiscoverAndChange\Assessments\Services\TokenRepository;
use PHPUnit\Framework\TestCase;

/**
 * TokenRepository is currently a stub: user tokens were removed and getUserTokens()
 * always returns an empty array. This locks in that contract so a future
 * reintroduction is a conscious change.
 */
class TokenRepositoryTest extends TestCase
{
    public function testGetUserTokensReturnsEmptyArray(): void
    {
        $repo = new TokenRepository();

        $this->assertSame([], $repo->getUserTokens(1));
        $this->assertSame([], $repo->getUserTokens('any-user'));
        $this->assertSame([], $repo->getUserTokens(null));
    }
}
