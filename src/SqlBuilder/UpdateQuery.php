<?php
/**
 * Created by PhpStorm.
 * User: Andreas
 * Date: 24.12.2015
 * Time: 13:52
 */

namespace Simplified\Database\SqlBuilder;

use Simplified\Database\Connection;

class UpdateQuery extends CommonQuery {
    public function __construct($table, Connection $connection) {
        parent::__construct($connection);
        $this->table = $table;
    }

    public function set(array $data) {
        $this->values = $data;
        return $this;
    }

    public function getQuery() {
        $set = array();
        foreach ($this->values as $key => $value) {
            if (is_string($value))
                $value = "'".escape($value)."'";
            $set[] = $key . "=" . $value;
        }
        $args = implode(", ", $set);
        $query = "UPDATE " . $this->table . " SET " . $args;
        return $query . " " . parent::getQuery();
    }

    public function execute() {
        $q = $this->getQuery();
        $stmt = $this->connection()->raw($q);
        return $stmt ? $stmt->rowCount() : 0;
    }
}