<?php

namespace Simplified\Database;
use Simplified\Core\IllegalArgumentException;
use Simplified\Core\NullPointerException;
use Simplified\Database\Schema\Schema;

class Connection implements ConnectionInterface {
    private $_conn;
    private $_params = array();
    private $schema;
    
    public function __construct(array $params = array()) {
        $this->_params = $params;
        $this->_conn = null;
        if ($this->connect() ){
            $this->schema = new Schema($this);
        }
    }

    public function getDatabaseSchema() {
        return $this->schema;
    }

    public function connect() {
        $dsn = null;
        if ($this->getDriverName() == "sqlite") {
            if (empty($this->getPath()))
                throw  new ConnectionException("Unable to connect to sqlite: path is empty");
            $dsn = $this->getDriverName() . ":" . STORAGE_PATH . $this->getPath();
            if (!is_dir(dirname(STORAGE_PATH . $this->getPath()))) {
                mkdir( dirname(STORAGE_PATH . $this->getPath()), 0775, true );
            }
        } else {
            $dsn = $this->getDriverName() . ":host=".$this->getHost().";dbname=".$this->getDatabase().';charset=utf8;';
        }

        try {
            $this->_conn = new \PDO($dsn, $this->getUsername(), $this->getPassword(),
                array(\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION, \PDO::ATTR_PERSISTENT => false));

        } catch (\PDOException $e) {
            throw  new ConnectionException($e->getMessage() . ", " . $dsn);
        }

        if ($this->_conn == null ) {
            throw new NullPointerException('\PDO::__construct('.$dsn.') returned null');
        }

        return $this->isConnected();
    }
    public function close() {
        if ($this->isConnected()) {
            $this->_conn = null;
            unset($this->_conn);
        }
    }

    public function isConnected() {
        return $this->_conn == null ? false : true;
    }

    public function getPath() {
        return isset($this->_params['path']) ? $this->_params['path'] : "";
    }
    
    public function getHost() {
        return isset($this->_params['host']) ? $this->_params['host'] : "";
    }
    
    public function getPort() {
        return isset($this->_params['port']) ? intval($this->_params['port']) : 0;
    }
    
    public function getUsername() {
        return isset($this->_params['user']) ? $this->_params['user'] : "";
    }
    
    public function getPassword() {
        return isset($this->_params['password']) ? $this->_params['password'] : "";
    }
    
    public function getDatabase() {
        return isset($this->_params['database']) ? $this->_params['database'] : "";
    }

    public function getDriverName() {
        return isset($this->_params['driver']) ? $this->_params['driver'] : "";
    }

    public function raw($query) {
        $stmt = null;
        if ($this->isConnected()) {
            $stmt = $this->_conn->query($query);
        }
        return $stmt;
    }

    public function quote($value) {
        return $this->isConnected() ? $this->_conn->quote($value) : $value;
    }

    public function getStructure() {
        return $this->structure;
    }

    public function prepare($query) {
        return $this->isConnected() ? $this->_conn->prepare($query) : null;
    }

    public function getAttribute($attrs) {
        return $this->isConnected() ? $this->_conn->getAttribute($attrs) : null;
    }

    public function lastInsertId() {
        return $this->isConnected() ? $this->_conn->lastInsertId() : -1;
    }
}