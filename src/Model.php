<?php

namespace Simplified\Database;

use Simplified\Config\Config;
use ReflectionProperty;
use Simplified\Database\SqlBuilder\InsertQuery;
use Simplified\Database\SqlBuilder\SelectQuery;
use Simplified\Database\SqlBuilder\UpdateQuery;
use Simplified\Database\SqlBuilder\DeleteQuery;

class Model {
    private $attributes = array();

    // override connection name
	static  $connection;

    // override primary key
    static  $primaryKey;

    // override table name
    static  $table;

	public function __construct($attributes = null) {
        if (is_array($attributes))
            $this->attributes = $attributes;
    }
	
	public function getTable() {
        $model_class = get_called_class();
        $ref = new ReflectionProperty($model_class, 'table');
        $table_name = $ref->getValue($this);
        if (!$table_name) {
            $table_name = strtolower(basename($model_class));
        }

        return $table_name;
	}

    public function getPrimaryKey() {
        $model_class = get_called_class();
        $ref = new ReflectionProperty($model_class, 'primaryKey');
        $key = $ref->getValue($this);
        if (null != $key) {
            return $key;
        }

        return 'id';
    }

    public function getConnection() {
        $model_class = get_called_class();
        $ref = new ReflectionProperty($model_class, 'connection');
        $connection = $ref->getValue($this);
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
        $config = Config::get('database.'.$connectionName, 'default');
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
            ->get();
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
            $rel_table = $instance->getTable();

            $data = $modelClass::where($rel_table . "." . $fk, '=', $id_value)->get();
            return $data;
        }
        throw new ModelException("Unknown model class $modelClass");
    }

    public function hasOne($modelClass, $foreignKey = null) {
        if (class_exists($modelClass)) {
            $pk = $this->getPrimaryKey();
            $id_value = $this->$pk;

            $instance = new $modelClass();
            $fk = $foreignKey ? $foreignKey : $this->getTable() . "_id";
            $rel_table = $instance->getTable();

            $data = $modelClass::where($rel_table . "." . $fk, '=', $id_value)->limit(1)->get();
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

