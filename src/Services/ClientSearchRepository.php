<?php

namespace OpenEMR\Modules\DiscoverAndChange\Assessments\Services;

use OpenEMR\Common\Logging\SystemLogger;
use OpenEMR\Common\Uuid\UuidRegistry;
use OpenEMR\Modules\DiscoverAndChange\Assessments\DTO\ClientSearchQueryDTO;
use OpenEMR\Common\Database\QueryPagination;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Models\Client;
use OpenEMR\Modules\DiscoverAndChange\Assessments\Models\ErrorCode;
use OpenEMR\Services\FacilityService;
use OpenEMR\Services\PatientService;
use OpenEMR\Services\Search\CompositeSearchField;
use OpenEMR\Services\Search\NumberSearchField;
use OpenEMR\Services\Search\SearchModifier;
use OpenEMR\Services\Search\SearchQueryConfig;
use OpenEMR\Services\Search\StringSearchField;
use OpenEMR\Services\Search\TokenSearchField;
use OpenEMR\Services\Search\TokenSearchValue;
use OpenEMR\Validators\ProcessingResult;

class ClientSearchRepository
{
    private ?int $companyId;
    private $_repo;

    private $logger;

    public function __construct(?int $companyID)
    {
 // private dbUtils:DBUtils, private config:AppConfig, private logger:Logger, companyID?:string) {
        $this->companyId = $companyID;
//        this._repo = this.dbUtils.getRepository(Patient);
        $this->logger = new SystemLogger();
    }

    public function searchClientList(ClientSearchQueryDTO $search, SearchQueryConfig $config, ?int $assignedUserId): ProcessingResult
    {
        $clients = $this->getClientList($search, $config);
        return $clients;
    }

    private function getClientList(ClientSearchQueryDTO $searchQueryDTO, SearchQueryConfig $config): ProcessingResult
    {
        $patientService = new PatientService();
        $searchParams = [];
        $searchModifier = $searchQueryDTO->exactMatch ? SearchModifier::EXACT : SearchModifier::CONTAINS;
        if ($searchQueryDTO->firstName) {
            $searchParams['fname'] = new StringSearchField('fname', $searchQueryDTO->firstName, $searchModifier);
        }
        if ($searchQueryDTO->lastName) {
            $searchParams['lname'] = new StringSearchField('lname', $searchQueryDTO->lastName, $searchModifier);
        }
        if ($searchQueryDTO->email) {
            // FHIR requires exact matching, but here we aren't in fhir world
            $searchParams['email'] = new StringSearchField('email', $searchQueryDTO->email, $searchModifier);
        }
        if ($searchQueryDTO->id) {
            if (UuidRegistry::isValidStringUUID($searchQueryDTO->id)) {
                $searchParams['uuid'] = new TokenSearchField('uuid', [$searchQueryDTO->id], true);
            } else {
                return []; // invalid id so we are going to return nothing.
            }
        }

        $processingResult = $patientService->search($searchParams, true, $config);
        if ($processingResult->hasErrors()) {
            $this->logger->error("ClientSearchRepository.getClientList() - Error searching for clients", ["errors" => $processingResult->getErrors()]);
            throw new SystemError(ErrorCode::INVALID_REQUEST, "Error searching for clients");
        } else if (!$processingResult->hasData()) {
            // no data found so we are returning nothing.
            return new ProcessingResult();
        }
        $facilityService = new FacilityService();
        $primaryEntity = $facilityService->getPrimaryBusinessEntity();
        // now we need to return the data model
        $clients = [];
        $indexByPids = [];
        $count = 0;
        $idsByClient = [];
        foreach ($processingResult->getData() as $record) {
            $client = new Client();
            $client->setId($record['uuid']);
            $client->setFirstName($record['fname']);
            $client->setLastName($record['lname']);
            $client->setEmail($record['email']);
            if (!empty($primaryEntity)) {
                $client->setCompanyID($primaryEntity['id']);
            }

            // need to grab the system user
            $idsByClient[$client->getId()] = $count;
            $clients[$count++] = $client;
        }
        $systemUserRepo = new SystemUserRepository();
        $usersByClient = $systemUserRepo->getUsersForClients(array_keys($idsByClient));
        foreach ($usersByClient as $clientId => $user) {
            $index = $idsByClient[$clientId];
            $clients[$index]->setAssignedUser($user);
        }

        $assignmentRepo = new AssignmentRepository();
        $assignmentRepo->populateAssignmentsForClients($clients);

        // since we are decorating the patients we need to set the pagination values here.
        $clientProcessingResult = new ProcessingResult();
        $clientProcessingResult->setPagination($processingResult->getPagination()->copy());
        $clientProcessingResult->setData($clients); // now using the same pagination which limits the results add in our data.
//
//        // now sort each client's assignments by the sortAssignmentsByDateAssigned function
//        foreach ($clients as $client) {
//            $client->sortAssignmentsByDateAssigned();
//        }
        return $clientProcessingResult;
    }
//
//
//    private async getClientsForPagination(qb:SelectQueryBuilder<Patient>, pagination:QueryPagination) {
//    // let offsetId = pagination.offsetId || 0;
//    if (pagination.offsetId) {
//        qb = qb.andWhere("client.id <= :offsetId", {offsetId: pagination.offsetId});
//        }
//    this.logger.debug("ClientSearchRepository.getClientsByPagination()", {pagination: pagination});
//        let clientPrivateService = new ClientPrivateService();
//        let qbPaginated = qb.take(pagination.limit + 1); // go one beyond so we can check if we have more results
//        let clients = await qbPaginated.getMany();
//        // let clientDecryptions = await clientPrivateService.decryptClientListWithPrivateInformation(clients);
//        let clientDecryptions = clients;
//        this.logger.debug("ClientSearchRepository.getClientsByPagination() records found", {recordCount: clientDecryptions.length});
//
//        // sort the assignments since the ORM isn't doing that
//        clientDecryptions.forEach(c => c.assignments.sort((a,b) => a.date_assigned.getTime() - b.date_assigned.getTime()));
//        return clientDecryptions.map(c => c.toDTO());
//    }
//
//    private async searchClientsById(id:string, exactMatch:boolean, qb:SelectQueryBuilder<Patient>, pagination:QueryPagination) {
//    this.logger.debug("calling searchClientsById()", {id: id});
//        if (exactMatch) {
//            qb = qb.andWhere('(client.pid = :search OR client.pid = :search)', {search: id});
//        }
//        else {
//            qb = qb.andWhere('(client.pid LIKE :search OR client.pubpid LIKE :search)', {search: "%" + id + "%"});
//        }
//        return this.getClientsForPagination(qb, pagination);
//    }
//
//    private async searchClientsByEmail(email:string, qb:SelectQueryBuilder<Patient>, pagination:QueryPagination) {
//
//    this.logger.debug("calling searchClientsByEmail()", {email: email});
//        if (!email) {
//            throw new SystemError(ErrorCode.INVALID_REQUEST, "Search requires email");
//        }
//        let info = new ClientPrivate();
//        info.email = email;
//
//        // let clientPrivateService = new ClientPrivateService();
//        // let index = await clientPrivateService.getEmailSearchIndexForClient(info);
//        // if (!index) {
//        //     throw new SystemError(ErrorCode.SYSTEM_ERROR, "Search index failed to generate");
//        // }
//        // let filter = (client:EntityClient) => {
//        //     let emailCheck = email.toLowerCase();
//        //     let emailCompare = client.privateInfo.email.toLowerCase();
//        //     return emailCheck == emailCompare;
//        // };
//        // qb = qb.andWhere('info.email_idx = :search', {search: index});
//        qb = qb.andWhere("client.email LIKE :email", {email: "%" + email + "%"});
//        return this.getClientsForPagination(qb, pagination);
//        // return this.searchClientsByFilter(filter, info, qb, pagination);
//    }
//
//    private async searchClientsByName(firstName:string, lastName:string, exactMatch:boolean, qb:SelectQueryBuilder<Patient>, pagination:QueryPagination) {
//    this.logger.debug("calling searchClientsByName", {firstName: firstName, lastName: lastName, pagination:pagination});
//        if (!firstName || !lastName) {
//            throw new SystemError(ErrorCode.INVALID_REQUEST, "Search requires both first and last name");
//        }
//        if (exactMatch) {
//            if (lastName && firstName) {
//                qb = qb.andWhere("(client.fname = :fname AND client.lname = :lname)", {fname: firstName, lname: lastName});
//            } else if (lastName) {
//                qb = qb.andWhere("client.lname = :lname", {lname: lastName});
//            } else {
//                qb = qb.andWhere("client.fname = :fname", {fname: firstName});
//            }
//        } else {
//            if (lastName && firstName) {
//                qb = qb.andWhere("(client.fname LIKE :fname AND client.lname LIKE :lname)", {fname: "%" + firstName + "%", lname: "%" + lastName + "%"});
//            } else if (lastName) {
//                qb = qb.andWhere("client.lname = :lname", {lname: "%" + lastName + "%"});
//            } else {
//                qb = qb.andWhere("client.fname = :fname", {fname: "%" + lastName + "%"});
//            }
//        }
//        return this.getClientsForPagination(qb, pagination);
//        // let info = new ClientPrivate();
//        // info.firstName = firstName;
//        // info.lastName = lastName;
//
//        // let clientPrivateService = new ClientPrivateService();
//
//        // let index = await clientPrivateService.getNameSearchIndexForClient(info);
//        // if (!index) {
//        //     throw new SystemError(ErrorCode.SYSTEM_ERROR, "Search index failed to generate");
//        // }
//        // let filter;
//        // if (exactMatch) {
//        //     filter = (client:EntityClient) => {
//        //         // we force full name match for first & last name.
//        //         return this.matchFullName(firstName.length+1, lastName.length+1, firstName, lastName, client.privateInfo);
//        //     };
//        // }
//        // else {
//        //     // only do full name match if first name < 1 char or last name < 4 char
//        //     filter = (client:EntityClient) => { return this.matchFullName(1, 4, firstName, lastName, client.privateInfo) };
//        // }
//        // qb = qb.andWhere('info.name_idx = :search', {search: index});
//        // return this.searchClientsByFilter(filter, info, qb, pagination);
//    }
//
//    private async searchClientsByFilter(filter:(r:EntityClient) => boolean, info:ClientPrivate, qb:SelectQueryBuilder<EntityClient>, pagination:QueryPagination) {
//
//    let clientPrivateService = new ClientPrivateService();
//        if (pagination.offsetId) {
//            // since we are in descending order we use less then on the offset id.
//            qb = qb.andWhere("client.id <= :offsetId", {offsetId: +(pagination.offsetId)});
//        }
//        let offset = 0;
//
//
//        // for now let's just return what we found...
//
//        // algorithm is we will grab all of them, and decrypt them in batches
//        // maxFound = 25
//        // offset = 0
//        // while (found < 25)
//        // grab from database group of limit starting from offset
//        // grab filtered results
//        //  decrypt all records
//        //  if firstNameMatch function && lastName match function
//        //      add to found list
//        // set offset = offset + limit
//
//        let limit = pagination.limit;
//        let found:EntityClient[] = [];
//        let recordsFound = null;
//        do {
//            let qbPaginated = qb.skip(offset).take(limit + 1); // go one beyond so we can check if we have more results
//            recordsFound = await qbPaginated.getMany();
//            if (!recordsFound || recordsFound.length == 0) { // exit the loop if we have no more data.
//                this.logger.debug("searchClientsByFilter() no records found, exiting loop");
//                break;
//            }
//            this.logger.debug("searchClientsByFilter() retrieved records", {recordsFoundSize: recordsFound.length});
//            let decryptedRecords = await clientPrivateService.decryptClientListWithPrivateInformation(recordsFound);
//            let filteredRecords = decryptedRecords.filter(r => filter(r));
//            this.logger.debug("searchClientsByFilter() filteredRecords found", {filteredSize: filteredRecords.length});
//            found = found.concat(filteredRecords);
//            offset += limit+1; // we don't want to get the last record again...
//        // need to be able to set our has more filter here...
//        } while (found.length <= (limit+1) && recordsFound.length >= limit); // we found the maximum limit we'll run the query again.
//
//        return found.map(c => c.toDTO());
//    }
//
//    private matchFullName(minFirstName, minLastName, firstName, lastName, info:ClientPrivate) {
//this.logger.debug("matchFullName()", {infoId: info.id, fname: info.firstName, lname: info.lastName});
//        return this.matchField(minFirstName, firstName, info.firstName) && this.matchField(minLastName, lastName, info.lastName)
//    }
//
//    private matchField(minChars:number, expect:string, check:string) {
//let expectItem = expect || "";
//        let checkItem = check || "";
//        let compare = expectItem.length - checkItem.length;
//        // the name we are expecting is bigger than the check so there is no match here.
//        if (compare > 0) {
//            return false;
//        }
//
//        let checkLC = checkItem.toLowerCase();
//        let expectLC = expect.toLowerCase();
//        this.logger.info("matchField()", {minChars: minChars, expect:expect, check:check});
//         // we only do exact matchings here if we are less than our minimum chars
//         // or the lengths are the same
//        if (expectLC.length < minChars || compare == 0) {
//            return checkLC == expectLC;
//        }
//        // we know expectItem is smaller than the check so we do a fuzzy match
//        return checkLC.substr(0, expectItem.length) == expectLC;
//    }
}
