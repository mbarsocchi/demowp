<?php

include_once("DBConfig.php");
include_once("Executer.php");

class DBCloner {

    public $dbConfigSource;
    public $errormsg;
    private $con = null;
    private $destName;

    function __construct($mysqlUserName, $mysqlPassword, $mysqlHostName) {
        $this->dbConfigSource = new DBConfig($mysqlHostName, $mysqlUserName, $mysqlPassword);
        $this->exec = new Executer();
    }

    function setDestName($destName) {
        $this->destName = $destName;
    }

    function getCon() {
        if ($this->con === null) {
            $this->con = $this->dbConfigSource->connect();
        }
        return $this->con;
    }

    private function recursive_unserialize_replace($data, $key = null) {
        if (is_string($data) && ( $unserialized = @unserialize($data) ) === false) {
            $data = str_replace("'", "\'", html_entity_decode($data, ENT_QUOTES, 'UTF-8'));
            $data = preg_replace_callback('/s:(\d+):"(.*?)";/', function ($match) {
                $temp = intval(strlen($match[2]));
                $result = 's:' . $temp . ':"' . $match[2] . '";';
                return $result;
            }, $data);
        }
        if (( $unserialized = @unserialize($data) ) === false) {
            echo "ERROR UNSERIALAZING[" . $data . "]";
        }
        return $data;
    }

    function importFile() {
        $command = "\"" . MYSQL_BIN_BASE_PATH . "mysql\" --host=" . $this->dbConfigSource->getHostDb() . " --user=" . $this->dbConfigSource->getUsernameDb() . " --password=" . $this->dbConfigSource->getPasswordDb() . " " . $this->mysqlDatabaseNameNew . " < \"" . $this->mysqlImportFilename . "\"";
        $this->log->debug($command);
        if (DEBUG) {
            echo $command . "</br>";
            @exec($command, $output = array(), $worked);
            if ($output != null) {
                print_r($output);
                echo "</br>";
            }
        } else {
            exec($command, $output = array(), $worked);
        }
        if ($worked == 1) {
            $this->errormsg .= "Impossibile importare il file " . $this->mysqlImportFilename . " sul DB";
            return false;
        }
    }

    function createDb($dbname) {
        if ($this->con === null) {
            $this->con = $this->dbConfigSource->connect();
        }
        $sql = "CREATE DATABASE " . $dbname;
        if (!$this->con->query($sql)) {
            $msg = "Could not create db " . $dbname . " " . $this->con->error;
            $this->errormsg .= $msg;
            return false;
        } else {
            return true;
        }
    }

    function migrate($dbname, $filename, $sourceName, $destName) {
        $result = false;
        if ($this->con === null) {
            $this->con = $this->dbConfigSource->connect();
        }
        $sql = "USE " . $dbname;
        if (!$this->con->query($sql)) {
            $this->errormsg .= "Could not select db " . $dbname . " " . mysql_error();
        } else {
            $result = true;
        }
        $remoteUrlBase = "localhost";

        $this->migrateDbFiles($filename, "http://" . $remoteUrlBase . "/" . $sourceName, "http://" . DOMAIN_URL_BASE . "/" . $destName);
        $command = "\"" . MYSQL_BIN_BASE_PATH . "mysql\" --host=" . $this->dbConfigSource->getHostDb() . " --user=" . $this->dbConfigSource->getUsernameDb() . " --password=" . $this->dbConfigSource->getPasswordDb() . " " . $dbname . " < \"" . dirname(__FILE__) . DIRECTORY_SEPARATOR . $filename . "\"";
        $this->exec->execute($command, false);
        if ($this->exec->getRetCode() != 0) {
            $this->errormsg .= $this->exec->getOutput();
        } else {
            $result = true;
        }
        return $result;
    }

    public function sqlFileCheckProperties($match) {
        if ($this->con === null) {
            $this->con = $this->dbConfigSource->connect();
        }
        if (strpos($match[3], ":") > 0 && strpos($match[3], "{") && strpos($match[3], $this->destName)) {
            $cleaned = str_replace('\"', '"', $match[3]);
            $cleaned = str_replace(array("\\r\\n", "\r\n", PHP_EOL,), "", $cleaned);
            //$cleaned = $this->recursive_unserialize_replace($cleaned, $match[3]);
            $result = "(" . $match[1] . ",'" . $match[2] . "','" . mysqli_real_escape_string($this->con, $cleaned) . "','" . $match[4] . "')";
        } else {
            $result = $match[0];
        }
        return $result;
    }

    public function changeNextGenOption() {
        if ($this->con === null) {
            $this->con = $this->dbConfigSource->connect();
        }
        $sql = "USE " . $this->mysqlDatabaseNameNew;
        mysql_query($sql, $this->con);
        $sql = "SELECT ID, post_content FROM wp_posts WHERE post_type='lightbox_library'";
        $result = $this->con->query($sql);
        if (!$result) {
            die("Database query failed: " . mysql_error());
        }
        while ($row = $result->fetch_array()) {
            $newString = str_replace('\/', '/', base64_decode($row['post_content']));
            $newString = str_replace($this->sourcename, $this->destName, $newString);
            $newString = str_replace('/', '\/', $newString);
            $cleaned = base64_encode($newString);
            $updQuery = "UPDATE wp_posts SET post_content='" . $cleaned . "',post_content_filtered='" . $cleaned . "' WHERE ID=" . $row['ID'];
            if (!$this->con->query($updQuery)) {
                die("Database query failed: " . mysql_error());
            }
        }
    }

    private function migrateDbFiles($fileName, $sourcename, $destName) {
        $content = file_get_contents($fileName);
        $content = str_replace($sourcename, $destName, $content);
        $pathTobeRemoved = str_replace("\\", "/", dirname(BASE_PATH . $sourcename . "\\index.php") . "\\");
        $content = str_replace($pathTobeRemoved, "", $content);
        $content = str_replace("),(", "),\r\n(", $content);
        $pattern = "/\((\d+),\s?'(.+?)',\s?'(.?|.+?)',\s?'(...?)'\)/";
        $content = preg_replace_callback($pattern, array($this, 'sqlFileCheckProperties'), $content);
        file_put_contents($fileName, $content);
    }

    public function cancellDB($dbname) {
        if ($this->con === null) {
            $this->con = $this->dbConfigSource->connect();
        }
        $sql = "DROP DATABASE IF EXISTS " . $dbname;
        if (!$this->con->query($sql)) {
            $this->errormsg .= "Could not delete existent db " . $dbname . " " . mysql_error();
            return false;
        }
    }

}
