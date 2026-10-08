<?php

declare(strict_types=1);

namespace OpenEMR\Modules\DiscoverAndChange\Assessments\Tests;

use DateTime;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Models\AssessmentGroup;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Models\AssessmentSnippet;
use PHPUnit\Framework\TestCase;

/**
 * Pure-unit (no DB) characterization tests for AssessmentGroup: snippet collection,
 * getters/setters, and serialization (date formatting + null-field passthrough).
 */
class AssessmentGroupTest extends TestCase
{
    public function testConstructorDefaults(): void
    {
        $g = new AssessmentGroup();

        $this->assertSame([], $g->getAssessments());
        $this->assertInstanceOf(DateTime::class, $g->getCreated());
        $this->assertInstanceOf(DateTime::class, $g->getUpdated());
        $this->assertNull($g->getClientId());
        $this->assertNull($g->getCompanyId());
        $this->assertNull($g->getProfileId());
    }

    public function testGettersAndSetters(): void
    {
        $g = new AssessmentGroup();
        $g->setId(5);
        $g->setName('Intake battery');
        $g->setProfileId('profile-1');
        $g->setClientId(11);
        $g->setCompanyId(22);

        $this->assertSame(5, $g->getId());
        $this->assertSame('Intake battery', $g->getName());
        $this->assertSame('profile-1', $g->getProfileId());
        $this->assertSame(11, $g->getClientId());
        $this->assertSame(22, $g->getCompanyId());
    }

    public function testSetClientIdAndCompanyIdAcceptNull(): void
    {
        $g = new AssessmentGroup();
        $g->setClientId(null);
        $g->setCompanyId(null);
        $this->assertNull($g->getClientId());
        $this->assertNull($g->getCompanyId());
    }

    public function testAddAssessmentSnippetAppends(): void
    {
        $g = new AssessmentGroup();

        $s1 = new AssessmentSnippet();
        $s1->setId(1);
        $s1->setName('a');
        $s1->setUid('u1');

        $s2 = new AssessmentSnippet();
        $s2->setId(2);
        $s2->setName('b');
        $s2->setUid('u2');

        $g->addAssessmentSnippet($s1);
        $g->addAssessmentSnippet($s2);

        $assessments = $g->getAssessments();
        $this->assertCount(2, $assessments);
        $this->assertSame(1, $assessments[0]->getId());
        $this->assertSame(2, $assessments[1]->getId());
    }

    public function testSetAssessmentsReplacesCollection(): void
    {
        $g = new AssessmentGroup();
        $s = new AssessmentSnippet();
        $s->setId(3);
        $s->setName('c');
        $s->setUid('u3');

        $g->setAssessments([$s]);
        $this->assertSame([$s], $g->getAssessments());
    }

    public function testCreatedAndUpdatedSetters(): void
    {
        $g = new AssessmentGroup();
        $created = new DateTime('2026-01-01T00:00:00+00:00');
        $updated = new DateTime('2026-02-02T00:00:00+00:00');
        $g->setCreated($created);
        $g->setUpdated($updated);
        $this->assertSame($created, $g->getCreated());
        $this->assertSame($updated, $g->getUpdated());
    }

    public function testJsonSerializeRoundTrip(): void
    {
        $g = new AssessmentGroup();
        $g->setId(5);
        $g->setName('Intake battery');
        $g->setProfileId('profile-1');
        $g->setClientId(11);
        $g->setCompanyId(22);
        $g->setCreated(new DateTime('2026-01-01 08:30:00'));
        $g->setUpdated(new DateTime('2026-02-02 09:45:00'));

        $snippet = new AssessmentSnippet();
        $snippet->setId(1);
        $snippet->setName('a');
        $snippet->setUid('u1');
        $g->addAssessmentSnippet($snippet);

        $out = $g->jsonSerialize();

        $this->assertSame(5, $out['id']);
        $this->assertSame('Intake battery', $out['name']);
        $this->assertSame('profile-1', $out['profileId']);
        $this->assertSame(11, $out['clientId']);
        $this->assertSame(22, $out['companyId']);
        $this->assertSame('2026-01-01 08:30:00', $out['created']);
        $this->assertSame('2026-02-02 09:45:00', $out['updated']);
        $this->assertCount(1, $out['assessments']);
        $this->assertSame($snippet, $out['assessments'][0]);
    }

    public function testJsonSerializeNullOptionalFieldsAndEmptyAssessments(): void
    {
        $g = new AssessmentGroup();
        $g->setId(8);
        $g->setName('Empty group');

        $out = $g->jsonSerialize();

        $this->assertNull($out['profileId']);
        $this->assertNull($out['clientId']);
        $this->assertNull($out['companyId']);
        $this->assertSame([], $out['assessments']);
    }
}
