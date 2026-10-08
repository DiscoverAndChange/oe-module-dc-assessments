<?php

declare(strict_types=1);

namespace OpenEMR\Modules\DiscoverAndChange\Assessments\Tests;

use OpenEMR\Modules\DiscoverAndChange\Assessments\Models\AssignedQuestionnaire;
use PHPUnit\Framework\TestCase;

/**
 * Pure-unit (no DB) characterization tests for AssignedQuestionnaire.
 *
 * AssignedQuestionnaire extends Assignment: fromJSON() calls parent::fromJSON()
 * then sets questionnaireId only. resultId/documentId/documentTemplateId are set
 * through their own setters (not via fromJSON) — see the characterization test
 * and the latent-bug notes in the report.
 */
class AssignedQuestionnaireTest extends TestCase
{
    public function testConstructorDefaults(): void
    {
        $q = new AssignedQuestionnaire();
        $this->assertSame('Questionnaire', $q->getType());
        $this->assertNull($q->getResultId());
        $this->assertNull($q->getDocumentId());
        $this->assertNull($q->getDocumentTemplateId());
    }

    public function testFromJsonPopulatesInheritedAndQuestionnaireId(): void
    {
        $q = new AssignedQuestionnaire();
        $q->fromJSON([
            'id' => 'uuid-1',
            'name' => 'Intake form',
            'type' => 'Questionnaire',
            'appointmentId' => '5',
            'dateAssigned' => '2026-01-02T03:04:05.000000+00:00',
            'questionnaireId' => 'q-123',
        ]);

        // inherited
        $this->assertSame('uuid-1', $q->getId());
        $this->assertSame('Intake form', $q->getName());
        $this->assertSame('Questionnaire', $q->getType());
        $this->assertSame('5', $q->getAppointmentId());
        $this->assertInstanceOf(\DateTime::class, $q->getDateAssigned());

        // subclass
        $this->assertSame('q-123', $q->getQuestionnaireId());
    }

    /**
     * REGRESSION (fixed v0.12.3): fromJSON() now hydrates resultId / documentId /
     * documentTemplateId when present, giving a symmetric serialize -> fromJSON round
     * trip (jsonSerialize() has always emitted these keys). A non-null resultId also
     * marks the item complete via setResultId()'s side effect.
     */
    public function testFromJsonHydratesResultAndDocumentFields(): void
    {
        $q = new AssignedQuestionnaire();
        $q->fromJSON([
            'id' => 'x',
            'name' => 'n',
            'type' => 'Questionnaire',
            'questionnaireId' => 'q-1',
            'resultId' => 'res-9',
            'documentId' => 'doc-9',
            'documentTemplateId' => 7,
        ]);

        $this->assertSame('res-9', $q->getResultId());
        $this->assertSame('doc-9', $q->getDocumentId());
        $this->assertSame(7, $q->getDocumentTemplateId());
        $this->assertTrue($q->getIsComplete());
    }

    /**
     * REGRESSION (fixed v0.12.3): resultId hydration must not clobber the payload's
     * dateCompleted. setResultId(non-null) resets dateCompleted to "now"; fromJSON
     * restores the parsed value when the payload carries one.
     */
    public function testFromJsonResultIdDoesNotClobberDateCompleted(): void
    {
        $q = new AssignedQuestionnaire();
        $q->fromJSON([
            'id' => 'x',
            'name' => 'n',
            'type' => 'Questionnaire',
            'questionnaireId' => 'q-1',
            'resultId' => 'res-9',
            'dateCompleted' => '2026-01-02T03:04:05.000000+00:00',
        ]);

        $this->assertSame('2026-01-02', $q->getDateCompleted()->format('Y-m-d'));
    }

    /**
     * REGRESSION (fixed v0.12.3): with no 'type' key, parent::fromJSON() no longer
     * overwrites the "Questionnaire" type the constructor set.
     */
    public function testFromJsonWithoutTypeKeyPreservesConstructorType(): void
    {
        $q = new AssignedQuestionnaire();
        $q->fromJSON(['id' => 'x', 'name' => 'n', 'questionnaireId' => 'q-1']);

        $this->assertSame('Questionnaire', $q->getType());
    }

    public function testDocumentSettersAreFluent(): void
    {
        $q = new AssignedQuestionnaire();

        $this->assertSame($q, $q->setDocumentId('doc-1'));
        $this->assertSame($q, $q->setDocumentTemplateId(42));
        $this->assertSame('doc-1', $q->getDocumentId());
        $this->assertSame(42, $q->getDocumentTemplateId());
    }

    public function testSetResultIdMarksComplete(): void
    {
        $q = new AssignedQuestionnaire();
        $q->setResultId('r-1');

        $this->assertSame('r-1', $q->getResultId());
        $this->assertTrue($q->getIsComplete());
        $this->assertInstanceOf(\DateTime::class, $q->getDateCompleted());
    }

    public function testJsonSerializeIncludesInheritedAndSubclassKeys(): void
    {
        $q = new AssignedQuestionnaire();
        $q->fromJSON([
            'id' => 'x1',
            'name' => 'RT',
            'type' => 'Questionnaire',
            'questionnaireId' => 'q-9',
        ]);
        $q->setResultId('res-9');
        $q->setDocumentId('doc-9');
        $q->setDocumentTemplateId(3);

        $out = $q->jsonSerialize();

        $this->assertSame('x1', $out['id']);
        $this->assertSame('Questionnaire', $out['type']);
        $this->assertSame('q-9', $out['questionnaireId']);
        $this->assertSame('res-9', $out['resultId']);
        $this->assertSame('doc-9', $out['documentId']);
        $this->assertSame(3, $out['documentTemplateId']);
    }

    public function testJsonSerializeNullsSubclassKeysWhenUnset(): void
    {
        $q = new AssignedQuestionnaire();
        $q->fromJSON(['id' => 'x', 'name' => 'n', 'type' => 'Questionnaire', 'questionnaireId' => 'q-1']);

        $out = $q->jsonSerialize();

        $this->assertArrayHasKey('resultId', $out);
        $this->assertNull($out['resultId']);
        $this->assertNull($out['documentId']);
        $this->assertNull($out['documentTemplateId']);
    }
}
