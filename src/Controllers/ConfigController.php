<?php

namespace OpenEMR\Modules\DiscoverAndChange\Assessments\Controllers;

use OpenEMR\Modules\DiscoverAndChange\Assessments\GlobalConfig;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Services\ResourceImporterService;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Utils\RestUtils;
use Twig\Environment;

class ConfigController
{
    const DEBUG_MODE = false;

    public function __construct(private GlobalConfig $configService, private Environment $twig)
    {
    }

    public function renderConfigAction($action, $queryVars)
    {
        $data = [
            'submitURL' => $this->configService->getPublicPathFQDN() . 'backend/index-backend.php'
            ,'action' => 'config-import'
            ,'msgError' => ''
            ,'msgSuccess' => ''
            ,'importUrl' => ''
            ,'logEntries' => []
            ,'debug' => self::DEBUG_MODE
        ];
        $text = $this->twig->render("discoverandchange/config/config.html.twig", $data);
        return RestUtils::returnTextResponse($text);
    }

    public function importConfigAction($action, $queryVars)
    {
        $data = [
            'submitURL' => $this->configService->getPublicPathFQDN() . 'backend/index-backend.php'
            ,'action' => 'config-import'
            ,'msgError' => ''
            ,'msgSuccess' => ''
            ,'importUrl' => ''
            ,'logEntries' => []
            ,'debug' => self::DEBUG_MODE
        ];

        try {
            $importUrl = $queryVars['importUrl'];

            if (empty($importUrl)) {
                throw new \InvalidArgumentException(xl("Import URL is empty"));
            }
            $guzzle = new \GuzzleHttp\Client();
            // TODO: @adunsulag need to require SSL for this
            $response = $guzzle->get($importUrl, ['verify' => false]);
            if (!empty($response)) {
                $response->getBody()->rewind();
                $strings = $response->getBody()->getContents();
                if (empty($strings)) {
                    throw new \InvalidArgumentException(xl("Import URL returned empty"));
                }
                $importer = new ResourceImporterService();
                $importer->import($strings, $_SESSION['authUserID']);

                $data['logEntries'] = $importer->getLogEntries();
                $data['msgSuccess'] = xl('Imported successfully');
            }
            // TODO: @adunsulag do we want to validate this against a public signed certificate?
            $text = $this->twig->render("discoverandchange/config/config.html.twig", $data);
            return RestUtils::returnTextResponse($text);
        } catch (\Exception $e) {
            $data['msgError'] = $e->getMessage();
            $data['logEntries']  = isset($importer) ? $importer->getLogEntries() : [];
            $data['msgSuccess'] = '';
            $text = $this->twig->render("discoverandchange/config/config.html.twig", $data);
            return RestUtils::returnTextResponse($text);
        }
    }
}
