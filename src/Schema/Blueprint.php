<?php
/**
 * Created by PhpStorm.
 * User: bratfisch
 * Date: 21.12.2015
 * Time: 10:16
 */

namespace Simplified\Database\Schema;

use Simplified\Database\Connection;

function tick($value) {
    if (!is_numeric($value)) {
        return sprintf("`%s`", $value);
    }
    return $value;
}

class Blueprint {
    const MODE_CREATE = 1;
    const MODE_DROP = 2;

    private $table;
    private $fields = array();
    private $pk = null;
    private $mode = Blueprint::MODE_CREATE;

    public function __construct($table) {
        $this->table = tick($table);
    }

    public function increments($fieldName) {
        $field = new TableField();
        $field->name = tick($fieldName);
        $field->type = "int";
        $field->extra = "auto_increment";
        $field->null = false;
        $field->default = null;
        $field->unique = false;
        $this->fields[] = $field;
        return $this;
    }

    public function string($fieldName) {
        $field = new TableField();
        $field->name = tick($fieldName);
        $field->type = "varchar(255)";
        $field->extra = null;
        $field->null = false;
        $field->default = null;
        $field->unique = false;
        $this->fields[] = $field;
        return $this;
    }

    public function text($fieldName) {
        $field = new TableField();
        $field->name = tick($fieldName);
        $field->type = "text";
        $field->extra = null;
        $field->null = false;
        $field->default = null;
        $field->unique = false;
        $this->fields[] = $field;
        return $this;
    }

    public function binary($fieldName) {
        $field = new TableField();
        $field->name = tick($fieldName);
        $field->type = "blob";
        $field->extra = null;
        $field->null = false;
        $field->default = null;
        $field->unique = false;
        $this->fields[] = $field;
        return $this;
    }

    public function tinyInteger($fieldName) {
        $field = new TableField();
        $field->name = tick($fieldName);
        $field->type = "tinyint";
        $field->extra = null;
        $field->null = false;
        $field->default = null;
        $field->unique = false;
        $this->fields[] = $field;
        return $this;
    }

    public function smallInteger($fieldName) {
        $field = new TableField();
        $field->name = tick($fieldName);
        $field->type = "smallint";
        $field->extra = null;
        $field->null = false;
        $field->default = null;
        $field->unique = false;
        $this->fields[] = $field;
        return $this;
    }

    public function mediumInteger($fieldName) {
        $field = new TableField();
        $field->name = tick($fieldName);
        $field->type = "mediumint";
        $field->extra = null;
        $field->null = false;
        $field->default = null;
        $field->unique = false;
        $this->fields[] = $field;
        return $this;
    }

    public function integer($fieldName) {
        $field = new TableField();
        $field->name = tick($fieldName);
        $field->type = "int";
        $field->extra = null;
        $field->null = false;
        $field->default = null;
        $field->unique = false;
        $this->fields[] = $field;
        return $this;
    }

    public function bigInteger($fieldName) {
        $field = new TableField();
        $field->name = tick($fieldName);
        $field->type = "bigint";
        $field->extra = null;
        $field->null = false;
        $field->default = null;
        $field->unique = false;
        $this->fields[] = $field;
        return $this;
    }

    public function dateTime($fieldName) {
        $field = new TableField();
        $field->name = tick($fieldName);
        $field->type = "datetime";
        $field->extra = null;
        $field->null = false;
        $field->default = null;
        $field->unique = false;
        $this->fields[] = $field;
        return $this;
    }

    public function timestamps() {
        $field1 = new TableField();
        $field1->name = tick('created_at');
        $field1->type = 'timestamp';
        $field1->extra = null;
        $field1->null = false;
        $field1->unique = false;
        $field1->default = '0000-00-00 00:00:00';
        $this->fields[] = $field1;

        $field2 = new TableField();
        $field2->name = tick('updated_at');
        $field2->type = 'timestamp';
        $field2->extra = 'on update current_timestamp';
        $field2->null = false;
        $field2->unique = false;
        $field2->default = 'current_timestamp';
        $this->fields[] = $field2;

        return $this;
    }

    public function primary($fieldName) {
        $this->pk = tick($fieldName);
        return $this;
    }

    public function unique() {
        $this->fields[count($this->fields)-1]->unique = true;
        return $this;
    }

    public function nullable() {
        $this->fields[count($this->fields)-1]->null = true;
        return $this;
    }

    public function create() {
        $this->mode = Blueprint::MODE_CREATE;
    }

    public function drop() {
        $this->mode = Blueprint::MODE_DROP;
    }

    public function defaults($value) {
        $value = tick($value);
        $this->fields[count($this->fields)-1]->default = $value;
        return $this;
    }

    public function toSql() {
        if ($this->mode == Blueprint::MODE_CREATE) {
            $fields = array();
            if (count($this->fields) == 0)
                return "";

            $sql = "create table if not exists " . $this->table . " (";
            foreach ($this->fields as $field) {
                $f  = $field->name;

                $f .= " ";
                $f .= $field->type . " ";
                if ($field->extra) {
                    $f .= $field->extra . " ";
                }
                if (!$field->null) {
                    $f .= "not null ";
                }
                if ($field->default) {
                    $value = $field->default;
                    if (!is_numeric($value) && $value != 'current_timestamp') {

                        // remove non printable characters
                        $value = preg_replace( '/[^[:print:]]/', '',$value);

                        // escape strings
                        $value = preg_replace( '/[\']/', "\\\\'",$value);
                        $value = preg_replace( '/[\"]/', "\\\"",$value);
                        $value = "'{$value}'";
                    }
                    $f .= "default " . $value;
                }
                $fields[] = $f;
            }
            $sql .= implode(", ", $fields);
            if ($this->pk) {
                $sql .= ", PRIMARY KEY ({$this->pk}) ";
            }

            $uniques = array();
            foreach ($this->fields as $field) {
                if ($field->unique)
                    $uniques[] = 'unique ('.$field->name.')';
            }

            if (count($uniques) > 0) {
                $sql .= ", ";
                $sql .= implode(", ", $uniques);
            }

            $sql .= ");";
            return $sql;
        }
    }

    public function build(Connection $conn) {
        $sql = $this->toSql();
        return strlen($sql) > 0 ? $conn->raw($sql) : false;
    }
}