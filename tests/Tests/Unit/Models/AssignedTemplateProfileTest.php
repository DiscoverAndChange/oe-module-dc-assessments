<?php

declare(strict_types=1);

namespace OpenEMR\Modules\DiscoverAndChange\Assessments\Tests;

use OpenEMR\Modules\DiscoverAndChange\Assessments\Models\Assignment;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Models\AssignedTemplateProfile;
use PHPUnit\Framework\TestCase;

/**
 * Pure-unit (no DB) characterization tests for AssignedTemplateProfile.
 *
 * AssignedTemplateProfile extends Assignment: fromJSON() calls parent::fromJSON()
 * then sets profileId. jsonSerialize() merges the subclass payload with the
 * parent payload.
 */
class AssignedTemplateProfileTest extends TestCase
{
    public function testConstructorTypeAndIsGroupType(): void
    {
        $p = new AssignedTemplateProfile();
        $this->assertSame('TemplateProfile', $p->getType());
        $this->assertTrue($p->isGroupType());
        $this->assertNull($p->getProfileId());
    }

    public function testFromJsonPopulatesInheritedAndProfileId(): void
    {
        $p = new AssignedTemplateProfile();
        $p->fromJSON([
            'id' => 'uuid-1',
            'name' => 'Onboarding profile',
            'type' => 'TemplateProfile',
            'dateAssigned' => '2026-01-02T03:04:05.000000+00:00',
            'profileId' => 'prof-123',
        ]);

        // inherited
        $this->assertSame('uuid-1', $p->getId());
        $this->assertSame('Onboarding profile', $p->getName());
        $this->assertSame('TemplateProfile', $p->getType());
        $this->assertInstanceOf(\DateTime::class, $p->getDateAssigned());

        // subclass
        $this->assertSame('prof-123', $p->getProfileId());
    }

    /**
     * CHARACTERIZATION of a suspected latent bug: with no 'type' key,
     * parent::fromJSON()'s "Assessment" default overwrites the "TemplateProfile"
     * set by the constructor, so isGroupType() flips to false after hydration.
     */
    public function testFromJsonWithoutTypeKeyResetsTypeToAssessment(): void
    {
        $p = new AssignedTemplateProfile();
        $p->fromJSON(['id' => 'x', 'name' => 'n', 'profileId' => 'prof-1']);

        $this->assertSame('Assessment', $p->getType());
        $this->assertFalse($p->isGroupType());
    }

    public function testSetProfileId(): void
    {
        $p = new AssignedTemplateProfile();
        $p->setProfileId('prof-7');

        $this->assertSame('prof-7', $p->getProfileId());
    }

    public function testJsonSerializeIncludesProfileIdAndInheritedKeys(): void
    {
        $p = new AssignedTemplateProfile();
        $p->fromJSON([
            'id' => 'x1',
            'name' => 'RT',
            'type' => 'TemplateProfile',
            'profileId' => 'prof-9',
        ]);

        $out = $p->jsonSerialize();

        $this->assertSame('prof-9', $out['profileId']);
        $this->assertSame('x1', $out['id']);
        $this->assertSame('TemplateProfile', $out['type']);
        $this->assertArrayHasKey('items', $out);
        $this->assertSame([], $out['items']);
    }

    public function testJsonSerializeSerializesChildItems(): void
    {
        $p = new AssignedTemplateProfile();
        $p->fromJSON(['id' => 'parent', 'name' => 'n', 'type' => 'TemplateProfile', 'profileId' => 'prof-1']);

        $child = new Assignment();
        $child->setId('child-1');
        $child->setName('Child');
        $child->setType('Questionnaire');
        $p->addItem($child);

        $out = $p->jsonSerialize();

        $this->assertCount(1, $out['items']);
        $this->assertSame('child-1', $out['items'][0]['id']);
        $this->assertSame('Questionnaire', $out['items'][0]['type']);
    }
}
