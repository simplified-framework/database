<?php
/**
 * Created by PhpStorm.
 * User: Andreas
 * Date: 24.12.2015
 * Time: 13:46
 */

namespace Simplified\Database\SqlBuilder;
use Simplified\Database\Connection;
use Simplified\Core\IllegalArgumentException;
use Simplified\Database\ModelException;

class CommonQuery extends BaseQuery{
    private $andWhere = array();
    private $joins = array();
    private $limit = null;
    private $groups = array();
    private $having = null;

    public function __construct(Connection $connection) {
        parent::__construct($connection);
    }

    // TODO implement more args
    public function where() {
        switch (func_num_args()) {
            case 1:
                // support raw where clauses: where("field = value")
                // support Closure where clauses: where(function($query){})
                $this->andWhere[] = func_get_arg(0);
                break;
            case 2:
                // support short hand where EQUAL clause: where("field", "value")
                // support short hand where IN clause: where("field", array())
                $field = func_get_arg(0);
                $params = func_get_arg(1);
                if (!is_string($field))
                    throw new IllegalArgumentException("First argument must be string");

                if (is_string($params) || is_numeric($params)) {
                    $escaped = is_string($params) ? "'" . $params . "'" : $params;
                    $this->andWhere[] = "$field=$escaped";
                }
                else
                    if (is_array($params)) {
                        $this->andWhere[] = "$field IN (" . implode(",", $params) . ")";
                    }
                break;
            case 3:
                $operator = func_get_arg(1);
                $field = func_get_arg(0);
                $params = func_get_arg(2);

                if (!WhereOperator::isValid($operator))
                    throw new IllegalArgumentException("Operator '$operator' must on of '<,>,=,!=,NOT,IN,NOT IN,LIKE'");

                if (!is_string($field) || !is_string($operator))
                    throw new IllegalArgumentException("First and second argument must be string");

                if (is_array($params) && ($operator != WhereOperator::IN && $operator != WhereOperator::NOT_IN))
                    throw new IllegalArgumentException("Second argument can't be used to compare table field and array");

                if (($operator == WhereOperator::IN || $operator == WhereOperator::NOT_IN) && !is_array($params))
                    throw new IllegalArgumentException("Third argument must be array");

                if (is_string($params) || is_numeric($params)) {
                    $escaped = is_string($params) ? "'" . $params . "'" : $params;
                    $this->andWhere[] = "$field $operator $escaped";
                }
                else
                    if (is_array($params)) {
                        $this->andWhere[] = "$field $operator (" . implode(",", $params) . ")";
                    }
                break;
            case 0:
                throw new IllegalArgumentException("Where clause needs at least one argument");
        }

        return $this;
    }

    public function whereIn($field, array $params) {
        $query = clone $this;
        return $query->where($field, "IN", $params);
    }

    public function whereNotIn($field, array $params) {
        $query = clone $this;
        return $query->where($field, "NOT IN", $params);
    }

    public function whereBetween($field, array $params) {
        if (count($params) != 2)
            throw new IllegalArgumentException("Illegal data in array");

        if (!is_string($field))
            throw new IllegalArgumentException("First argument must be string");

        $this->andWhere[] = "$field BETWEEN " . $params[0] . " AND " . $params[1];
        return $this;
    }

    public function whereNotBetween($field, array $params) {
        if (count($params) != 2)
            throw new IllegalArgumentException("Illegal data in array");

        if (!is_string($field))
            throw new IllegalArgumentException("First argument must be string");

        $this->andWhere[] = "$field NOT BETWEEN " . $params[0] . " AND " . $params[1];
        return $this;
    }

    public function join($table, $arg2, $arg3=null) {
        if (func_num_args() == 2) {
            if (!$arg2 instanceof \Closure)
                throw new ModelException("Argument must be instance of Closure");

            $join = new Join($table);
            $arg2($join);

            $this->joins[] = (string) $join;
            $this->fields[] = $join->getTableShortcut() . ".*";
        }
        else {
            if (!is_string($table) || !is_string($arg2) || !is_string($arg3))
                throw new IllegalArgumentException("Table, primary key and foreign key argument must be string");

            $this->joins[] = "LEFT JOIN $table ON $arg2=$arg3";
        }
        return $this;
    }

    public function groupBy(array $columns = array()) {
        $this->groups = $columns;
        return $this;
    }

    public function limit ($num) {
        $this->limit = is_numeric($num) ? $num : null;
        return $this;
    }

    public function having(Aggregate $aggregate, $operator, $value) {
        if (is_string($value))
            $value = "'".$value."'";
        $this->having = " HAVING $aggregate $operator $value";
        return $this;
    }

    public function getQuery() {
        $query = "";
        if (count($this->joins) > 0)
            $query .= implode(" ", $this->joins);

        if (count($this->andWhere) > 0)
            $query .= " WHERE " . implode(" AND ", $this->andWhere);

        if (count($this->groups) > 0)
            $query .= " GROUP BY " . implode(",", $this->groups);

        if ($this->having)
            $query .= " " . $this->having;

        if ($this->limit)
            $query .= " LIMIT " . $this->limit;
        return $query;
    }
}