<?php

declare(strict_types=1);

namespace OpenEMR\Modules\DiscoverAndChange\Assessments\Tests\Services\Task;

use OpenEMR\Common\Database\QueryUtils;
use OpenEMR\Common\Uuid\UuidRegistry;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Services\Task\QuestionnairePortalTaskFHIRResourceService;
use OpenEMR\Services\DocumentTemplates\DocumentTemplateService;
use OpenEMR\Services\PatientService;
use OpenEMR\Services\Search\ReferenceSearchField;
use OpenEMR\Services\Search\ReferenceSearchValue;
use OpenEMR\Services\Search\SearchFieldException;
use OpenEMR\Validators\ProcessingResult;
use PHPUnit\Framework\TestCase;

/**
 * DB-backed coverage for the SEARCH side of QuestionnairePortalTaskFHIRResourceService:
 * searchForOpenEMRRecords() (protected) and getTaskDataForTemplates() (private), invoked
 * via reflection. Sibling QuestionnairePortalTaskFHIRResourceServiceTest already covers
 * supportsCode()/parseOpenEMRRecord()/update() — those are NOT duplicated here.
 *
 * These are service-layer methods: they do NOT call AclMain, so they are reachable in the
 * unit harness. No mailer and no network are touched; the only non-DB collaborator that
 * must be fed in (the patient ReferenceSearchField) is a createMock.
 *
 * Covered paths:
 *   - searchForOpenEMRRecords(): the required-patient guard (throws SearchFieldException),
 *     and the empty-result path for a real, freshly seeded patient that has no portal-
 *     assigned questionnaire templates (exercises PatientService::getPidByUuid and
 *     DocumentTemplateService::getPortalAssignedTemplates against the DB).
 *   - getTaskDataForTemplates(): the guard that skips any template whose template_content
 *     carries no {Questionnaire:id} token (exercises the real onsite_documents +
 *     patient_data lookups, then returns an empty ProcessingResult).
 *
 * DEFERRED (happy path) — building an actual Task requires seeding document-template rows
 * (document_templates / onsite_documents assigned to the patient) AND a matching core
 * `questionnaire` row that QuestionnaireService::fetchQuestionnaireById() can resolve to a
 * uuid + name. Those core/document-template fixtures are not reasonably seedable here, so
 * the record-producing branch of getTaskDataForTemplates() is left to the integration suite.
 */
class QuestionnairePortalTaskFHIRResourceServiceSearchTest extends TestCase
{
    /** patient_data.id (forced == pid) of the throwaway patient. */
    private int $pid;

    /** canonical uuid string of the throwaway patient. */
    private string $patientUuid;

    protected function setUp(): void
    {
        parent::setUp();

        QueryUtils::sqlStatementThrowException(
            "INSERT INTO " . PatientService::TABLE_NAME . " (fname, lname, pubpid, date) VALUES ('phptest-qpt', 'phptest-qpt', 'phptest-qpt', NOW())"
        );
        $this->pid = (int) QueryUtils::fetchSingleValue(
            "SELECT id FROM " . PatientService::TABLE_NAME . " WHERE fname = 'phptest-qpt' ORDER BY id DESC LIMIT 1",
            'id',
            []
        );
        QueryUtils::sqlStatementThrowException(
            "UPDATE " . PatientService::TABLE_NAME . " SET pid = ? WHERE id = ?",
            [$this->pid, $this->pid]
        );
        UuidRegistry::createMissingUuidsForTables(['patient_data']);
        /** @var string $uuidBytes */
        $uuidBytes = QueryUtils::fetchSingleValue(
            "SELECT uuid FROM " . PatientService::TABLE_NAME . " WHERE id = ?",
            'uuid',
            [$this->pid]
        );
        $this->patientUuid = UuidRegistry::uuidToString($uuidBytes);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        QueryUtils::sqlStatementThrowException(
            "DELETE FROM " . PatientService::TABLE_NAME . " WHERE fname LIKE 'phptest%'",
            [],
            true
        );
    }

    /**
     * @param array<string, mixed> $params
     */
    private function invokeSearch(array $params): ProcessingResult
    {
        $service = new QuestionnairePortalTaskFHIRResourceService();
        $m = new \ReflectionMethod($service, 'searchForOpenEMRRecords');
        $m->setAccessible(true);
        /** @var ProcessingResult $result */
        $result = $m->invoke($service, $params);
        return $result;
    }

    /**
     * @param list<array<string, mixed>> $templates
     */
    private function invokeGetTaskDataForTemplates(array $templates): ProcessingResult
    {
        $service = new QuestionnairePortalTaskFHIRResourceService();
        $m = new \ReflectionMethod($service, 'getTaskDataForTemplates');
        $m->setAccessible(true);
        /** @var ProcessingResult $result */
        $result = $m->invoke($service, new DocumentTemplateService(), new ProcessingResult(), $templates);
        return $result;
    }

    /** A patient ReferenceSearchField whose single value resolves to the seeded uuid. */
    private function patientField(string $uuid): ReferenceSearchField
    {
        $value = $this->createMock(ReferenceSearchValue::class);
        $value->method('getId')->willReturn($uuid);

        $field = $this->createMock(ReferenceSearchField::class);
        $field->method('getValues')->willReturn([$value]);
        return $field;
    }

    public function testSearchThrowsWhenPatientFieldMissing(): void
    {
        $this->expectException(SearchFieldException::class);
        $this->invokeSearch([]);
    }

    public function testSearchReturnsEmptyForPatientWithoutAssignedTemplates(): void
    {
        // the patient is brand new and has no portal-assigned questionnaire templates,
        // so getPortalAssignedTemplates returns nothing and the result carries no data.
        $result = $this->invokeSearch(['patient' => $this->patientField($this->patientUuid)]);

        $this->assertInstanceOf(ProcessingResult::class, $result);
        $this->assertTrue($result->isValid());
        $this->assertCount(0, $result->getData(), 'no assigned templates => no task records');
    }

    public function testGetTaskDataForTemplatesSkipsTemplateWithoutQuestionnaireToken(): void
    {
        // template_content has no {Questionnaire:id} token, so the regex never matches and
        // the template is skipped without producing a record (and without touching
        // QuestionnaireService). The onsite_documents + patient_data lookups still run.
        $templates = [
            [
                'id' => 999999,
                'pid' => $this->pid,
                'template_content' => 'this template has no questionnaire token',
                'profile_date' => null,
                'modified_date' => null,
            ],
        ];

        $result = $this->invokeGetTaskDataForTemplates($templates);

        $this->assertInstanceOf(ProcessingResult::class, $result);
        $this->assertCount(0, $result->getData());
    }
}
