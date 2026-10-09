<?php

declare(strict_types=1);

namespace OpenEMR\Modules\DiscoverAndChange\Assessments\Tests;

use OpenEMR\Common\Auth\OpenIDConnect\Entities\ClientEntity;
use OpenEMR\Common\Auth\OpenIDConnect\Repositories\ClientRepository;
use OpenEMR\Modules\DiscoverAndChange\Assessments\GlobalConfig;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Services\SmartAppClientService;
use PHPUnit\Framework\TestCase;

/**
 * Covers SmartAppClientService now that its ClientRepository dependency is constructor-injected
 * (previously hardcoded `new ClientRepository()`, which made the registration/enable logic
 * untestable without hitting the oauth_clients table).
 *
 * The SMART client's "enabled" state is exactly what FrontendDispatchController gates the patient
 * SPA on, so these pin the orchestration: an existing client short-circuits, a first-time client is
 * registered as a patient client with the in-EHR launch-authorization flow skipped and then enabled,
 * and isClientEnabled() reflects the entity's state.
 */
class SmartAppClientServiceTest extends TestCase
{
    private function config(?string $clientId): GlobalConfig
    {
        $arr = [];
        if ($clientId !== null) {
            $arr[GlobalConfig::DC_ASSESSMENTS_CONFIG_CLIENT_ID] = $clientId;
        }
        return new GlobalConfig($arr);
    }

    /** A ClientRepository whose getClientEntity() returns a fixed result and records lookups. */
    private function lookupRepo(ClientEntity|false $entity): ClientRepository
    {
        return new class ($entity) extends ClientRepository {
            public ?string $lookedUp = null;
            public function __construct(private ClientEntity|false $fixedEntity)
            {
                parent::__construct();
            }
            public function getClientEntity($clientIdentifier): ClientEntity|false
            {
                $this->lookedUp = $clientIdentifier;
                return $this->fixedEntity;
            }
        };
    }

    private function enabledEntity(bool $enabled): ClientEntity
    {
        $entity = new ClientEntity();
        $entity->setIsEnabled($enabled);
        return $entity;
    }

    public function testIsClientEnabledReturnsFalseWhenClientNotFound(): void
    {
        $service = new SmartAppClientService($this->config(null), $this->lookupRepo(false));
        $this->assertFalse($service->isClientEnabled('missing-client'));
    }

    public function testIsClientEnabledReturnsTrueForEnabledClient(): void
    {
        $service = new SmartAppClientService($this->config(null), $this->lookupRepo($this->enabledEntity(true)));
        $this->assertTrue($service->isClientEnabled('some-client'));
    }

    public function testIsClientEnabledReturnsFalseForDisabledClient(): void
    {
        $service = new SmartAppClientService($this->config(null), $this->lookupRepo($this->enabledEntity(false)));
        $this->assertFalse($service->isClientEnabled('some-client'));
    }

    /** When a client id is already configured the method returns it and never touches the repository. */
    public function testGetRegisteredClientIdReturnsExistingWithoutRegistering(): void
    {
        $repo = new class extends ClientRepository {
            public function getClientEntity($clientIdentifier): ClientEntity|false
            {
                throw new \LogicException('getClientEntity must not be called when a client id is already configured');
            }
            public function insertNewClient($clientId, $info, $site): bool
            {
                throw new \LogicException('insertNewClient must not be called when a client id is already configured');
            }
        };
        $service = new SmartAppClientService($this->config('already-registered'), $repo);
        $this->assertSame('already-registered', $service->getRegisteredClientId());
    }

    /** First-time registration: patient client, launch-auth flow skipped, enabled, id persisted. */
    public function testGetRegisteredClientIdRegistersAndEnablesPatientClient(): void
    {
        $repo = new class extends ClientRepository {
            /** @var array<string, mixed>|null */
            public ?array $insertedParams = null;
            public bool $enabledSaved = false;
            public function generateClientId()
            {
                return 'generated-client-id';
            }
            public function generateRegistrationAccessToken()
            {
                return 'reg-token';
            }
            public function generateRegistrationClientUriPath()
            {
                return 'reg-uri-path';
            }
            public function insertNewClient($clientId, $info, $site): bool
            {
                $this->insertedParams = $info;
                return true;
            }
            public function getClientEntity($clientIdentifier): ClientEntity|false
            {
                $entity = new ClientEntity();
                $entity->setIsEnabled(false);
                return $entity;
            }
            public function saveIsEnabled(ClientEntity $client, $isEnabled): bool
            {
                $this->enabledSaved = (bool) $isEnabled;
                return true;
            }
        };

        $savedClientId = null;
        $config = new class ([], $savedClientId) extends GlobalConfig {
            /** @param array<string, mixed> $globalsArray */
            public function __construct(array $globalsArray, public ?string &$savedClientId)
            {
                parent::__construct($globalsArray);
            }
            public function getSmartAppClientId()
            {
                return null; // force the registration path
            }
            public function saveSmartAppClientId(string $clientId)
            {
                $this->savedClientId = $clientId; // capture instead of the real DB write
            }
        };

        $service = new SmartAppClientService($config, $repo);
        $clientId = $service->getRegisteredClientId();

        $this->assertSame('generated-client-id', $clientId);
        $this->assertSame('generated-client-id', $savedClientId, 'the new client id must be persisted via GlobalConfig');
        $this->assertTrue($repo->enabledSaved, 'the newly registered client must be enabled');
        $this->assertIsArray($repo->insertedParams);
        $this->assertSame('patient', $repo->insertedParams['client_role']);
        $this->assertTrue($repo->insertedParams['skip_ehr_launch_authorization_flow'], 'module clients skip the in-EHR launch-authorization flow');
    }
}
