<?php

namespace OpenEMR\Modules\DiscoverAndChange\Assessments\Services;

use OpenEMR\Common\Auth\OpenIDConnect\Repositories\ScopeRepository;
use OpenEMR\Common\Twig\TwigExtension;
use OpenEMR\Core\Header;
use OpenEMR\Core\Kernel;
use OpenEMR\Modules\DiscoverAndChange\Assessments\GlobalConfig;
use OpenEMR\Services\Globals\GlobalsService;
use OpenEMR\Services\LogoService;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;
use Twig\TwigFunction;

class SimplifiedOAuthTwigExtension extends AbstractExtension implements GlobalsInterface
{
    private ?string $primaryLogo;

    public function __construct(private ScopeRepository $scopeRepository, private LogoService $logoService, private GlobalConfig $globalConfig)
    {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction(
                'dacShowUpdatedScopePage',
                function ($scopesByResource) {
                    // no patient set right now but user is set.
                    if (!empty($_SESSION['user_id']) && empty($_SESSION['pid'])) {
                        // logged in as user so we are going to bail out
                        return false;
                    }
                    if (empty($scopesByResource['Questionnaire'])) {
                        return false; // we are only showing this page if we have people requesting the Questionnaire scope
                    }
                    return $this->globalConfig->shouldDisplayUpdatedOAuthPages();
                }
            ),
            new TwigFunction(
                'shouldDisplayUpdatedOAuthPages',
                function () {
                    return $this->globalConfig->shouldDisplayUpdatedOAuthPages();
                }
            ),
            new TwigFunction(
                'displaySimplifiedKey',
                function ($key) {
                    return in_array($key, ['Patient', 'Questionnaire', 'QuestionnaireResponse', 'Task']);
                }
            ),
            new TwigFunction(
                'getSimplifiedIconClassesForKey',
                function ($key) {
                    $icon = '';
                    switch ($key) {
                        case 'Patient':
                            $icon = 'fa fa-user';
                            break;
                        case 'Questionnaire':
                            $icon = 'fa fa-question-circle';
                            break;
                        case 'QuestionnaireResponse':
                            $icon = 'fa fa-square-poll-vertical';
                            break;
                        case 'Task':
                            $icon = 'fa fa-tasks';
                            break;
                        default:
                            $icon = 'fa fa-question-circle';
                    }
                    return $icon;
                }
            ),
            new TwigFunction(
                'getSimplifiedTextForKey',
                function ($key) {
                    // we can find out if the current session is a user or a patient... and respond accordingly
                    $text = '';
                    switch ($key) {
                        case 'Patient':
                            $text = xl('Information about you including names, contact information, race, and other administrative information');
                            break;
                        case 'Questionnaire':
                            $text = xl('Surveys, Assessments, and Questionnaires');
                            break;
                        case 'QuestionnaireResponse':
                            $text = xl('Results from Surveys, Assessments, and Questionnaires');
                            break;
                        case 'Task':
                            $text = xl('Tasks / Items assigned to be completed');
                            break;
                        default:
                            $text = xl($key);
                    }
                    return $text;
                }
            )
        ];
    }

    public function getGlobals(): array
    {
        // so we don't grab this call
        if (!isset($this->primaryLogo)) {
            $this->primaryLogo = $this->logoService->getLogo("core/login/primary") ?? "";
        }
        return [
            'dacPrimaryLogo' => $this->primaryLogo
            ,'applicationName' => $this->globalConfig->getApplicationName()
        ];
    }
}
