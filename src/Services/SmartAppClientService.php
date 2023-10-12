<?php

namespace OpenEMR\Modules\DiscoverAndChange\Assessments\Services;

use OpenEMR\Common\Utils\RandomGenUtils;
use OpenEMR\FHIR\Config\ServerConfig;
use OpenEMR\Modules\DiscoverAndChange\Assessments\GlobalConfig;
use OpenEMR\RestControllers\AuthorizationController;
use OpenEMR\Common\Auth\OpenIDConnect\Repositories\ClientRepository;

class SmartAppClientService
{
    public function __construct(private GlobalConfig $globalConfig)
    {
    }

    public function getRegisteredClientId()
    {
        $clientId = $this->globalConfig->getSmartAppClientId();
        if (empty($clientId)) {
            $clientRepository = new ClientRepository();

            // time to setup the data for this
            // TODO: @adunsulag we need to abstract this out of the AuthorizationController into its own service
            $clientId = $clientRepository->generateClientId();
            $reg_token = $clientRepository->generateRegistrationAccessToken();
            $reg_client_uri_path = $clientRepository->generateRegistrationClientUriPath();
//            $client_secret = $clientRepository->generateClientSecret();

            // TODO: @adunsulag wouldn't it be better to convert this into an actual Client Entity Model ?
            $params = array(
                'client_id' => $clientId,
                'client_role' => 'patient', // 'user', switch when we deal with confidential clients.
                'redirect_uris' => [$this->globalConfig->getSmartAppClientPublicPathRedirectUri(), $this->globalConfig->getSmartAppAdminPublicPathRedirectUri()],
                'post_logout_redirect_uris' => [],
                'client_name' => $this->globalConfig->getSmartAppName(),
                // our in-ehr launch is to the admin url
                'initiate_login_uri' => $this->globalConfig->getSmartAppAdminLoginPublicPath(),
                'token_endpoint_auth_method' => 'client_secret_post',
                'contacts' => $this->globalConfig->getSmartAppContactAddress(),
                'scope' => $this->globalConfig->getSmartAppScopes(),
                'client_id_issued_at' => time(),
                'registration_access_token' => $reg_token,
                'registration_client_uri_path' => $reg_client_uri_path,
                // as we are a module we want to skip the authentication/authorization flow.
                'skip_ehr_launch_authorization_flow' => true
            );
            $clientRepository->insertNewClient($clientId, $params, $GLOBALS['site_id']);
            // make sure our client is enabled.

            $clientEntity = $clientRepository->getClientEntity($clientId);
            $clientRepository->saveIsEnabled($clientEntity, true);
            $this->globalConfig->saveSmartAppClientId($clientId);
        }
        return $clientId;
    }

    public function isClientEnabled(string $clientId)
    {
        $clientRepository = new ClientRepository();
        $client = $clientRepository->getClientEntity($clientId);
        return !empty($client) && $client->isEnabled();
    }
}
