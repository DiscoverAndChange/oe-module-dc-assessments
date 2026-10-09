<?php

namespace OpenEMR\Modules\DiscoverAndChange\Assessments\Services;

use OpenEMR\Core\OEGlobalsBag;
use OpenEMR\Common\Utils\RandomGenUtils;
use OpenEMR\FHIR\Config\ServerConfig;
use OpenEMR\Modules\DiscoverAndChange\Assessments\GlobalConfig;
use OpenEMR\RestControllers\AuthorizationController;
use OpenEMR\Common\Auth\OpenIDConnect\Repositories\ClientRepository;

class SmartAppClientService
{
    private ClientRepository $clientRepository;

    public function __construct(private GlobalConfig $globalConfig, ?ClientRepository $clientRepository = null)
    {
        // default preserves the previous hardcoded behaviour; tests inject a fake/real repo.
        $this->clientRepository = $clientRepository ?? new ClientRepository();
    }

    /**
     * The PUBLIC patient SMART client used for the standalone patient launch (assessment taking).
     * @return mixed
     */
    public function getRegisteredClientId()
    {
        $clientId = $this->globalConfig->getSmartAppClientId();
        if ($clientId === null || $clientId === '') {
            $clientId = $this->registerClient(
                'patient',
                false,
                $this->globalConfig->getSmartAppScopes(),
                [
                    $this->globalConfig->getSmartAppClientPublicPathRedirectUri(),
                    $this->globalConfig->getSmartAppAdminPublicPathRedirectUri(),
                ],
                null
            );
            $this->globalConfig->saveSmartAppClientId($clientId);
        }
        return $clientId;
    }

    /**
     * The CONFIDENTIAL provider/admin SMART client used for the in-EHR provider launch. OpenEMR only
     * grants user/* scopes to confidential clients, so the provider app cannot share the public patient
     * client -- without this, provider user/* scopes (e.g. user/clients.read) are dropped and provider
     * API calls 401. The client secret is stored server-side (never shipped to the browser).
     * @return mixed
     */
    public function getRegisteredProviderClientId()
    {
        $clientId = $this->globalConfig->getProviderClientId();
        if ($clientId === null || $clientId === '') {
            $secretRaw = $this->clientRepository->generateClientSecret();
            $secret = is_string($secretRaw) ? $secretRaw : '';
            $clientId = $this->registerClient(
                'user',
                true,
                $this->globalConfig->getSmartAppProviderScopes(),
                [$this->globalConfig->getSmartAppAdminPublicPathRedirectUri()],
                ['authorization_code', 'refresh_token'],
                $secret
            );
            $this->globalConfig->saveProviderClientId($clientId);
            $this->globalConfig->saveProviderClientSecret($secret);
        }
        return $clientId;
    }

    /**
     * Register + enable a SMART client and return its id.
     *
     * @param list<string> $redirectUris
     * @param list<string>|null $grantTypes
     * @return string
     */
    private function registerClient(
        string $clientRole,
        bool $isConfidential,
        string $scope,
        array $redirectUris,
        ?array $grantTypes,
        ?string $clientSecret = null
    ): string {
        $clientRepository = $this->clientRepository;
        /** @var string $clientId */
        $clientId = $clientRepository->generateClientId();

        $params = [
            'client_id' => $clientId,
            'client_role' => $clientRole,
            'redirect_uris' => $redirectUris,
            'post_logout_redirect_uris' => null,
            'client_name' => $this->globalConfig->getSmartAppName(),
            // our in-ehr launch is to the admin url
            'initiate_login_uri' => $this->globalConfig->getSmartAppAdminLoginPublicPath(),
            'token_endpoint_auth_method' => 'client_secret_post',
            'contacts' => $this->globalConfig->getSmartAppContactAddress(),
            'scope' => $scope,
            'client_id_issued_at' => time(),
            'registration_access_token' => $clientRepository->generateRegistrationAccessToken(),
            'registration_client_uri_path' => $clientRepository->generateRegistrationClientUriPath(),
            // as we are a module we want to skip the authentication/authorization flow.
            'skip_ehr_launch_authorization_flow' => true,
            'is_confidential' => $isConfidential,
        ];
        if ($clientSecret !== null) {
            $params['client_secret'] = $clientSecret;
        }
        if ($grantTypes !== null) {
            $params['grant_types'] = $grantTypes;
        }

        $clientRepository->insertNewClient($clientId, $params, OEGlobalsBag::getInstance()->get('site_id'));
        // make sure our client is enabled.
        $clientEntity = $clientRepository->getClientEntity($clientId);
        $clientRepository->saveIsEnabled($clientEntity, true);

        return $clientId;
    }

    /** @return bool */
    public function isClientEnabled(string $clientId)
    {
        $client = $this->clientRepository->getClientEntity($clientId);
        return $client !== false && $client->isEnabled();
    }
}
