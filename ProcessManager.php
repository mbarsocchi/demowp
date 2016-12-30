<?php

include_once("config.php");
include_once("./DBConfig.php");
include_once("./Executer.php");

class ProcessManager {

    private $con;

    function getProcInDb() {
        $results = array();
        $db = new DBConfig(MYSQL_HOST, MYSQL_USER_NAME, MYSQL_PASSWORD);
        $this->con = $db->connect();
        $sql = "SELECT site,pid from " . DB_SITEDEMO_NAME . ".processrunning";
        $result = $this->con->query($sql);
        while ($row = $result->fetch_array()) {
            $results[$row['pid']] = $row['site'];
        }
        return $results;
    }

    function getProceRunning($name) {
        $results = array();
        $exec = new Executer();
        $cmd = "ps aux | grep " . $name . "|grep -v grep | awk '{ print $2 }'";
        $exec->execute($cmd);
        $pids = $exec->getStdOut();
        if ($pids != null && is_array($pids)) {
            foreach ($pids as $pid) {
                $results[] = trim($pid);
            }
        }
        return $results;
    }

}
