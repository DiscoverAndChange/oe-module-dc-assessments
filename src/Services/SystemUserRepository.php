<?php

namespace OpenEMR\Modules\DiscoverAndChange\Assessments\Services;

use OpenEMR\Common\Acl\AclMain;
use OpenEMR\Common\System\System;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Models\Role;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Models\SystemUser;
use OpenEMR\Services\FacilityService;
use OpenEMR\Services\PatientService;
use OpenEMR\Services\Search\TokenSearchField;
use OpenEMR\Services\UserService;

class SystemUserRepository
{
    /**
     * @return SystemUser[]
     */
    public function getUsers()
    {

        $userRepo = new UserService();
        $userRepo->toggleSensitiveFields(['username']);
        /** @var list<array{uuid: string, username: ?string, active: string|int, fname?: string|null, lname?: string|null}> $users */
        $users = $userRepo->getAll();
        // now we need to hydrate them and convert them to SystemUser classes
        $systemUsers = [];
        $facRepo = new FacilityService();
        /** @var array{id?: int, name?: string}|null $primaryEntity */
        $primaryEntity = $facRepo->getPrimaryBusinessEntity();
        foreach ($users as $user) {
            // OpenEMR's users table also holds non-login "address book" entries (external
            // providers) with a NULL username. Those are not assessment system users and
            // must be skipped — otherwise hydrateUser() feeds null into the non-null
            // SystemUser::$username and throws (crashing the assessment-users endpoint).
            if (($user['username'] ?? '') === '') {
                continue;
            }
            $systemUsers[] = $this->hydrateUser($user, $primaryEntity);
        }
        return $systemUsers;
    }
    /**
     * @param array{uuid: string, username: ?string, active: string|int, fname?: string|null, lname?: string|null} $user
     * @param array{id?: int, name?: string}|null $primaryEntity
     * @return SystemUser
     */
    public function hydrateUser(array $user, $primaryEntity)
    {
        $companyId = isset($primaryEntity['id']) ? $primaryEntity['id'] : null;
        // Defensive: a non-login user row can have a NULL username (address-book entry);
        // callers other than getUsers() (e.g. getUsersForClients) could still pass one in.
        $username = $user['username'] ?? '';
        // we will treat the uuid as the username as we don't want to reveal that anymore to the frontend
        $systemUser = new SystemUser($user['uuid'], $username, $companyId);
        if (AclMain::aclCheckCore('admin', 'super', $username)) {
            $systemUser->setRole(Role::SuperUser);
        } else {
            // if we need to introduce the role of company admin's we can do that here, but not sure there is an ACL for that.
            $systemUser->setRole(Role::Registered);
        }
        $systemUser->setFirstName(($user['fname'] ?? ''));
        $systemUser->setLastName(($user['lname'] ?? ''));
        $systemUser->setCompanyName(($primaryEntity['name'] ?? ''));
        $systemUser->setEnabled($user['active'] == '1');
        // TODO: if we need different capabilities we can set that here.
        return $systemUser;
    }

    /**
     * @param array<mixed> $clientIds
     * @return SystemUser[]
     */
    public function getUsersForClients(array $clientIds)
    {
        $patientService = new PatientService();
        /** @var array<int|string, int|string> $mappedProviderIds */
        $mappedProviderIds = $patientService->getProviderIDsForPatientUuids($clientIds);
        // tokens are required to be strings
        $userIds = array_map(strval(...), array_values($mappedProviderIds));
        $idSearch = new TokenSearchField('id', $userIds);
        $userRepo = new UserService();
        $userRepo->toggleSensitiveFields(['username']);
        /** @var list<array{uuid: string, username: string, active: string|int, id: int|string, fname?: string|null, lname?: string|null}> $users */
        $users = $userRepo->getAll(['id' => $idSearch]);
        $mappedProviderUuids = [];
        foreach ($users as $user) {
            $mappedProviderUuids[$user['uuid']] = $user['id'];
        }

        // now we need to hydrate them and convert them to SystemUser classes
        $systemUsers = [];
        $facRepo = new FacilityService();
        /** @var array{id?: int, name?: string}|null $primaryEntity */
        $primaryEntity = $facRepo->getPrimaryBusinessEntity();
        $userIdIndex = [];
        foreach ($users as $user) {
            $systemUser = $this->hydrateUser($user, $primaryEntity);
            $providerId = $mappedProviderUuids[$systemUser->getId()];
            $userIdIndex[$providerId] = $systemUser;
        }
        // we have uuid => systemUser
        // we have clientId => id
        // we need a mapping from user.id => providerID
        $resultClientSystemUserMap = [];
        foreach ($mappedProviderIds as $clientId => $providerId) {
            if (!empty($userIdIndex[$providerId])) {
                $resultClientSystemUserMap[$clientId] = $userIdIndex[$providerId];
            }
        }
        return $resultClientSystemUserMap;
    }
}
