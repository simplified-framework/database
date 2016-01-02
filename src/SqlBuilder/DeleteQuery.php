<?php
/**
 * Created by PhpStorm.
 * User: Andreas
 * Date: 24.12.2015
 * Time: 13:53
 */

namespace Simplified\Database\SqlBuilder;
use Simplified\Database\Connection;

class DeleteQuery extends CommonQuery {
    public function __construct($table, Connection $connection) {
        parent::__construct($connection);
        $this->table = $table;
    }

    public function getQuery() {
        $query = "DELETE FROM " . $this->table;
        return $query . " " . parent::getQuery();
    }

    public function execute() {
        $q = $this->getQuery();
        $stmt = $this->connection()->raw($q);
        return $stmt ? $stmt->rowCount() : 0;
    }
}