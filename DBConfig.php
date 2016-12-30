<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of DBConfig
 *
 * @author Miro
 */
class DBConfig {

    public $hostDb;
    public $usernameDb;
    public $passwordDb;

    function __construct($hostDb, $usernameDb, $passwordDb) {
        $this->hostDb = $hostDb;
        $this->usernameDb = $usernameDb;
        $this->passwordDb = $passwordDb;
    }

    public function getHostDb() {
        return $this->hostDb;
    }

    public function setHostDb($hostDb) {
        $this->hostDb = $hostDb;
    }

    public function getUsernameDb() {
        return $this->usernameDb;
    }

    public function setUsernameDb($usernameDb) {
        $this->usernameDb = $usernameDb;
    }

    public function getPasswordDb() {
        return $this->passwordDb;
    }

    public function setPasswordDb($passwordDb) {
        $this->passwordDb = $passwordDb;
    }

    public function connect() {
        $mysqli = new mysqli($this->hostDb, $this->usernameDb, $this->passwordDb);
        return $mysqli;
    }

}
