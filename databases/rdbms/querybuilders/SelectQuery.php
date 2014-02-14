<?php
/**
 * Copyright (C) 2014 David Young
 *
 * Builds a select query
 */
namespace RamODev\Databases\RDBMS\QueryBuilders;

require_once(__DIR__ . "/Query.php");
require_once(__DIR__ . "/ConditionalQueryBuilder.php");

class SelectQuery extends Query
{
    /** @var ConditionalQueryBuilder Handles functionality common to conditional queries */
    protected $conditionalQueryBuilder = null;
    /** @var array The list of select expressions */
    protected $selectExpressions = array();
    /** @var array The list of join statements */
    protected $joins = array("inner" => array(), "left" => array(), "right" => array());
    /** @var array The list of group by clauses */
    protected $groupByClauses = array();
    /** @var array The list of having conditions */
    protected $havingConditions = array();
    /** @var int $limit The number of rows to limit to */
    protected $limit = -1;
    /** @var int $offset The number of rows to offset by */
    protected $offset = -1;
    /** @var array The list of expressions to order by */
    protected $orderBy = array();

    /**
     * @param string $expression,... A variable list of select expressions
     */
    public function __construct($expression)
    {
        $this->selectExpressions = func_get_args();
        $this->conditionalQueryBuilder = new ConditionalQueryBuilder();
    }

    /**
     * Adds to a "GROUP BY" clause
     *
     * @param string $expression,... A variable list of expressions of what to group by
     * @return $this
     */
    public function addGroupBy($expression)
    {
        $this->groupByClauses = array_merge($this->groupByClauses, func_get_args());

        return $this;
    }

    /**
     * Adds to a "ORDER BY" clause
     *
     * @param string $expression,... A variable list of expressions to order by
     * @return $this
     */
    public function addOrderBy($expression)
    {
        $this->orderBy = array_merge($this->orderBy, func_get_args());

        return $this;
    }

    /**
     * Adds more select expressions
     *
     * @param string $expression,... A variable list of select expressions
     * @return SelectQuery The select query builder
     */
    public function addSelectExpression($expression)
    {
        $this->selectExpressions = array_merge($this->selectExpressions, func_get_args());

        return $this;
    }

    /**
     * Adds to a "HAVING" condition that will be "AND"ed with other conditions
     *
     * @param string $condition,... A variable list of conditions to be met
     * @return $this
     */
    public function andHaving($condition)
    {
        $this->havingConditions = call_user_func_array(array($this->conditionalQueryBuilder, "addConditionToClause"), array_merge(array($this->havingConditions, "AND"), func_get_args()));

        return $this;
    }

    /**
     * Adds to a "WHERE" condition that will be "AND"ed with other conditions
     *
     * @param string $condition,... A variable list of conditions to be met
     * @return $this
     */
    public function andWhere($condition)
    {
        call_user_func_array(array($this->conditionalQueryBuilder, "andWhere"), func_get_args());

        return $this;
    }

    /**
     * Specifies which table we're selecting from
     *
     * @param string $tableName The name of the table we're selecting from
     * @param string $tableAlias The alias of the table name
     * @return $this
     */
    public function from($tableName, $tableAlias = "")
    {
        $this->setTable($tableName, $tableAlias);

        return $this;
    }

    /**
     * Gets the SQL statement as a string
     *
     * @return string The SQL statement
     */
    public function getSQL()
    {
        // Build our selector
        $sql = "SELECT " . implode(", ", $this->selectExpressions) . " FROM " . $this->tableName . (empty($this->tableAlias) ? "" : " AS " . $this->tableAlias);

        // Add any joins
        foreach($this->joins as $type => $joinsByType)
        {
            foreach($joinsByType as $join)
            {
                $sql .= " " . strtoupper($type) . " JOIN " . $join["tableName"] . (empty($join["tableAlias"]) ? "" : " AS " . $join["tableAlias"]) . " ON " . $join["condition"];
            }
        }

        $sql .= $this->conditionalQueryBuilder->getClauseConditionSQL("WHERE", $this->conditionalQueryBuilder->getWhereConditions());

        // Add groupings
        if(count($this->groupByClauses) > 0)
        {
            $sql .= " GROUP BY " . implode(", ", $this->groupByClauses);
        }

        // Add any groupings' conditions
        $sql .= $this->conditionalQueryBuilder->getClauseConditionSQL("HAVING", $this->havingConditions);

        // Order our query
        if(count($this->orderBy) > 0)
        {
            $sql .= " ORDER BY " . implode(", ", $this->orderBy);
        }

        // Add a limit
        if($this->limit !== -1)
        {
            $sql .= " LIMIT " . $this->limit;
        }

        // Add an offset
        if($this->offset !== -1)
        {
            $sql .= " OFFSET " . $this->offset;
        }

        return $sql;
    }

    /**
     * Starts a "GROUP BY" clause
     * Only call this method once per query because it will overwrite an previously-set "GROUP BY" expressions
     *
     * @param string $expression,... A variable list of expressions of what to group by
     * @return $this
     */
    public function groupBy($expression)
    {
        $this->groupByClauses = func_get_args();

        return $this;
    }

    /**
     * Starts a "HAVING" condition
     * Only call this method once per query because it will overwrite an previously-set "HAVING" expressions
     *
     * @param string $condition,... A variable list of conditions to be met
     * @return $this
     */
    public function having($condition)
    {
        // We want to wipe out anything already in the condition list
        $this->havingConditions = array();
        $this->havingConditions = call_user_func_array(array($this->conditionalQueryBuilder, "addConditionToClause"), array_merge(array($this->havingConditions, "AND"), func_get_args()));

        return $this;
    }

    /**
     * Adds a inner join to our query
     *
     * @param string $tableName The name of the table we're joining
     * @param string $tableAlias The alias of the table name
     * @param string $condition The "ON" portion of the join
     * @return $this
     */
    public function innerJoin($tableName, $tableAlias, $condition)
    {
        $this->joins["inner"][] = array("tableName" => $tableName, "tableAlias" => $tableAlias, "condition" => $condition);

        return $this;
    }

    /**
     * Adds a join to our query
     * This is the same thing as an inner join
     *
     * @param string $tableName The name of the table we're joining
     * @param string $tableAlias The alias of the table name
     * @param string $condition The "ON" portion of the join
     * @return $this
     */
    public function join($tableName, $tableAlias, $condition)
    {
        return $this->innerJoin($tableName, $tableAlias, $condition);
    }

    /**
     * Adds a left join to our query
     *
     * @param string $tableName The name of the table we're joining
     * @param string $tableAlias The alias of the table name
     * @param string $condition The "ON" portion of the join
     * @return $this
     */
    public function leftJoin($tableName, $tableAlias, $condition)
    {
        $this->joins["left"][] = array("tableName" => $tableName, "tableAlias" => $tableAlias, "condition" => $condition);

        return $this;
    }

    /**
     * Limits the number of rows returned by our query
     *
     * @param int $numRows The number of rows to limit in our results
     * @return $this
     */
    public function limit($numRows)
    {
        $this->limit = (int)$numRows;

        return $this;
    }

    /**
     * Skips the input number of rows before returning rows
     *
     * @param int $numRows The number of rows to skip in our results
     * @return $this
     */
    public function offset($numRows)
    {
        $this->offset = (int)$numRows;

        return $this;
    }

    /**
     * Adds to a "HAVING" condition that will be "OR"ed with other conditions
     *
     * @param string $condition,... A variable list of conditions to be met
     * @return $this
     */
    public function orHaving($condition)
    {
        $this->havingConditions = call_user_func_array(array($this->conditionalQueryBuilder, "addConditionToClause"), array_merge(array($this->havingConditions, "OR"), func_get_args()));

        return $this;
    }

    /**
     * Adds to a "WHERE" condition that will be "OR"ed with other conditions
     *
     * @param string $condition,... A variable list of conditions to be met
     * @return $this
     */
    public function orWhere($condition)
    {
        call_user_func_array(array($this->conditionalQueryBuilder, "orWhere"), func_get_args());

        return $this;
    }

    /**
     * Starts an "ORDER BY" clause
     * Only call this method once per query because it will overwrite an previously-set "ORDER BY" expressions
     *
     * @param string $expression,... A variable list of expressions to order by
     * @return $this
     */
    public function orderBy($expression)
    {
        $this->orderBy = func_get_args();

        return $this;
    }

    /**
     * Adds a right join to our query
     *
     * @param string $tableName The name of the table we're joining
     * @param string $tableAlias The alias of the table name
     * @param string $condition The "ON" portion of the join
     * @return $this
     */
    public function rightJoin($tableName, $tableAlias, $condition)
    {
        $this->joins["right"][] = array("tableName" => $tableName, "tableAlias" => $tableAlias, "condition" => $condition);

        return $this;
    }

    /**
     * Starts a "WHERE" condition
     * Only call this method once per query because it will overwrite an previously-set "WHERE" expressions
     *
     * @param string $condition,... A variable list of conditions to be met
     * @return $this
     */
    public function where($condition)
    {
        call_user_func_array(array($this->conditionalQueryBuilder, "where"), func_get_args());

        return $this;
    }
}