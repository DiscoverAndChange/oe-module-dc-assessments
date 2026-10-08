<?php

declare(strict_types=1);

namespace OpenEMR\Modules\DiscoverAndChange\Assessments\Tests;

use OpenEMR\Modules\DiscoverAndChange\Assessments\GlobalConfig;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Services\MessageTemplateRepository;
use PHPUnit\Framework\TestCase;
use Twig\Environment;

/**
 * Pure-unit tests for MessageTemplateRepository. The repository renders the patient
 * invitation message via an injected Twig environment and GlobalConfig, so both are
 * mocked — no DB, no real template filesystem.
 */
class MessageTemplateRepositoryTest extends TestCase
{
    private function makeRepo(Environment $twig, GlobalConfig $config): MessageTemplateRepository
    {
        return new MessageTemplateRepository($twig, $config);
    }

    public function testGetTemplateSettingsBuildsDisplayNameAndSubject(): void
    {
        $twig = $this->createMock(Environment::class);
        // Capture the data array passed to the template and assert the trimmed name.
        $twig->expects($this->once())
            ->method('render')
            ->with(
                $this->equalTo('discoverandchange/notifications/patient-complete-tasks.text.twig'),
                $this->callback(function ($data) {
                    return $data['client_display_name'] === 'Jane Doe'
                        && $data['patient_portal_url'] === 'https://portal.example/launch';
                })
            )
            ->willReturn('rendered-body');

        $config = $this->createMock(GlobalConfig::class);
        $config->method('getSmartAppPatientLaunchUri')->willReturn('https://portal.example/launch');
        $config->method('getApplicationName')->willReturn('DAC');

        $repo = $this->makeRepo($twig, $config);
        $settings = $repo->getTemplateSettingsForFacility(['fname' => 'Jane', 'lname' => 'Doe'], null);

        $this->assertSame('rendered-body', $settings['template']);
        $this->assertStringStartsWith('DAC-', $settings['subject']);
    }

    public function testGetTemplateSettingsToleratesMissingNameColumns(): void
    {
        $twig = $this->createMock(Environment::class);
        $twig->method('render')->willReturnCallback(function ($tpl, $data) {
            // display name of an empty record trims to ''
            return 'name=[' . $data['client_display_name'] . ']';
        });
        $config = $this->createMock(GlobalConfig::class);
        $config->method('getSmartAppPatientLaunchUri')->willReturn('');
        $config->method('getApplicationName')->willReturn('DAC');

        $repo = $this->makeRepo($twig, $config);
        $settings = $repo->getTemplateSettingsForFacility([], null);

        $this->assertSame('name=[]', $settings['template']);
    }

    public function testGetTemplateForClientReturnsSubjectAndMessage(): void
    {
        $twig = $this->createMock(Environment::class);
        $twig->method('render')->willReturn('body');
        $config = $this->createMock(GlobalConfig::class);
        $config->method('getSmartAppPatientLaunchUri')->willReturn('u');
        $config->method('getApplicationName')->willReturn('DAC');

        $repo = $this->makeRepo($twig, $config);
        $out = $repo->getTemplateForClient(['fname' => 'A', 'lname' => 'B']);

        $this->assertArrayHasKey('subject', $out);
        $this->assertSame('body', $out['message']);
    }
}
