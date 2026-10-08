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
     * CHARACTERIZATION of a suspected latent bug: fromJSON() does NOT hydrate
     * resultId / documentId / documentTemplateId even when those keys are
     * present in the input array — they remain the constructor nulls. Locked in
     * so the upcoming refactor must consciously decide to change it.
     */
    public function testFromJsonIgnoresResultAndDocumentFields(): void
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

        $this->assertNull($q->getResultId());
        $this->assertNull($q->getDocumentId());
        $this->assertNull($q->getDocumentTemplateId());
        $this->assertFalse($q->getIsComplete());
    }

    /**
     * CHARACTERIZATION of a suspected latent bug: with no 'type' key,
     * parent::fromJSON() applies its "Assessment" default and overwrites the
     * "Questionnaire" set by the constructor.
     */
    public function testFromJsonWithoutTypeKeyResetsTypeToAssessment(): void
    {
        $q = new AssignedQuestionnaire();
        $q->fromJSON(['id' => 'x', 'name' => 'n', 'questionnaireId' => 'q-1']);

        $this->assertSame('Assessment', $q->getType());
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
