<?php
/**
 * Copyright (C) 2014 David Young
 *
 * Builds an insert query
 */
namespace RamODev\Databases\RDBMS\PostgreSQL\QueryBuilders;
use RamODev\Databases\RDBMS\QueryBuilders;

require_once(__DIR__ . "/../../querybuilders/InsertQuery.php");
require_once(__DIR__ . "/AugmentingQueryBuilder.php");

class InsertQuery extends QueryBuilders\InsertQuery
{
    /** @var AugmentingQueryBuilder Handles functionality common to augmenting queries */
    protected $augmentingQueryBuilder = null;

    /**
     * @param string $tableName The name of the table we're inserting into
     * @param array $columnNamesToValues The mapping of column names to their respective values
     */
    public function __construct($tableName, $columnNamesToValues)
    {
        parent::__construct($tableName, $columnNamesToValues);

        $this->augmentingQueryBuilder = new AugmentingQueryBuilder();
        $this->augmentingQueryBuilder->addColumnValues($columnNamesToValues);
    }

    /**
     * Adds to a "RETURNING" clause
     *
     * @param string $expression,... A variable list of expressions to add to our "RETURNING" clause
     * @return $this
     */
    public function addReturning($expression)
    {
        call_user_func_array(array($this->augmentingQueryBuilder, "addReturning"), func_get_args());

        return $this;
    }

    /**
     * Gets the SQL statement as a string
     *
     * @return string The SQL statement
     */
    public function getSQL()
    {
        $sql = parent::getSQL();
        $sql .= $this->augmentingQueryBuilder->getReturningClauseSQL();

        return $sql;
    }

    /**
     * Starts a "RETURNING" clause
     * Only call this method once per query because it will overwrite an previously-set "RETURNING" expressions
     *
     * @param string $expression,... A variable list of expressions to add to our "RETURNING" clause
     * @return $this
     */
    public function returning($expression)
    {
        call_user_func_array(array($this->augmentingQueryBuilder, "returning"), func_get_args());

        return $this;
    }
} 