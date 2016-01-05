<?php
/**
 * Created by PhpStorm.
 * User: bratfisch
 * Date: 05.01.2016
 * Time: 13:15
 */

namespace Simplified\Database\SqlBuilder;


class Join {
    private $table;
    private $shortTableName;
    private $joins = array();

    public function __construct($tableName) {
        $this->table = $tableName;
        $this->shortTableName = substr($this->table, 0, 1);
    }

    public function on($primaryKey, $operation, $foreignKey) {
        $this->joins[] = "JOIN {$this->table} {$this->shortTableName} ON {$this->shortTableName}.{$primaryKey} {$operation} {$foreignKey}";
    }

    public function getTable() {
        return $this->table;
    }

    public function getTableShortcut() {
        return $this->shortTableName;
    }

    public function __toString() {
        return implode(' ', $this->joins);
    }
}