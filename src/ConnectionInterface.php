<?php

namespace Simplified\Database;

interface ConnectionInterface {
    // connection data
    public function getUsername();
    public function getPassword();
    public function getDatabase();
    public function getDriverName();
    public function connect();
    public function isConnected();
    public function close();

    // schema
    public function getDatabaseSchema();
}