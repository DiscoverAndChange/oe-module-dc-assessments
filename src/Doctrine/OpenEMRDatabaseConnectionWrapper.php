<?php

namespace OpenEMR\Modules\DiscoverAndChange\Assessments\Doctrine;

use Doctrine\DBAL\Driver\Connection;
use Doctrine\DBAL\Driver\Result;
use Doctrine\DBAL\Driver\Statement;
use Doctrine\DBAL\ParameterType;

/**
 * TODO: @adunsulag remove this unused class, look at rmeoving other unused classes.
 * @method object getNativeConnection()
 */
class OpenEMRDatabaseConnectionWrapper implements Connection
{
    public function prepare(string $sql): Statement
    {
        // TODO: Implement prepare() method.
    }

    public function query(string $sql): Result
    {
        // TODO: Implement query() method.
    }

    public function quote($value, $type = ParameterType::STRING)
    {
        // TODO: Implement quote() method.
    }

    public function exec(string $sql): int
    {
        // TODO: Implement exec() method.
    }

    public function lastInsertId($name = null)
    {
        \sqlGetLastInsertId();
    }

    public function beginTransaction()
    {
        \sqlBeginTrans();
    }

    public function commit()
    {
        \sqlCommitTrans();
    }

    public function rollBack()
    {
        \sqlRollbackTrans();
    }

    public function __call(string $name, array $arguments)
    {
        // TODO: Implement @method object getNativeConnection()
    }
}
