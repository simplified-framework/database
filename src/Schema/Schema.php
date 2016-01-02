<?php
/**
 * Created by PhpStorm.
 * User: Andreas
 * Date: 20.12.2015
 * Time: 16:03
 */

namespace Simplified\Database\Schema;

use Simplified\Config\Config;
use Simplified\Core\Collection;
use Simplified\Core\IllegalArgumentException;
use Simplified\Database\Connection;
use Simplified\Database\DriverException;

class Schema {
    private $driver;
    private $schema;
    public function __construct(Connection $driver) {
        $this->driver = $driver;
        $this->loadSchema();
    }

    private function loadSchema() {
        $this->schema = new Collection();
        if ($this->driver->isConnected()) {
            try {
                if ($this->driver->getDriverName() == "sqlite")
                    $stmt = $this->driver->raw("SELECT name FROM sqlite_master WHERE type='table'");
                else
                    $stmt = $this->driver->raw('SHOW TABLES');

                if ($stmt != null) {
                    if ($stmt->execute()) {
                        $stmt->setFetchMode(\PDO::FETCH_COLUMN, 0);
                        while ($name = $stmt->fetch()) {
                            $table = new Table($name, $this->driver);
                            $this->schema[$name] = $table;
                        }
                    }
                }
            } catch (\PDOException $e) {
                throw new DriverException("Unable to fetch database schema: " . $e->getMessage());
            }
        }

        return $this->schema;
    }

    public function table($name) {
        if (isset($this->schema[$name])) {
            return $this->schema[$name];
        }

        return null;
    }

    public function tables() {
        return $this->schema;
    }

    public function tableNames() {
        return array_keys($this->tables()->toArray());
    }

    public static function create($table, \Closure $fn) {
        $bp = new Blueprint($table);
        $fn($bp);

        $connectionName = 'default';
        $trace = debug_backtrace();
        if (isset($trace[1]) && isset($trace[1]['object'])) {
            $clazz = get_class($trace[1]['object']);
            if ($clazz != null && class_exists($clazz)) {
                $ref = new \ReflectionMethod($clazz, 'getConnection');
                $attribute = $ref->invoke($trace[1]['object']);
                if ($attribute)
                    $connectionName = $attribute;
            }
        }

        $conf = Config::getAll('database');
        if (!isset($conf[$connectionName]))
            throw new IllegalArgumentException('Unknown database connection name: ' . $connectionName);

        $bp->build(new Connection($conf[$connectionName]));
    }
}