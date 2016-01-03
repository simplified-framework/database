<?php

namespace Simplified\Database\SqlBuilder;

use PhpCollection\Sequence;
use Simplified\Core\IllegalArgumentException;
use Simplified\Database\Connection;

class SelectQuery extends CommonQuery {
    private $distinct = false;
    private $orderBy = array();

    public function __construct($from, Connection $conn) {
        if (!is_string($from) || is_null($from))
            throw new IllegalArgumentException("No table name specified");

        parent::__construct($conn);
        $this->type = "SELECT";
        $this->fields = $from.".*";
        $this->table = $from;
    }

    public function select($fields) {
        $this->fields = $fields;
        return $this;
    }

    public function distinct($enable = true) {
        $this->distinct = $enable ? true : false;
        return $this;
    }

    public function orderBy($field, $direction = "ASC") {
        $dir = strtoupper($direction) == "ASC" ? "ASC" : "DESC";
        $this->orderBy[] = "ORDER BY $field $dir";
        return $this;
    }

    public function getQuery() {
        $fields = is_array($this->fields) ? implode(",", $this->fields) : $this->fields;
        $distinct = $this->distinct ? "DISTINCT" : null;
        $query = "SELECT $distinct $fields FROM " . $this->table . " " . parent::getQuery();
        if (count($this->orderBy) > 0) {
            $query .= " " . implode(", ", $this->orderBy);
        }
        return $query;
    }

    public function get() {
        $query = $this->getQuery();
        $stmt = $this->connection()->raw($query);
        if ($stmt && $stmt->rowCount() > 0) {
            if ($this->objectClass)
                $stmt->setFetchMode(\PDO::FETCH_CLASS,$this->objectClass);
            else
                $stmt->setFetchMode(\PDO::FETCH_COLUMN, 0);
            $result = $stmt->rowCount() == 1 ? $stmt->fetch() : new Sequence($stmt->fetchAll());
            return $result;
        }
        return null;
    }

    // TODO implement dynamic aggregate method

    public function count() {
        $clone = clone $this;
        return $clone->select("COUNT(*) AS counter")->setObjectClassName(null)->get();
    }

    public function min($field) {
        $clone = clone $this;
        return $clone->select("MIN($field) AS counter")->setObjectClassName(null)->get();
    }

    public function max($field) {
        $clone = clone $this;
        return $clone->select("MAX($field) AS counter")->setObjectClassName(null)->get();
    }

    public function avg($field) {
        $clone = clone $this;
        return $clone->select("AVG($field) AS counter")->setObjectClassName(null)->get();
    }

    public function sum($field) {
        $clone = clone $this;
        return $clone->select("SUM($field) AS counter")->setObjectClassName(null)->get();
    }
}