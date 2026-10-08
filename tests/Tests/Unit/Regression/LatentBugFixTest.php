<?php

declare(strict_types=1);

namespace OpenEMR\Modules\DiscoverAndChange\Assessments\Tests;

use OpenEMR\Common\Crypto\CryptoGen;
use OpenEMR\Common\Logging\SystemLogger;
use OpenEMR\Common\Uuid\UuidRegistry;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Models\SystemUser;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Services\AssessmentRepository;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Services\LibraryAssetResultBlobRepository;
use PHPUnit\Framework\TestCase;

/**
 * Regression tests for latent bugs the characterization suite surfaced and that
 * the v0.11.2 fix pass addressed. Each previously threw on realistic input.
 */
class LatentBugFixTest extends TestCase
{
    /**
     * Before: SystemUser::$_companyName had no default, so jsonSerialize() on a
     * freshly-constructed user threw "typed property ... must not be accessed
     * before initialization". Now it defaults to ''.
     */
    public function testFreshSystemUserSerializesWithoutCrash(): void
    {
        $user = new SystemUser('uuid-1', 'bob', null);

        $out = $user->jsonSerialize();

        $this->assertSame('', $out['_companyName']);
        $this->assertSame('uuid-1', $out['_id']);
        $this->assertNull($out['_companyID']);
    }

    /**
     * Before: an absent/unparseable `date` made createFromFormat() return false,
     * assigned to the non-null AssessmentSummary::$date => TypeError. Now the
     * constructor default (a DateTime) is kept.
     */
    public function testAssessmentSummaryKeepsDefaultDateWhenDateAbsent(): void
    {
        $repo = new AssessmentRepository(new SystemLogger());
        $m = new \ReflectionMethod($repo, 'hydrateAssessmentSummaryFromDatabaseRecord');
        $m->setAccessible(true);
        $bytes = UuidRegistry::uuidToBytes('00000000-0000-4000-8000-0000000000aa');

        // no 'date' key at all
        $summary = $m->invoke($repo, [
            'uuid' => $bytes,
            'id' => 1,
            'uid' => 'phptest-uid',
            'name' => 'n',
            'description' => 'd',
            'data' => '{}',
            'company_id' => 3,
        ]);

        $this->assertInstanceOf(\DateTime::class, $summary->date);
    }

    /**
     * Before: an absent/unparseable `creation_date` made createFromFormat() return
     * false, passed to setCreationDate(\DateTime) => TypeError. Now the DTO's
     * constructor default is kept.
     */
    public function testResultBlobKeepsDefaultCreationDateWhenAbsent(): void
    {
        $repo = new LibraryAssetResultBlobRepository(new SystemLogger(), new CryptoGen());
        $m = new \ReflectionMethod($repo, 'hydrateResultBlobFromRecord');
        $m->setAccessible(true);

        // empty answers/journal keep it off the decrypt path; no 'creation_date'
        $blob = $m->invoke($repo, [
            'id' => 'phptest-result-1',
            'asset_id' => 7,
            'assignmentitem_id' => 'item-1',
            'answers' => '',
            'journal_entry' => '',
        ]);

        $this->assertInstanceOf(\DateTime::class, $blob->getCreationDate());
        $this->assertSame(7, $blob->getAssetId());
    }
}
