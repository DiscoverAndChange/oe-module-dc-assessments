<?php

declare(strict_types=1);

namespace OpenEMR\Modules\DiscoverAndChange\Assessments\Tests;

use OpenEMR\Common\Logging\SystemLogger;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Models\AssignedAssessment;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Models\AssignedLibraryAsset;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Models\Assignment;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Services\ClientRepository;
use PHPUnit\Framework\TestCase;

/**
 * Covers ClientRepository's input-validation / guard branches, which throw before any
 * DB access. The happy paths orchestrate AssignmentRepository/AssessmentGroupService/
 * ListService against seeded data and are left to DB integration tests.
 */
class ClientRepositoryTest extends TestCase
{
    private function repo(): ClientRepository
    {
        return new ClientRepository(new SystemLogger());
    }

    public function testAddTemplateProfileAssignmentRejectsEmptyClientId(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->repo()->addTemplateProfileAssignmentToClient('', 'profile-1', 1, null);
    }

    public function testAddAssignmentRejectsAssignmentWithNoItems(): void
    {
        $assignment = new Assignment();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('at least one item');
        $this->repo()->addAssignmentToClient('client-uuid', $assignment, 1);
    }

    public function testAddAssignmentRejectsAssessmentItemWithEmptyUid(): void
    {
        $item = new AssignedAssessment();
        $item->setUid('');
        $assignment = new Assignment();
        $assignment->addItem($item);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Assessment id must be set');
        $this->repo()->addAssignmentToClient('client-uuid', $assignment, 1);
    }

    public function testAddAssignmentRejectsLibraryAssetItemWithEmptyAssetId(): void
    {
        $item = new AssignedLibraryAsset();
        $item->setAssetId(0);
        $assignment = new Assignment();
        $assignment->addItem($item);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Asset id must be set');
        $this->repo()->addAssignmentToClient('client-uuid', $assignment, 1);
    }
}
