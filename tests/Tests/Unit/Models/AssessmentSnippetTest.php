<?php

declare(strict_types=1);

namespace OpenEMR\Modules\DiscoverAndChange\Assessments\Tests;

use OpenEMR\Modules\DiscoverAndChange\Assessments\Models\AssessmentSnippet;
use PHPUnit\Framework\TestCase;

/**
 * Pure-unit (no DB) characterization tests for AssessmentSnippet getters/setters
 * and serialization.
 */
class AssessmentSnippetTest extends TestCase
{
    public function testGettersAndSetters(): void
    {
        $s = new AssessmentSnippet();
        $s->setId(42);
        $s->setName('PHQ-9');
        $s->setUid('uid-42');

        $this->assertSame(42, $s->getId());
        $this->assertSame('PHQ-9', $s->getName());
        $this->assertSame('uid-42', $s->getUid());
    }

    public function testJsonSerialize(): void
    {
        $s = new AssessmentSnippet();
        $s->setId(7);
        $s->setName('GAD-7');
        $s->setUid('uid-7');

        $out = $s->jsonSerialize();

        $this->assertSame(
            ['id' => 7, 'name' => 'GAD-7', 'uid' => 'uid-7'],
            $out
        );
    }
}
