<?php

namespace Simplified\Database\SqlBuilder;

use Simplified\Core\IllegalArgumentException;

class Aggregate {
    private $field;
    private $type;
    private static $aggregates = array("COUNT", "MIN", "MAX", "AVG", "SUM");

    public static function isAggregate($name) {
        return in_array(strtoupper($name), self::$aggregates);
    }

    public static function __callStatic($name, $arguments) {
        if (!Aggregate::isAggregate($name))
            throw new IllegalArgumentException("Invalid aggregate function");

        if (!is_array($arguments) || !isset($arguments[0]))
            throw new IllegalArgumentException("One argument for table field required");

        if (!is_string($arguments[0]))
            throw new IllegalArgumentException("Table field must be a string");

        return new self($arguments[0], $name);
    }

    public function __toString() {
        return strtoupper($this->type) . "(" . $this->field . ")";
    }

    private function __construct($field, $type) {
        $this->field = $field;
        $this->type = $type;
    }
}