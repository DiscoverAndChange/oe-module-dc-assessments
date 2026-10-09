<?php

declare(strict_types=1);

namespace OpenEMR\Modules\DiscoverAndChange\Assessments\Tests;

use OpenEMR\Common\Database\QueryUtils;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Services\QuestionnaireResponseOnSiteDocumentService;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Tests\Support\AssignmentFixture;
use OpenEMR\Services\QuestionnaireResponseService;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../Support/AssignmentFixture.php';

/**
 * Proves the questionnaire-response PDF actually generates and is stored as a PDF Document.
 * This is the completion-flow step the module owner relies on:
 * QuestionnaireResponseOnSiteDocumentService::createDocument() flattens the response to HTML and
 * runs it through PatientPortalPDFDocumentCreator -> a stored \Document (application/pdf).
 */
class QuestionnaireResponsePdfTest extends TestCase
{
    use AssignmentFixture;

    /** @var int[] ids of documents created during the test, removed in tearDown */
    private array $createdDocumentIds = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedPatientAndAssessment();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        foreach ($this->createdDocumentIds as $docId) {
            QueryUtils::sqlStatementThrowException("DELETE FROM categories_to_documents WHERE document_id = ?", [$docId], true);
            QueryUtils::sqlStatementThrowException("DELETE FROM documents WHERE id = ?", [$docId], true);
        }
        // belt-and-suspenders: any document left attached to the throwaway patient
        QueryUtils::sqlStatementThrowException("DELETE FROM documents WHERE foreign_id = ?", [$this->fxPid], true);
        $this->cleanupAssignmentFixtures();
    }

    public function testCreateDocumentGeneratesAndStoresAPdf(): void
    {
        $qr = [
            'patient_id' => $this->fxPid,
            'response_id' => 'phptest-resp-1',
            'item' => [
                ['linkId' => 'q1', 'text' => 'How are you feeling?', 'answer' => [['valueString' => 'Fine']]],
            ],
        ];

        $service = new QuestionnaireResponseOnSiteDocumentService(new QuestionnaireResponseService());
        $document = $service->createDocument('phptest-template-1', 'Patient Information', $qr, 'phptest Questionnaire');

        $this->assertInstanceOf(\Document::class, $document);
        $docId = (int) $document->get_id();
        $this->assertGreaterThan(0, $docId, 'a document row should be created');
        $this->createdDocumentIds[] = $docId;

        $this->assertSame('application/pdf', $document->get_mimetype());
        // the strongest proof the PDF engine actually produced a PDF: the stored bytes
        $data = $document->get_data();
        $this->assertIsString($data);
        $this->assertStringStartsWith('%PDF', $data, 'stored document should be a real PDF');
    }
}
