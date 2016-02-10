<?php

namespace Simplified\Database;

use Doctrine\Common\Inflector\Inflector;
use Simplified\Config\Config;
use Simplified\Core\NullPointerException;
use Simplified\Database\SqlBuilder\InsertQuery;
use Simplified\Database\SqlBuilder\SelectQuery;
use Simplified\Database\SqlBuilder\UpdateQuery;
use Simplified\Database\SqlBuilder\DeleteQuery;

class Model {
    private $attributes = array();
    private $ref;

    // override connection name
    static  $connection;

    // override primary key
    static  $primaryKey;

    // override table name
    static  $table;

    private function getReflection() {
        if ($this->ref == null) {
            $model_class = get_called_class();
            $this->ref = new \ReflectionClass($model_class);
        }
        return $this->ref;
    }

    public function __construct($attributes = null) {
        if (is_array($attributes))
            $this->attributes = $attributes;
    }

    public function getTable() {
        $table_name = $this->getReflection()
            ->getStaticPropertyValue('table');

        if (!$table_name) {
            $model_class = get_called_class();

            $ref = new \ReflectionClass($model_class);
            $table_name = $ref->getShortName();
            $table_name = Inflector::tableize($table_name);
            $table_name = strtolower(basename($table_name));
        }

        return $table_name;
    }

    public function getPrimaryKey() {
        $key = $this->getReflection()
            ->getStaticPropertyValue('primaryKey');

        if (null != $key) {
            return $key;
        }

        return 'id';
    }

    public function getConnection() {
        $connection = $this->getReflection()
            ->getStaticPropertyValue('connection');

        if (null != $connection) {
            return $connection;
        }

        return 'default';
    }

    public static function all() {
        $model_class = get_called_class();
        $instance = new $model_class();
        $table_name = $instance->getTable();

        $connectionName = $instance->getConnection();
        $config = Config::get('database.'.$connectionName);
        if ($config == null)
            throw new NullPointerException("Unable to connect to database: configuration '$connectionName' doesn't exists");
        $conn = new Connection($config);

        return (new SelectQuery($table_name, $conn))
            ->setObjectClassName($model_class)
            ->get();
    }

    public static function find($id) {
        if (!is_numeric($id))
            throw new IllegalArgumentException("Argument must be numeric");

        $model_class = get_called_class();
        $instance = new $model_class();
        $table_name = $instance->getTable();

        $connectionName = $instance->getConnection();
        $config = Config::get('database.'.$connectionName, 'default');
        $conn = new Connection($config);

        return (new SelectQuery($table_name, $conn))
            ->setObjectClassName($model_class)
            ->where($instance->getPrimaryKey(), $id)
            ->first();
    }

    public static function select($fields) {
        $model_class = get_called_class();
        $instance = new $model_class();
        $table_name = $instance->getTable();

        $connectionName = $instance->getConnection();
        $config = Config::get('database.'.$connectionName, 'default');
        $conn = new Connection($config);

        return (new SelectQuery($table_name, $conn))
            ->setObjectClassName($model_class)
            ->select($fields);
    }

    public static function where () {
        $model_class = get_called_class();
        $instance = new $model_class();
        $table_name = $instance->getTable();

        $connectionName = $instance->getConnection();
        $config = Config::get('database.'.$connectionName, 'default');
        $conn = new Connection($config);

        switch (func_num_args()) {
            case 1:
                $arg = func_get_arg(0);
                return (new SelectQuery($table_name, $conn))
                    ->setObjectClassName($model_class)
                    ->where($arg);
            case 2:
                $arg1 = func_get_arg(0);
                $arg2 = func_get_arg(1);
                return (new SelectQuery($table_name, $conn))
                    ->setObjectClassName($model_class)
                    ->where($arg1, $arg2);
            case 3:
                $arg1 = func_get_arg(0);
                $arg2 = func_get_arg(1);
                $arg3 = func_get_arg(2);
                return (new SelectQuery($table_name, $conn))
                    ->setObjectClassName($model_class)
                    ->where($arg1, $arg2, $arg3);
        }
    }

    public static function whereIn () {
        $model_class = get_called_class();
        $instance = new $model_class();
        $table_name = $instance->getTable();

        $connectionName = $instance->getConnection();
        $config = Config::get('database.'.$connectionName, 'default');
        $conn = new Connection($config);

        switch (func_num_args()) {
            case 1:
                $arg = func_get_arg(0);
                return (new SelectQuery($table_name, $conn))
                    ->setObjectClassName($model_class)
                    ->where($arg);
            case 2:
                $arg1 = func_get_arg(0);
                $arg2 = func_get_arg(1);
                return (new SelectQuery($table_name, $conn))
                    ->setObjectClassName($model_class)
                    ->where($arg1, "IN", $arg2);
        }
    }

    public function save() {
        $table_name = $this->getTable();
        $config = Config::get('database.'.$this->getConnection(), 'default');
        $conn = new Connection($config);

        $pk = $this->getPrimaryKey();
        if (isset($this->attributes[$pk])) {
            return (new UpdateQuery($table_name, $conn))
                ->set($this->attributes)
                ->where($pk, $this->attributes[$pk])
                ->execute();
        } else {
            $id = (new InsertQuery($table_name, $conn))
                ->set($this->attributes)
                ->execute();
            if ($id > 0) {
                $this->attributes[$pk] = $id;
            }
            return $id;
        }
    }

    public function delete() {
        $table_name = $this->getTable();
        $config = Config::get('database.'.$this->getConnection(), 'default');
        $conn = new Connection($config);

        $pk = $this->getPrimaryKey();
        if (!isset($this->attributes[$pk]))
            return -1;

        return (new DeleteQuery($table_name, $conn))
            ->where($pk, $this->attributes[$pk])
            ->execute();
    }

    public function hasMany($modelClass, $foreignKey = null) {
        if (class_exists($modelClass)) {
            $pk = $this->getPrimaryKey();
            $id_value = $this->$pk;

            $instance = new $modelClass();
            $fk = $foreignKey ? $foreignKey : $this->getTable() . "_id";

            $data = $modelClass::where($fk, '=', $id_value)->get();
            return $data;
        }
        throw new ModelException("Unknown model class $modelClass");
    }

    public function hasOne($modelClass, $foreignKey = null, $local_key = null) {
        if (class_exists($modelClass)) {
            $rel_table = $this->getTable();

            $fk = $foreignKey ? $foreignKey : $rel_table."_id";
            $pk = $this->getPrimaryKey();
            $id_value = $local_key ? $this->$local_key : $this->$pk;

            $data = $modelClass::where($fk, '=', $id_value)->first();
            return $data;
        }
        throw new ModelException("Unknown model class $modelClass");
    }

    public function belongsTo($modelClass, $foreignKey = null) {
        if (class_exists($modelClass)) {
            $rel_table = $this->getTable();

            $fk = $foreignKey ? $foreignKey : $rel_table."_id";
            $id_value = $this->$fk;

            $data = $modelClass::where('id', '=', $id_value)->first();
            return $data;
        }
        throw new ModelException("Unknown model class $modelClass");
    }

    public function __get($name) {
        if (isset($this->attributes[$name])) {
            return $this->attributes[$name];
        }

        if (isset($this->$name))
            return $this->$name;

        if (method_exists($this, $name)) {
            return call_user_func(array($this, $name));
        }
    }

    public function __set($name, $value) {
        if (!isset($this->$name)) {
            $this->attributes[$name] = $value;
        } else {
            $this->$name = $value;
        }
    }

    public function __call($name, $params) {
        if (isset($this->attributes[$name]))
            return $this->attributes[$name];
    }
}
